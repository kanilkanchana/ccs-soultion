<?php

use App\Http\Controllers\CcsWarrantyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', \App\Http\Middleware\CcsStaffGuard::class, \App\Http\Middleware\CcsLanguage::class, 'throttle:60,1'])->prefix('ccs')->name('ccs.')->group(function () {
    Route::get('/warranties/customer-preview',[\App\Http\Controllers\CcsCustomerWarrantyPageController::class,'preview'])->name('warranties.customer-preview');
    Route::get('/warranties', [CcsWarrantyController::class, 'index'])->name('warranties');
    Route::get('/warranties/{id}/print', [CcsWarrantyController::class, 'printCard'])->whereNumber('id')->name('warranties.print');
    Route::post('/warranties/rules', [CcsWarrantyController::class, 'createRule'])->name('warranties.rules.store');
    Route::post('/warranties/rules/{id}/update', [CcsWarrantyController::class, 'updateRule'])->whereNumber('id')->name('warranties.rules.update');
    Route::post('/warranties/rules/{id}/approve', [CcsWarrantyController::class, 'approveRule'])->whereNumber('id')->name('warranties.rules.approve');
    Route::post('/warranties/{id}/free-services', [CcsWarrantyController::class, 'recordService'])->whereNumber('id')->name('warranties.free-services');
    Route::post('/warranties/{id}/approve', [CcsWarrantyController::class, 'approveRecord'])->whereNumber('id')->name('warranties.approve');
    Route::post('/warranties/assign', [CcsWarrantyController::class, 'assign'])->name('warranties.assign');
});
