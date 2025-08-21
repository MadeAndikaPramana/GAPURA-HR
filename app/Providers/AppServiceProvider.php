<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TrainingStatusService;
use App\Services\CertificateService;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TrainingStatusService as singleton
        $this->app->singleton(TrainingStatusService::class, function ($app) {
            return new TrainingStatusService();
        });

        // Register CertificateService as singleton (if needed)
        $this->app->singleton(CertificateService::class, function ($app) {
            return new CertificateService();
        });

        // Register NotificationService as singleton (if needed)
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Boot services if needed
    }
}
