<?php
namespace App\Http\Controllers;
use App\Services\CcsDirectoryQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CcsDirectoryController extends Controller
{
 public function customers(Request $request,CcsDirectoryQueries $directory){
  $request->validate(['q'=>['nullable','string','max:150'],'credit'=>['nullable','in:1'],'page'=>['nullable','integer','min:1','max:100000']]);$search=trim((string)$request->query('q',''));$credit=$request->boolean('credit');
  $base=$directory->customers();
  if($search!=='')$base->where(function($q)use($search){foreach(['c.name','c.customer_code','c.phone','c.whatsapp'] as $n=>$field){if($n===0)$q->where($field,'like','%'.$search.'%');else $q->orWhere($field,'like','%'.$search.'%');}$q->orWhereExists(function($v)use($search){$v->selectRaw('1')->from('vehicles as v')->whereColumn('v.customer_id','c.id')->whereNull('v.deleted_at')->where('v.registration_number','like','%'.$search.'%');});});
  $query=DB::query()->fromSub($base,'directory');if($credit)$query->where('credit_balance','>',0);$customers=$query->orderByDesc('id')->paginate(20)->withQueryString();
  $metrics=DB::query()->fromSub($directory->customers(),'directory')->selectRaw('COUNT(*) as customers, COALESCE(SUM(vehicle_count),0) as vehicles, COALESCE(SUM(credit_balance),0) as outstanding')->first();
  $activeThisMonth=DB::query()->fromSub($directory->customers(),'directory')->where('last_visit','>=',now()->startOfMonth()->toDateTimeString())->count();
  return view('ccs.customers',compact('customers','search','credit','metrics','activeThisMonth'));
 }
 public function vehicles(Request $request,CcsDirectoryQueries $directory){
  $request->validate(['q'=>['nullable','string','max:150'],'customer_id'=>['nullable','integer','min:1'],'page'=>['nullable','integer','min:1','max:100000']]);$search=trim((string)$request->query('q',''));$selectedCustomer=(int)$request->query('customer_id',0);$query=$directory->vehicles();
  if($search!=='')$query->where(function($q)use($search){foreach(['v.registration_number','v.vehicle_code','v.make','v.model','c.name','c.phone','c.customer_code'] as $i=>$field){if($i===0)$q->where($field,'like','%'.$search.'%');else $q->orWhere($field,'like','%'.$search.'%');}});
  if($selectedCustomer)$query->where('c.id',$selectedCustomer);$vehicles=$query->orderByDesc('v.id')->paginate(20)->withQueryString();$customers=DB::table('customers')->where('is_system_walkin',false)->whereNull('deleted_at')->orderBy('name')->get(['id','name','phone','customer_code']);
  return view('ccs.vehicles',compact('vehicles','customers','search','selectedCustomer'));
 }
}
