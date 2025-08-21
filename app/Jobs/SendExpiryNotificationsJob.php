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

class SendExpiryNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 1;

    protected $daysToExpiry;
    protected $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(int $daysToExpiry = null, int $batchSize = 50)
    {
        $this->daysToExpiry = $daysToExpiry;
        $this->batchSize = $batchSize;

        // Set queue priority based on urgency
        if ($daysToExpiry !== null && $daysToExpiry <= 7) {
            $this->onQueue('urgent');
        } elseif ($daysToExpiry !== null && $daysToExpiry <= 30) {
            $this->onQueue('high');
        } else {
            $this->onQueue('normal');
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware()
    {
        return [
            new WithoutOverlapping('expiry-notifications-' . ($this->daysToExpiry ?? 'all'))
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService)
    {
        Log::info('Starting expiry notifications job', [
            'days_to_expiry' => $this->daysToExpiry,
            'batch_size' => $this->batchSize
        ]);

        $startTime = microtime(true);
        $totalProcessed = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        try {
            if ($this->daysToExpiry !== null) {
                // Process specific day notifications
                $result = $this->processDayNotifications($notificationService, $this->daysToExpiry);
                $totalProcessed = $result['processed'];
                $successCount = $result['success'];
                $errorCount = $result['errors'];
                $errors = $result['error_details'];
            } else {
                // Process all standard expiry periods
                $standardPeriods = [90, 60, 30, 7];

                foreach ($standardPeriods as $days) {
                    $result = $this->processDayNotifications($notificationService, $days);
                    $totalProcessed += $result['processed'];
                    $successCount += $result['success'];
                    $errorCount += $result['errors'];
                    $errors = array_merge($errors, $result['error_details']);
                }
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('Expiry notifications job completed', [
                'total_processed' => $totalProcessed,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'execution_time_ms' => $executionTime,
                'errors' => $errors
            ]);

            // Log summary to database for reporting
            $this->logJobSummary($totalProcessed, $successCount, $errorCount, $executionTime);

        } catch (\Exception $e) {
            Log::error('Expiry notifications job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Process notifications for a specific number of days
     */
    protected function processDayNotifications(NotificationService $notificationService, int $days)
    {
        $processed = 0;
        $success = 0;
        $errors = 0;
        $errorDetails = [];

        Log::info("Processing {$days}-day expiry notifications");

        // Get certificates expiring in specific days
        $certificates = Certificate::expiringIn($days)
            ->with(['trainingRecord.employee', 'trainingRecord.trainingType'])
            ->whereHas('trainingRecord.employee', function ($query) {
                $query->where('status', 'active');
            })
            ->chunk($this->batchSize, function ($chunk) use ($notificationService, $days, &$processed, &$success, &$errors, &$errorDetails) {
                foreach ($chunk as $certificate) {
                    try {
                        // Check if notification was already sent recently
                        if ($this->wasRecentlyNotified($certificate, $days)) {
                            Log::debug("Skipping recently notified certificate", [
                                'certificate_id' => $certificate->id,
                                'days' => $days
                            ]);
                            $processed++;
                            continue;
                        }

                        // Send notification
                        $result = $notificationService->sendRenewalReminder($certificate, $days);

                        if ($result) {
                            $success++;
                            Log::debug("Notification sent successfully", [
                                'certificate_id' => $certificate->id,
                                'employee_name' => $certificate->trainingRecord->employee->name,
                                'training_type' => $certificate->trainingRecord->trainingType->name,
                                'days' => $days
                            ]);
                        } else {
                            $errors++;
                            $errorDetails[] = [
                                'certificate_id' => $certificate->id,
                                'employee_name' => $certificate->trainingRecord->employee->name,
                                'error' => 'Notification service returned false'
                            ];
                        }

                        $processed++;

                        // Add small delay to prevent overwhelming email servers
                        usleep(100000); // 100ms delay

                    } catch (\Exception $e) {
                        $errors++;
                        $errorDetails[] = [
                            'certificate_id' => $certificate->id,
                            'employee_name' => $certificate->trainingRecord->employee->name ?? 'Unknown',
                            'error' => $e->getMessage()
                        ];

                        Log::error("Failed to send notification", [
                            'certificate_id' => $certificate->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        return [
            'processed' => $processed,
            'success' => $success,
            'errors' => $errors,
            'error_details' => $errorDetails
        ];
    }

    /**
     * Check if certificate was recently notified for the same period
     */
    protected function wasRecentlyNotified(Certificate $certificate, int $days)
    {
        $recentThreshold = now()->subHours(24); // Don't send same notification within 24 hours

        return \App\Models\Notification::where('recipient_id', $certificate->trainingRecord->employee_id)
            ->where('related_type', 'certificate')
            ->where('related_id', $certificate->id)
            ->where('created_at', '>=', $recentThreshold)
            ->whereJsonContains('data', ['days_until_expiry' => $days])
            ->exists();
    }

    /**
     * Log job summary to database for reporting
     */
    protected function logJobSummary(int $processed, int $success, int $errors, float $executionTime)
    {
        try {
            \App\Models\SystemLog::create([
                'level' => $errors > 0 ? 'warning' : 'info',
                'message' => 'Expiry notifications job completed',
                'context' => [
                    'job' => 'SendExpiryNotificationsJob',
                    'days_to_expiry' => $this->daysToExpiry,
                    'total_processed' => $processed,
                    'success_count' => $success,
                    'error_count' => $errors,
                    'execution_time_ms' => $executionTime,
                    'success_rate' => $processed > 0 ? round(($success / $processed) * 100, 2) : 0
                ],
                'channel' => 'jobs'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log job summary', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SendExpiryNotificationsJob failed permanently', [
            'days_to_expiry' => $this->daysToExpiry,
            'batch_size' => $this->batchSize,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Notify administrators about job failure
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
                    'title' => 'Training Notification Job Failed',
                    'message' => "The expiry notifications job has failed permanently. Error: {$exception->getMessage()}",
                    'priority' => 'urgent',
                    'data' => json_encode([
                        'job_class' => static::class,
                        'days_to_expiry' => $this->daysToExpiry,
                        'error' => $exception->getMessage(),
                        'failed_at' => now()->toISOString()
                    ])
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about job failure', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [30, 120, 300]; // 30s, 2m, 5m
    }

    /**
     * Determine if the job should be retried based on the exception
     */
    public function retryUntil()
    {
        return now()->addMinutes(30);
    }

    /**
     * Get tags for monitoring and debugging
     */
    public function tags()
    {
        return [
            'notifications',
            'expiry',
            'days:' . ($this->daysToExpiry ?? 'all'),
            'batch:' . $this->batchSize
        ];
    }

    /**
     * Static method to dispatch notifications for all standard periods
     */
    public static function dispatchAll($delay = 0)
    {
        $standardPeriods = [90, 60, 30, 7];

        foreach ($standardPeriods as $index => $days) {
            static::dispatch($days)
                ->delay(now()->addMinutes($delay + ($index * 2))); // Stagger by 2 minutes
        }
    }

    /**
     * Static method to dispatch urgent notifications (7 days or less)
     */
    public static function dispatchUrgent()
    {
        static::dispatch(7)->onQueue('urgent');
        static::dispatch(1)->onQueue('urgent')->delay(now()->addMinutes(5));

        // Also check for expired certificates
        static::dispatchExpiredNotifications();
    }

    /**
     * Static method to dispatch notifications for expired certificates
     */
    public static function dispatchExpiredNotifications()
    {
        dispatch(new SendExpiredCertificateNotificationsJob())->onQueue('urgent');
    }
}
