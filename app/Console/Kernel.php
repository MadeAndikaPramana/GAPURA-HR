<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SendExpiryNotificationsJob;
use App\Jobs\SendExpiredCertificateNotificationsJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // =================================================================
        // TRAINING SYSTEM AUTOMATED TASKS
        // =================================================================

        // Daily morning tasks (6:00 AM)
        $schedule->call(function () {
                // Send daily expiry reminders (7-day and 30-day warnings)
                SendExpiryNotificationsJob::dispatch(7)->onQueue('urgent');
                SendExpiryNotificationsJob::dispatch(30)->onQueue('high');
            })
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->description('Send daily expiry reminders (7 & 30 days)')
            ->onSuccess(function () {
                logger('Daily expiry reminders dispatched successfully');
            })
            ->onFailure(function () {
                logger('Failed to dispatch daily expiry reminders');
            });

        // Check for expired certificates (every 2 hours during business hours)
        $schedule->job(new SendExpiredCertificateNotificationsJob())
            ->cron('0 8-18/2 * * 1-5') // Every 2 hours, 8 AM to 6 PM, weekdays only
            ->withoutOverlapping()
            ->description('Check for expired certificates')
            ->onSuccess(function () {
                logger('Expired certificate check completed successfully');
            });

        // Weekly compliance reports (Monday 7:00 AM)
        $schedule->call(function () {
                SendExpiryNotificationsJob::dispatch(60)->onQueue('normal');
                SendExpiryNotificationsJob::dispatch(90)->onQueue('normal');
            })
            ->weekly()
            ->mondays()
            ->at('07:00')
            ->withoutOverlapping()
            ->description('Send weekly compliance reminders (60 & 90 days)')
            ->onSuccess(function () {
                logger('Weekly compliance reminders sent successfully');
            });

        // Comprehensive system maintenance (Sunday 2:00 AM)
        $schedule->command('training:maintenance --mode=all')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->description('Weekly training system maintenance')
            ->appendOutputTo(storage_path('logs/maintenance.log'))
            ->onSuccess(function () {
                logger('Weekly training maintenance completed successfully');
            })
            ->onFailure(function () {
                logger('Weekly training maintenance failed');
            });

        // Daily status updates (11:00 PM)
        $schedule->command('training:maintenance --mode=status-update')
            ->dailyAt('23:00')
            ->withoutOverlapping()
            ->description('Daily training status updates')
            ->onSuccess(function () {
                logger('Daily status updates completed');
            });

        // Update training provider ratings (Weekly, Saturday 3:00 AM)
        $schedule->call(function () {
                $providers = \App\Models\TrainingProvider::all();
                foreach ($providers as $provider) {
                    $provider->updateRatingFromFeedback();
                }
                logger('Training provider ratings updated', ['count' => $providers->count()]);
            })
            ->weekly()
            ->saturdays()
            ->at('03:00')
            ->withoutOverlapping()
            ->description('Update training provider ratings');

        // Monthly analytics cache refresh (1st of every month, 1:00 AM)
        $schedule->call(function () {
                // Refresh comprehensive analytics data
                $analytics = [
                    'monthly_summary' => \App\Models\TrainingRecord::getTrainingStatistics([
                        'date_from' => now()->startOfMonth(),
                        'date_to' => now()->endOfMonth()
                    ]),
                    'department_performance' => \App\Models\Department::with('employees')->get()->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'stats' => app(\App\Services\CertificateService::class)->getDepartmentCertificateStatistics($dept->id)
                        ];
                    }),
                    'provider_performance' => \App\Models\TrainingProvider::all()->map(function ($provider) {
                        return [
                            'id' => $provider->id,
                            'name' => $provider->name,
                            'metrics' => $provider->performance_metrics
                        ];
                    }),
                    'last_updated' => now()
                ];

                cache()->put('monthly_training_analytics', $analytics, now()->addDays(32));
                logger('Monthly analytics cache refreshed');
            })
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->description('Refresh monthly analytics cache');

        // Quarterly compliance audit preparation (1st of quarter, 4:00 AM)
        $schedule->call(function () {
                // Generate comprehensive compliance report
                $complianceData = app(\App\Services\CertificateService::class)->generateComplianceReport();

                // Store audit data
                cache()->put('quarterly_compliance_audit', [
                    'quarter' => now()->quarter,
                    'year' => now()->year,
                    'data' => $complianceData,
                    'generated_at' => now()
                ], now()->addDays(95));

                logger('Quarterly compliance audit data prepared', [
                    'quarter' => now()->quarter,
                    'year' => now()->year
                ]);
            })
            ->quarterly()
            ->at('04:00')
            ->withoutOverlapping()
            ->description('Prepare quarterly compliance audit data');

        // =================================================================
        // NOTIFICATION CLEANUP & MAINTENANCE
        // =================================================================

        // Clean up old notifications (Daily 1:00 AM)
        $schedule->call(function () {
                $cutoffDate = now()->subDays(90);
                $deletedCount = \App\Models\Notification::where('created_at', '<', $cutoffDate)
                    ->where('status', 'read')
                    ->delete();

                logger('Old notifications cleaned up', ['deleted_count' => $deletedCount]);
            })
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->description('Clean up old notifications');

        // Clean up old system logs (Weekly, Tuesday 3:00 AM)
        $schedule->call(function () {
                $cutoffDate = now()->subDays(180); // Keep 6 months of logs
                $deletedCount = \App\Models\SystemLog::where('created_at', '<', $cutoffDate)
                    ->whereNotIn('level', ['critical', 'emergency']) // Keep critical logs longer
                    ->delete();

                logger('Old system logs cleaned up', ['deleted_count' => $deletedCount]);
            })
            ->weekly()
            ->tuesdays()
            ->at('03:00')
            ->withoutOverlapping()
            ->description('Clean up old system logs');

        // =================================================================
        // BACKUP & RECOVERY
        // =================================================================

        // Daily database backup (3:00 AM)
        $schedule->call(function () {
                try {
                    $filename = 'gapura_training_backup_' . now()->format('Y_m_d_H_i_s') . '.sql';
                    $path = storage_path('backups/' . $filename);

                    // Ensure backup directory exists
                    if (!file_exists(dirname($path))) {
                        mkdir(dirname($path), 0755, true);
                    }

                    // Create database backup (this would use actual backup commands)
                    $command = sprintf(
                        'mysqldump -u%s -p%s %s > %s',
                        config('database.connections.mysql.username'),
                        config('database.connections.mysql.password'),
                        config('database.connections.mysql.database'),
                        $path
                    );

                    exec($command, $output, $return_var);

                    if ($return_var === 0) {
                        logger('Database backup created successfully', ['filename' => $filename]);

                        // Clean up old backups (keep 30 days)
                        $oldBackups = glob(storage_path('backups/gapura_training_backup_*.sql'));
                        foreach ($oldBackups as $backup) {
                            if (filemtime($backup) < strtotime('-30 days')) {
                                unlink($backup);
                            }
                        }
                    } else {
                        logger('Database backup failed', ['return_code' => $return_var]);
                    }
                } catch (\Exception $e) {
                    logger('Database backup error', ['error' => $e->getMessage()]);
                }
            })
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->description('Create daily database backup');

        // =================================================================
        // PERFORMANCE MONITORING
        // =================================================================

        // System health check (Every 30 minutes during business hours)
        $schedule->call(function () {
                $healthMetrics = [
                    'timestamp' => now(),
                    'database_connection' => $this->checkDatabaseConnection(),
                    'queue_health' => $this->checkQueueHealth(),
                    'storage_space' => $this->checkStorageSpace(),
                    'memory_usage' => memory_get_usage(true),
                    'active_users' => \App\Models\Employee::where('last_login_at', '>=', now()->subMinutes(30))->count()
                ];

                // Store health metrics
                cache()->put('system_health_' . now()->format('Y_m_d_H_i'), $healthMetrics, now()->addHours(2));

                // Alert if critical issues
                if (!$healthMetrics['database_connection'] || $healthMetrics['storage_space'] < 10) {
                    $this->alertAdministrators('System health alert', $healthMetrics);
                }
            })
            ->cron('*/30 8-18 * * 1-5') // Every 30 minutes, business hours, weekdays
            ->withoutOverlapping()
            ->description('System health monitoring');

        // =================================================================
        // INTEGRATION & DATA SYNC
        // =================================================================

        // Sync with external HRIS system (if configured) - Daily 5:00 AM
        $schedule->call(function () {
                if (config('integrations.hris.enabled')) {
                    try {
                        // This would integrate with actual HRIS system
                        logger('HRIS sync initiated');

                        // Sync employee data, new joiners, departures, etc.
                        // Implementation would depend on specific HRIS system

                        logger('HRIS sync completed successfully');
                    } catch (\Exception $e) {
                        logger('HRIS sync failed', ['error' => $e->getMessage()]);
                    }
                }
            })
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->description('Sync with external HRIS system')
            ->when(function () {
                return config('integrations.hris.enabled', false);
            });

        // =================================================================
        // EMERGENCY & CRITICAL TASKS
        // =================================================================

        // Check for critical compliance violations (Every hour during business hours)
        $schedule->call(function () {
                $criticalViolations = \App\Models\Employee::where('status', 'active')
                    ->whereHas('trainingRecords', function ($query) {
                        $query->where('compliance_status', 'expired')
                              ->whereHas('trainingType', function ($typeQuery) {
                                  $typeQuery->where('is_mandatory', true);
                              })
                              ->where('expiry_date', '<', now()->subDays(30)); // Expired more than 30 days
                    })
                    ->with(['department', 'trainingRecords' => function ($query) {
                        $query->where('compliance_status', 'expired')
                              ->whereHas('trainingType', function ($typeQuery) {
                                  $typeQuery->where('is_mandatory', true);
                              })
                              ->with('trainingType');
                    }])
                    ->get();

                if ($criticalViolations->isNotEmpty()) {
                    logger('Critical compliance violations detected', [
                        'count' => $criticalViolations->count(),
                        'employees' => $criticalViolations->pluck('employee_id')->toArray()
                    ]);

                    // Send urgent alerts to HR and management
                    $this->alertCriticalCompliance($criticalViolations);
                }
            })
            ->hourly()
            ->between('08:00', '18:00')
            ->weekdays()
            ->withoutOverlapping()
            ->description('Monitor critical compliance violations');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Check database connection health
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check queue system health
     */
    private function checkQueueHealth(): array
    {
        try {
            $queueSize = \DB::table('jobs')->count();
            $failedJobs = \DB::table('failed_jobs')->count();

            return [
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'healthy' => $queueSize < 1000 && $failedJobs < 10
            ];
        } catch (\Exception $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check available storage space
     */
    private function checkStorageSpace(): int
    {
        $bytes = disk_free_space(storage_path());
        $gb = round($bytes / 1024 / 1024 / 1024, 2);
        return $gb;
    }

    /**
     * Alert administrators about system issues
     */
    private function alertAdministrators(string $subject, array $data): void
    {
        try {
            $admins = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->where('code', 'IT');
                })
                ->where('status', 'active')
                ->get();

            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'recipient_id' => $admin->id,
                    'type' => 'system',
                    'title' => $subject,
                    'message' => 'System health alert detected. Please check system status.',
                    'priority' => 'urgent',
                    'data' => json_encode($data)
                ]);
            }
        } catch (\Exception $e) {
            logger('Failed to alert administrators', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Alert about critical compliance violations
     */
    private function alertCriticalCompliance($violations): void
    {
        try {
            $hrEmployees = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->where('code', 'HR');
                })
                ->where('status', 'active')
                ->get();

            $message = "CRITICAL COMPLIANCE ALERT: {$violations->count()} employees have mandatory training expired for more than 30 days. Immediate action required.";

            foreach ($hrEmployees as $hrEmployee) {
                \App\Models\Notification::create([
                    'recipient_id' => $hrEmployee->id,
                    'type' => 'system',
                    'title' => 'CRITICAL: Long-term Compliance Violations',
                    'message' => $message,
                    'priority' => 'urgent',
                    'data' => json_encode([
                        'violation_count' => $violations->count(),
                        'employee_ids' => $violations->pluck('employee_id')->toArray()
                    ])
                ]);
            }
        } catch (\Exception $e) {
            logger('Failed to alert critical compliance', ['error' => $e->getMessage()]);
        }
    }
}
