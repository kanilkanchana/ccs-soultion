<?php
namespace App\Services;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsStockWarrantyService
{
 public function snapshot(?int $ruleId): ?array {
  if(!$ruleId)return null;
  $rule=DB::table('warranty_rules')->where('id',$ruleId)->where('approved_by_owner',true)->where('active',true)->first();
  if(!$rule)throw ValidationException::withMessages(['stock'=>'The selected part warranty package is not approved/active. Check Parts & Stock.']);
  return ['name'=>$rule->name,'category'=>$rule->category,'coverage_kind'=>$rule->coverage_kind,'duration_months'=>$rule->duration_months,'distance_km'=>$rule->distance_km,'limit_basis'=>$rule->limit_basis,'terms'=>$rule->terms,'free_service_count'=>(int)$rule->free_service_count];
 }
 // Called inside the invoice's transaction. No card is issued for a draft/void bill.
 public function issueInvoice(int $invoiceId,int $userId): void {
  $invoice=DB::table('invoices')->where('id',$invoiceId)->lockForUpdate()->first();
  if(!$invoice||!in_array($invoice->status,['issued','partially_paid','paid'],true))return;
  $job=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('j.id',$invoice->repair_job_id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['j.id','j.mileage_at_service_km']);
  if(!$job)throw ValidationException::withMessages(['stock'=>'The invoice vehicle/customer is no longer available.']);
  $start=Carbon::parse($invoice->issued_at??now())->startOfDay();
  foreach(DB::table('invoice_items')->where('invoice_id',$invoiceId)->whereNotNull('sale_warranty_snapshot')->get() as $item){
   if(DB::table('warranties')->where('stock_sale_item_id',$item->id)->exists())continue;
   $terms=json_decode($item->sale_warranty_snapshot,true);
   if(!is_array($terms)||!$item->sale_warranty_rule_id)throw ValidationException::withMessages(['stock'=>'Invalid saved warranty package. Contact the owner.']);
   $months=(int)($terms['duration_months']??0);$distance=(int)($terms['distance_km']??0);$base=$job->mileage_at_service_km;
   $pending=$distance>0 && $base===null;
   $terms=array_merge($terms,['covered_serial'=>$item->serial_number,'covered_description'=>$item->description,'battery_year'=>$item->item_year,'quantity'=>$item->quantity,
    'starting_mileage_km'=>$base,'provisional'=>$pending,'approval_pending_reason'=>$pending?'Starting mileage is required.':null,'issued_from'=>'stock_sale',
    'approved_by'=>$pending?null:$userId,'approved_at'=>$pending?null:now()->toIso8601String()]);
   $id=DB::table('warranties')->insertGetId(['repair_job_id'=>$job->id,'warranty_rule_id'=>$item->sale_warranty_rule_id,'invoice_item_id'=>$item->id,'stock_sale_item_id'=>$item->id,
    'starts_on'=>$start->toDateString(),'expires_on'=>$months>0?$start->copy()->addMonthsNoOverflow($months)->subDay()->toDateString():null,
    'mileage_limit_km'=>$distance>0&&$base!==null?(int)$base+$distance:null,'terms_snapshot'=>json_encode($terms,JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]);
   DB::table('audit_logs')->insert(['user_id'=>$userId,'action'=>$pending?'stock_warranty_pending_mileage':'stock_warranty_issued','entity_type'=>'warranty','entity_id'=>$id,'metadata'=>json_encode(['invoice_id'=>$invoiceId,'invoice_item_id'=>$item->id,'warranty_rule_id'=>$item->sale_warranty_rule_id]),'created_at'=>now(),'updated_at'=>now()]);
  }
 }
}
