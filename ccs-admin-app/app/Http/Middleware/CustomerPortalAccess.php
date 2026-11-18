<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class CustomerPortalAccess
{
    public function handle(Request $request, Closure $next){
        $guard=Auth::guard('customer_portal');$account=$guard->user();
        $allowed=(string)$request->session()->get('customer_restore_epoch','initial')===app(\App\Services\CcsSqliteBackup::class)->epoch() && $account && $account->active
            && (int)$request->session()->get('customer_portal_version',0)===(int)$account->access_version
            && DB::table('customers')->where('id',$account->customer_id)->whereNull('deleted_at')->exists();
        if(!$allowed){
            $guard->logout();$request->session()->forget('customer_portal_version');
            if($request->expectsJson()||$request->is('customer/api/*'))return response()->json(['message'=>'Please sign in to your customer account.'],401)->header('Cache-Control','no-store, private');
            return redirect()->route('customer.login');
        }
        $request->attributes->set('portal_account',$account);
        $response=$next($request);
        $response->headers->set('Cache-Control','no-store, private');
        $response->headers->set('Vary','Cookie');
        return $response;
    }
}
