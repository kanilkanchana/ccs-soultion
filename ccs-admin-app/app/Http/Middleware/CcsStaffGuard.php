<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CcsAdministration;
class CcsStaffGuard {
 public function handle(Request $request,Closure $next){
  $user=$request->user('web');if(!$user)return $next($request);
  $admin=app(CcsAdministration::class);$version=(int)($user->ccs_session_version??1);
  $fingerprint=hash('sha256',$user->getAuthPassword());
  $epoch=app(\App\Services\CcsSqliteBackup::class)->epoch();$restoreChanged=(string)$request->session()->get('ccs_restore_epoch','initial')!==$epoch;
  $passwordChanged=$request->session()->has('ccs_password_fingerprint')&&!hash_equals((string)$request->session()->get('ccs_password_fingerprint'),$fingerprint);
  $expired=$request->session()->has('ccs_staff_version')&&(int)$request->session()->get('ccs_staff_version')!==$version;
  $minutes=(int)$admin->setting('idle_minutes',60);$last=$request->session()->get('ccs_last_activity');
  if(!$user->ccs_active||$expired||$passwordChanged||$restoreChanged||($last&&time()-(int)$last>$minutes*60)){
   $admin->audit($user->id,'staff_session_ended','user',$user->id);Auth::guard('web')->logout();$request->session()->invalidate();$request->session()->regenerateToken();return $request->expectsJson()?response()->json(['message'=>'Session ended. Please sign in again.'],401):redirect()->route('ccs.login')->withErrors(['email'=>'Session ended. Please sign in again.']);
  }
  $request->session()->put('ccs_restore_epoch',$epoch);$request->session()->put('ccs_password_fingerprint',$fingerprint);$request->session()->put('ccs_staff_version',$version);if(!$request->is('ccs/notifications/count','t/notifications/count'))$request->session()->put('ccs_last_activity',time());
  if(!$admin->can($user,$request->route()?->getName()??'', $request->method())){$admin->audit($user->id,'staff_access_denied','route',null,['route'=>$request->route()?->getName(),'method'=>$request->method()]);abort(403,'Your role cannot access this action.');}
  // mobile routing v95 — no preference/database changes, no redirect for JSON or POST.
if($request->isMethod('GET')&&!$request->expectsJson()&&$request->is('ccs','ccs/*')&&preg_match('/Android|iPhone|iPad|iPod|Mobile|Tablet/i',$request->userAgent()??'')){
 $name=$request->route()?->getName()??'';$alias='t.'.substr($name,4);
 if(str_starts_with($name,'ccs.')&&app('router')->has($alias))return redirect()->route($alias,array_merge($request->query(),$request->route()->parameters()));
}
$response=app(\App\Http\Middleware\CcsHumanRulesMiddleware::class)->handle($request,$next);$response->headers->set('Cache-Control','no-store, private');$response->headers->set('X-Content-Type-Options','nosniff');$response->headers->set('X-Frame-Options','SAMEORIGIN');$response->headers->set('X-Robots-Tag','noindex, nofollow');return $response;
 }
}
