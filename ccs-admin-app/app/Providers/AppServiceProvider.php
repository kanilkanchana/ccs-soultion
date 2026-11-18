<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['auth.guards.customer_portal'=>['driver'=>'session','provider'=>'ccs_customers'],
            'auth.providers.ccs_customers'=>['driver'=>'eloquent','model'=>\App\Models\CustomerPortalAccount::class]]);
        config(['auth.guards.ccs_developer'=>['driver'=>'session','provider'=>'ccs_developers'],'auth.providers.ccs_developers'=>['driver'=>'eloquent','model'=>\App\Models\CcsDeveloperAccount::class]]);
        $this->configureDefaults();
        app('url')->formatPathUsing(fn($path,$route)=>\App\Services\CcsUiRoutes::formatPath($path,$route,request()->is('t','t/*')));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
