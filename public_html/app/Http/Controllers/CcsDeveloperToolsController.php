<?php
namespace App\Http\Controllers;
use App\Models\CcsDeveloperAccount;
use App\Services\CcsSqliteBackup;
use App\Services\CcsTestData;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CcsDeveloperToolsController extends Controller {
 private function actor(Request $r): CcsDeveloperAccount {return $r->attributes->get('ccs_developer');}
 private function password(Request $r): void {$r->validate(['current_password'=>'required|string|max:128']);if(!Hash::check($r->current_password,$this->actor($r)->password))throw ValidationException::withMessages(['current_password'=>'Current developer password is incorrect.']);}
 private function event(Request $r,string $action,array $details): void {DB::table('ccs_developer_events')->insert(['developer_account_id'=>$this->actor($r)->id,'action'=>$action,'details'=>json_encode($details),'created_at'=>now(),'updated_at'=>now()]);}
 public function team(Request $r){$developer=$this->actor($r);abort_unless($developer->can_manage_accounts,403);$accounts=CcsDeveloperAccount::orderBy('id')->get();return view('developer.team',compact('developer','accounts'));}
 public function saveMember(Request $r){
  $developer=$this->actor($r);abort_unless($developer->can_manage_accounts,403);$this->password($r);
  $r->merge(['email'=>mb_strtolower(trim((string)$r->email))]);
  $data=$r->validate(['id'=>'nullable|integer|exists:ccs_developer_accounts,id','name'=>'required|string|max:150','email'=>['required','email','max:255',\Illuminate\Validation\Rule::unique('ccs_developer_accounts','email')->ignore($r->input('id'))],'password'=>[$r->filled('id')?'nullable':'required','string','min:16','max:64','confirmed'],'active'=>'nullable|boolean','can_clear_data'=>'nullable|boolean']);
  DB::transaction(function()use($r,$data,$developer){
   $account=!empty($data['id'])?CcsDeveloperAccount::lockForUpdate()->findOrFail($data['id']):new CcsDeveloperAccount();
   abort_if($account->exists&&($account->id===$developer->id||$account->can_manage_accounts),403,'Team administrators are managed from the server terminal.');
   $account->name=$data['name'];$account->email=$data['email'];$account->active=$r->boolean('active');$account->can_clear_data=$r->boolean('can_clear_data');$account->can_manage_accounts=false;$account->authorized_by=$developer->authorized_by;$account->access_version=(int)($account->access_version??0)+1;
   if(!empty($data['password']))$account->password=Hash::make($data['password']);$account->save();
   $this->event($r,'developer_team_saved',['member_id'=>$account->id,'active'=>$account->active,'can_clear_data'=>$account->can_clear_data,'password_reset'=>!empty($data['password'])]);
  });return back()->with('success','Developer member saved. Previous member sessions and cleanup reviews are revoked.');
 }
 public function testData(Request $r,CcsTestData $test){$developer=$this->actor($r);$pack=file_get_contents(resource_path('test-data/full-workshop.json'));$p=json_decode($pack,true,32,JSON_THROW_ON_ERROR);$p['date']=now('Asia/Colombo')->toDateString();$p['batch']='DEMO-'.now('Asia/Colombo')->format('ymd-His');$pack=json_encode($p,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);$available=$test->available();return view('developer.test-data',compact('developer','pack','available'));}
 public function reviewImport(Request $r,CcsTestData $test){abort_unless($test->available(),403,'Test imports are disabled on this environment.');abort_unless($this->actor($r)->can_clear_data,403);$r->validate(['data'=>'required|string|max:100000']);$pack=$test->parse($r->data);if(DB::table('ccs_test_imports')->where('batch_key',$pack['batch'])->exists())throw ValidationException::withMessages(['data'=>'This batch key was already used. Choose a new key.']);$summary=$test->review($pack);$token=bin2hex(random_bytes(20));$r->session()->put('ccs_test_review',['token'=>$token,'pack'=>$pack,'until'=>time()+600,'developer_id'=>$this->actor($r)->id,'version'=>$this->actor($r)->access_version]);$developer=$this->actor($r);return view('developer.test-review',compact('developer','pack','summary','token'));}
 // Serialize all developer import/backup/removal writes with ordinary web business requests.
 private function operation(Request $r,string $reason,callable $callback){
  $lock=$r->attributes->get('ccs_database_lock');if(!is_resource($lock)||!flock($lock,LOCK_EX|LOCK_NB))throw ValidationException::withMessages(['data'=>'Another request is active. Wait a moment and retry.']);
  $queue=config('queue.default');if(!in_array($queue,['database','sync','null'],true)||($queue==='database'&&((config('queue.connections.database.connection')&&config('queue.connections.database.connection')!==config('database.default'))||config('queue.connections.database.table','jobs')!=='jobs'||DB::table('jobs')->exists())))throw ValidationException::withMessages(['data'=>'Use an empty standard database queue (or sync/null queue) for this maintenance operation.']);
  $mode=app(\Illuminate\Contracts\Foundation\MaintenanceMode::class);if($mode->active())throw ValidationException::withMessages(['data'=>'The site is already in maintenance.']);
  $activated=false;
  try{$mode->activate(['time'=>time(),'status'=>503,'retry'=>15]);$activated=true;$backup=app(CcsSqliteBackup::class)->create(null,$reason);return $callback($backup);}
  catch(ValidationException $e){throw $e;}
  catch(\Throwable $e){report($e);throw ValidationException::withMessages(['data'=>$e instanceof \RuntimeException?$e->getMessage():'Operation failed. Check the application log. Database writes are transactional.']);}
  finally{if($activated)$mode->deactivate();}
 }
 public function applyImport(Request $r,CcsTestData $test){
  abort_unless($test->available(),403,'Test imports are disabled on this environment.');
  $developer=$this->actor($r);abort_unless($developer->can_clear_data,403);$this->password($r);$r->validate(['confirmation'=>'required|accepted','token'=>'required|string']);$review=$r->session()->get('ccs_test_review');
  if(!$review||!hash_equals($review['token'],$r->token)||$review['until']<time()||$review['developer_id']!==$developer->id||$review['version']!==$developer->access_version)throw ValidationException::withMessages(['data'=>'Import review expired or access changed. Review the pack again.']);
  $result=$this->operation($r,'before_test_import',function($backup)use($test,$review,$developer){$id=$test->import($review['pack'],$developer,$backup['id']);return ['id'=>$id,'backup'=>$backup['id']];});$r->session()->forget('ccs_test_review');return redirect()->route('developer.calendar',['date'=>$review['pack']['date']])->with('success','Test batch imported: '.$review['pack']['batch'].'. Safety backup: '.$result['backup']);
 }
 public function calendar(Request $r,CcsSqliteBackup $backups){$r->validate(['date'=>'nullable|date_format:Y-m-d']);$developer=$this->actor($r);$date=$r->input('date',now('Asia/Colombo')->toDateString());$archives=$developer->can_clear_data?$backups->listing($date):[];$batches=DB::table('ccs_test_imports')->where('data_date',$date)->orderByDesc('created_at')->get();$start=Carbon::parse($date,'Asia/Colombo')->startOfDay()->utc();$end=$start->copy()->addDay();$events=DB::table('ccs_developer_events')->where('created_at','>=',$start)->where('created_at','<',$end)->orderByDesc('id')->limit(100)->get();return view('developer.calendar',compact('developer','date','archives','batches','events'));}
 public function removeBatch(Request $r,string $id,CcsTestData $test){abort_unless($test->available(),403,'Test batch removal is disabled on this environment.');abort_unless($this->actor($r)->can_clear_data,403);$this->password($r);$batch=DB::table('ccs_test_imports')->where('id',$id)->first();abort_unless($batch,404);$r->validate(['confirmation'=>'required|accepted','phrase'=>'required|string']);if($r->phrase!=='REMOVE '.$batch->batch_key)throw ValidationException::withMessages(['phrase'=>'Type REMOVE followed by the exact batch key.']);$this->operation($r,'before_test_batch_removal',function($backup)use($test,$id,$r){$test->remove($id,$this->actor($r),$backup['id']);});return back()->with('success','Only the selected test batch was removed. A safety backup was saved.');}
 public function backup(Request $r){abort_unless($this->actor($r)->can_clear_data,403);$this->password($r);$manifest=$this->operation($r,'developer_manual',function($backup)use($r){$this->event($r,'developer_backup_created',['backup_id'=>$backup['id']]);return $backup;});return redirect()->route('developer.calendar',['date'=>Carbon::parse($manifest['created_at'])->timezone('Asia/Colombo')->toDateString()])->with('success','Full database backup saved: '.$manifest['id']);}
 public function download(Request $r,string $id,CcsSqliteBackup $backups){
  abort_unless($this->actor($r)->can_clear_data,403);$path=$backups->archive($id);$this->event($r,'developer_backup_downloaded',['backup_id'=>$id]);$meta=json_decode(file_get_contents(dirname($path).'/manifest.json'),true);$name='CCS-Backup-'.Carbon::parse($meta['created_at'])->timezone('Asia/Colombo')->format('Y-m-d-His').'-'.$id.'.zip';$size=filesize($path);
  return response()->streamDownload(function()use($path){while(ob_get_level()>0){if(!@ob_end_clean())break;}$handle=fopen($path,'rb');if($handle===false)throw new \RuntimeException('Cannot read backup.');try{fpassthru($handle);}finally{fclose($handle);}},$name,['Content-Type'=>'application/zip','Content-Length'=>(string)$size,'Content-Encoding'=>'identity','Cache-Control'=>'no-store, private','X-Content-Type-Options'=>'nosniff']);
 }
}
