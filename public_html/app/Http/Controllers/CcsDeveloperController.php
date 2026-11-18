<?php
namespace App\Http\Controllers;
use App\Models\CcsDeveloperAccount;
use App\Services\CcsDeveloperReset;
use App\Services\CcsSqliteBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class CcsDeveloperController extends Controller {
 private function event(?int $id,string $action,array $details=[]): void {DB::table('ccs_developer_events')->insert(['developer_account_id'=>$id,'action'=>$action,'details'=>json_encode($details),'created_at'=>now(),'updated_at'=>now()]);}
 public function loginForm(){return response()->view('developer.login')->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow')->header('X-Frame-Options','SAMEORIGIN')->header('X-Content-Type-Options','nosniff');}
 public function login(Request $request){$data=$request->validate(['email'=>['required','email','max:255'],'password'=>['required','string','max:128']]);$guard=Auth::guard('ccs_developer');if(!$guard->attempt(['email'=>mb_strtolower(trim($data['email'])),'password'=>$data['password'],'active'=>true],false))throw ValidationException::withMessages(['email'=>'Developer email or password is incorrect, or access is disabled.']);$account=$guard->user();$request->session()->regenerate();$request->session()->put(['ccs_dev_version'=>$account->access_version,'ccs_dev_password'=>hash('sha256',$account->password),'ccs_dev_epoch'=>app(CcsSqliteBackup::class)->epoch(),'ccs_dev_last'=>time()]);$this->event($account->id,'developer_signed_in');return redirect()->route('developer.home');}
 public function logout(Request $request){$guard=Auth::guard('ccs_developer');$guard->forgetUser();$request->session()->forget([$guard->getName(),'ccs_dev_version','ccs_dev_password','ccs_dev_epoch','ccs_dev_last']);$request->session()->migrate(true);$request->session()->regenerateToken();return redirect()->route('developer.login');}
 public function home(Request $request){$developer=$request->attributes->get('ccs_developer');$events=DB::table('ccs_developer_events')->orderByDesc('id')->limit(30)->get();$database=DB::connection()->getConfig('database');return view('developer.home',compact('developer','events','database'));}
 public function prepare(Request $request,CcsDeveloperReset $reset){$developer=$request->attributes->get('ccs_developer');abort_unless($developer->can_clear_data,403);$data=$request->validate(['scope'=>['required','in:records,full_business']]);try{$plan=$reset->prepare($developer,$data['scope']);return redirect()->route('developer.review',$plan['id']);}catch(\Throwable $e){report($e);throw ValidationException::withMessages(['cleanup'=>$e instanceof \RuntimeException?$e->getMessage():'Could not prepare cleanup. No business data changed.']);}}
 public function review(Request $request,string $id,CcsDeveloperReset $reset){try{$plan=$reset->plan($id);}catch(\Throwable $e){abort(404,'Cleanup review not found.');}$developer=$request->attributes->get('ccs_developer');abort_unless($plan['developer_id']===$developer->id&&$developer->can_clear_data,403);return view('developer.review',compact('plan','developer'));}
 public function apply(Request $request,string $id,CcsDeveloperReset $reset){
  $developer=$request->attributes->get('ccs_developer');abort_unless($developer->can_clear_data,403);$data=$request->validate(['password'=>['required','string'],'confirmation'=>['required','accepted'],'phrase'=>['required','string']]);
  if(!Hash::check($data['password'],$developer->password))throw ValidationException::withMessages(['password'=>'Developer password is incorrect.']);
  if($data['phrase']!=='CLEAR '.strtoupper(substr($id,0,8)))throw ValidationException::withMessages(['phrase'=>'Type the exact cleanup phrase shown on this review.']);
  $lock=$request->attributes->get('ccs_database_lock');if(!is_resource($lock)||!flock($lock,LOCK_EX|LOCK_NB))throw ValidationException::withMessages(['cleanup'=>'Another system request is active. Wait a few seconds and retry.']);
  $maintenance=app(\Illuminate\Contracts\Foundation\MaintenanceMode::class);if($maintenance->active())throw ValidationException::withMessages(['cleanup'=>'The system is already in maintenance.']);$completed=false;@set_time_limit(0);ignore_user_abort(true);
  try{$maintenance->activate(['time'=>time(),'status'=>503,'retry'=>15,'refresh'=>15]);$result=$reset->apply($id,$developer,$lock);$completed=true;Auth::guard('ccs_developer')->forgetUser();Auth::guard('web')->forgetUser();$request->session()->invalidate();$request->session()->regenerateToken();\Illuminate\Support\Facades\Artisan::call('optimize:clear');$maintenance->deactivate();return response()->view('developer.result',['result'=>$result,'failed'=>false])->header('Cache-Control','no-store, private');}
  catch(\Throwable $e){report($e);if(!$completed&&$e->getCode()!==9001){$maintenance->deactivate();throw ValidationException::withMessages(['cleanup'=>$e instanceof \RuntimeException?$e->getMessage():'Cleanup did not finish. Business changes were rolled back.']);}return response()->view('developer.result',['result'=>null,'failed'=>true],503)->header('Cache-Control','no-store, private');}
 }
 public function clearCache(Request $request){\Illuminate\Support\Facades\Artisan::call('optimize:clear');$this->event($request->attributes->get('ccs_developer')->id,'application_cache_cleared');return back()->with('success','Application caches cleared. Business records were not changed.');}
}
