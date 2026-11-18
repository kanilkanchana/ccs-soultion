<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;
class CcsUiRoute {
 public function handle(Request $request,Closure $next){
  $route=$request->route();if($route&&($canonical=$route->getAction('ccs_canonical'))){$action=$route->getAction();$action['as']=$canonical;$route->setAction($action);}
  return $next($request);
 }
}
