<?php
use Illuminate\Support\Facades\Route;use App\Http\Controllers\CcsHumanRulesController;
Route::prefix('ccs/pos/rules')->name('ccs.pos.rules.')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class])->group(function(){
Route::get('/',[CcsHumanRulesController::class,'index'])->name('index');
Route::post('/abandoned',[CcsHumanRulesController::class,'abandoned'])->middleware('throttle:20,1')->name('abandoned');
Route::post('/context',[CcsHumanRulesController::class,'context'])->middleware('throttle:60,1')->name('context');
Route::post('/approvals/{id}',[CcsHumanRulesController::class,'decision'])->whereNumber('id')->name('decision');
Route::post('/payments/{id}/reverse',[CcsHumanRulesController::class,'reverse'])->whereNumber('id')->middleware('throttle:10,1')->name('reverse');
Route::post('/invoices/{id}/cancel',[CcsHumanRulesController::class,'cancel'])->whereNumber('id')->middleware('throttle:10,1')->name('cancel');
});

Route::post('ccs/settings/cashier-rules',[CcsHumanRulesController::class,'policy'])->name('ccs.settings.rules')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,'auth:web',\App\Http\Middleware\CcsStaffGuard::class,\App\Http\Middleware\CcsLanguage::class]);
