<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CcsAdministration {
 public function role($user): string {if(!$user)return 'guest';if(in_array(strtolower($user->email),config('ccs_admin.owner_emails',[]),true))return 'owner';return $user->ccs_role??'legacy';}
 public function owner($user): bool {return $this->role($user)==='owner';}
 public function can($user,string $route,string $method='GET'): bool {
  if(!$user || (isset($user->ccs_active)&&!$user->ccs_active))return false;$role=$this->role($user);if($role==='owner')return true;
  if(str_starts_with($route,'ccs.settings.developer-'))return false;
  if(str_starts_with($route,'ccs.settings.backups'))return false;
  if(in_array($route,['ccs.settings.users','ccs.settings.user-save','ccs.settings.security','ccs.settings.audit'],true))return false;
  if(str_starts_with($route,'ccs.claims.decision'))return false;
  if(in_array($role,['legacy','manager'],true))return true;
  // CCS_CARE_V3_PERMISSIONS
  if(str_starts_with($route,'ccs.messages.'))return in_array($role,['cashier','manager','legacy'],true);
  // CCS_WEBSITE_PERMISSIONS
  if(str_starts_with($route,'ccs.website.'))return in_array($role,['cashier','manager','legacy'],true);
  if(str_starts_with($route,'ccs.customer-access'))return false;
  if($route==='ccs.stock.cancel-draft')return $role==='cashier';
  if($role==='cashier' && $method!=='GET'){
   $stockPermissions=['ccs.stock.create'=>'stock_create','ccs.stock.update'=>'stock_update','ccs.stock.receive'=>'stock_receive','ccs.stock.unit-update'=>'stock_serial_manage','ccs.stock.unit-restore'=>'stock_serial_manage','ccs.stock.unit-remove'=>'stock_serial_manage'];
   if(isset($stockPermissions[$route]))return (bool)($this->preferences($user->id)['owner_permissions'][$stockPermissions[$route]]??false);
  }
  if(str_starts_with($route,'ccs.stock'))return $method==='GET'&&$role==='cashier';
  if(str_starts_with($route,'ccs.warranties'))return $method==='GET';
  if(str_starts_with($route,'ccs.invoices')||str_starts_with($route,'ccs.invoice-items')||str_starts_with($route,'ccs.pos')||str_starts_with($route,'ccs.payments')||str_starts_with($route,'ccs.credit')||$route==='ccs.purchases')return $role==='cashier';
  if(str_starts_with($route,'ccs.claims'))return true;
  if(str_starts_with($route,'ccs.settings'))return in_array($route,['ccs.settings','ccs.settings.language','ccs.settings.preferences','ccs.settings.ui-mode','ccs.settings.input-method','ccs.settings.theme'],true);
  return in_array($role,['cashier','technician'],true)&&(str_starts_with($route,'ccs.jobs')||str_starts_with($route,'ccs.customers')||str_starts_with($route,'ccs.vehicles')||str_starts_with($route,'ccs.notifications')||in_array($route,['ccs.dashboard','ccs.api.search.nic'],true));
 }
 public function preferences(int $userId): array {return array_merge(['input_method'=>'english','scanner'=>'camera','paper'=>'a4','invoice_locale'=>'en','label_width'=>50,'label_height'=>30,'notify_jobs'=>true,'notify_stock'=>true,'notify_warranty'=>true,'notify_claims'=>true],json_decode(DB::table('ccs_staff_preferences')->where('user_id',$userId)->value('preferences')??'[]',true)??[]);}
 public function setting(string $key,$default=null){$value=DB::table('ccs_system_settings')->where('key',$key)->value('value');return $value===null?$default:json_decode($value,true);}
 public function audit($userId,string $action,string $entity,$id=null,array $meta=[]): void {DB::table('audit_logs')->insert(['user_id'=>$userId,'action'=>$action,'entity_type'=>$entity,'entity_id'=>$id,'metadata'=>json_encode($meta),'created_at'=>now(),'updated_at'=>now()]);}
}
