<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
class CcsCareSandboxServiceProvider extends ServiceProvider {
    public function boot(): void {
        app(\App\Services\CcsMessagingSettings::class)->apply();
        if(!$this->app->runningInConsole())$this->app->terminating(function(){app(\App\Services\CcsCareAutoMessages::class)->afterRequest(request());});
        $this->loadRoutesFrom(base_path('routes/ccs_care_sandbox.php'));
        $this->callAfterResolving(Schedule::class,function(Schedule $schedule){
            if(app(\App\Services\CcsCareEnvironment::class)->allowed() && config('ccs_care_sandbox.enabled')===true && config('ccs_care_sandbox.notifications_enabled')===true) {
                $schedule->command('ccs:care-notifications')->everyMinute()->withoutOverlapping(5);
            }
        });
    }
}
