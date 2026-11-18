<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CcsCareLocalOnly {
    public function handle(Request $r,Closure $next) {
        abort_unless(app()->environment('local') && config('ccs_care_sandbox.enabled')===true && in_array($r->ip(),['127.0.0.1','::1'],true),404);
        $response=$next($r);
        $response->headers->set('Cache-Control','no-store, private');
        $response->headers->set('X-Robots-Tag','noindex, nofollow');
        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('Referrer-Policy','no-referrer');
        return $response;
    }
}
