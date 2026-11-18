<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CcsCareController;
Route::prefix('customer-care')->name('ccs-care.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,\App\Http\Middleware\CcsCareLocalOnly::class])->group(function(){
    Route::get('/',[CcsCareController::class,'page'])->name('page');
    Route::post('/direct',[CcsCareController::class,'direct'])->middleware('throttle:8,1,care-direct')->name('direct');
    Route::post('/otp',[CcsCareController::class,'requestOtp'])->middleware('throttle:8,1,care-otp')->name('otp');
    Route::post('/verify',[CcsCareController::class,'verify'])->middleware('throttle:10,1,care-verify')->name('verify');
    Route::get('/records',[CcsCareController::class,'records'])->middleware('throttle:30,1,care-records')->name('records');
    Route::post('/logout',[CcsCareController::class,'logout'])->name('logout');
});

Route::prefix('ccs/settings/whatsapp')->name('ccs.settings.whatsapp.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
    Route::get('/',[\App\Http\Controllers\CcsWhatsAppSettingsController::class,'index'])->name('index');
    Route::post('/',[\App\Http\Controllers\CcsWhatsAppSettingsController::class,'save'])->middleware('throttle:5,1,whatsapp-settings')->name('save');
    Route::post('/sample',[\App\Http\Controllers\CcsWhatsAppSettingsController::class,'sample'])->middleware('throttle:2,1,whatsapp-sample')->name('sample');
});

Route::prefix('developer/messaging')->name('developer.messaging.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,\App\Http\Middleware\CcsLanguage::class,\App\Http\Middleware\CcsDeveloperAccess::class])->group(function(){
    Route::get('/',[\App\Http\Controllers\CcsCareDeveloperController::class,'index'])->name('index');
    Route::post('/',[\App\Http\Controllers\CcsCareDeveloperController::class,'save'])->middleware('throttle:5,1,care-developer')->name('save');
});

Route::prefix('ccs/messages')->name('ccs.messages.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
    Route::get('/',[\App\Http\Controllers\CcsCareMessagesController::class,'index'])->name('index');
    Route::post('/send',[\App\Http\Controllers\CcsCareMessagesController::class,'send'])->middleware('throttle:3,1,care-manual')->name('send');
    Route::get('/invoice/{id}/pdf',[\App\Http\Controllers\CcsCareMessagesController::class,'pdf'])->whereNumber('id')->middleware('throttle:8,1,care-pdf')->name('pdf');
});
