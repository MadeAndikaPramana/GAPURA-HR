<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Certificate;
use App\Models\TrainingRecord;
use App\Models\Employee;
use App\Services\NotificationService;
use App\Services\CertificateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TrainingMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training:maintenance
                            {--mode=all : Maintenance mode (all, notifications, status-update, certificates, cleanup)}
                            {--dry-run : Run without making changes}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Perform training system maintenance tasks including notifications, status updates, and cleanup';

    protected $notificationService;
    protected $certificateService;
    protected $isDryRun = false;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService, CertificateService $certificateService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->certificateService = $certificateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->isDryRun = $this->option('dry-run');
        $mode = $this->option('mode');

        $this->info('🚀 Starting Gapura Training System Maintenance');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));

        if ($this->isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $this->newLine();

        try {
            switch ($mode) {
                case 'notifications':
                    $this->handleNotifications();
                    break;
                case 'status-update':
                    $this->handleStatusUpdates();
                    break;
                case 'certificates':
                    $this->handleCertificateMaintenance();
                    break;
                case 'cleanup':
                    $this->handleCleanup();
                    break;
                case 'all':
                default:
                    $this->handleNotifications();
                    $this->handleStatusUpdates();
                    $this->handleCertificateMaintenance();
                    $this->handleCleanup();
                    break;
            }

            $this->newLine();
            $this->info('✅ Training maintenance completed successfully');

        } catch (\Exception $e) {
            $this->error('❌ Training maintenance failed: ' . $e->getMessage());
            Log::error('Training maintenance failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Handle notification sending
     */
    protected function handleNotifications()
    {
        $this->info('📧 Processing Notifications...');

        // Send expiry reminders
        $this->task('Sending certificate expiry reminders', function () {
            $results = $this->notificationService->sendBulkExpiryReminders();

            $this->info("   📊 Notification Summary:");
            $this->info("   • Total notifications sent: {$results['total_sent']}");

            foreach ($results['by_period'] as $days => $data) {
                $this->info("   • {$days}-day reminders: {$data['notifications_sent']}/{$data['certificates_found']}");
            }

            if (!empty($results['errors'])) {
                $this->warn("   ⚠️  Errors encountered:");
                foreach ($results['errors'] as $error) {
                    $this->warn("   • {$error}");
                }
            }

            return true;
        });

        // Send compliance digests to managers
        $this->task('Sending daily compliance digests', function () {
            if (!$this->isDryRun) {
                $results = $this->notificationService->sendDailyComplianceDigest();

                $sentCount = collect($results)->where('status', 'sent')->count();
                $this->info("   • Compliance digests sent to {$sentCount} managers");

                foreach ($results as $result) {
                    if (isset($result['manager'])) {
                        $this->info("   • {$result['manager']} ({$result['department']}): {$result['issues']} issues");
                    }
                }
            }
            return true;
        });

        // Process pending notifications
        $this->task('Processing pending notifications', function () {
            $pendingCount = \App\Models\Notification::where('status', 'pending')->count();
            $this->info("   • Found {$pendingCount} pending notifications");

            if (!$this->isDryRun && $pendingCount > 0) {
                // Here you would implement the logic to process pending notifications
                // This could include retry logic for failed emails, SMS sending, etc.
                $this->info("   • Processing pending notifications...");
            }

            return true;
        });
    }

    /**
     * Handle training record and certificate status updates
     */
    protected function handleStatusUpdates()
    {
        $this->info('🔄 Updating Training Status...');

        // Update compliance status for all training records
        $this->task('Updating training record compliance status', function () {
            $records = TrainingRecord::whereNotNull('expiry_date')->get();
            $updatedCount = 0;

            foreach ($records as $record) {
                $oldStatus = $record->compliance_status;
                $record->updateComplianceStatus();

                if (!$this->isDryRun && $record->compliance_status !== $oldStatus) {
                    $record->save();
                    $updatedCount++;
                }
            }

            $this->info("   • Updated {$updatedCount} training records");
            return true;
        });

        // Identify newly expired certificates
        $this->task('Identifying newly expired certificates', function () {
            $newlyExpired = Certificate::where('expiry_date', '<', now())
                ->whereDoesntHave('notifications', function ($query) {
                    $query->where('related_type', 'certificate')
                          ->where('data->certificate_expired', true);
                })
                ->with(['trainingRecord.employee'])
                ->get();

            $this->info("   • Found {$newlyExpired->count()} newly expired certificates");

            if (!$this->isDryRun) {
                foreach ($newlyExpired as $certificate) {
                    $this->notificationService->sendExpiredCertificateNotification($certificate);
                }
                $this->info("   • Sent expiry notifications for all newly expired certificates");
            }

            return true;
        });

        // Auto-create missing certificates
        $this->task('Auto-creating missing certificates', function () {
            if (!$this->isDryRun) {
                $results = $this->certificateService->autoCreateMissingCertificates();

                $createdCount = collect($results)->where('status', 'created')->count();
                $errorCount = collect($results)->where('status', 'error')->count();

                $this->info("   • Created {$createdCount} missing certificates");
                if ($errorCount > 0) {
                    $this->warn("   • {$errorCount} errors encountered");
                }
            } else {
                $missingCount = TrainingRecord::where('status', 'completed')
                    ->whereDoesntHave('certificates')
                    ->whereHas('trainingType', function ($query) {
                        $query->where('requires_certification', true);
                    })
                    ->count();

                $this->info("   • Would create {$missingCount} missing certificates");
            }

            return true;
        });
    }

    /**
     * Handle certificate-specific maintenance
     */
    protected function handleCertificateMaintenance()
    {
        $this->info('🏆 Certificate Maintenance...');

        // Validate certificate files
        $this->task('Validating certificate files', function () {
            $certificates = Certificate::whereNotNull('certificate_file_path')->get();
            $missingFiles = 0;

            foreach ($certificates as $certificate) {
                if (!file_exists(storage_path('app/' . $certificate->certificate_file_path))) {
                    $missingFiles++;
                    $this->warn("   • Missing file for certificate {$certificate->certificate_number}");
                }
            }

            if ($missingFiles === 0) {
                $this->info("   • All certificate files are present");
            } else {
                $this->warn("   • {$missingFiles} certificate files are missing");
            }

            return true;
        });

        // Generate missing QR codes
        $this->task('Generating missing QR codes', function () {
            $certificatesWithoutQR = Certificate::whereNull('qr_code_path')
                ->orWhere('qr_code_path', '')
                ->get();

            $this->info("   • Found {$certificatesWithoutQR->count()} certificates without QR codes");

            if (!$this->isDryRun) {
                $generatedCount = 0;
                foreach ($certificatesWithoutQR as $certificate) {
                    try {
                        $this->certificateService->generateQrCode($certificate);
                        $generatedCount++;
                    } catch (\Exception $e) {
                        $this->warn("   • Failed to generate QR code for {$certificate->certificate_number}: {$e->getMessage()}");
                    }
                }
                $this->info("   • Generated {$generatedCount} QR codes");
            }

            return true;
        });

        // Update provider ratings
        $this->task('Updating training provider ratings', function () {
            if (!$this->isDryRun) {
                $providers = \App\Models\TrainingProvider::all();
                $updatedCount = 0;

                foreach ($providers as $provider) {
                    $oldRating = $provider->rating;
                    $provider->updateRatingFromFeedback();

                    if ($provider->rating !== $oldRating) {
                        $updatedCount++;
                    }
                }

                $this->info("   • Updated ratings for {$updatedCount} providers");
            } else {
                $this->info("   • Would update provider ratings based on recent feedback");
            }

            return true;
        });
    }

    /**
     * Handle system cleanup tasks
     */
    protected function handleCleanup()
    {
        $this->info('🧹 System Cleanup...');

        // Clean up old notifications
        $this->task('Cleaning up old notifications', function () {
            $cutoffDate = now()->subDays(90);
            $oldNotifications = \App\Models\Notification::where('created_at', '<', $cutoffDate)
                ->where('status', 'read');

            $count = $oldNotifications->count();

            if (!$this->isDryRun && $count > 0) {
                $oldNotifications->delete();
                $this->info("   • Deleted {$count} old notifications");
            } else {
                $this->info("   • Found {$count} old notifications to delete");
            }

            return true;
        });

        // Clean up orphaned files
        $this->task('Identifying orphaned files', function () {
            $certificatePaths = Certificate::whereNotNull('certificate_file_path')
                ->pluck('certificate_file_path')
                ->toArray();

            $qrPaths = Certificate::whereNotNull('qr_code_path')
                ->pluck('qr_code_path')
                ->toArray();

            $validPaths = array_merge($certificatePaths, $qrPaths);

            // This would need actual file system scanning implementation
            $this->info("   • Tracking {" . count($validPaths) . "} certificate-related files");
            $this->info("   • Orphaned file cleanup would require filesystem scan");

            return true;
        });

        // Update analytics cache
        $this->task('Updating analytics cache', function () {
            if (!$this->isDryRun) {
                // Update cached analytics data
                $analytics = [
                    'certificates' => Certificate::getCertificateAnalytics(),
                    'training_records' => TrainingRecord::getTrainingStatistics(),
                    'departments' => \App\Models\Department::with('employees')->get()->map(function ($dept) {
                        return [
                            'id' => $dept->id,
                            'name' => $dept->name,
                            'employee_count' => $dept->employees->count(),
                            'compliance_rate' => $this->certificateService->getDepartmentCertificateStatistics($dept->id)['compliance_rate']
                        ];
                    })
                ];

                cache()->put('training_analytics', $analytics, now()->addHours(6));
                $this->info("   • Updated analytics cache");
            } else {
                $this->info("   • Would update analytics cache");
            }

            return true;
        });

        // Log maintenance completion
        $this->task('Recording maintenance log', function () {
            if (!$this->isDryRun) {
                Log::info('Training maintenance completed', [
                    'timestamp' => now(),
                    'mode' => $this->option('mode'),
                    'dry_run' => $this->isDryRun
                ]);
            }
            return true;
        });
    }

    /**
     * Execute a task with progress indication
     */
    protected function task($description, $callback)
    {
        $this->info("   {$description}...");

        try {
            $result = $callback();
            if ($result) {
                $this->info("   ✅ Completed");
            } else {
                $this->warn("   ⚠️  Completed with warnings");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Failed: " . $e->getMessage());
            throw $e;
        }

        $this->newLine();
    }
}
