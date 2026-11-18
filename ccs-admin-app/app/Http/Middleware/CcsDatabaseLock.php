<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CcsDatabaseLock {
 public function handle(Request $request,Closure $next){$path=app(\App\Services\CcsSqliteBackup::class)->root().'/database.lock';$lock=fopen($path,'c');if(!$lock||!flock($lock,LOCK_SH|LOCK_NB)){if($lock)fclose($lock);abort(503,'Database maintenance is in progress. Please retry shortly.');}$request->attributes->set('ccs_database_lock',$lock);try{return $next($request);}finally{flock($lock,LOCK_UN);fclose($lock);}}
}
