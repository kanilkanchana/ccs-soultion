<?php
namespace App\Services;
class CcsUiRoutes {
 public static function register($router): void {
  $collection=$router->getRoutes();$originals=iterator_to_array($collection);
  foreach($originals as $route){$name=$route->getName();$uri=$route->uri();
   if(!$name||!str_starts_with($name,'ccs.')||!in_array(\App\Http\Middleware\CcsStaffGuard::class,$route->middleware(),true))continue;
   if($uri!=='ccs'&&!str_starts_with($uri,'ccs/'))continue;
   $action=$route->getAction();$action['as']='t.'.substr($name,4);$action['ccs_canonical']=$name;$action['middleware']=array_values(array_unique(array_merge([\App\Http\Middleware\CcsUiRoute::class],$route->middleware())));
   $alias=clone $route;$alias->setUri($uri==='ccs'?'t/dashboard':'t/'.substr($uri,4));$alias->setAction($action);$alias->setRouter($router);$collection->add($alias);
  }$collection->refreshNameLookups();$collection->refreshActionLookups();
 }
 public static function formatPath(string $path,$route,bool $touch): string {
  if(!$touch||!$route||!str_starts_with($route->getName()??'','ccs.'))return $path;
  if(!app('router')->has('t.'.substr($route->getName(),4)))return $path;
  if($path==='/ccs')return '/t/dashboard';
  return str_starts_with($path,'/ccs/')?'/t/'.substr($path,5):$path;
 }
}
