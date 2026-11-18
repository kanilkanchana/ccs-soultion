<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class CcsDeveloperAccess {
 public function handle(Request $request,Closure $next){
  $guard=Auth::guard('ccs_developer');$account=$guard->user();if($account)$account=\App\Models\CcsDeveloperAccount::find($account->id);$epoch=app(\App\Services\CcsSqliteBackup::class)->epoch();$last=(int)$request->session()->get('ccs_dev_last',0);
  if(!$account||!$account->active||(int)$request->session()->get('ccs_dev_version',0)!==$account->access_version||$request->session()->get('ccs_dev_password')!==hash('sha256',$account->password)||$request->session()->get('ccs_dev_epoch')!==$epoch||!$last||time()-$last>900){
   $guard->forgetUser();$request->session()->forget(['ccs_dev_version','ccs_dev_password','ccs_dev_epoch','ccs_dev_last']);$request->session()->forget($guard->getName());return redirect()->route('developer.login')->withErrors(['email'=>'Developer session ended. Please sign in again.']);
  }
  $request->session()->put('ccs_dev_last',time());$request->attributes->set('ccs_developer',$account);$response=$next($request);$response->headers->set('Cache-Control','no-store, private');$response->headers->set('X-Frame-Options','SAMEORIGIN');$response->headers->set('X-Content-Type-Options','nosniff');$response->headers->set('X-Robots-Tag','noindex, nofollow');return $response;
 }
}
