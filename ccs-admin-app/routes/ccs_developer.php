<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CcsDeveloperController;
Route::prefix('developer')->name('developer.')->middleware(\App\Http\Middleware\CcsLanguage::class)->group(function(){
 Route::get('/login',[CcsDeveloperController::class,'loginForm'])->name('login');
 Route::post('/login',[CcsDeveloperController::class,'login'])->middleware('throttle:5,1,developer-login')->name('login.submit');
 Route::middleware(\App\Http\Middleware\CcsDeveloperAccess::class)->group(function(){
  Route::get('/team',[\App\Http\Controllers\CcsDeveloperToolsController::class,'team'])->name('team');
  Route::post('/team',[\App\Http\Controllers\CcsDeveloperToolsController::class,'saveMember'])->middleware('throttle:5,1,developer-team')->name('team.save');
  Route::get('/test-data',[\App\Http\Controllers\CcsDeveloperToolsController::class,'testData'])->name('test-data');
  Route::post('/test-data/review',[\App\Http\Controllers\CcsDeveloperToolsController::class,'reviewImport'])->middleware('throttle:10,1,developer-import-review')->name('test-data.review');
  Route::post('/test-data/import',[\App\Http\Controllers\CcsDeveloperToolsController::class,'applyImport'])->middleware('throttle:3,1,developer-import')->name('test-data.import');
  Route::get('/calendar',[\App\Http\Controllers\CcsDeveloperToolsController::class,'calendar'])->name('calendar');
  Route::post('/test-data/{id}/remove',[\App\Http\Controllers\CcsDeveloperToolsController::class,'removeBatch'])->middleware('throttle:3,1,developer-test-remove')->name('test-data.remove');
  Route::post('/backups',[\App\Http\Controllers\CcsDeveloperToolsController::class,'backup'])->middleware('throttle:3,1,developer-backup')->name('backup');
  Route::get('/backups/{id}',[\App\Http\Controllers\CcsDeveloperToolsController::class,'download'])->middleware('throttle:6,1,developer-download')->name('backup.download');
  Route::get('/',[CcsDeveloperController::class,'home'])->name('home');
  Route::post('/logout',[CcsDeveloperController::class,'logout'])->name('logout');
  Route::post('/cache',[CcsDeveloperController::class,'clearCache'])->middleware('throttle:3,1,developer-cache')->name('cache');
  Route::post('/cleanup',[CcsDeveloperController::class,'prepare'])->middleware('throttle:4,1,developer-prepare')->name('prepare');
  Route::get('/cleanup/{id}',[CcsDeveloperController::class,'review'])->name('review');
  Route::post('/cleanup/{id}',[CcsDeveloperController::class,'apply'])->middleware('throttle:3,1,developer-apply')->name('apply');
 });
});
