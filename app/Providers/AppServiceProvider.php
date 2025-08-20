<?php

// ========================================================================
// app/Providers/AppServiceProvider.php - Register TrainingStatusService
// ========================================================================

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TrainingStatusService;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Boot services if needed
    }
}
