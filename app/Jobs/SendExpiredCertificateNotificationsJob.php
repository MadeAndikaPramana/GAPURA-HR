<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use App\Models\Certificate;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendExpiredCertificateNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 1;

    protected $batchSize;
    protected $checkDaysBack;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchSize = 50, int $checkDaysBack = 7)
    {
        $this->batchSize = $batchSize;
        $this->checkDaysBack = $checkDaysBack; // Check certificates that expired in the last X days
        $this->onQueue('urgent'); // Expired certificates are always urgent
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            new WithoutOverlapping('expired-certificate-notifications')
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService)
    {
        Log::info('Starting expired certificate notifications job', [
            'batch_size' => $this->batchSize,
            'check_days_back' => $this->checkDaysBack
        ]);

        $startTime = microtime(true);
        $totalProcessed = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            // Get certificates that expired recently but haven't been notified
            $expiredCertificates = Certificate::expired()
                ->with(['trainingRecord.employee.department', 'trainingRecord.trainingType'])
                ->whereHas('trainingRecord.employee', function ($query) {
                    $query->where('status', 'active');
                })
                ->where('expiry_date', '>=', now()->subDays($this->checkDaysBack))
                ->whereDoesntHave('notifications', function ($query) {
                    $query->where('type', 'system')
                          ->where('title', 'like', '%Certificate Has Expired%')
                          ->where('created_at', '>=', now()->subHours(24)); // Don't spam daily
                })
                ->chunk($this->batchSize, function ($chunk) use ($notificationService, &$totalProcessed, &$successCount, &$errorCount, &$errors) {
                    foreach ($chunk as $certificate) {
                        try {
                            // Send expired notification
                            $result = $notificationService->sendExpiredCertificateNotification($certificate);

                            if ($result) {
                                $successCount++;

                                // Update training record compliance status
                                $certificate->trainingRecord->updateComplianceStatus();
                                $certificate->trainingRecord->save();

                                Log::info("Expired certificate notification sent", [
                                    'certificate_id' => $certificate->id,
                                    'employee_name' => $certificate->trainingRecord->employee->name,
                                    'training_type' => $certificate->trainingRecord->trainingType->name,
                                    'expired_date' => $certificate->expiry_date,
                                    'days_expired' => abs($certificate->days_until_expiry)
                                ]);
                            } else {
                                $errorCount++;
                                $errors[] = [
                                    'certificate_id' => $certificate->id,
                                    'employee_name' => $certificate->trainingRecord->employee->name,
                                    'error' => 'Notification service returned false'
                                ];
                            }

                            $totalProcessed++;

                        } catch (\Exception $e) {
                            $errorCount++;
                            $errors[] = [
                                'certificate_id' => $certificate->id,
                                'employee_name' => $certificate->trainingRecord->employee->name ?? 'Unknown',
                                'error' => $e->getMessage()
                            ];

                            Log::error("Failed to send expired certificate notification", [
                                'certificate_id' => $certificate->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });

            // Also check for newly expired certificates (expired today)
            $newlyExpiredCount = $this->processNewlyExpiredCertificates($notificationService);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Expired certificate notifications job completed', [
                'total_processed' => $totalProcessed,
                'newly_expired_processed' => $newlyExpiredCount,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'execution_time_ms' => $executionTime,
                'errors' => $errors
            ]);

            // Log critical compliance issues
            $this->logComplianceIssues();

            // Log summary to database
            $this->logJobSummary($totalProcessed + $newlyExpiredCount, $successCount, $errorCount, $executionTime);

        } catch (\Exception $e) {
            Log::error('Expired certificate notifications job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Process certificates that expired today
     */
    protected function processNewlyExpiredCertificates(NotificationService $notificationService)
    {
        $processed = 0;

        $newlyExpired = Certificate::whereDate('expiry_date', now()->toDateString())
            ->with(['trainingRecord.employee', 'trainingRecord.trainingType'])
            ->whereHas('trainingRecord.employee', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        foreach ($newlyExpired as $certificate) {
            try {
                $notificationService->sendExpiredCertificateNotification($certificate);

                // Mark training record as expired
                $certificate->trainingRecord->update(['compliance_status' => 'expired']);

                Log::warning("Certificate expired today", [
                    'certificate_id' => $certificate->id,
                    'employee_name' => $certificate->trainingRecord->employee->name,
                    'training_type' => $certificate->trainingRecord->trainingType->name
                ]);

                $processed++;

            } catch (\Exception $e) {
                Log::error("Failed to process newly expired certificate", [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    /**
     * Log critical compliance issues that need immediate attention
     */
    protected function logComplianceIssues()
    {
        try {
            // Count employees with expired mandatory training
            $criticalIssues = \App\Models\Employee::where('status', 'active')
                ->whereHas('trainingRecords', function ($query) {
                    $query->where('compliance_status', 'expired')
                          ->whereHas('trainingType', function ($typeQuery) {
                              $typeQuery->where('is_mandatory', true);
                          });
                })
                ->with(['department', 'trainingRecords' => function ($query) {
                    $query->where('compliance_status', 'expired')
                          ->whereHas('trainingType', function ($typeQuery) {
                              $typeQuery->where('is_mandatory', true);
                          })
                          ->with('trainingType');
                }])
                ->get();

            if ($criticalIssues->isNotEmpty()) {
                Log::critical('Critical compliance issues detected', [
                    'affected_employees' => $criticalIssues->count(),
                    'details' => $criticalIssues->map(function ($employee) {
                        return [
                            'employee_id' => $employee->employee_id,
                            'name' => $employee->name,
                            'department' => $employee->department->name,
                            'expired_mandatory_trainings' => $employee->trainingRecords->map(function ($record) {
                                return [
                                    'training' => $record->trainingType->name,
                                    'expired_date' => $record->expiry_date,
                                    'days_expired' => abs($record->days_until_expiry)
                                ];
                            })
                        ];
                    })
                ]);

                // Create system alert for HR
                $this->createCriticalComplianceAlert($criticalIssues);
            }

        } catch (\Exception $e) {
            Log::error('Failed to log compliance issues', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create critical compliance alert for HR team
     */
    protected function createCriticalComplianceAlert($criticalIssues)
    {
        try {
            $hrEmployees = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->where('code', 'HR');
                })
                ->where('status', 'active')
                ->get();

            $alertMessage = "CRITICAL COMPLIANCE ALERT\n\n";
            $alertMessage .= "Number of employees with expired mandatory training: " . $criticalIssues->count() . "\n\n";
            $alertMessage .= "Affected employees:\n";

            foreach ($criticalIssues->take(10) as $employee) { // Limit to first 10 for readability
                $alertMessage .= "• {$employee->name} ({$employee->employee_id}) - {$employee->department->name}\n";
                foreach ($employee->trainingRecords as $record) {
                    $daysExpired = abs($record->days_until_expiry);
                    $alertMessage .= "  - {$record->trainingType->name}: Expired {$daysExpired} days ago\n";
                }
            }

            if ($criticalIssues->count() > 10) {
                $alertMessage .= "\n... and " . ($criticalIssues->count() - 10) . " more employees.";
            }

            $alertMessage .= "\n\nImmediate action required to restore compliance.";

            foreach ($hrEmployees as $hrEmployee) {
                \App\Models\Notification::create([
                    'recipient_id' => $hrEmployee->id,
                    'type' => 'system',
                    'title' => 'CRITICAL: Multiple Expired Mandatory Trainings',
                    'message' => $alertMessage,
                    'priority' => 'urgent',
                    'data' => json_encode([
                        'alert_type' => 'critical_compliance',
                        'affected_count' => $criticalIssues->count(),
                        'alert_date' => now()->toISOString()
                    ])
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to create critical compliance alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log job summary to database
     */
    protected function logJobSummary(int $processed, int $success, int $errors, float $executionTime)
    {
        try {
            \App\Models\SystemLog::create([
                'level' => $errors > 0 ? 'warning' : 'info',
                'message' => 'Expired certificate notifications job completed',
                'context' => [
                    'job' => 'SendExpiredCertificateNotificationsJob',
                    'total_processed' => $processed,
                    'success_count' => $success,
                    'error_count' => $errors,
                    'execution_time_ms' => $executionTime,
                    'check_days_back' => $this->checkDaysBack,
                    'success_rate' => $processed > 0 ? round(($success / $processed) * 100, 2) : 0
                ],
                'channel' => 'jobs'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log expired certificate job summary', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendExpiredCertificateNotificationsJob failed permanently', [
            'batch_size' => $this->batchSize,
            'check_days_back' => $this->checkDaysBack,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // This is critical - notify IT administrators immediately
        try {
            $itAdmins = \App\Models\Employee::whereHas('department', function ($query) {
                    $query->where('code', 'IT');
                })
                ->where('status', 'active')
                ->get();

            foreach ($itAdmins as $admin) {
                \App\Models\Notification::create([
                    'recipient_id' => $admin->id,
                    'type' => 'system',
                    'title' => 'CRITICAL: Expired Certificate Notification Job Failed',
                    'message' => "The expired certificate notifications job has failed permanently. This means expired certificates are not being tracked automatically. Error: {$exception->getMessage()}",
                    'priority' => 'urgent',
                    'data' => json_encode([
                        'job_class' => static::class,
                        'error' => $exception->getMessage(),
                        'failed_at' => now()->toISOString(),
                        'impact' => 'Compliance tracking disrupted'
                    ])
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify IT admins about critical job failure', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [60, 300, 900]; // 1m, 5m, 15m
    }

    /**
     * Determine when the job should timeout
     */
    public function retryUntil()
    {
        return now()->addHour();
    }

    /**
     * Get tags for monitoring and debugging
     */
    public function tags()
    {
        return [
            'notifications',
            'expired',
            'critical',
            'compliance',
            'batch:' . $this->batchSize
        ];
    }
}
