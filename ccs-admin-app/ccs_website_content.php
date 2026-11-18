<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CcsWebsiteContentController;
Route::get('/website-content.js',[CcsWebsiteContentController::class,'feed'])->name('ccs.website.content.public');
Route::prefix('ccs/website-content')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
 Route::get('/',[CcsWebsiteContentController::class,'index'])->name('ccs.website.content');
 Route::post('/',[CcsWebsiteContentController::class,'save'])->middleware('throttle:30,1,website-content-save')->name('ccs.website.content.save');
 Route::post('/{id}/status',[CcsWebsiteContentController::class,'toggle'])->whereNumber('id')->middleware('throttle:30,1,website-content-status')->name('ccs.website.content.status');
});
