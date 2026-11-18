<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\CcsStockService;
class CcsStockController extends Controller
{
    private function serials(?string $value): array {
        $serials=array_values(array_filter(array_map('trim',preg_split('/\R/',(string)$value)),fn($s)=>$s!==''));
        if(count($serials)>200)throw ValidationException::withMessages(['serials'=>'Add at most 200 serials at a time.']);
        $seen=[];foreach($serials as $serial){$key=mb_strtoupper($serial);if(mb_strlen($serial)>100||isset($seen[$key]))throw ValidationException::withMessages(['serials'=>'Serials must be unique, at most 100 characters, one per line.']);$seen[$key]=true;}
        return $serials;
    }
    private function receiveStock(object $part,int $quantity,array $serials,int $userId): void {
        if($part->has_serial&&!($part->serial_on_sale??false)){
            if(!$serials)throw ValidationException::withMessages(['serials'=>'Enter the actual serial numbers, one per line.']);
            $keys=array_map(fn($s)=>mb_strtoupper($s),$serials);
            if(DB::table('stock_units')->whereIn('serial_key',$keys)->exists())throw ValidationException::withMessages(['serials'=>'A serial is already registered. Serials cannot be reused on another unit.']);
            foreach($serials as $serial)DB::table('stock_units')->insert(['inventory_item_id'=>$part->id,'serial_number'=>$serial,'serial_key'=>mb_strtoupper($serial),'status'=>'available','created_at'=>now(),'updated_at'=>now()]);
            $quantity=count($serials);
        }elseif($quantity<1)throw ValidationException::withMessages(['quantity'=>'Enter a positive stock quantity.']);
        DB::table('inventory_items')->where('id',$part->id)->increment('quantity_on_hand',$quantity,['updated_at'=>now()]);
        DB::table('stock_movements')->insert(['inventory_item_id'=>$part->id,'recorded_by'=>$userId,'quantity_change'=>$quantity,'reason'=>'stock_received','notes'=>($part->serial_on_sale??false)?'Received '.$quantity.' sealed packs; serial capture at POS.':($part->has_serial?'Received '.count($serials).' serialized units.':'Stock received.'),'created_at'=>now(),'updated_at'=>now()]);
    }
    public function index(Request $request){
        $request->validate(['q'=>['nullable','string','max:150'],'category'=>['nullable','string','max:80'],'low'=>['nullable','boolean']]);$q=trim((string)$request->query('q',''));
        $query=DB::table('inventory_items');if($q!=='')$query->where(fn($x)=>$x->where('name','like','%'.$q.'%')->orWhere('sku','like','%'.$q.'%')->orWhere('barcode',$q)->orWhereExists(fn($u)=>$u->selectRaw('1')->from('stock_units as s')->whereColumn('s.inventory_item_id','inventory_items.id')->where('s.serial_key',mb_strtoupper($q))));
        if($request->filled('category'))$query->where('category',$request->string('category')->toString());
        if($request->boolean('low'))$query->whereColumn('quantity_on_hand','<=','reorder_level');
        $metrics=DB::table('inventory_items')->selectRaw('COUNT(*) as total, SUM(CASE WHEN quantity_on_hand > 0 AND quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) as low, SUM(CASE WHEN quantity_on_hand = 0 THEN 1 ELSE 0 END) as empty, SUM(quantity_on_hand * COALESCE(purchase_price_lkr,0)) as value')->first();
        $categories=DB::table('inventory_items')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $parts=$query->orderBy('quantity_on_hand')->paginate(15)->withQueryString();
        $units=DB::table('stock_units')->selectRaw('stock_units.*, EXISTS (SELECT 1 FROM invoice_items WHERE invoice_items.stock_unit_id = stock_units.id) as has_history')->whereIn('inventory_item_id',$parts->pluck('id'))->orderByDesc('id')->get()->groupBy('inventory_item_id');
        $enabled=app(CcsStockService::class)->enabled();
        $warrantyPackages=DB::table('warranty_rules')->where('approved_by_owner',true)->where('active',true)->orderBy('name')->get();
        return view('ccs.stock',compact('parts','units','q','enabled','warrantyPackages','metrics','categories'));
    }
    public function create(Request $request){
        $data=$request->validate(['sku'=>['required','string','max:60','unique:inventory_items,sku'],'name'=>['required','string','max:150'],'barcode'=>['nullable','string','max:150',\Illuminate\Validation\Rule::unique('inventory_items','barcode')->ignore($request->route('id'))],
            'selling_price_lkr'=>['required','numeric','min:0','max:99999999','decimal:0,2'],'purchase_price_lkr'=>['nullable','numeric','min:0','max:99999999','decimal:0,2'],
            'category'=>['nullable','string','max:80'],'service_application'=>['nullable','string','max:150'],'reorder_level'=>['nullable','integer','min:0','max:10000'],'item_year'=>['nullable','string','max:50'],'is_battery_module'=>['nullable','boolean'],'condition'=>['nullable','string','max:30'],'supplier'=>['nullable','string','max:150'],
            'serial_on_sale'=>['nullable','boolean'],'has_serial'=>['nullable','boolean'],'quantity'=>['nullable','integer','min:0','max:10000'],'serials'=>['nullable','string','max:21000'],'default_warranty_rule_id'=>['nullable','integer',\Illuminate\Validation\Rule::exists('warranty_rules','id')->where('approved_by_owner',1)->where('active',1)]]);
        $serialized=$request->boolean('has_serial')||$request->boolean('is_battery_module')||$request->boolean('serial_on_sale');$serials=$this->serials($data['serials']??null);
        if($request->boolean('serial_on_sale')&&$serials)throw ValidationException::withMessages(['serials'=>'For sealed packs enter quantity only. Enter the real serial in POS after opening.']);
        if(!$serialized&&$serials)throw ValidationException::withMessages(['serials'=>'Enable Has Serial Number to enter serials.']);
        DB::transaction(function()use($data,$serialized,$serials,$request){
            $id=DB::table('inventory_items')->insertGetId(['sku'=>$data['sku'],'name'=>$data['name'],'barcode'=>$data['barcode']??null,'selling_price_lkr'=>$data['selling_price_lkr'],'purchase_price_lkr'=>$data['purchase_price_lkr']??null,
                'category'=>$data['category']??null,'service_application'=>$data['service_application']??null,'reorder_level'=>$data['reorder_level']??0,'item_year'=>$data['item_year']??null,'condition'=>$data['condition']??null,'supplier'=>$data['supplier']??null,'default_warranty_rule_id'=>$data['default_warranty_rule_id']??null,'is_battery_module'=>$request->boolean('is_battery_module'),'has_serial'=>$serialized,'serial_on_sale'=>$request->boolean('serial_on_sale'),'active'=>true,'quantity_on_hand'=>0,'created_at'=>now(),'updated_at'=>now()]);
            if($serials||($data['quantity']??0)>0)$this->receiveStock(DB::table('inventory_items')->where('id',$id)->first(),(int)($data['quantity']??0),$serials,$request->user()->id);
        },3);
        return back()->with('success','Part saved. Stock is ready for the POS trial.');
    }
    public function update(Request $request,int $id){
        $data=$request->validate(['name'=>['required','string','max:150'],'barcode'=>['nullable','string','max:150',\Illuminate\Validation\Rule::unique('inventory_items','barcode')->ignore($request->route('id'))],'selling_price_lkr'=>['required','numeric','min:0','max:99999999','decimal:0,2'],
            'purchase_price_lkr'=>['nullable','numeric','min:0','max:99999999','decimal:0,2'],'category'=>['nullable','string','max:80'],'service_application'=>['nullable','string','max:150'],'reorder_level'=>['nullable','integer','min:0','max:10000'],'item_year'=>['nullable','string','max:50'],
            'is_battery_module'=>['nullable','boolean'],'condition'=>['nullable','string','max:30'],'supplier'=>['nullable','string','max:150'],'serial_on_sale'=>['nullable','boolean'],'active'=>['nullable','boolean'],'default_warranty_rule_id'=>['nullable','integer',\Illuminate\Validation\Rule::exists('warranty_rules','id')->where('approved_by_owner',1)->where('active',1)]]);
        DB::transaction(function()use($id,$data,$request){
            $part=DB::table('inventory_items')->where('id',$id)->lockForUpdate()->first();abort_unless($part,404);
            if($request->boolean('serial_on_sale')&&!$part->has_serial)throw ValidationException::withMessages(['serial_on_sale'=>'Create a serialized part type for sealed packs.']);
            if((bool)($part->serial_on_sale??false)!==$request->boolean('serial_on_sale')&&((int)$part->quantity_on_hand>0||DB::table('stock_units')->where('inventory_item_id',$id)->exists()))throw ValidationException::withMessages(['serial_on_sale'=>'Serial capture mode can only change on an empty part without unit history. Create a separate part type.']);
            if($request->boolean('is_battery_module')&&!$part->has_serial)throw ValidationException::withMessages(['is_battery_module'=>'Battery modules must use actual unit serials. Create a serialized module type instead of converting quantity stock.']);
            DB::table('inventory_items')->where('id',$id)->update(['serial_on_sale'=>$request->boolean('serial_on_sale'),'is_battery_module'=>$request->boolean('is_battery_module'),'default_warranty_rule_id'=>$data['default_warranty_rule_id']??null,'name'=>$data['name'],'barcode'=>$data['barcode']??null,'selling_price_lkr'=>$data['selling_price_lkr'],'purchase_price_lkr'=>$data['purchase_price_lkr']??null,'category'=>$data['category']??null,'service_application'=>$data['service_application']??null,'reorder_level'=>$data['reorder_level']??$part->reorder_level,'item_year'=>$data['item_year']??null,'condition'=>$data['condition']??null,'supplier'=>$data['supplier']??null,'active'=>$request->boolean('active'),'updated_at'=>now()]);
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'stock_part_updated','entity_type'=>'inventory_item','entity_id'=>$id,'metadata'=>json_encode(['old_price'=>$part->selling_price_lkr,'new_price'=>$data['selling_price_lkr']]),'created_at'=>now(),'updated_at'=>now()]);
        },3);return back()->with('success','Part details updated. Saved invoices keep their original details.');
    }
    public function receive(Request $request,int $id){
        $data=$request->validate(['quantity'=>['nullable','integer','min:0','max:10000'],'serials'=>['nullable','string','max:21000']]);$serials=$this->serials($data['serials']??null);
        DB::transaction(function()use($id,$data,$serials,$request){$part=DB::table('inventory_items')->where('id',$id)->lockForUpdate()->first();abort_unless($part,404);
            if(($part->serial_on_sale??false)&&$serials)throw ValidationException::withMessages(['serials'=>'Receive sealed packs by quantity.']);
            if(!$part->has_serial&&$serials)throw ValidationException::withMessages(['serials'=>'This part is not serialized. Enter a quantity instead.']);
            $this->receiveStock($part,(int)($data['quantity']??0),$serials,$request->user()->id);
        },3);return back()->with('success','Stock received.');
    }
    public function updateUnit(Request $request,int $id,int $unitId){
        $data=$request->validate(['serial_number'=>['required','string','max:100']]);$serial=trim($data['serial_number']);
        if($serial==='')throw ValidationException::withMessages(['serial_number'=>'Enter the actual serial number.']);
        DB::transaction(function()use($id,$unitId,$serial,$request){
            $part=DB::table('inventory_items')->where('id',$id)->lockForUpdate()->first();abort_unless($part,404);
            $unit=DB::table('stock_units')->where('inventory_item_id',$id)->where('id',$unitId)->lockForUpdate()->first();abort_unless($unit,404);
            if($unit->status!=='available'||DB::table('invoice_items')->where('stock_unit_id',$unitId)->exists())throw ValidationException::withMessages(['serial_number'=>'Only available serials without invoice history can be edited. Sold/reserved serial history is protected.']);
            if(DB::table('stock_units')->where('serial_key',mb_strtoupper($serial))->where('id','!=',$unitId)->exists())throw ValidationException::withMessages(['serial_number'=>'This serial is already registered.']);
            DB::table('stock_units')->where('id',$unitId)->update(['serial_number'=>$serial,'serial_key'=>mb_strtoupper($serial),'updated_at'=>now()]);
            app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'stock_serial_corrected','stock_unit',$unitId,['old_serial'=>$unit->serial_number,'new_serial'=>$serial,'inventory_item_id'=>$id]);
        },3);return back()->with('success','Serial updated. Quantity unchanged.');
    }
    public function removeUnit(Request $request,int $id,int $unitId){
        $request->validate(['confirmation'=>['required','accepted']]);
        DB::transaction(function()use($id,$unitId,$request){
            $part=DB::table('inventory_items')->where('id',$id)->lockForUpdate()->first();abort_unless($part,404);
            $unit=DB::table('stock_units')->where('inventory_item_id',$id)->where('id',$unitId)->lockForUpdate()->first();abort_unless($unit,404);
            if($unit->status==='removed')return;
            if($unit->status!=='available'||DB::table('invoice_items')->where('stock_unit_id',$unitId)->exists())throw ValidationException::withMessages(['serial_number'=>'Only available serials without invoice history can be removed. Sold/reserved serial history is protected.']);
            if((int)$part->quantity_on_hand<1)throw ValidationException::withMessages(['quantity'=>'Stock quantity is inconsistent. Ask the owner to review before removing a unit.']);
            DB::table('stock_units')->where('id',$unitId)->update(['status'=>'removed','updated_at'=>now()]);
            DB::table('inventory_items')->where('id',$id)->decrement('quantity_on_hand',1,['updated_at'=>now()]);
            DB::table('stock_movements')->insert(['inventory_item_id'=>$id,'recorded_by'=>$request->user()->id,'quantity_change'=>-1,'reason'=>'serial_removed','notes'=>'Removed available serial '.$unit->serial_number,'created_at'=>now(),'updated_at'=>now()]);
            app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'stock_serial_removed','stock_unit',$unitId,['serial'=>$unit->serial_number,'inventory_item_id'=>$id]);
        },3);return back()->with('success','Available serial removed. Quantity reduced by one; history retained.');
    }
    public function restoreUnit(Request $request,int $id,int $unitId){
        DB::transaction(function()use($id,$unitId,$request){
            $part=DB::table('inventory_items')->where('id',$id)->lockForUpdate()->first();abort_unless($part,404);
            $unit=DB::table('stock_units')->where('inventory_item_id',$id)->where('id',$unitId)->lockForUpdate()->first();abort_unless($unit,404);
            if($unit->status==='available')return;
            if($unit->status!=='removed'||DB::table('invoice_items')->where('stock_unit_id',$unitId)->exists())throw ValidationException::withMessages(['serial_number'=>'Only a removed, unused unit can be restored to stock.']);
            DB::table('stock_units')->where('id',$unitId)->update(['status'=>'available','updated_at'=>now()]);DB::table('inventory_items')->where('id',$id)->increment('quantity_on_hand',1,['updated_at'=>now()]);
            DB::table('stock_movements')->insert(['inventory_item_id'=>$id,'recorded_by'=>$request->user()->id,'quantity_change'=>1,'reason'=>'serial_restored','notes'=>'Restored removed serial '.$unit->serial_number,'created_at'=>now(),'updated_at'=>now()]);
            app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'stock_serial_restored','stock_unit',$unitId,['serial'=>$unit->serial_number,'inventory_item_id'=>$id]);
        },3);return back()->with('success','Serial restored to available stock.');
    }
    public function toggle(Request $request){
        $request->validate(['enabled'=>['nullable','boolean']]);$enabled=$request->boolean('enabled');
        DB::table('ccs_feature_settings')->where('key','stock_pos')->update(['enabled'=>$enabled]);
        DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'stock_pos_trial_changed','entity_type'=>'feature','entity_id'=>null,'metadata'=>json_encode(['enabled'=>$enabled]),'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success',$enabled?'Stock POS trial enabled.':'Stock POS trial disabled. Manual POS and saved invoices remain available.');
    }
    public function moduleReading(Request $request){
        $data=$request->validate(['job_id'=>['required','integer'],'serial'=>['required','string','max:100']]);
        $job=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('j.id',$data['job_id'])->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['j.id']);abort_unless($job,404);
        $record=DB::table('battery_service_records')->where('repair_job_id',$job->id)->orderByDesc('id')->first();
        $reading=$record?DB::table('battery_module_readings')->where('battery_service_record_id',$record->id)->whereRaw('UPPER(serial_number) = ?',[mb_strtoupper(trim($data['serial']))])->first(['position','voltage']):null;
        return response()->json(['reading'=>$reading])->header('Cache-Control','no-store, private');
    }
    public function catalog(Request $request){
        $request->validate(['q'=>['nullable','string','max:150']]);if(!app(CcsStockService::class)->enabled())return response()->json(['data'=>[],'enabled'=>false]);
        $q=trim((string)$request->query('q',''));
        $parts=DB::table('inventory_items')->where('active',true)->where('quantity_on_hand','>',0)->when($q!=='',function($query)use($q){$query->where(function($p)use($q){$p->where('name','like','%'.$q.'%')->orWhere('sku','like','%'.$q.'%')->orWhere('barcode',$q)->orWhereExists(function($u)use($q){$u->selectRaw('1')->from('stock_units as s')->whereColumn('s.inventory_item_id','inventory_items.id')->where('s.serial_key',mb_strtoupper($q))->where('s.status','available');});});})->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM stock_units su WHERE su.inventory_item_id = inventory_items.id AND su.serial_key = ? AND su.status = 'available') THEN 0 WHEN barcode = ? THEN 1 WHEN sku = ? THEN 2 ELSE 3 END",[mb_strtoupper($q),$q,$q])->orderBy('name')->limit(25)->get(['id','sku','name','category','quantity_on_hand','selling_price_lkr','has_serial','serial_on_sale','is_battery_module','item_year','condition','supplier','barcode','default_warranty_rule_id']);
        foreach($parts as $part){$part->warranty_package=$part->default_warranty_rule_id?DB::table('warranty_rules')->where('id',$part->default_warranty_rule_id)->value('name'):null;}
        foreach($parts as $part)$part->units=DB::table('stock_units')->where('inventory_item_id',$part->id)->where('status','available')->orderByRaw('CASE WHEN serial_key = ? THEN 0 ELSE 1 END',[mb_strtoupper($q)])->orderBy('serial_number')->limit(200)->get(['id','serial_number']);
        return response()->json(['data'=>$parts,'enabled'=>true])->header('Cache-Control','no-store, private');
    }
    public function cancelDraft(Request $request,int $id){
        $request->validate(['confirmation'=>['required','accepted']]);
        DB::transaction(function()use($id,$request){$invoice=DB::table('invoices')->where('id',$id)->lockForUpdate()->first();abort_unless($invoice,404);
            if($invoice->status==='void')return;
            if($invoice->status!=='draft'||DB::table('payments')->where('invoice_id',$id)->exists())throw ValidationException::withMessages(['stock'=>'Only unpaid draft invoices can be cancelled here.']);
            app(CcsStockService::class)->releaseDraft($invoice,$request->user()->id);
            DB::table('invoices')->where('id',$id)->update(['status'=>'void','updated_at'=>now()]);
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'stock_draft_cancelled','entity_type'=>'invoice','entity_id'=>$id,'metadata'=>null,'created_at'=>now(),'updated_at'=>now()]);
        },3);return back()->with('success','Draft cancelled and reserved stock released. Invoice history is retained.');
    }
}
