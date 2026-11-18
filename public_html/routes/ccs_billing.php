<?php

use App\Http\Controllers\CcsBillingController;
use Illuminate\Support\Facades\Route;

// Loaded by routes/web.php, not from inside an existing route group.
Route::middleware(['web', 'auth', \App\Http\Middleware\CcsStaffGuard::class, \App\Http\Middleware\CcsLanguage::class, 'throttle:30,1'])->prefix('ccs')->name('ccs.')->group(function () {
    Route::post('invoices/{id}/finalize', [CcsBillingController::class, 'issue'])
        ->whereNumber('id')->name('invoices.finalize');
    Route::post('invoices/{id}/payments', [CcsBillingController::class, 'payment'])
        ->whereNumber('id')->name('invoices.payments.store');
});
