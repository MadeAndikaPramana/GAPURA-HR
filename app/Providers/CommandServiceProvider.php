<?php
// app/Providers/CommandServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * All available console commands for MPGA Training System
     */
    protected $commands = [
        // Existing commands (already in your project)
        \App\Console\Commands\TrainingMaintenanceCommand::class,
        \App\Console\Commands\CheckCertificateExpiry::class,
        \App\Console\Commands\UpdateTrainingStatus::class,

        // New commands (if created from previous artifacts)
        // \App\Console\Commands\SetupMPGASystem::class,
        // \App\Console\Commands\TestComplianceSystem::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register commands only in console environment
        if ($this->app->runningInConsole()) {
            $this->commands($this->getAvailableCommands());
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get available commands (only register existing classes)
     */
    private function getAvailableCommands(): array
    {
        return array_filter($this->commands, function ($command) {
            return class_exists($command);
        });
    }
}
