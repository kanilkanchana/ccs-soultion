<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerPortalAuthController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CcsCustomerAccessController;
use App\Http\Middleware\CustomerPortalAccess;

Route::prefix('customer')->name('customer.')->middleware(\App\Http\Middleware\CcsLanguage::class)->group(function(){
    Route::get('/demo',function(){return response()->view('customer.demo')->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow');})->middleware('throttle:60,1')->name('demo');
    Route::get('/login',[CustomerPortalAuthController::class,'form'])->name('login');
    Route::post('/email/request',[\App\Http\Controllers\CustomerEmailOtpController::class,'send'])->middleware('throttle:10,1')->name('email.request');
    Route::post('/email/verify',[\App\Http\Controllers\CustomerEmailOtpController::class,'verify'])->middleware('throttle:12,1')->name('email.verify');
    Route::get('/session',function(){return response()->json(['csrf_token'=>csrf_token()])->header('Cache-Control','no-store, private');})->middleware('throttle:30,1')->name('session');
    Route::post('/login',[CustomerPortalAuthController::class,'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::post('/logout',[CustomerPortalAuthController::class,'logout'])->middleware('throttle:30,1')->name('logout');
    Route::middleware([CustomerPortalAccess::class,'throttle:60,1'])->group(function(){
        Route::get('/warranties',[\App\Http\Controllers\CcsCustomerWarrantyPageController::class,'customer'])->name('warranty-page');
        Route::get('/',[CustomerPortalController::class,'home'])->name('home');
        Route::prefix('api')->name('api.')->group(function(){
            Route::get('/serial-lookup',[\App\Http\Controllers\CcsSerialLookupController::class,'customer'])->name('serial-lookup');
            Route::get('/me',[CustomerPortalController::class,'me'])->name('me');
            Route::get('/vehicles',[CustomerPortalController::class,'vehicles'])->name('vehicles');
            Route::get('/search',[CustomerPortalController::class,'search'])->name('search');
            Route::get('/vehicles/{id}/history',[CustomerPortalController::class,'history'])->whereNumber('id')->name('history');
            Route::get('/services/{id}',[CustomerPortalController::class,'service'])->whereNumber('id')->name('service');
            Route::get('/vehicles/{id}/warranties',[CustomerPortalController::class,'warranties'])->whereNumber('id')->name('warranties');
        });
    });
});
Route::prefix('ccs/customer-access')->name('ccs.customer-access.')->middleware(['auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class,'throttle:30,1'])->group(function(){
    Route::get('/serial-preview',[\App\Http\Controllers\CcsSerialLookupController::class,'page'])->name('serial-preview');
    Route::get('/serial-preview/data',[\App\Http\Controllers\CcsSerialLookupController::class,'preview'])->name('serial-preview.data');
    Route::get('/serial-preview/find-customers',[\App\Http\Controllers\CcsSerialLookupController::class,'findCustomers'])->name('serial-preview.find-customers');
    Route::get('/serial-preview/customer-search',[\App\Http\Controllers\CcsSerialLookupController::class,'searchCustomers'])->name('serial-preview.customer-search');
    Route::get('/serial-preview/customer-serials',[\App\Http\Controllers\CcsSerialLookupController::class,'customerSerials'])->name('serial-preview.customer-serials');
    Route::get('/',[CcsCustomerAccessController::class,'index'])->name('index');
    Route::post('/',[CcsCustomerAccessController::class,'save'])->name('save');
    Route::post('/{id}/revoke',[CcsCustomerAccessController::class,'revoke'])->whereNumber('id')->name('revoke');
});
