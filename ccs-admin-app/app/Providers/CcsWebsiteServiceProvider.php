<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class CcsWebsiteServiceProvider extends ServiceProvider {
 public function boot(): void {$this->loadRoutesFrom(base_path('routes/ccs_website.php'));}
}
