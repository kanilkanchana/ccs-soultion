<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CustomerPortalAuthController extends Controller
{
    public function form(Request $request){if($request->boolean('reset'))$request->session()->forget(['ccs_email_challenge','ccs_email_address']);return response()->view($request->query('method')==='password'?'customer.login':'customer.email-login',['otpReady'=>app(\App\Services\CcsCustomerEmailOtp::class)->ready()])->header('Cache-Control','no-store, private')->header('Referrer-Policy','no-referrer');}
    public function login(Request $request){
        $data=$request->validate(['username'=>['required','string','max:80'],'password'=>['required','string','max:200']]);
        $guard=Auth::guard('customer_portal');
        if(!$guard->attempt(['username'=>mb_strtolower(trim($data['username'])),'password'=>$data['password'],'active'=>true])){
            throw ValidationException::withMessages(['username'=>'The username or password is incorrect.']);
        }
        $account=$guard->user();
        if(!DB::table('customers')->where('id',$account->customer_id)->whereNull('deleted_at')->exists()){
            $guard->logout();throw ValidationException::withMessages(['username'=>'This customer account is unavailable.']);
        }
        $request->session()->regenerate();
        $request->session()->put('customer_portal_version',$account->access_version);
        $request->session()->put('customer_restore_epoch',app(\App\Services\CcsSqliteBackup::class)->epoch());
        if($request->expectsJson())return response()->json(['data'=>['customer_id'=>$account->customer_id],'message'=>'Signed in.','csrf_token'=>csrf_token()])->header('Cache-Control','no-store, private');
        return redirect()->route('customer.home');
    }
    public function logout(Request $request){
        Auth::guard('customer_portal')->logout();$request->session()->forget('customer_portal_version');
        $request->session()->migrate(true);$request->session()->regenerateToken();
        if($request->expectsJson())return response()->json(['message'=>'Signed out.'])->header('Cache-Control','no-store, private');
        return redirect()->route('customer.login');
    }
}
