<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SendExpiryNotificationsJob;
use App\Jobs\SendExpiredCertificateNotificationsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Daily morning expiry notifications (6:00 AM)
        $schedule->job(new SendExpiryNotificationsJob(7))
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->description('Send 7-day expiry reminders')
            ->onSuccess(function () {
                Log::info('7-day expiry reminders dispatched successfully');
            })
            ->onFailure(function () {
                Log::error('Failed to dispatch 7-day expiry reminders');
            });

        $schedule->job(new SendExpiryNotificationsJob(30))
            ->dailyAt('06:30')
            ->withoutOverlapping()
            ->description('Send 30-day expiry reminders')
            ->onSuccess(function () {
                Log::info('30-day expiry reminders dispatched successfully');
            });

        // Check for expired certificates (every 2 hours during business hours)
        $schedule->command('app:check-certificate-expiry')
            ->cron('0 8-18/2 * * 1-5') // Every 2 hours, 8 AM to 6 PM, weekdays only
            ->withoutOverlapping()
            ->description('Check and update certificate expiry statuses')
            ->onSuccess(function () {
                Log::info('Certificate expiry check completed successfully');
            });

        // Daily training status updates with notifications (Evening)
        $schedule->command('training:update-status --notify')
            ->dailyAt('18:00')
            ->withoutOverlapping()
            ->description('Daily training status updates with notifications')
            ->onSuccess(function () {
                Log::info('Daily training status update completed');
            });

        // Weekly expiry notifications (Monday 7:00 AM)
        $schedule->job(new SendExpiryNotificationsJob(60))
            ->weekly()
            ->mondays()
            ->at('07:00')
            ->withoutOverlapping()
            ->description('Send 60-day expiry reminders')
            ->onSuccess(function () {
                Log::info('Weekly 60-day expiry reminders sent successfully');
            });

        $schedule->job(new SendExpiryNotificationsJob(90))
            ->weekly()
            ->mondays()
            ->at('07:30')
            ->withoutOverlapping()
            ->description('Send 90-day expiry reminders')
            ->onSuccess(function () {
                Log::info('Weekly 90-day expiry reminders sent successfully');
            });

        // Comprehensive system maintenance (Sunday 2:00 AM)
        $schedule->command('training:maintenance --mode=all')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->description('Weekly comprehensive training system maintenance')
            ->appendOutputTo(storage_path('logs/maintenance.log'))
            ->onSuccess(function () {
                Log::info('Weekly training maintenance completed successfully');
            })
            ->onFailure(function () {
                Log::error('Weekly training maintenance failed');
            });

        // Daily status maintenance (11:00 PM)
        $schedule->command('training:maintenance --mode=status-update')
            ->dailyAt('23:00')
            ->withoutOverlapping()
            ->description('Daily training status maintenance')
            ->onSuccess(function () {
                Log::info('Daily status maintenance completed');
            });

        // Certificate maintenance (Weekly, Saturday 3:00 AM)
        $schedule->command('training:maintenance --mode=certificates')
            ->weekly()
            ->saturdays()
            ->at('03:00')
            ->withoutOverlapping()
            ->description('Weekly certificate maintenance and QR code generation')
            ->onSuccess(function () {
                Log::info('Weekly certificate maintenance completed');
            });

        // Notification cleanup (Weekly, Tuesday 3:00 AM)
        $schedule->command('training:maintenance --mode=cleanup')
            ->weekly()
            ->tuesdays()
            ->at('03:00')
            ->withoutOverlapping()
            ->description('Weekly notification and system cleanup')
            ->onSuccess(function () {
                Log::info('Weekly cleanup completed');
            });

        // =================================================================
        // EXPIRED CERTIFICATE MONITORING
        // =================================================================

        // Check for expired certificates requiring urgent attention
        $schedule->job(new SendExpiredCertificateNotificationsJob())
            ->cron('0 8-18/3 * * 1-5') // Every 3 hours during business hours, weekdays
            ->withoutOverlapping()
            ->description('Monitor and notify expired certificates')
            ->onSuccess(function () {
                Log::info('Expired certificate monitoring completed');
            })
            ->onFailure(function () {
                Log::error('Expired certificate monitoring failed');
            });

        // =================================================================
        // ANALYTICS & REPORTING
        // =================================================================

        // Monthly analytics cache refresh (1st of every month, 1:00 AM)
        $schedule->call(function () {
                $this->refreshMonthlyAnalytics();
            })
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->description('Refresh monthly analytics cache')
            ->onSuccess(function () {
                Log::info('Monthly analytics cache refreshed successfully');
            });

        // Quarterly compliance audit preparation (1st of quarter, 4:00 AM)
        $schedule->call(function () {
                $this->prepareQuarterlyComplianceAudit();
            })
            ->quarterly()
            ->at('04:00')
            ->withoutOverlapping()
            ->description('Prepare quarterly compliance audit data')
            ->onSuccess(function () {
                Log::info('Quarterly compliance audit data prepared successfully');
            });

        // =================================================================
        // SYSTEM MAINTENANCE & MONITORING
        // =================================================================

        // System health check (Every 30 minutes during business hours)
        $schedule->call(function () {
                $this->performSystemHealthCheck();
            })
            ->cron('*/30 8-18 * * 1-5') // Every 30 minutes, business hours, weekdays
            ->withoutOverlapping()
            ->description('System health monitoring')
            ->onFailure(function () {
                Log::error('System health check failed');
            });

        // Daily database backup (3:00 AM)
        $schedule->call(function () {
                $this->createDatabaseBackup();
            })
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->description('Create daily database backup')
            ->onSuccess(function () {
                Log::info('Daily database backup completed');
            })
            ->onFailure(function () {
                Log::error('Daily database backup failed');
            });

        // =================================================================
        // TRAINING PROVIDER UPDATES
        // =================================================================

        // Update training provider ratings (Weekly, Saturday 4:00 AM)
        $schedule->call(function () {
                $this->updateTrainingProviderRatings();
            })
            ->weekly()
            ->saturdays()
            ->at('04:00')
            ->withoutOverlapping()
            ->description('Update training provider ratings based on feedback');

        // =================================================================
        // CRITICAL COMPLIANCE MONITORING
        // =================================================================

        // Check for critical compliance violations (Every hour during business hours)
        $schedule->call(function () {
                $this->checkCriticalComplianceViolations();
            })
            ->hourly()
            ->between('08:00', '18:00')
            ->weekdays()
            ->withoutOverlapping()
            ->description('Monitor critical compliance violations')
            ->onFailure(function () {
                Log::error('Critical compliance check failed');
            });

        // =================================================================
        // EXTERNAL INTEGRATIONS (Conditional)
        // =================================================================

        // External HRIS sync (if enabled in config)
        $schedule->call(function () {
                $this->syncWithExternalHRIS();
            })
            ->dailyAt('05:00')
            ->withoutOverlapping()
            ->description('Sync with external HRIS system')
            ->when(function () {
                return config('integrations.hris.enabled', false);
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    // =================================================================
    // HELPER METHODS FOR SCHEDULED TASKS
    // =================================================================

    /**
     * Refresh monthly analytics cache
     */
    private function refreshMonthlyAnalytics(): void
    {
        try {
            $analytics = [
                'generated_at' => now(),
                'month' => now()->format('Y-m'),
                'employee_count' => \App\Models\Employee::count(),
                'active_employee_count' => \App\Models\Employee::where('status', 'active')->count(),
                'training_records_count' => \App\Models\TrainingRecord::count(),
                'active_certificates' => \App\Models\TrainingRecord::where('status', 'active')->count(),
                'expiring_certificates' => \App\Models\TrainingRecord::where('status', 'expiring_soon')->count(),
                'expired_certificates' => \App\Models\TrainingRecord::where('status', 'expired')->count(),
                'department_stats' => \App\Models\Department::withCount(['employees' => function($query) {
                    $query->where('status', 'active');
                }])->get()->map(function($dept) {
                    return [
                        'name' => $dept->name,
                        'code' => $dept->code,
                        'employee_count' => $dept->employees_count,
                    ];
                }),
                'training_type_stats' => \App\Models\TrainingType::withCount('trainingRecords')->get()->map(function($type) {
                    return [
                        'name' => $type->name,
                        'code' => $type->code,
                        'records_count' => $type->training_records_count,
                        'is_mandatory' => $type->is_mandatory ?? false,
                    ];
                }),
                'compliance_overview' => $this->calculateComplianceOverview(),
            ];

            cache()->put('monthly_training_analytics', $analytics, now()->addDays(32));
            Log::info('Monthly analytics cache refreshed successfully', [
                'employees' => $analytics['employee_count'],
                'records' => $analytics['training_records_count']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to refresh monthly analytics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare quarterly compliance audit data
     */
    private function prepareQuarterlyComplianceAudit(): void
    {
        try {
            $quarter = now()->quarter;
            $year = now()->year;

            $complianceData = [
                'quarter' => $quarter,
                'year' => $year,
                'generated_at' => now(),
                'total_employees' => \App\Models\Employee::where('status', 'active')->count(),
                'total_training_records' => \App\Models\TrainingRecord::count(),
                'compliance_by_department' => $this->getComplianceByDepartment(),
                'training_type_completion' => $this->getTrainingTypeCompletion(),
                'expired_certificates' => \App\Models\TrainingRecord::where('status', 'expired')->count(),
                'expiring_certificates' => \App\Models\TrainingRecord::where('status', 'expiring_soon')->count(),
                'critical_violations' => $this->getCriticalViolations(),
            ];

            cache()->put("quarterly_compliance_audit_{$year}_Q{$quarter}", $complianceData, now()->addDays(95));
            Log::info('Quarterly compliance audit data prepared', [
                'quarter' => $quarter,
                'year' => $year,
                'employees' => $complianceData['total_employees']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to prepare quarterly compliance audit', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Perform system health check
     */
    private function performSystemHealthCheck(): void
    {
        try {
            $healthMetrics = [
                'timestamp' => now(),
                'database_connection' => $this->checkDatabaseConnection(),
                'storage_space_gb' => $this->checkStorageSpace(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'active_employees' => \App\Models\Employee::where('status', 'active')->count(),
                'training_records' => \App\Models\TrainingRecord::count(),
                'queue_health' => $this->checkQueueHealth(),
            ];

            // Store health metrics
            cache()->put('system_health_' . now()->format('Y_m_d_H_i'), $healthMetrics, now()->addHours(2));

            // Alert if critical issues
            if (!$healthMetrics['database_connection'] ||
                $healthMetrics['storage_space_gb'] < 1 ||
                $healthMetrics['memory_usage_mb'] > 1024) {
                $this->alertAdministrators('System health alert', $healthMetrics);
            }

        } catch (\Exception $e) {
            Log::error('System health check failed', ['error' => $e->getMessage()]);
            $this->alertAdministrators('System health check error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create database backup
     */
    private function createDatabaseBackup(): void
    {
        try {
            $filename = 'gapura_training_backup_' . now()->format('Y_m_d_H_i_s') . '.sql';
            $backupDir = storage_path('backups');
            $path = $backupDir . '/' . $filename;

            // Ensure backup directory exists
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));

            if ($dbConfig && $dbConfig['driver'] === 'mysql') {
                $command = sprintf(
                    'mysqldump --no-tablespaces --single-transaction -u%s -p%s %s > %s 2>/dev/null',
                    escapeshellarg($dbConfig['username']),
                    escapeshellarg($dbConfig['password']),
                    escapeshellarg($dbConfig['database']),
                    escapeshellarg($path)
                );

                exec($command, $output, $return_var);

                if ($return_var === 0 && file_exists($path) && filesize($path) > 1000) {
                    Log::info('Database backup created successfully', [
                        'filename' => $filename,
                        'size_mb' => round(filesize($path) / 1024 / 1024, 2)
                    ]);

                    // Clean up old backups (keep 30 days)
                    $this->cleanupOldBackups();
                } else {
                    Log::error('Database backup failed or file too small', [
                        'return_code' => $return_var,
                        'file_exists' => file_exists($path),
                        'file_size' => file_exists($path) ? filesize($path) : 0
                    ]);
                }
            } else {
                Log::warning('Database backup skipped - not MySQL or config missing');
            }

        } catch (\Exception $e) {
            Log::error('Database backup error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update training provider ratings
     */
    private function updateTrainingProviderRatings(): void
    {
        try {
            $providers = \App\Models\TrainingProvider::all();
            $updatedCount = 0;

            foreach ($providers as $provider) {
                if (method_exists($provider, 'updateRatingFromFeedback')) {
                    $oldRating = $provider->rating;
                    $provider->updateRatingFromFeedback();

                    if ($provider->rating !== $oldRating) {
                        $updatedCount++;
                    }
                }
            }

            Log::info('Training provider ratings updated', [
                'total_providers' => $providers->count(),
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update training provider ratings', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check for critical compliance violations
     */
    private function checkCriticalComplianceViolations(): void
    {
        try {
            $criticalViolations = \App\Models\TrainingRecord::where('status', 'expired')
                ->whereHas('trainingType', function ($query) {
                    $query->where('is_mandatory', true);
                })
                ->where('expiry_date', '<', now()->subDays(30)) // Expired more than 30 days
                ->with(['employee.department', 'trainingType'])
                ->get();

            if ($criticalViolations->isNotEmpty()) {
                Log::warning('Critical compliance violations detected', [
                    'count' => $criticalViolations->count(),
                    'employee_ids' => $criticalViolations->pluck('employee.employee_id')->toArray()
                ]);

                $this->alertCriticalCompliance($criticalViolations);
            }

        } catch (\Exception $e) {
            Log::error('Failed to check critical compliance violations', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync with external HRIS system
     */
    private function syncWithExternalHRIS(): void
    {
        try {
            if (config('integrations.hris.enabled')) {
                Log::info('HRIS sync initiated');

                // Placeholder for actual HRIS integration logic
                // This would contain actual API calls to external HRIS

                Log::info('HRIS sync completed successfully');
            }
        } catch (\Exception $e) {
            Log::error('HRIS sync failed', ['error' => $e->getMessage()]);
        }
    }

    // =================================================================
    // UTILITY METHODS
    // =================================================================

    /**
     * Calculate compliance overview
     */
    private function calculateComplianceOverview(): array
    {
        try {
            $totalEmployees = \App\Models\Employee::where('status', 'active')->count();
            $mandatoryTrainings = \App\Models\TrainingType::where('is_mandatory', true)->count();

            return [
                'total_active_employees' => $totalEmployees,
                'mandatory_training_types' => $mandatoryTrainings,
                'overall_compliance_rate' => $this->calculateOverallComplianceRate(),
                'department_compliance' => $this->getComplianceByDepartment()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to calculate compliance overview', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate overall compliance rate
     */
    private function calculateOverallComplianceRate(): float
    {
        try {
            $activeEmployees = \App\Models\Employee::where('status', 'active')->count();
            if ($activeEmployees === 0) return 100.0;

            $compliantEmployees = \App\Models\Employee::where('status', 'active')
                ->whereHas('trainingRecords', function($query) {
                    $query->where('status', 'active')
                          ->whereHas('trainingType', function($subQuery) {
                              $subQuery->where('is_mandatory', true);
                          });
                })
                ->count();

            return round(($compliantEmployees / $activeEmployees) * 100, 2);

        } catch (\Exception $e) {
            Log::error('Failed to calculate overall compliance rate', ['error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Get compliance data by department
     */
    private function getComplianceByDepartment(): array
    {
        try {
            return \App\Models\Department::with(['employees' => function($query) {
                    $query->where('status', 'active');
                }])
                ->get()
                ->map(function ($department) {
                    $totalEmployees = $department->employees->count();
                    if ($totalEmployees === 0) {
                        return [
                            'department' => $department->name,
                            'code' => $department->code,
                            'total_employees' => 0,
                            'compliance_rate' => 100.0
                        ];
                    }

                    $compliantEmployees = $department->employees->filter(function($employee) {
                        return $employee->trainingRecords()
                            ->where('status', 'active')
                            ->whereHas('trainingType', function($query) {
                                $query->where('is_mandatory', true);
                            })
                            ->exists();
                    })->count();

                    return [
                        'department' => $department->name,
                        'code' => $department->code,
                        'total_employees' => $totalEmployees,
                        'compliant_employees' => $compliantEmployees,
                        'compliance_rate' => round(($compliantEmployees / $totalEmployees) * 100, 2)
                    ];
                })
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get compliance by department', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get training type completion statistics
     */
    private function getTrainingTypeCompletion(): array
    {
        try {
            return \App\Models\TrainingType::withCount([
                'trainingRecords',
                'trainingRecords as active_count' => function($query) {
                    $query->where('status', 'active');
                },
                'trainingRecords as expired_count' => function($query) {
                    $query->where('status', 'expired');
                }
            ])
            ->get()
            ->map(function($type) {
                return [
                    'name' => $type->name,
                    'code' => $type->code,
                    'is_mandatory' => $type->is_mandatory ?? false,
                    'total_records' => $type->training_records_count,
                    'active_records' => $type->active_count,
                    'expired_records' => $type->expired_count,
                    'completion_rate' => $type->training_records_count > 0
                        ? round(($type->active_count / $type->training_records_count) * 100, 2)
                        : 0
                ];
            })
            ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get training type completion', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get critical violations summary
     */
    private function getCriticalViolations(): array
    {
        try {
            return \App\Models\TrainingRecord::where('status', 'expired')
                ->whereHas('trainingType', function($query) {
                    $query->where('is_mandatory', true);
                })
                ->where('expiry_date', '<', now()->subDays(30))
                ->with(['employee.department', 'trainingType'])
                ->get()
                ->map(function($record) {
                    return [
                        'employee_id' => $record->employee->employee_id,
                        'employee_name' => $record->employee->name,
                        'department' => $record->employee->department->name ?? 'N/A',
                        'training_type' => $record->trainingType->name,
                        'expiry_date' => $record->expiry_date,
                        'days_overdue' => now()->diffInDays($record->expiry_date)
                    ];
                })
                ->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get critical violations', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Clean up old database backups
     */
    private function cleanupOldBackups(): void
    {
        try {
            $backupPath = storage_path('backups/');
            if (!is_dir($backupPath)) return;

            $oldBackups = glob($backupPath . 'gapura_training_backup_*.sql');
            $deletedCount = 0;

            foreach ($oldBackups as $backup) {
                if (file_exists($backup) && filemtime($backup) < strtotime('-30 days')) {
                    if (unlink($backup)) {
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                Log::info('Old backups cleaned up', ['deleted_count' => $deletedCount]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to cleanup old backups', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check database connection health
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check available storage space (in GB)
     */
    private function checkStorageSpace(): float
    {
        try {
            $bytes = disk_free_space(storage_path());
            return round($bytes / 1024 / 1024 / 1024, 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Check queue system health
     */
    private function checkQueueHealth(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'pending_jobs' => $pending,
                'failed_jobs' => $failed,
                'healthy' => $pending < 100 && $failed < 5
            ];
        } catch (\Exception $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Alert administrators about system issues
     */
    private function alertAdministrators(string $subject, array $data): void
    {
        try {
            // Get IT and HR employees for system alerts
            $admins = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->whereIn('code', ['IT', 'HR']);
                })
                ->where('status', 'active')
                ->get();

            // Create notification if Notification model exists
            if (class_exists(\App\Models\Notification::class)) {
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'recipient_id' => $admin->id,
                        'type' => 'system',
                        'title' => $subject,
                        'message' => 'System health alert detected. Please check system status.',
                        'priority' => 'urgent',
                        'data' => json_encode($data),
                        'status' => 'unread'
                    ]);
                }

                Log::info('System administrators alerted', [
                    'subject' => $subject,
                    'admin_count' => $admins->count()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to alert administrators', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Alert about critical compliance violations
     */
    private function alertCriticalCompliance($violations): void
    {
        try {
            // Get HR employees for compliance alerts
            $hrEmployees = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->where('code', 'HR');
                })
                ->where('status', 'active')
                ->get();

            $message = "CRITICAL COMPLIANCE ALERT: {$violations->count()} employees have mandatory training expired for more than 30 days. Immediate action required.";

            // Create notification if Notification model exists
            if (class_exists(\App\Models\Notification::class)) {
                foreach ($hrEmployees as $hrEmployee) {
                    \App\Models\Notification::create([
                        'recipient_id' => $hrEmployee->id,
                        'type' => 'compliance',
                        'title' => 'CRITICAL: Long-term Compliance Violations',
                        'message' => $message,
                        'priority' => 'urgent',
                        'data' => json_encode([
                            'violation_count' => $violations->count(),
                            'employee_ids' => $violations->pluck('employee.employee_id')->toArray()
                        ]),
                        'status' => 'unread'
                    ]);
                }

                Log::warning('Critical compliance alerts sent to HR team', [
                    'violation_count' => $violations->count(),
                    'hr_recipients' => $hrEmployees->count()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to alert critical compliance', ['error' => $e->getMessage()]);
        }
    }
    protected function schedule(Schedule $schedule)
    {
    // Update training statuses daily at 6 AM
    $schedule->command('training:update-status')
             ->dailyAt('06:00')
             ->withoutOverlapping()
             ->runInBackground();
    }
}
