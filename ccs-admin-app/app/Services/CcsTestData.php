<?php
namespace App\Services;
use App\Models\CcsDeveloperAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CcsTestData {
 // Child-first ordering also used for isolated batch removal. IDs are recorded by the importer only.
 private const TABLES=['warranty_claims','warranty_service_visits','warranties','battery_module_readings','battery_service_records','payments','invoice_items','stock_units','stock_movements','invoices','repair_jobs','vehicles','customers','purchases','inventory_items','warranty_rules'];
 public function available(): bool {return app()->environment(['local','testing','staging']) && DB::connection()->getDriverName()==='sqlite';}
 public function parse(string $json): array {
  if(!$this->available())throw new RuntimeException('Test import/removal is available only in local/testing/staging SQLite installations.');
  try{$p=json_decode($json,true,32,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw ValidationException::withMessages(['data'=>'Invalid JSON. Paste the complete test pack.']);}
  $p=Validator::make(is_array($p)?$p:[],[
   'format'=>'required|in:ccs-test-pack-v1','batch'=>'required|regex:/^[A-Z0-9][A-Z0-9-]{2,19}$/',
   'date'=>'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Colombo')->toDateString(),'records'=>'required|array|list|min:1|max:30',
   'records.*'=>'required|array:customer,phone,nic,plate,make,model,year,mileage,status,part,total,payments,age_days,voltage,claim,free_visit,customer_ref',
   'records.*.customer'=>'required|string|max:100','records.*.plate'=>'required|regex:/^[A-Z0-9-]{2,20}$/',
   'records.*.phone'=>['sometimes','string','regex:/^000[0-9]{7}$/'], 'records.*.nic'=>['sometimes','string','regex:/^TEST-NIC-[A-Z0-9-]{1,11}$/'],
   'records.*.make'=>'required|string|max:60','records.*.model'=>'required|string|max:80','records.*.year'=>'required|integer|min:1990|max:2100',
   'records.*.mileage'=>'required|integer|min:0|max:3000000',
   'records.*.status'=>'required|in:new_enquiry,vehicle_received,vehicle_for_assessment,inspection_booked,repair_in_progress,awaiting_parts,awaiting_approval,testing,ready,delivered,completed,closed',
   'records.*.part'=>'required|in:none,service,abs,module,battery','records.*.total'=>'required|integer|min:0|max:10000000',
   'records.*.payments'=>'present|array|list|max:10','records.*.payments.*'=>'required|integer|min:1|max:10000000',
   'records.*.age_days'=>'required|integer|min:0|max:1500','records.*.voltage'=>'nullable|numeric|min:0|max:100',
   'records.*.customer_ref'=>'sometimes|integer|min:1|max:30','records.*.claim'=>'sometimes|boolean','records.*.free_visit'=>'sometimes|boolean',
  ])->validate();
  foreach($p['records'] as $i=>$r){
   if(isset($r['customer_ref']) && ($r['customer_ref']>$i || $p['records'][$r['customer_ref']-1]['customer']!==$r['customer']))throw ValidationException::withMessages(['data'=>'customer_ref must point to an earlier row with the same customer name.']);
   if(isset($r['customer_ref']) && (isset($r['phone']) || isset($r['nic'])))throw ValidationException::withMessages(['data'=>'Customer phone and NIC belong on the first row for that customer.']);
   if(array_sum($r['payments'])>$r['total'] || ($r['part']==='none' && ($r['total']!==0 || $r['payments']!==[])) || ($r['part']!=='none' && $r['total']===0))throw ValidationException::withMessages(['data'=>'Row '.($i+1).': payments cannot exceed total; open jobs must have zero total and no payments.']);
   if($r['part']==='module' && (!array_key_exists('voltage',$r)||$r['voltage']===null))throw ValidationException::withMessages(['data'=>'Row '.($i+1).': battery module voltage is required.']);
   if(($r['claim']??false)&&!in_array($r['part'],['abs','module','battery'],true))throw ValidationException::withMessages(['data'=>'Claims require a covered part.']);
   if(($r['free_visit']??false)&&$r['part']!=='battery')throw ValidationException::withMessages(['data'=>'Free service visits require the test battery package.']);
  }
  return $p;
 }
 public function review(array $p): array {
  $counts=['customers'=>count(array_filter($p['records'],fn($r)=>!isset($r['customer_ref']))),'vehicles'=>count($p['records']),'jobs'=>count($p['records']),'invoices'=>0,'payments'=>0,'warranties'=>0,'stock_units'=>0];$total=0;$paid=0;
  foreach($p['records'] as $r){if($r['part']!=='none'){$counts['invoices']++;$counts['payments']+=count($r['payments']);$total+=$r['total'];$paid+=array_sum($r['payments']);}if(in_array($r['part'],['abs','module','battery'],true)){$counts['warranties']++;$counts['stock_units']+=3;}}
  return ['counts'=>$counts,'total'=>$total,'paid'=>$paid,'due'=>$total-$paid];
 }
 private function add(string $table,array $data,array &$ids): int {$id=DB::table($table)->insertGetId($data);$ids[$table][]=$id;return $id;}
 public function fingerprint(array $ids): string {
  $hash=hash_init('sha256');foreach(self::TABLES as $table){hash_update($hash,$table);foreach(DB::table($table)->whereIn('id',$ids[$table]??[])->orderBy('id')->get() as $row)hash_update($hash,json_encode($row,JSON_THROW_ON_ERROR));}return hash_final($hash);
 }
 public function import(array $p,CcsDeveloperAccount $actor,string $backupId): string {
  if(!$this->available()||!$actor->active||!$actor->can_clear_data)throw new RuntimeException('Test-data permission unavailable.');
  $p=$this->parse(json_encode($p,JSON_THROW_ON_ERROR));
  if(DB::table('ccs_test_imports')->where('batch_key',$p['batch'])->exists())throw new RuntimeException('This batch key was already used. Choose a new key; repeated imports are blocked.');
  $owner=DB::table('users')->where('id',$actor->authorized_by)->first();
  if(!$owner||!app(CcsAdministration::class)->owner($owner)||!$owner->ccs_active)throw new RuntimeException('The authorizing owner must still be active.');
  return DB::transaction(function()use($p,$actor,$owner,$backupId){
   $ids=[];$customerRows=[];$batch=$p['batch'];$today=Carbon::parse($p['date'],'Asia/Colombo')->startOfDay();
   $rules=[];
   foreach(['abs'=>[6,0,'abs'],'module'=>[6,0,'module'],'battery'=>[36,3,'new_battery']] as $kind=>[$months,$visits,$coverage]){
    if(!collect($p['records'])->contains('part',$kind))continue;
    $stamp=now();$rules[$kind]=$this->add('warranty_rules',['name'=>'TEST '.$batch.' '.ucfirst($kind).' warranty','category'=>$kind==='abs'?'abs':'hybrid_battery','coverage_kind'=>$coverage,'duration_months'=>$months,'free_service_count'=>$visits,'limit_basis'=>'time_first','terms'=>'TEST DATA ONLY. '.$months.' months; not a client-approved commercial policy.','approved_by_owner'=>true,'active'=>true,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
   }
   foreach($p['records'] as $i=>$r){
    $key=$batch.'-'.str_pad((string)($i+1),2,'0',STR_PAD_LEFT);$at=$today->copy()->subDays($r['age_days'])->setTime(9,0);$stamp=$at->copy()->utc();
    $customer=isset($r['customer_ref'])?$customerRows[$r['customer_ref']-1]:$this->add('customers',['customer_code'=>'TEST-C-'.$key,'name'=>$r['customer'],'phone'=>$r['phone']??null,'nic'=>$r['nic']??null,'address'=>'TEST DATA — sample address, Colombo','notes'=>'TEST BATCH '.$batch.' — fictional customer; contact details are non-dialable test values.','created_at'=>$stamp,'updated_at'=>$stamp],$ids);
    $customerRows[$i]=$customer;
    $plate='TEST-'.$r['plate'];
    $normalized=preg_replace('/[^A-Z0-9]/','',$plate);
    foreach(DB::table('vehicles')->pluck('registration_number') as $existing)if(preg_replace('/[^A-Z0-9]/','',strtoupper($existing))===$normalized)throw new RuntimeException('Duplicate test number plate: '.$plate.'. Change it in the pack. No rows were imported.');
    $vehicle=$this->add('vehicles',['customer_id'=>$customer,'vehicle_code'=>'TEST-V-'.$key,'registration_number'=>$plate,'make'=>$r['make'],'model'=>$r['model'],'manufacture_year'=>$r['year'],'current_mileage_km'=>$r['mileage'],'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
    $job=$this->add('repair_jobs',['job_number'=>'TEST-J-'.$key,'vehicle_id'=>$vehicle,'status'=>$r['status'],'complaint'=>'TEST — '.$r['part'].' inspection / replacement','diagnosis'=>'Synthetic test record','job_category'=>$r['part']==='abs'?'abs_brakes':(in_array($r['part'],['module','battery'],true)?'hybrid_battery':'diagnostics'),'service_type'=>'TEST workshop service','technician_name'=>'Kasun — TEST Technician','mileage_at_service_km'=>$r['mileage'],'received_at'=>$stamp,'completed_at'=>in_array($r['status'],['ready','delivered','completed','closed'],true)?$stamp:null,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
    if($r['part']==='none')continue;
    $paid=array_sum($r['payments']);$invoice=$this->add('invoices',['invoice_number'=>'TEST-I-'.$key,'repair_job_id'=>$job,'status'=>$paid===$r['total']?'paid':($paid>0?'partially_paid':'issued'),'subtotal_lkr'=>$r['total'],'total_lkr'=>$r['total'],'is_credit_sale'=>$paid<$r['total'],'issued_at'=>$stamp,'print_locale'=>'en','print_paper'=>'a4','created_at'=>$stamp,'updated_at'=>$stamp],$ids);
    $stock=null;$unit=null;$serial=null;$snapshot=null;$name='TEST diagnostic service';
    if($r['part']!=='service'){
     $kind=$r['part'];$name='TEST '.(['abs'=>'Toyota Prius ABS Pump','module'=>'Toyota Prius Battery Module','battery'=>'Toyota Aqua New Battery'][$kind]);
     $stock=$this->add('inventory_items',['sku'=>'TEST-P-'.$key,'barcode'=>'TEST-BC-'.$key,'name'=>$name,'condition'=>$kind==='battery'?'new':'reconditioned','category'=>$kind==='abs'?'ABS':'Hybrid Battery','service_application'=>'TEST Toyota hybrid part','quantity_on_hand'=>2,'reorder_level'=>2,'selling_price_lkr'=>$r['total'],'purchase_price_lkr'=>(int)round($r['total']*.6),'supplier'=>'Lanka Hybrid Parts — TEST','has_serial'=>true,'is_battery_module'=>$kind==='module','active'=>true,'default_warranty_rule_id'=>$rules[$kind],'item_year'=>(string)$r['year'],'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
     for($n=1;$n<=3;$n++){$number='TEST-S-'.$key.'-'.$n;$id=$this->add('stock_units',['inventory_item_id'=>$stock,'serial_number'=>$number,'serial_key'=>$number,'status'=>$n===1?'sold':'available','invoice_id'=>$n===1?$invoice:null,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);if($n===1){$serial=$number;$unit=$id;}}
     $this->add('stock_movements',['inventory_item_id'=>$stock,'quantity_change'=>3,'reason'=>'stock_received','recorded_by'=>$owner->id,'notes'=>'TEST receipt '.$batch,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
     $this->add('stock_movements',['inventory_item_id'=>$stock,'repair_job_id'=>$job,'quantity_change'=>-1,'reason'=>'pos_sale','recorded_by'=>$owner->id,'notes'=>'TEST invoice '.$batch,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
     $this->add('purchases',['purchase_date'=>$at->toDateString(),'module_name'=>$name,'quantity'=>3,'year'=>(string)$r['year'],'condition'=>$kind==='battery'?'new':'reconditioned','supplier'=>'Lanka Hybrid Parts — TEST','price_lkr'=>(int)round($r['total']*.6),'is_credit'=>true,'payment_date'=>null,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
     $snapshot=app(CcsStockWarrantyService::class)->snapshot($rules[$kind]);
    }
    $this->add('invoice_items',['invoice_id'=>$invoice,'inventory_item_id'=>$stock,'stock_unit_id'=>$unit,'description'=>$name,'quantity'=>1,'unit_price_lkr'=>$r['total'],'line_total_lkr'=>$r['total'],'serial_number'=>$serial,'stock_managed'=>$stock!==null,'requires_serial'=>$stock!==null,'is_battery_module'=>$r['part']==='module','item_origin'=>$stock?'stock':'service','item_condition'=>$stock?($r['part']==='battery'?'new':'reconditioned'):null,'item_year'=>$stock?(string)$r['year']:null,'item_supplier'=>$stock?'Lanka Hybrid Parts — TEST':null,'sale_warranty_rule_id'=>$stock?$rules[$r['part']]:null,'sale_warranty_snapshot'=>$snapshot?json_encode($snapshot):null,'created_at'=>$stamp,'updated_at'=>$stamp],$ids);
    foreach($r['payments'] as $n=>$amount){$date=$at->copy()->addDays($n);if($date->gt($today->copy()->endOfDay()))$date=$today->copy()->setTime(12,0);$this->add('payments',['invoice_id'=>$invoice,'amount_lkr'=>$amount,'method'=>'cash','reference'=>'TEST receipt '.$key.'-'.($n+1),'paid_at'=>$date->copy()->utc(),'created_at'=>$date->copy()->utc(),'updated_at'=>$date->copy()->utc()],$ids);}
    if($r['part']==='module'){
     $record=$this->add('battery_service_records',['repair_job_id'=>$job,'vehicle_id'=>$vehicle,'recorded_by'=>$owner->id,'request_token'=>(string)Str::uuid(),'service_date'=>$at->toDateString(),'mileage_km'=>$r['mileage'],'module_count'=>1,'notes'=>'TEST module reading','created_at'=>$stamp,'updated_at'=>$stamp],$ids);
     $this->add('battery_module_readings',['battery_service_record_id'=>$record,'position'=>1,'serial_number'=>$serial,'voltage'=>$r['voltage'],'notes'=>'TEST measured voltage'],$ids);
    }
    if($stock){
     app(CcsStockWarrantyService::class)->issueInvoice($invoice,$owner->id);
     $warranty=DB::table('warranties')->where('repair_job_id',$job)->value('id');$ids['warranties'][]=$warranty;
     if($r['claim']??false)$this->add('warranty_claims',['warranty_id'=>$warranty,'created_by'=>$owner->id,'status'=>'pending','received_on'=>$today->toDateString(),'problem'=>'TEST — warning light returned; inspect and review coverage.','created_at'=>now(),'updated_at'=>now()],$ids);
     if($r['free_visit']??false)$this->add('warranty_service_visits',['warranty_id'=>$warranty,'repair_job_id'=>$job,'recorded_by'=>$owner->id,'service_date'=>$today->toDateString(),'mileage_km'=>$r['mileage'],'notes'=>'TEST first free service recorded','created_at'=>now(),'updated_at'=>now()],$ids);
    }
   }
   if(DB::select('PRAGMA foreign_key_check'))throw new RuntimeException('Test data relationship check failed.');
   $id=(string)Str::uuid();DB::table('ccs_test_imports')->insert(['id'=>$id,'batch_key'=>$batch,'data_date'=>$p['date'],'developer_account_id'=>$actor->id,'state'=>'imported','record_ids'=>json_encode($ids),'fingerprint'=>$this->fingerprint($ids),'backup_id'=>$backupId,'created_at'=>now(),'updated_at'=>now()]);
   $this->event($actor,'test_data_imported',['batch'=>$batch,'date'=>$p['date'],'backup_id'=>$backupId,'summary'=>$this->review($p)]);
   return $id;
  });
 }
 private function event(CcsDeveloperAccount $actor,string $action,array $details): void {DB::table('ccs_developer_events')->insert(['developer_account_id'=>$actor->id,'action'=>$action,'details'=>json_encode($details),'created_at'=>now(),'updated_at'=>now()]);}
 public function remove(string $id,CcsDeveloperAccount $actor,string $backupId): void {
  if(!$this->available()||!$actor->active||!$actor->can_clear_data)throw new RuntimeException('Test-data clearing permission unavailable.');
  DB::transaction(function()use($id,$actor,$backupId){
   $batch=DB::table('ccs_test_imports')->where('id',$id)->lockForUpdate()->first();
   if(!$batch||$batch->state!=='imported')throw new RuntimeException('Test batch was already removed or is unavailable.');
   $ids=json_decode($batch->record_ids,true,32,JSON_THROW_ON_ERROR);
   if(!hash_equals($batch->fingerprint,$this->fingerprint($ids)))throw new RuntimeException('This batch has been edited or cleared since import. Isolated removal is blocked. Use the full business cleanup review if this is a disposable test database.');
   // Reject any new child links, including potential cascading children outside this batch.
   foreach(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $table){
    $quoted='"'.str_replace('"','""',$table->name).'"';
    foreach(DB::select('PRAGMA foreign_key_list('.$quoted.')') as $fk){
     if(empty($ids[$fk->table]))continue;
     $query=DB::table($table->name)->whereIn($fk->from,$ids[$fk->table]);
     if(!empty($ids[$table->name]))$query->whereNotIn('id',$ids[$table->name]);
     if($query->exists())throw new RuntimeException('Other records now depend on this test batch. Isolated removal was blocked.');
    }
   }
   foreach(self::TABLES as $table)DB::table($table)->whereIn('id',$ids[$table]??[])->delete();
   if(DB::select('PRAGMA foreign_key_check'))throw new RuntimeException('Relationship check failed; removal rolled back.');
   DB::table('ccs_test_imports')->where('id',$id)->update(['state'=>'removed','updated_at'=>now()]);
   $this->event($actor,'test_data_removed',['batch'=>$batch->batch_key,'date'=>$batch->data_date,'backup_id'=>$backupId]);
  });
 }
}
