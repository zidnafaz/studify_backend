<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\ClassSchedule;
use App\Policies\ClassSchedulePolicy;

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
        // Force HTTPS in production (for Render deployment)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(ClassSchedule::class, ClassSchedulePolicy::class);
    }
}
