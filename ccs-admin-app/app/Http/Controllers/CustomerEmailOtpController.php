<?php
namespace App\Http\Controllers;
use App\Services\CcsCustomerEmailOtp;use App\Models\CustomerPortalAccount;use Illuminate\Http\Request;use Illuminate\Support\Facades\Auth;
class CustomerEmailOtpController extends Controller{
public function send(Request $r,CcsCustomerEmailOtp $otp){$result=$otp->issue($r);return redirect()->route('customer.login')->with('otp_status',$result['message']);}
public function verify(Request $r,CcsCustomerEmailOtp $otp){abort_unless($otp->ready(),503,'Email sign-in is not enabled.');try{$id=$otp->verify($r);}catch(\Illuminate\Validation\ValidationException $e){return redirect()->route('customer.login')->withErrors($e->errors());}$account=CustomerPortalAccount::findOrFail($id);Auth::guard('customer_portal')->login($account,false);$r->session()->regenerate();$r->session()->put('customer_portal_version',$account->access_version);$r->session()->put('customer_restore_epoch',app(\App\Services\CcsSqliteBackup::class)->epoch());$r->session()->forget(['ccs_email_challenge','ccs_email_address']);return redirect()->route('customer.home');}
}
