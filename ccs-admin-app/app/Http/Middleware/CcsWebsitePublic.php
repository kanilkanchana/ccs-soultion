<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\CcsWebsiteSettings;
class CcsWebsitePublic {
 public function handle(Request $r,Closure $next){
  $settings=app(CcsWebsiteSettings::class);$s=$settings->values();
  abort_unless($settings->ready()&&$s['enabled']===true,503,'Website connection is not enabled yet. Contact the workshop directly.');
  $origin=$r->header('Origin');$self=$r->getSchemeAndHttpHost();
  if($origin===null){abort_unless($r->isMethod('GET')||$r->isMethod('HEAD')||in_array($self,$s['origins'],true),403,'Origin is not allowed.');}
  else abort_unless(in_array($origin,$s['origins'],true),403,'Website origin is not allowed.');
  abort_if((int)$r->header('Content-Length',0)>20000,413,'Request is too large.');
  $r->headers->set('Accept','application/json');
  try{if($r->isMethod('OPTIONS'))$response=response('',204);else $response=$next($r);}
  catch(\Illuminate\Validation\ValidationException $e){$response=response()->json(['message'=>'Check the request details.','errors'=>$e->errors()],422);}
  catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){$response=response()->json(['message'=>$e->getMessage()?:'Request could not be completed.'],$e->getStatusCode(),$e->getHeaders());}
  catch(\Throwable){$response=response()->json(['message'=>'Online requests are temporarily unavailable. Please contact the workshop directly.'],503);}
  if($origin!==null){$response->headers->set('Access-Control-Allow-Origin',$origin);$response->headers->set('Vary','Origin');$response->headers->set('Access-Control-Allow-Methods','GET, POST, OPTIONS');$response->headers->set('Access-Control-Allow-Headers','Content-Type, Accept');}
  $response->headers->set('Cache-Control','no-store, private');$response->headers->set('X-Content-Type-Options','nosniff');$response->headers->set('Referrer-Policy','no-referrer');return $response;
 }
}
