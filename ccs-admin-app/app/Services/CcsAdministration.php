<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CcsAdministration {
 public function role($user): string {
  if(!$user)return 'guest';if(in_array(strtolower($user->email),config('ccs_admin.owner_emails',[]),true))return 'owner';
  $role=$user->ccs_role??'legacy';return in_array($role,['staff','manager','legacy'],true)?'cashier':$role;
 }
 public function owner($user): bool {return $this->role($user)==='owner';}
 public function can($user,string $route,string $method='GET'): bool {
  if(!$user||(isset($user->ccs_active)&&!$user->ccs_active))return false;
  if(str_starts_with($route,'t.'))$route='ccs.'.substr($route,2);
  $role=$this->role($user);if($role==='owner')return true;
  if(in_array($route,['ccs.settings','ccs.settings.language','ccs.settings.preferences','ccs.settings.ui-mode','ccs.settings.input-method','ccs.settings.theme'],true))return true;
  if(str_starts_with($route,'ccs.settings')||str_starts_with($route,'ccs.messages.')||in_array($route,['ccs.stock.trial','ccs.warranties.approve','ccs.warranties.rules.approve'],true)||str_starts_with($route,'ccs.claims.decision'))return false;
  if($role==='technician'&&$route==='ccs.pos.rules.context')return $method==='POST';
  if($role==='technician')return in_array($route,['ccs.dashboard','ccs.jobs','ccs.stock'],true)?$method==='GET':($route==='ccs.jobs.status'&&$method==='POST');
  if($role!=='cashier')return false;
  if(str_starts_with($route,'ccs.customer-access'))return str_starts_with($route,'ccs.customer-access.serial-preview')&&$method==='GET';
  foreach(['ccs.customers','ccs.vehicles','ccs.jobs','ccs.stock','ccs.pos','ccs.invoices','ccs.invoice-items','ccs.payments','ccs.credit','ccs.warranties','ccs.claims','ccs.website','ccs.notifications','ccs.serial-records'] as $prefix)if($route===$prefix||str_starts_with($route,$prefix.'.'))return true;
  return in_array($route,['ccs.dashboard','ccs.purchases','ccs.api.search.nic'],true);
 }
 public function preferences(int $userId): array {return array_merge(['input_method'=>'english','scanner'=>'camera','paper'=>'a4','invoice_locale'=>'en','label_width'=>50,'label_height'=>30,'notify_jobs'=>true,'notify_stock'=>true,'notify_warranty'=>true,'notify_claims'=>true],json_decode(DB::table('ccs_staff_preferences')->where('user_id',$userId)->value('preferences')??'[]',true)??[]);}
 public function setting(string $key,$default=null){$value=DB::table('ccs_system_settings')->where('key',$key)->value('value');return $value===null?$default:json_decode($value,true);}
 public function audit($userId,string $action,string $entity,$id=null,array $meta=[]): void {DB::table('audit_logs')->insert(['user_id'=>$userId,'action'=>$action,'entity_type'=>$entity,'entity_id'=>$id,'metadata'=>json_encode($meta),'created_at'=>now(),'updated_at'=>now()]);}
}
