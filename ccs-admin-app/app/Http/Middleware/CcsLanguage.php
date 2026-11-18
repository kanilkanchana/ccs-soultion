<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CcsLanguage {
    public function handle(Request $request, Closure $next) {
        $locale=$request->session()->get('ccs_language',$request->cookie('ccs_language','en'));
        app()->setLocale(in_array($locale,array_keys(config('ccs_admin.locales')),true)?$locale:'en');
        return $next($request);
    }
}
