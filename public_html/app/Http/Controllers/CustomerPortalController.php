<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CustomerPortalController extends Controller
{
    private function vehiclesQuery(Request $request){
        return DB::table('vehicles')->where('customer_id',$request->attributes->get('portal_account')->customer_id)->whereNull('deleted_at');
    }
    private function recordsQuery(Request $request){
        return DB::table('battery_service_records as s')->join('vehicles as v','v.id','=','s.vehicle_id')
            ->join('repair_jobs as j','j.id','=','s.repair_job_id')
            ->where('v.customer_id',$request->attributes->get('portal_account')->customer_id)
            ->whereNull('v.deleted_at')->whereNull('j.deleted_at')
            ->select('s.id','s.vehicle_id','s.service_date','s.mileage_km','s.module_count','v.registration_number','v.make','v.model','j.job_number');
    }
    private function linkedParts(Request $request,?string $serial=null,?string $plate=null,?int $vehicleId=null){
        $query=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')
            ->where('v.customer_id',$request->attributes->get('portal_account')->customer_id)->whereNull('v.deleted_at')->whereNull('j.deleted_at')
            ->whereIn('inv.status',['issued','partially_paid','paid'])->where(function($p){$p->where('item.stock_managed',true)->orWhereIn('item.item_origin',['manual_part','customer_supplied']);})->whereNotNull('item.serial_number');
        if($serial!==null&&trim($serial)!=='')$query->whereRaw('LOWER(item.serial_number) = ?',[mb_strtolower(trim($serial))]);
        if($plate!==null&&trim($plate)!=='')$query->whereRaw("UPPER(REPLACE(REPLACE(v.registration_number, ' ', ''), '-', '')) = ?",[mb_strtoupper(str_replace([' ','-'],'',trim($plate)))]);
        if($vehicleId!==null)$query->where('v.id',$vehicleId);
        return $query->orderByDesc('item.id')->limit(100)->get(['item.id','item.description','item.item_origin','item.serial_number','v.id as vehicle_id','v.registration_number','j.job_number','inv.issued_at as recorded_at']);
    }
    private function paged($query,$parts=[]){
        $page=$query->orderByDesc('s.service_date')->orderByDesc('s.id')->paginate(20)->withQueryString();
        return response()->json(['data'=>$page->items(),'parts'=>$parts,'parts_limit'=>100,'meta'=>['current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'per_page'=>$page->perPage(),'total'=>$page->total()],
            'links'=>['next'=>$page->nextPageUrl(),'previous'=>$page->previousPageUrl()]]);
    }
    public function home(){return view('customer.home');}
    public function me(Request $request){
        $customer=DB::table('customers')->where('id',$request->attributes->get('portal_account')->customer_id)->first(['name','customer_code']);
        return response()->json(['data'=>['name'=>$customer->name,'customer_code'=>$customer->customer_code],'csrf_token'=>csrf_token()]);
    }
    public function vehicles(Request $request){
        return response()->json(['data'=>$this->vehiclesQuery($request)->orderBy('registration_number')->get(['id','registration_number','make','model','manufacture_year'])]);
    }
    public function search(Request $request){
        $data=$request->validate(['serial_number'=>['nullable','required_without:registration_number','string','max:100'],
            'registration_number'=>['nullable','required_without:serial_number','string','max:30'],'page'=>['nullable','integer','min:1','max:100000']]);
        $query=$this->recordsQuery($request);
        if($request->filled('serial_number')){
            $serial=mb_strtolower(trim($data['serial_number']));
            $query->whereExists(function($q)use($serial){$q->selectRaw('1')->from('battery_module_readings as m')->whereColumn('m.battery_service_record_id','s.id')->whereRaw('LOWER(m.serial_number) = ?',[$serial]);});
        }
        if($request->filled('registration_number')){
            $plate=mb_strtoupper(str_replace([' ','-'],'',trim($data['registration_number'])));
            $query->whereRaw("UPPER(REPLACE(REPLACE(v.registration_number, ' ', ''), '-', '')) = ?",[$plate]);
        }
        return $this->paged($query,$this->linkedParts($request,$data['serial_number']??null,$data['registration_number']??null));
    }
    public function history(Request $request,int $id){
        $request->validate(['page'=>['nullable','integer','min:1','max:100000']]);
        abort_unless($this->vehiclesQuery($request)->where('id',$id)->exists(),404);
        return $this->paged($this->recordsQuery($request)->where('s.vehicle_id',$id),$this->linkedParts($request,null,null,$id));
    }
    public function service(Request $request,int $id){
        $record=$this->recordsQuery($request)->where('s.id',$id)->first();abort_unless($record,404);
        $modules=DB::table('battery_module_readings')->where('battery_service_record_id',$record->id)->orderBy('position')->get(['position','serial_number','voltage']);
        return response()->json(['data'=>['service'=>$record,'modules'=>$modules]]);
    }
    public function warranties(Request $request,int $id){
        abort_unless($this->vehiclesQuery($request)->where('id',$id)->exists(),404);
        $approved=app(\App\Services\CcsSerialLookupService::class)->warranties((int)$request->attributes->get('portal_account')->customer_id,$id);
        return response()->json(['data'=>$approved,'message'=>$approved?'Approved warranty records.':'No approved customer-visible warranty records.']);
    }
}
