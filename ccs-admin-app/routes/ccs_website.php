<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{CcsWebsitePublicController,CcsWebsiteInboxController,CcsWebsiteSettingsController};
Route::prefix('website-api')->name('ccs.website.public.')->middleware([\App\Http\Middleware\CcsWebsitePublic::class,\App\Http\Middleware\CcsDatabaseLock::class,'throttle:30,1,website-public'])->group(function(){
 Route::options('/{path?}',fn()=>response('',204))->where('path','.*')->name('options');
 Route::get('/config',[CcsWebsitePublicController::class,'config'])->name('config');
 Route::post('/requests',[CcsWebsitePublicController::class,'submit'])->middleware('throttle:6,1,website-intake')->name('requests');
 Route::post('/records',[CcsWebsitePublicController::class,'lookup'])->middleware('throttle:8,1,website-lookup')->name('records');
});
Route::prefix('ccs/website-requests')->name('ccs.website.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
 Route::get('/',[CcsWebsiteInboxController::class,'index'])->name('index');
 Route::get('/{id}',[CcsWebsiteInboxController::class,'show'])->whereNumber('id')->name('show');
 Route::post('/{id}',[CcsWebsiteInboxController::class,'update'])->whereNumber('id')->middleware('throttle:20,1,website-review')->name('update');
 Route::post('/{id}/job',[CcsWebsiteInboxController::class,'convert'])->whereNumber('id')->middleware('throttle:5,1,website-job')->name('convert');
});
Route::prefix('ccs/settings/website')->name('ccs.settings.website.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
 Route::get('/',[CcsWebsiteSettingsController::class,'index'])->name('index');
 Route::post('/',[CcsWebsiteSettingsController::class,'save'])->middleware('throttle:5,1,website-settings')->name('save');
});
Route::prefix('developer/website')->name('developer.website.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,\App\Http\Middleware\CcsDeveloperAccess::class])->group(function(){
 Route::get('/',[CcsWebsiteSettingsController::class,'developer'])->name('index');
 Route::post('/',[CcsWebsiteSettingsController::class,'developerSave'])->middleware('throttle:5,1,website-developer')->name('save');
});
