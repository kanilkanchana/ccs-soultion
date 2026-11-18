<?php
namespace App\Http\Controllers;
use App\Services\CcsSerialLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class CcsSerialLookupController extends Controller
{
    private function input(Request $request): array
    {
        $data=$request->validate(['serial_number'=>['required','string','max:100'],'page'=>['nullable','integer','min:1','max:100000']]);
        if(trim($data['serial_number'])==='')throw \Illuminate\Validation\ValidationException::withMessages(['serial_number'=>'Enter an actual serial number.']);
        return $data;
    }
    public function customer(Request $request,CcsSerialLookupService $lookup)
    {
        $data=$this->input($request);
        $customerId=(int)$request->attributes->get('portal_account')->customer_id;
        return response()->json($lookup->lookup($customerId,$data['serial_number'],(int)($data['page']??1)))->header('Cache-Control','no-store, private');
    }
    public function preview(Request $request,CcsSerialLookupService $lookup)
    {
        $request->validate(['customer_id'=>['required','integer',Rule::exists('customers','id')->whereNull('deleted_at')]]);
        $data=$this->input($request);
        $result=$lookup->lookup((int)$request->input('customer_id'),$data['serial_number'],(int)($data['page']??1));
        $result['meta']['preview']=true;
        return response()->json($result)->header('Cache-Control','no-store, private');
    }
    public function page()
    {
        $customers=DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(['id','name','customer_code']);
        return response()->view('ccs.serial-preview',compact('customers'))->header('Cache-Control','no-store, private');
    }
    public function searchCustomers(Request $request)
    {
        $data=$request->validate(['q'=>['required','string','min:2','max:100']]); $q=trim($data['q']);
        if(mb_strlen($q)<2)return response()->json(['data'=>[],'has_more'=>false])->header('Cache-Control','no-store, private');
        $customers=DB::table('customers as c')->whereNull('c.deleted_at')->where(function($query)use($q){$query->where('c.name','like','%'.$q.'%')->orWhere('c.customer_code','like','%'.$q.'%')->orWhere('c.phone','like','%'.$q.'%')->orWhere('c.nic','like','%'.$q.'%')->orWhereExists(function($v)use($q){$v->selectRaw('1')->from('vehicles as v')->whereColumn('v.customer_id','c.id')->whereNull('v.deleted_at')->where('v.registration_number','like','%'.$q.'%');});})->orderBy('c.name')->limit(51)->get(['c.id','c.name','c.customer_code','c.phone','c.nic']);
        $vehicles=DB::table('vehicles')->whereNull('deleted_at')->whereIn('customer_id',$customers->take(50)->pluck('id'))->get(['customer_id','registration_number'])->groupBy('customer_id');
        $customers->take(50)->each(function($c)use($vehicles){$c->plates=($vehicles->get($c->id)??collect())->pluck('registration_number')->all();});
        return response()->json(['data'=>$customers->take(50)->values(),'has_more'=>$customers->count()>50])->header('Cache-Control','no-store, private');
    }
    public function customerSerials(Request $request)
    {
        $data=$request->validate(['customer_id'=>['required','integer',Rule::exists('customers','id')->whereNull('deleted_at')],'page'=>['nullable','integer','min:1','max:100000']]); $id=(int)$data['customer_id'];
        $owned=function($q)use($id){return $q->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('c.id',$id)->whereNull('c.deleted_at')->whereNull('v.deleted_at')->whereNull('j.deleted_at');};
        $battery=$owned(DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id'))->whereColumn('s.vehicle_id','v.id')->whereNotNull('m.serial_number')->whereRaw("TRIM(m.serial_number) <> ''")->selectRaw('m.serial_number as serial_number');
        $parts=$owned(DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id'))->whereIn('inv.status',['issued','partially_paid','paid'])->where(function($q){$q->where('item.stock_managed',true)->orWhereIn('item.item_origin',['manual_part','customer_supplied']);})->whereNotNull('item.serial_number')->whereRaw("TRIM(item.serial_number) <> ''")->selectRaw('item.serial_number as serial_number');
        $records=DB::query()->fromSub($battery->union($parts),'serials')->orderBy('serial_number')->paginate(30);
        return response()->json($records)->header('Cache-Control','no-store, private');
    }
    public function findCustomers(Request $request)
    {
        $data=$this->input($request); $serial=mb_strtolower(trim($data['serial_number']));
        $owned=function($q){return $q->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at');};
        $battery=$owned(DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id'))->whereColumn('s.vehicle_id','v.id')->whereRaw('LOWER(m.serial_number) = ?',[$serial])->select('c.id','c.name','c.customer_code','c.phone');
        $parts=$owned(DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id'))->whereIn('inv.status',['issued','partially_paid','paid'])->where(function($q){$q->where('item.stock_managed',true)->orWhereIn('item.item_origin',['manual_part','customer_supplied']);})->whereRaw('LOWER(item.serial_number) = ?',[$serial])->select('c.id','c.name','c.customer_code','c.phone');
        $customers=DB::query()->fromSub($battery->union($parts),'matches')->orderBy('name')->limit(51)->get();
        return response()->json(['data'=>$customers->take(50)->values(),'has_more'=>$customers->count()>50])->header('Cache-Control','no-store, private');
    }
}
