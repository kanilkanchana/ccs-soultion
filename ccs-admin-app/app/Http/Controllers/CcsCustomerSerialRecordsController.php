<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CcsCustomerSerialRecordsController extends Controller
{
 public function searchCustomers(Request $request){
  $data=$request->validate(['q'=>['required','string','max:100']]); $q=trim($data['q']);
  if(mb_strlen($q)<2)return response()->json(['data'=>[]])->header('Cache-Control','no-store, private');
  $customers=DB::table('customers as c')->whereNull('c.deleted_at')->where(function($query)use($q){$query->where('c.name','like','%'.$q.'%')->orWhere('c.customer_code','like','%'.$q.'%')->orWhere('c.phone','like','%'.$q.'%')->orWhereExists(function($v)use($q){$v->selectRaw('1')->from('vehicles as v')->whereColumn('v.customer_id','c.id')->whereNull('v.deleted_at')->where('v.registration_number','like','%'.$q.'%');});})->orderBy('c.name')->limit(20)->get(['c.id','c.name','c.customer_code','c.phone']);
  return response()->json(['data'=>$customers])->header('Cache-Control','no-store, private');
 }
 public function index(Request $request,int $id){
  $data=$request->validate(['serial'=>['nullable','string','max:100']]);
  $customer=DB::table('customers')->where('id',$id)->whereNull('deleted_at')->first(['id','name','customer_code','phone']);abort_unless($customer,404);
  $serial=trim((string)($data['serial']??''));
  $base=function($query)use($id){return $query->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('c.id',$id)->whereNull('c.deleted_at')->whereNull('v.deleted_at')->whereNull('j.deleted_at');};
  $battery=$base(DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id'))
   ->whereColumn('s.vehicle_id','v.id')->whereNotNull('m.serial_number')->whereRaw("TRIM(m.serial_number) <> ''")
   ->when($serial!=='',fn($q)=>$q->whereRaw('LOWER(m.serial_number) = ?',[mb_strtolower($serial)]))
   ->selectRaw("'battery_module' AS source, m.id AS record_id, s.id AS service_id, j.id AS job_id, j.job_number, v.id AS vehicle_id, v.registration_number, v.make, v.model, s.service_date AS recorded_at, 'Battery Module' AS part_name, m.serial_number, m.position, m.voltage, NULL AS item_condition, NULL AS item_year, 1 AS is_battery_module");
  $parts=$base(DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id'))
   ->whereIn('inv.status',['issued','partially_paid','paid'])->whereNotNull('item.serial_number')->whereRaw("TRIM(item.serial_number) <> ''")
   ->when($serial!=='',fn($q)=>$q->whereRaw('LOWER(item.serial_number) = ?',[mb_strtolower($serial)]))
   ->selectRaw("'invoice_part' AS source, item.id AS record_id, NULL AS service_id, j.id AS job_id, j.job_number, v.id AS vehicle_id, v.registration_number, v.make, v.model, inv.issued_at AS recorded_at, item.description AS part_name, item.serial_number, NULL AS position, NULL AS voltage, item.item_condition, item.item_year, item.is_battery_module");
  $query=DB::query()->fromSub($battery->unionAll($parts),'records')->orderByDesc('recorded_at')->orderBy('source')->orderByDesc('record_id');
  if ($request->query('print') === '1') {
   $records=$query->get();
   $jobs=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('v.customer_id',$id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereIn('j.id',$records->pluck('job_id')->unique())->select('j.*','v.registration_number','v.make','v.model')->get()->keyBy('id');
   $services=DB::table('battery_service_records')->whereIn('repair_job_id',$jobs->keys())->orderBy('service_date')->orderBy('id')->get()->groupBy('repair_job_id');
   return response()->view('ccs.customer-serial-records-print',compact('customer','records','serial','jobs','services'))->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow');
  }
  $records=$query->paginate(20)->withQueryString();
  return response()->view('ccs.customer-serial-records',compact('customer','records','serial'))->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow');
 }
}
