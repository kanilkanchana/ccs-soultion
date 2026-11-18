<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{CcsDatabaseViewerController,CcsDeveloperDatabaseChangesController as Changes};
class CcsDatabaseViewerServiceProvider extends ServiceProvider {
 public function boot():void {
  Route::prefix('developer/database')->name('developer.database')->middleware(['web',\App\Http\Middleware\CcsDatabaseLock::class,\App\Http\Middleware\CcsDeveloperAccess::class])->group(function(){
   Route::get('/',[CcsDatabaseViewerController::class,'index'])->name('')->middleware('throttle:60,1');
   Route::get('/change',[Changes::class,'form'])->name('.change');
   Route::post('/preview',[Changes::class,'preview'])->middleware('throttle:5,1')->name('.preview');
   Route::post('/apply',[Changes::class,'apply'])->middleware('throttle:3,1')->name('.apply');
   Route::get('/permissions',[Changes::class,'permissions'])->name('.permissions');
   Route::post('/permissions',[Changes::class,'savePermissions'])->middleware('throttle:5,1')->name('.permissions.save');
   Route::get('/history',[Changes::class,'history'])->name('.history');
  });
 }
}
