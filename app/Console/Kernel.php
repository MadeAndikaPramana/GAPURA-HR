<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Update training statuses daily at 6:00 AM
        $schedule->command('training:update-status')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/training-status-daily.log'));

        // Send notifications weekly on Monday at 8:00 AM
        $schedule->command('training:update-status --notify')
                 ->weeklyOn(1, '08:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/training-notifications.log'));

        // Generate comprehensive monthly report on the 1st of each month at 9:00 AM
        $schedule->command('training:update-status --report --notify')
                 ->monthlyOn(1, '09:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/training-monthly-report.log'));

        // Quick status check every hour (no notifications, just status updates)
        $schedule->command('training:update-status')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/training-status-hourly.log'));

        // Weekly cleanup of old logs (keep last 30 days)
        $schedule->call(function () {
            $logFiles = [
                storage_path('logs/training-status-daily.log'),
                storage_path('logs/training-notifications.log'),
                storage_path('logs/training-monthly-report.log'),
                storage_path('logs/training-status-hourly.log'),
            ];

            foreach ($logFiles as $logFile) {
                if (file_exists($logFile) && filemtime($logFile) < strtotime('-30 days')) {
                    // Archive old log instead of deleting
                    $archiveName = $logFile . '.' . date('Y-m-d', filemtime($logFile)) . '.old';
                    rename($logFile, $archiveName);
                }
            }
        })->weekly()->name('cleanup-training-logs');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
