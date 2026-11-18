<?php
namespace App\Services;
use App\Models\CcsDeveloperAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
class CcsDeveloperReset {
 // Fixed allowlist, in foreign-key-safe deletion order. Never accept table names/SQL from input.
 private const TABLES=['warranty_claims','warranty_service_visits','warranties','battery_module_readings','battery_service_records','payments','invoice_items','stock_units','stock_movements','invoices','repair_jobs','customer_portal_accounts','vehicles','customers','purchases','ccs_notification_reads'];
 public function tables(): array {return array_values(array_filter(self::TABLES,fn($table)=>Schema::hasTable($table)));}
 private function signature(array $data): string {if(!config('app.key'))throw new RuntimeException('APP_KEY is required.');return hash_hmac('sha256',json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),(string)config('app.key'));}
 private function file(string $id): string {if(!Str::isUuid($id))throw new RuntimeException('Invalid cleanup review.');return app(CcsSqliteBackup::class)->root().'/clear-'.$id.'.json';}
 private function write(array $plan): void {$file=$this->file($plan['id']);$plan['signature']=$this->signature($plan);if(file_put_contents($file,json_encode($plan,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Cannot save the cleanup review.');@chmod($file,0600);}
 public function plan(string $id): array {$file=$this->file($id);if(!is_file($file))throw new RuntimeException('Cleanup review not found.');$plan=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);$signature=$plan['signature']??'';unset($plan['signature']);if(!is_string($signature)||!hash_equals($this->signature($plan),$signature))throw new RuntimeException('Cleanup review signature failed.');return $plan;}
 private function state(): array {
  $tables=array_merge($this->tables(),['inventory_items']);$counts=[];$hash=hash_init('sha256');
  foreach($tables as $table){$counts[$table]=0;hash_update($hash,$table."\n");foreach(DB::table($table)->orderBy('id')->cursor() as $row){$counts[$table]++;hash_update($hash,json_encode((array)$row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");}}
  return ['counts'=>$counts,'fingerprint'=>hash_final($hash)];
 }
 private function checkQueue(): void {
  $queue=(string)config('queue.default','database');
  if(!in_array($queue,['database','sync','null'],true))throw new RuntimeException('Browser cleanup supports the standard database/sync queue only. External queue quiescence must be handled by deployment maintenance.');
  if($queue==='database'&&((config('queue.connections.database.connection')&&config('queue.connections.database.connection')!==config('database.default'))||config('queue.connections.database.table','jobs')!=='jobs'))throw new RuntimeException('Browser cleanup needs the standard queue table on this database.');
 }
 private function checkDependencies(string $scope): void {
  $deleted=$this->tables();if($scope==='full_business')$deleted[]='inventory_items';
  foreach(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'") as $table){
   if(in_array($table->name,$deleted,true))continue;
   $quoted='"'.str_replace('"','""',$table->name).'"';
   foreach(DB::select('PRAGMA foreign_key_list('.$quoted.')') as $foreign)if(in_array($foreign->table,$deleted,true))throw new RuntimeException('An additional retained table depends on cleared data: '.$table->name.'. Review cleanup support before proceeding.');
  }
 }
 public function prepare(CcsDeveloperAccount $actor,string $scope): array {
  if(!$actor->active||!$actor->can_clear_data||!in_array($scope,['records','full_business'],true))throw new RuntimeException('Cleanup permission or scope is invalid.');
  app(CcsSqliteBackup::class)->database();$this->checkQueue();$this->checkDependencies($scope);$state=DB::transaction(fn()=>$this->state());
  $plan=['id'=>(string)Str::uuid(),'developer_id'=>$actor->id,'access_version'=>$actor->access_version,'scope'=>$scope,'database'=>app(CcsSqliteBackup::class)->database(),'state'=>'ready','expires_at'=>time()+600]+$state;$this->write($plan);return $plan;
 }
 private function journal(string $action,array $details): void {$path=app(CcsSqliteBackup::class)->root().'/developer-operations.ndjson';if(file_put_contents($path,json_encode(['at'=>gmdate('c'),'action'=>$action]+$details,JSON_THROW_ON_ERROR)."\n",FILE_APPEND|LOCK_EX)===false)throw new RuntimeException('Cannot write the developer operation journal.');}
 public function apply(string $id,CcsDeveloperAccount $actor,$lock): array {
  $plan=$this->plan($id);$fresh=CcsDeveloperAccount::find($actor->id);
  if(!$fresh||!$fresh->active||!$fresh->can_clear_data||$fresh->access_version!==$actor->access_version||$plan['developer_id']!==$actor->id||$plan['access_version']!==$actor->access_version||$plan['state']!=='ready'||$plan['expires_at']<time())throw new RuntimeException('Cleanup review expired, was used, or developer access changed. Prepare a new review.');
  $backups=app(CcsSqliteBackup::class);if(($plan['database']??null)!==$backups->database())throw new RuntimeException('The database location changed after review. Prepare a new review.');
  if(!app()->isDownForMaintenance()||!is_resource($lock)||realpath(stream_get_meta_data($lock)['uri']??'')!==realpath($backups->root().'/database.lock')||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Exclusive maintenance access is required.');
  $this->checkQueue();
  if(Schema::hasTable('jobs')&&DB::table('jobs')->exists())throw new RuntimeException('Background jobs are queued or running. Complete them before clearing business data.');
  $this->checkDependencies($plan['scope']);
  $current=DB::transaction(fn()=>$this->state());if(!hash_equals($plan['fingerprint'],$current['fingerprint']))throw new RuntimeException('Business data changed after review. Prepare a new review with current record counts.');
  // A failed backup aborts before any business rows can be deleted.
  $backup=$backups->create(null,'before_business_reset');$plan['backup_id']=$backup['id'];$plan['state']='applying';$this->write($plan);$this->journal('cleanup_started',['plan_id'=>$id,'developer_id'=>$actor->id,'backup_id'=>$backup['id'],'scope'=>$plan['scope']]);$committed=false;
  try{
   DB::statement('PRAGMA foreign_keys = ON');
   if((int)DB::selectOne('PRAGMA foreign_keys')->foreign_keys!==1)throw new RuntimeException('Foreign-key protection must be enabled.');
   DB::transaction(function()use($plan,$actor,$backups){
    foreach($this->tables() as $table)DB::table($table)->delete();
    if($plan['scope']==='full_business')DB::table('inventory_items')->delete();else DB::table('inventory_items')->update(['quantity_on_hand'=>0,'updated_at'=>now()]);
    if($plan['scope']==='full_business'&&DB::table('inventory_items')->exists())throw new RuntimeException('The catalogue was not cleared.');
    if($plan['scope']==='records'&&DB::table('inventory_items')->where('quantity_on_hand','!=',0)->exists())throw new RuntimeException('Stock balances were not cleared.');
    DB::table('users')->update(['remember_token'=>null]);
    if(DB::select('PRAGMA foreign_key_check'))throw new RuntimeException('Cleanup relationship checks failed. Changes were rolled back.');
    foreach($this->tables() as $table)if(DB::table($table)->exists())throw new RuntimeException('A business table was not cleared.');
    DB::table('ccs_developer_events')->insert(['developer_account_id'=>$actor->id,'action'=>'business_data_cleared','details'=>json_encode(['plan_id'=>$plan['id'],'scope'=>$plan['scope'],'backup_id'=>$plan['backup_id'],'counts_before'=>$plan['counts']]),'created_at'=>now(),'updated_at'=>now()]);
    app(CcsAdministration::class)->audit(null,'developer_business_reset','maintenance',null,['developer_id'=>$actor->id,'scope'=>$plan['scope'],'backup_id'=>$plan['backup_id']]);
    if(file_put_contents($backups->root().'/session-epoch',(string)Str::uuid(),LOCK_EX)===false)throw new RuntimeException('Could not revoke old sessions.');
   });$committed=true;
   $plan['state']='completed';$plan['completed_at']=time();$this->write($plan);$this->journal('cleanup_completed',['plan_id'=>$id,'backup_id'=>$backup['id']]);return $plan;
  }catch(\Throwable $e){
   if($committed)throw new RuntimeException('Cleanup committed but final recording failed. Keep maintenance enabled and review the safety backup/journal.',9001,$e);
   $plan['state']='failed';$this->write($plan);$this->journal('cleanup_rolled_back',['plan_id'=>$id,'backup_id'=>$backup['id']]);throw $e;
  }
 }
}
