<?php
use Illuminate\Support\Facades\Route;use App\Http\Controllers\CcsFacebookSyncController;
Route::prefix('ccs/website-content/facebook')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
 Route::post('/connection',[CcsFacebookSyncController::class,'configure'])->middleware('throttle:5,1,facebook-connection')->name('ccs.website.facebook.connection');
 Route::post('/settings',[CcsFacebookSyncController::class,'settings'])->middleware('throttle:10,1,facebook-settings')->name('ccs.website.facebook.settings');
 Route::post('/sync',[CcsFacebookSyncController::class,'run'])->middleware('throttle:2,1,facebook-sync')->name('ccs.website.facebook.sync');
});
