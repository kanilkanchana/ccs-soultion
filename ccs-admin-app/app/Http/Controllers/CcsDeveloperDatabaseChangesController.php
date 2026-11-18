<?php
namespace App\Http\Controllers;
use App\Services\{CcsDeveloperDatabaseChanges,CcsSqliteBackup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CcsDeveloperDatabaseChangesController extends Controller {
 private function actor(Request $r){$a=$r->attributes->get('ccs_developer');abort_unless($a&&$a->active,403);return $a;}
 public function form(Request $r,CcsDeveloperDatabaseChanges $changes){
  $developer=$this->actor($r);$d=$r->validate(['action'=>'required|in:add,edit,delete','id'=>'nullable|integer|min:1']);$changes->authorize($developer,$d['action']);
  $row=$d['action']==='add'?null:$changes->row((int)($d['id']??0));if($row)$changes->unlocked($row);$action=$d['action'];return view('developer.database-change',compact('developer','row','action'));
 }
 public function preview(Request $r,CcsDeveloperDatabaseChanges $changes){
  $developer=$this->actor($r);$d=$r->validate(['action'=>'required|in:add,edit,delete','id'=>'nullable|integer|min:1']);$changes->password($r);$plan=$changes->preview($r,$d['action'],$d['action']==='add'?null:(int)($d['id']??0));
  // Store no password; only one pending change per developer session.
  $r->session()->put('ccs_database_change',$plan);return view('developer.database-change-review',compact('developer','plan'));
 }
 public function apply(Request $r,CcsDeveloperDatabaseChanges $changes,CcsSqliteBackup $backups){
  $developer=$this->actor($r);$changes->password($r);$r->validate(['operation_id'=>'required|uuid','confirm'=>'accepted','phrase'=>'required|string']);$plan=$r->session()->get('ccs_database_change');
  abort_unless(is_array($plan)&&hash_equals($plan['operation_id'],$r->input('operation_id')),409,'No matching change preview. Prepare a new preview.');
  abort_unless($plan['developer_id']===$developer->id&&$plan['access_version']===$developer->access_version&&$plan['expires']>=time(),409,'Change preview expired.');$changes->authorize($developer,$plan['action']);
  $expected=strtoupper($plan['action']).' '.($plan['row_id']??'NEW');if($r->input('phrase')!==$expected)throw ValidationException::withMessages(['phrase'=>'Type exactly: '.$expected]);
  $lock=$r->attributes->get('ccs_database_lock');if(!is_resource($lock)||!flock($lock,LOCK_EX|LOCK_NB))throw ValidationException::withMessages(['data'=>'Another request is active. Wait a moment and retry.']);
  $mode=app(\Illuminate\Contracts\Foundation\MaintenanceMode::class);$activated=false;
  try{
   $queue=config('queue.default');if(!in_array($queue,['database','sync','null'],true)||($queue==='database'&&((config('queue.connections.database.connection')&&config('queue.connections.database.connection')!==config('database.default'))||config('queue.connections.database.table','jobs')!=='jobs'||DB::table('jobs')->exists())))throw ValidationException::withMessages(['data'=>'Pause workers and use an empty standard database queue, or sync/null, before database maintenance.']);
   if($mode->active())throw ValidationException::withMessages(['data'=>'Maintenance is already active.']);
   $mode->activate(['time'=>time(),'status'=>503,'retry'=>15]);$activated=true;
   // A failed safety backup prevents all changes.
   $backup=$backups->create(null,'before_developer_row_'.$plan['action']);$id=$changes->commit($plan,$developer,$backup['id']);$r->session()->forget('ccs_database_change');
  }catch(ValidationException $e){throw $e;}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){throw $e;}catch(\Throwable $e){report($e);throw ValidationException::withMessages(['data'=>'No change was completed. Review the log and backup configuration, then prepare a new preview.']);}
  finally{if($activated)$mode->deactivate();flock($lock,LOCK_SH);}
  return redirect()->route('developer.database',['table'=>CcsDeveloperDatabaseChanges::TABLE])->with('success','Change applied to row '.$id.'. Safety backup: '.$backup['id']);
 }
 public function permissions(Request $r,CcsDeveloperDatabaseChanges $changes){
  $developer=$this->actor($r);abort_unless($developer->can_manage_accounts,403);abort_unless($changes->ready(),503,'Run the database-controls migration.');$accounts=DB::table('ccs_developer_accounts')->select('id','name','email','active')->get();$grants=DB::table('ccs_developer_db_permissions')->get()->keyBy('developer_account_id');return view('developer.database-permissions',compact('developer','accounts','grants'));
 }
 public function savePermissions(Request $r,CcsDeveloperDatabaseChanges $changes){
  $developer=$this->actor($r);abort_unless($developer->can_manage_accounts,403);abort_unless($changes->ready(),503);$changes->password($r);$d=$r->validate(['developer_id'=>'required|integer|exists:ccs_developer_accounts,id','can_add'=>'nullable|boolean','can_edit'=>'nullable|boolean','can_delete'=>'nullable|boolean']);
  DB::transaction(function()use($r,$d,$developer){$account=DB::table('ccs_developer_accounts')->where('id',$d['developer_id'])->first();$before=DB::table('ccs_developer_db_permissions')->where('developer_account_id',$account->id)->first();$values=['can_add'=>$r->boolean('can_add'),'can_edit'=>$r->boolean('can_edit'),'can_delete'=>$r->boolean('can_delete')];if(!$account->active&&in_array(true,$values,true))throw ValidationException::withMessages(['developer_id'=>'Enable the developer account before granting write access.']);
   DB::table('ccs_developer_db_permissions')->updateOrInsert(['developer_account_id'=>$account->id],$values+['created_at'=>$before->created_at??now(),'updated_at'=>now()]);
   DB::table('ccs_developer_db_changes')->insert(['operation_id'=>(string)Str::uuid(),'developer_account_id'=>$developer->id,'action'=>'permissions','table_name'=>'ccs_developer_db_permissions','row_id'=>$account->id,'before_json'=>$before?json_encode($before):null,'after_json'=>json_encode($values),'reason'=>'Explicit per-developer database permission change','created_at'=>now(),'updated_at'=>now()]);
  });return back()->with('success','Permissions saved. Pending changes are checked again before execution.');
 }
 public function history(Request $r,CcsDeveloperDatabaseChanges $changes){$developer=$this->actor($r);abort_unless($changes->ready(),503);$events=DB::table('ccs_developer_db_changes as h')->leftJoin('ccs_developer_accounts as d','d.id','=','h.developer_account_id')->select('h.*','d.name as developer_name')->orderByDesc('h.id')->paginate(20);return view('developer.database-history',compact('developer','events'));}
}
