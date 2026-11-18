<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema,Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CcsDeveloperDatabaseChanges {
 public const TABLE='ccs_website_requests';
 public function ready():bool{return Schema::hasTable(self::TABLE)&&Schema::hasTable('ccs_developer_db_permissions')&&Schema::hasTable('ccs_developer_db_changes');}
 public function grant($actor):array {
  if(!$actor||!$actor->active||!$this->ready())return ['add'=>false,'edit'=>false,'delete'=>false];
  $g=DB::table('ccs_developer_db_permissions')->where('developer_account_id',$actor->id)->first();return ['add'=>(bool)($g->can_add??false),'edit'=>(bool)($g->can_edit??false),'delete'=>(bool)($g->can_delete??false)];
 }
 public function authorize($actor,string $action):void {abort_unless(in_array($action,['add','edit','delete'],true)&&($this->grant($actor)[$action]??false),403,'Database change permission is disabled.');}
 public function password(Request $r):void {
  $r->validate(['current_password'=>'required|string|max:128']);$actor=$r->attributes->get('ccs_developer');
  if(!$actor||!Hash::check($r->input('current_password'),$actor->password))throw ValidationException::withMessages(['current_password'=>'Current developer password is incorrect.']);
 }
 public function row(int $id):object {$row=DB::table(self::TABLE)->where('id',$id)->first();abort_unless($row,404);return $row;}
 public function unlocked(object $row):void {if($row->repair_job_id||$row->customer_id||$row->vehicle_id||$row->status==='converted')throw ValidationException::withMessages(['data'=>'Linked requests are protected. Use the Website Requests / Job Cards workflow.']);}
 public function fingerprint(object $row):string {return hash('sha256',json_encode((array)$row,JSON_THROW_ON_ERROR));}
 public function values(Request $r):array {
  $d=$r->validate(['kind'=>'required|in:inspection,quote,contact,warranty,complaint','name'=>'required|string|min:2|max:150','phone'=>'required|string|max:30','vehicle'=>'nullable|string|max:180','registration_number'=>'nullable|string|max:30','reference'=>'nullable|string|max:120','message'=>'required|string|max:4000']);
  $d['phone']=app(CcsWebsiteIntake::class)->phone($d['phone']);if(!preg_match('/^[1-9][0-9]{7,14}$/D',$d['phone']))throw ValidationException::withMessages(['phone'=>'Enter a valid phone number.']);
  $d['name']=trim($d['name']);if(mb_strlen($d['name'])<2)throw ValidationException::withMessages(['name'=>'Enter a customer name.']);
  if(in_array($d['kind'],['inspection','quote'],true)&&empty(trim($d['vehicle']??'')))throw ValidationException::withMessages(['vehicle'=>'Vehicle is required for inspection / quote.']);
  return $d;
 }
 public function preview(Request $r,string $action,?int $id=null):array {
  $actor=$r->attributes->get('ccs_developer');$this->authorize($actor,$action);abort_unless(DB::connection()->getDriverName()==='sqlite',422,'Database changes currently require SQLite safety backups.');
  $r->validate(['reason'=>'required|string|min:10|max:500']);$before=$id?$this->row($id):null;if($before)$this->unlocked($before);
  $after=$action==='delete'?null:$this->values($r);
  return ['operation_id'=>(string)Str::uuid(),'developer_id'=>$actor->id,'access_version'=>$actor->access_version,'action'=>$action,'row_id'=>$id,'before'=>$before?(array)$before:null,'after'=>$after,'fingerprint'=>$before?$this->fingerprint($before):null,'reason'=>$r->input('reason'),'expires'=>time()+600];
 }
 public function commit(array $plan,$actor,string $backupId):int {
  $this->authorize($actor,$plan['action']);abort_unless($plan['developer_id']===$actor->id&&$plan['access_version']===$actor->access_version&&$plan['expires']>=time(),409,'Change preview expired. Review it again.');
  return DB::transaction(function()use($plan,$actor,$backupId){
   if(DB::table('ccs_developer_db_changes')->where('operation_id',$plan['operation_id'])->exists())throw ValidationException::withMessages(['data'=>'This change was already applied.']);
   if($plan['row_id']){$row=$this->row($plan['row_id']);$this->unlocked($row);if(!hash_equals($plan['fingerprint'],$this->fingerprint($row)))throw ValidationException::withMessages(['data'=>'This request changed after preview. Review it again.']);}
   $id=$plan['row_id'];$after=$plan['after'];
   if($plan['action']==='add'){$uuid=(string)Str::uuid();$id=DB::table(self::TABLE)->insertGetId($after+['submission_id'=>$uuid,'public_reference'=>'CCS-DEV-'.Str::upper(Str::random(12)),'payload_hash'=>hash('sha256',$uuid),'status'=>'new','contact_consent'=>false,'reminder_consent'=>false,'source_page'=>'developer','source_origin'=>'internal','version'=>1,'updated_by'=>null,'created_at'=>now(),'updated_at'=>now()]);}
   elseif($plan['action']==='edit')DB::table(self::TABLE)->where('id',$id)->update($after+['version'=>$row->version+1,'updated_at'=>now()]);
   else DB::table(self::TABLE)->where('id',$id)->delete();
   $saved=$plan['action']==='delete'?null:(array)$this->row($id);
   DB::table('ccs_developer_db_changes')->insert(['operation_id'=>$plan['operation_id'],'developer_account_id'=>$actor->id,'action'=>$plan['action'],'table_name'=>self::TABLE,'row_id'=>$id,'before_json'=>$plan['before']?json_encode($plan['before'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null,'after_json'=>$saved?json_encode($saved,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null,'reason'=>$plan['reason'],'backup_id'=>$backupId,'created_at'=>now(),'updated_at'=>now()]);
   return (int)$id;
  });
 }
}
