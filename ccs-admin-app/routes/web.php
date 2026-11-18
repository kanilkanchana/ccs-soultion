
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

require __DIR__.'/ccs_website_content.php';
require __DIR__.'/ccs_facebook_sync.php';
/* v101 public intake route */
if(!\Illuminate\Support\Facades\Route::has('ccs.website.public.requests'))require __DIR__.'/ccs_website.php';
foreach(\Illuminate\Support\Facades\Route::getRoutes() as $route){if(in_array($route->getName(),['ccs.website.public.requests','ccs.website.public.options'],true))$route->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);}
require __DIR__.'/ccs_human_rules.php';
\App\Services\CcsUiRoutes::register(app('router'));
