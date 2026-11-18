<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CcsDirectoryQueries
{
 public function customers(){
  $final="inv.status IN ('issued','partially_paid','paid')";
  return DB::table('customers as c')->where('c.is_system_walkin',false)->whereNull('c.deleted_at')->select('c.*')
   ->selectSub(function($q){$q->from('vehicles as v')->selectRaw('COUNT(*)')->whereColumn('v.customer_id','c.id')->whereNull('v.deleted_at');},'vehicle_count')
   ->selectSub(function($q){$q->from('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->selectRaw('MAX(COALESCE(j.received_at,j.created_at))')->whereColumn('v.customer_id','c.id')->whereNull('j.deleted_at')->whereNull('v.deleted_at');},'last_visit')
   ->selectSub(function($q)use($final){$q->from('invoices as inv')->join('repair_jobs as j','j.id','=','inv.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->selectRaw('COALESCE(SUM(inv.total_lkr),0)')->whereColumn('v.customer_id','c.id')->whereRaw($final)->whereNull('j.deleted_at')->whereNull('v.deleted_at');},'total_billed')
   ->selectSub(function($q)use($final){$balance="ROUND(inv.total_lkr - COALESCE((SELECT SUM(p.amount_lkr) FROM payments p WHERE p.invoice_id=inv.id),0),2)";$q->from('invoices as inv')->join('repair_jobs as j','j.id','=','inv.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->selectRaw("COALESCE(SUM(CASE WHEN ($balance)>0 THEN ($balance) ELSE 0 END),0)")->whereColumn('v.customer_id','c.id')->whereRaw($final)->whereNull('j.deleted_at')->whereNull('v.deleted_at');},'credit_balance');
 }
 public function vehicles(){
  return DB::table('vehicles as v')->where('v.is_system_walkin',false)->join('customers as c','c.id','=','v.customer_id')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->select('v.*','c.name as customer_name','c.phone','c.customer_code')
   ->selectSub(function($q){$q->from('repair_jobs as j')->selectRaw('COUNT(*)')->whereColumn('j.vehicle_id','v.id')->whereNull('j.deleted_at');},'job_count')
   ->selectSub(function($q){$q->from('repair_jobs as j')->selectRaw('MAX(COALESCE(j.received_at,j.created_at))')->whereColumn('j.vehicle_id','v.id')->whereNull('j.deleted_at');},'last_visit');
 }
}
