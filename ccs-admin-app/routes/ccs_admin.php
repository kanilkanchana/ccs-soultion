<?php

use App\Http\Controllers\CcsAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/ccs/login', [CcsAdminController::class, 'loginForm'])->middleware(\App\Http\Middleware\CcsLanguage::class)->name('ccs.login');
Route::get('/ccs/setup', [CcsAdminController::class, 'setup'])->middleware(\App\Http\Middleware\CcsLanguage::class)->name('ccs.setup');
Route::post('/ccs/setup/language', [CcsAdminController::class, 'setupLanguage'])->middleware('throttle:30,1')->name('ccs.setup.language');
Route::post('/ccs/setup/complete', [CcsAdminController::class, 'setupComplete'])->middleware('throttle:30,1')->name('ccs.setup.complete');
Route::post('/ccs/login', [CcsAdminController::class, 'login'])->middleware('throttle:8,1')->name('ccs.login.submit');
Route::post('/ccs/logout', [CcsAdminController::class, 'logout'])->middleware('auth')->name('ccs.logout');

Route::prefix('ccs')->name('ccs.')->middleware(['auth', \App\Http\Middleware\CcsStaffGuard::class, \App\Http\Middleware\CcsLanguage::class])->group(function () {
    Route::get('/', [CcsAdminController::class, 'dashboard'])->name('dashboard');
    
    Route::post('/settings/ui-mode', [\App\Http\Controllers\CcsSettingsController::class, 'uiMode'])->name('settings.ui-mode');
    Route::get('/settings', [\App\Http\Controllers\CcsSettingsController::class, 'index'])->name('settings');
    Route::post('/settings/language', [\App\Http\Controllers\CcsSettingsController::class, 'language'])->name('settings.language');

    Route::get('/settings/backups',[\App\Http\Controllers\CcsBackupController::class,'index'])->name('settings.backups');
    Route::post('/settings/backups/reports',[\App\Http\Controllers\CcsBackupController::class,'reports'])->middleware('throttle:4,1')->name('settings.backups.reports');
    Route::get('/settings/backups/reports/{id}',[\App\Http\Controllers\CcsBackupController::class,'reportDownload'])->name('settings.backups.reports-download');
    Route::post('/settings/backups/create',[\App\Http\Controllers\CcsBackupController::class,'create'])->middleware('throttle:6,1')->name('settings.backups.create');
    Route::get('/settings/backups/{id}/download',[\App\Http\Controllers\CcsBackupController::class,'download'])->name('settings.backups.download');
    Route::post('/settings/backups/prepare',[\App\Http\Controllers\CcsBackupController::class,'stage'])->middleware('throttle:5,1')->name('settings.backups.stage');
    Route::get('/settings/backups/{id}/review',[\App\Http\Controllers\CcsBackupController::class,'review'])->name('settings.backups.review');
    Route::post('/settings/backups/{id}/restore',[\App\Http\Controllers\CcsBackupController::class,'apply'])->middleware('throttle:3,1')->name('settings.backups.apply');
    Route::get('/settings/users',[\App\Http\Controllers\CcsSettingsController::class,'users'])->name('settings.users');
    Route::post('/settings/users',[\App\Http\Controllers\CcsSettingsController::class,'saveUser'])->name('settings.user-save');
    Route::post('/settings/security',[\App\Http\Controllers\CcsSettingsController::class,'security'])->name('settings.security');
    Route::post('/settings/input-method',[\App\Http\Controllers\CcsSettingsController::class,'inputMethod'])->name('settings.input-method');
    Route::post('/settings/preferences',[\App\Http\Controllers\CcsSettingsController::class,'preferences'])->name('settings.preferences');
    Route::post('/settings/theme',[\App\Http\Controllers\CcsSettingsController::class,'theme'])->name('settings.theme');
    Route::get('/settings/audit',[\App\Http\Controllers\CcsSettingsController::class,'audit'])->name('settings.audit');
    Route::get('/notifications',[\App\Http\Controllers\CcsNotificationController::class,'index'])->name('notifications.index');
    Route::get('/notifications/count',[\App\Http\Controllers\CcsNotificationController::class,'count'])->name('notifications.count');
    Route::post('/notifications/read',[\App\Http\Controllers\CcsNotificationController::class,'read'])->name('notifications.read');
    Route::get('/claims',[\App\Http\Controllers\CcsClaimController::class,'index'])->name('claims.index');
    Route::post('/claims',[\App\Http\Controllers\CcsClaimController::class,'store'])->name('claims.store');
    Route::post('/claims/{id}/findings',[\App\Http\Controllers\CcsClaimController::class,'update'])->whereNumber('id')->name('claims.update');
    Route::post('/claims/{id}/decision',[\App\Http\Controllers\CcsClaimController::class,'decision'])->whereNumber('id')->name('claims.decision');
    Route::post('/claims/{id}/job',[\App\Http\Controllers\CcsClaimController::class,'job'])->whereNumber('id')->name('claims.job');
    Route::get('/stock/{id}/labels',[\App\Http\Controllers\CcsLabelController::class,'index'])->whereNumber('id')->name('stock.labels');
    Route::get('/stock/{id}/labels/print',[\App\Http\Controllers\CcsLabelController::class,'print'])->whereNumber('id')->name('stock.labels.print');
    Route::post('/invoices/{id}/print-settings',[\App\Http\Controllers\CcsSettingsController::class,'invoicePrint'])->whereNumber('id')->name('invoices.print-settings');
    Route::get('/invoices/{id}/print',[\App\Http\Controllers\CcsSettingsController::class,'printView'])->whereNumber('id')->name('invoices.print');

    Route::get('/vehicles/search', [\App\Http\Controllers\CcsVehicleSearchController::class, 'index'])->name('vehicles.search');

    // Customers & Vehicles
    Route::get('/customers', [\App\Http\Controllers\CcsDirectoryController::class, 'customers'])->name('customers');
    Route::post('/customers', [CcsAdminController::class, 'saveCustomer'])->name('customers.save');
    Route::get('/serial-records/customer-search',[\App\Http\Controllers\CcsCustomerSerialRecordsController::class,'searchCustomers'])->name('serial-records.customer-search');
    Route::get('/customers/{id}/serial-records',[\App\Http\Controllers\CcsCustomerSerialRecordsController::class,'index'])->whereNumber('id')->name('customers.serial-records');
    Route::get('/customers/{id}', [CcsAdminController::class, 'customerShow'])->whereNumber('id')->name('customers.show');
    Route::put('/customers/{id}', [CcsAdminController::class, 'updateCustomer'])->whereNumber('id')->name('customers.update');
    Route::get('/vehicles', [\App\Http\Controllers\CcsDirectoryController::class, 'vehicles'])->name('vehicles.index');
    Route::post('/vehicles', [CcsAdminController::class, 'saveVehicle'])->name('vehicles.save');
    
    // Repair Jobs
    Route::get('/jobs', [CcsAdminController::class, 'jobs'])->name('jobs');
    Route::post('/jobs', [CcsAdminController::class, 'saveJob'])->name('jobs.save');
    Route::get('/jobs/{id}/card', [CcsAdminController::class, 'jobCard'])->whereNumber('id')->name('jobs.card');
    Route::post('/jobs/{id}/card', [CcsAdminController::class, 'updateJobDetails'])->whereNumber('id')->name('jobs.card.update');
    Route::post('/jobs/{id}/status', [CcsAdminController::class, 'updateJob'])->whereNumber('id')->name('jobs.status');
    
    Route::get('/jobs/{id}/battery', [\App\Http\Controllers\CcsBatteryController::class, 'show'])->whereNumber('id')->name('jobs.battery');
    Route::post('/jobs/{id}/battery', [\App\Http\Controllers\CcsBatteryController::class, 'store'])->whereNumber('id')->name('jobs.battery.save');

    Route::post('/invoices/{id}/complete-serials', [\App\Http\Controllers\CcsBillingController::class, 'completeSerials'])->whereNumber('id')->name('invoices.complete-serials');
    Route::get('/stock', [\App\Http\Controllers\CcsStockController::class, 'index'])->name('stock');
    Route::post('/stock', [\App\Http\Controllers\CcsStockController::class, 'create'])->name('stock.create');
    Route::post('/stock/trial', [\App\Http\Controllers\CcsStockController::class, 'toggle'])->name('stock.trial');
    Route::get('/stock/module-reading', [\App\Http\Controllers\CcsStockController::class, 'moduleReading'])->name('stock.module-reading');
    Route::get('/stock/catalog', [\App\Http\Controllers\CcsStockController::class, 'catalog'])->name('stock.catalog');
    Route::get('/warranties/customer-jobs',[\App\Http\Controllers\CcsWarrantyController::class,'customerJobs'])->name('warranties.customer-jobs');
    Route::post('/stock/{id}/units/{unitId}/update',[\App\Http\Controllers\CcsStockController::class,'updateUnit'])->whereNumber(['id','unitId'])->name('stock.unit-update');
    Route::post('/stock/{id}/units/{unitId}/restore',[\App\Http\Controllers\CcsStockController::class,'restoreUnit'])->whereNumber(['id','unitId'])->name('stock.unit-restore');
    Route::post('/stock/{id}/units/{unitId}/remove',[\App\Http\Controllers\CcsStockController::class,'removeUnit'])->whereNumber(['id','unitId'])->name('stock.unit-remove');
    Route::post('/stock/{id}/update', [\App\Http\Controllers\CcsStockController::class, 'update'])->whereNumber('id')->name('stock.update');
    Route::post('/stock/{id}/receive', [\App\Http\Controllers\CcsStockController::class, 'receive'])->whereNumber('id')->name('stock.receive');
    Route::post('/invoices/{id}/cancel-stock-draft', [\App\Http\Controllers\CcsStockController::class, 'cancelDraft'])->whereNumber('id')->name('stock.cancel-draft');

    // POS & Invoices
    Route::get('/pos/jobs/search', [CcsAdminController::class, 'posJobSearch'])->name('pos.jobs.search');
    Route::get('/payments', [\App\Http\Controllers\CcsPaymentOverviewController::class, 'index'])->name('payments.index');
    Route::get('/pos', [CcsAdminController::class, 'pos'])->name('pos');
    Route::post('/pos', [CcsAdminController::class, 'createInvoice'])->name('pos.save');
    Route::get('/invoices/{id}', [CcsAdminController::class, 'invoice'])->whereNumber('id')->name('invoices.show');

    Route::post('/invoice-items/{id}/details', [CcsAdminController::class, 'updateInvoiceItemDetails'])->whereNumber('id')->name('invoice-items.details');

    // Customer credit report (existing URL retained)
    Route::get('/purchases', [CcsAdminController::class, 'purchases'])->name('purchases');

    // Credit Ledger Management & NIC Search
    Route::get('/credit-ledger', [CcsAdminController::class, 'creditLedgerView'])->name('credit.ledger');
    Route::get('/api/search-nic', [CcsAdminController::class, 'searchCustomerByNic'])->name('api.search.nic');
});
