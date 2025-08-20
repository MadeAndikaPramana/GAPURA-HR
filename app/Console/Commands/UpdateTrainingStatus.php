<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrainingStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UpdateTrainingStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training:update-status
                          {--notify : Send notifications for expiring certificates}
                          {--report : Generate detailed status report}
                          {--force : Force update even if recently updated}';

    /**
     * The console command description.
     */
    protected $description = 'Update training record statuses based on expiry dates and send notifications';

    protected $statusService;

    public function __construct(TrainingStatusService $statusService)
    {
        parent::__construct();
        $this->statusService = $statusService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->info('🚀 GAPURA Training Status Update');
        $this->line('=====================================');

        $startTime = microtime(true);

        try {
            // Check if we should run (prevent too frequent updates)
            if (!$this->option('force') && $this->wasRecentlyUpdated()) {
                $this->warn('⚠️  Status was updated recently. Use --force to override.');
                return 0;
            }

            // Update all training statuses
            $this->line('🔄 Updating training statuses...');
            $updatedCount = $this->statusService->updateAllStatuses();

            if ($updatedCount > 0) {
                $this->info("✅ Training statuses updated successfully!");
                $this->line("📊 Records updated: {$updatedCount}");
            } else {
                $this->line("📊 No status changes required");
            }

            // Show current statistics
            $this->showStatistics();

            // Send notifications if requested
            if ($this->option('notify')) {
                $this->sendNotifications();
            }

            // Generate report if requested
            if ($this->option('report')) {
                $this->generateReport();
            }

            // Log successful execution
            $this->logExecution($updatedCount);

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->line('');
            $this->info("⏱️  Execution completed in {$executionTime}s");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error updating training statuses: " . $e->getMessage());
            $this->line("🔍 Check logs for detailed error information");

            Log::error('Training status update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options(),
                'timestamp' => Carbon::now()->toISOString()
            ]);

            return 1;
        }
    }

    /**
     * Check if status was updated recently (within 1 hour)
     */
    protected function wasRecentlyUpdated()
    {
        $cacheKey = 'training_status_last_updated';
        $lastUpdated = Cache::get($cacheKey);

        if ($lastUpdated) {
            $lastUpdatedTime = Carbon::parse($lastUpdated);
            return $lastUpdatedTime->diffInMinutes(Carbon::now()) < 60;
        }

        return false;
    }

    /**
     * Log execution to cache
     */
    protected function logExecution($updatedCount)
    {
        $cacheKey = 'training_status_last_updated';
        Cache::put($cacheKey, Carbon::now()->toISOString(), 3600); // Cache for 1 hour

        // Also log execution stats
        $statsKey = 'training_status_execution_stats';
        $stats = Cache::get($statsKey, []);
        $stats[] = [
            'timestamp' => Carbon::now()->toISOString(),
            'updated_count' => $updatedCount,
            'options' => $this->options()
        ];

        // Keep only last 10 executions
        $stats = array_slice($stats, -10);
        Cache::put($statsKey, $stats, 86400); // Cache for 24 hours
    }

    /**
     * Show current training statistics
     */
    protected function showStatistics()
    {
        $stats = $this->statusService->getDashboardStats();

        $this->line('');
        $this->line('<comment>📈 Current Training Statistics</comment>');
        $this->line('──────────────────────────────');

        // Main stats
        $this->line("👥 Total Employees: <info>{$stats['total_employees']}</info>");
        $this->line("📚 Total Training Records: <info>{$stats['total_trainings']}</info>");
        $this->line("✅ Active Certificates: <info>{$stats['active_certificates']}</info>");
        $this->line("⚠️  Expiring Soon: <info>{$stats['expiring_soon']}</info>");
        $this->line("❌ Expired: <info>{$stats['expired']}</info>");

        // Compliance rate
        if ($stats['total_trainings'] > 0) {
            $complianceRate = round(($stats['active_certificates'] / $stats['total_trainings']) * 100, 2);
            $indicator = $complianceRate >= 90 ? '🟢' : ($complianceRate >= 70 ? '🟡' : '🔴');
            $this->line("📊 Overall Compliance: {$indicator} <info>{$complianceRate}%</info>");
        }

        // Department summary (top 5)
        if ($stats['compliance_by_department']->count() > 0) {
            $this->line('');
            $this->line('<comment>🏢 Top Departments by Compliance</comment>');
            $this->line('──────────────────────────────');

            $topDepts = $stats['compliance_by_department']->take(5);
            foreach ($topDepts as $dept) {
                $rate = $dept->compliance_rate ?: 0;
                $indicator = $rate >= 90 ? '🟢' : ($rate >= 70 ? '🟡' : '🔴');
                $this->line("{$indicator} {$dept->department_name}: <info>{$rate}%</info>");
            }
        }
    }

    /**
     * Send notifications for expiring certificates
     */
    protected function sendNotifications()
    {
        $this->line('');
        $this->line('<comment>📧 Processing Notifications</comment>');
        $this->line('──────────────────────────────');

        // Get expiring records
        $expiringRecords = $this->statusService->getExpiringSoon(30);
        $criticalRecords = $this->statusService->getExpiringSoon(7);

        if ($expiringRecords->count() > 0) {
            $this->line("📋 Found <info>{$expiringRecords->count()}</info> certificates expiring in 30 days");

            if ($criticalRecords->count() > 0) {
                $this->warn("⚠️  <options=bold>{$criticalRecords->count()} certificates expire within 7 days</options=bold>");

                // Show critical ones
                foreach ($criticalRecords->take(5) as $record) {
                    $daysLeft = Carbon::parse($record->expiry_date)->diffInDays(Carbon::now());
                    $this->line("  🔸 {$record->employee->name} - {$record->trainingType->name} (<comment>{$daysLeft} days</comment>)");
                }

                if ($criticalRecords->count() > 5) {
                    $remaining = $criticalRecords->count() - 5;
                    $this->line("  ... and <info>{$remaining}</info> more");
                }
            }

            // Send email notifications
            $this->sendEmailNotifications($expiringRecords, $criticalRecords);

        } else {
            $this->line('✅ No certificates expiring in the next 30 days');
        }
    }

    /**
     * Send email notifications
     */
    protected function sendEmailNotifications($expiringRecords, $criticalRecords)
    {
        // Group by department for better organization
        $departmentGroups = $expiringRecords->groupBy(function($record) {
            return $record->employee->department->name ?? 'No Department';
        });

        $this->line('');
        $this->line('📨 Notification Summary:');

        foreach ($departmentGroups as $department => $records) {
            $this->line("  📧 {$department}: {$records->count()} notifications");
        }

        // Log notification details
        Log::info('Training expiry notifications processed', [
            'total_expiring' => $expiringRecords->count(),
            'critical_expiring' => $criticalRecords->count(),
            'by_department' => $departmentGroups->map->count()->toArray(),
            'timestamp' => Carbon::now()->toISOString()
        ]);

        $this->comment('📬 Notifications logged for HR team review');
    }

    /**
     * Generate detailed report
     */
    protected function generateReport()
    {
        $this->line('');
        $this->line('<comment>📋 Generating Detailed Report</comment>');
        $this->line('──────────────────────────────');

        $report = $this->statusService->generateComplianceReport();

        // Department compliance details
        if (!empty($report['department_details'])) {
            $this->line('');
            $this->line('<comment>🏢 Department Compliance Details</comment>');
            $this->line('──────────────────────────────');

            foreach ($report['department_details'] as $dept) {
                $rate = $dept['compliance_rate'];
                $indicator = $rate >= 90 ? '🟢' : ($rate >= 70 ? '🟡' : '🔴');
                $status = strtoupper($dept['status']);

                $this->line("{$indicator} <info>{$dept['department']}</info>");
                $this->line("     Employees: {$dept['total_employees']} | Certificates: {$dept['total_certificates']} | Active: {$dept['active_certificates']} | Rate: {$rate}% ({$status})");
            }
        }

        // Training type compliance
        if (!empty($report['training_type_details'])) {
            $this->line('');
            $this->line('<comment>📚 Training Type Compliance</comment>');
            $this->line('──────────────────────────────');

            foreach ($report['training_type_details'] as $type) {
                $rate = $type['active_percentage'];
                $indicator = $rate >= 80 ? '🟢' : ($rate >= 60 ? '🟡' : '🔴');

                $this->line("{$indicator} <info>{$type['training_type']}</info> ({$type['category']})");
                $this->line("     Total: {$type['total_records']} | Active: {$type['active_count']} | Expiring: {$type['expiring_count']} | Expired: {$type['expired_count']} | Rate: {$rate}%");
            }
        }

        // Critical alerts
        if (!empty($report['critical_alerts'])) {
            $this->line('');
            $this->line('<comment>🚨 Critical Alerts</comment>');
            $this->line('──────────────────────────────');

            foreach ($report['critical_alerts'] as $alert) {
                $icon = $alert['type'] === 'expired' ? '❌' : '⚠️';
                $this->line("{$icon} {$alert['message']}");
            }
        }

        // Save report to log
        Log::info('Training compliance report generated', $report);

        $this->comment('📄 Detailed report saved to logs');
    }
}
