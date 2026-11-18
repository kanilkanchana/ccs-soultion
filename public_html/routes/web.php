
<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\CcsDatabaseLock::class)->group(function () {
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

// CCS admin routes
require __DIR__.'/ccs_admin.php';

// Laravel authentication routes
require __DIR__.'/auth.php';

require __DIR__.'/ccs_warranty.php';

require __DIR__.'/ccs_billing.php';
require __DIR__.'/ccs_customer_portal.php';
require __DIR__.'/ccs_developer.php';

});

\App\Services\CcsUiRoutes::register(app('router'));
