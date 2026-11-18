<?php
namespace App\Services;
use Illuminate\Http\Request;
class CcsWarrantyApproval
{
    public function allowed(Request $request): bool
    {
        $user=$request->user('web');
        return $user && (app(CcsAdministration::class)->owner($user) || in_array(strtolower((string)$user->email),config('ccs_warranty.approver_emails',[]),true));
    }
    public function requireApprover(Request $request): void
    {
        abort_unless($this->allowed($request),403,'This staff account cannot approve warranties.');
    }
}
