<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TrainingStatusService;
use App\Models\TrainingRecord;
use Carbon\Carbon;

class UpdateTrainingStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training:update-status
                            {--dry-run : Show what would be updated without making changes}
                            {--days= : Number of days to check for expiring records (default: 30)}
                            {--force : Force update even if already run today}';

    /**
     * The console command description.
     */
    protected $description = 'Update training record statuses based on expiry dates';

    /**
     * Training status service instance.
     */
    protected TrainingStatusService $statusService;

    /**
     * Create a new command instance.
     */
    public function __construct(TrainingStatusService $statusService)
    {
        parent::__construct();
        $this->statusService = $statusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting Training Status Update Process');
        $this->info('═══════════════════════════════════════════');

        $startTime = microtime(true);
        $dryRun = $this->option('dry-run');
        $warningDays = (int) $this->option('days') ?: 30;
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }

        // Check if already run today (unless forced)
        if (!$force && $this->wasRunToday()) {
            $this->warn('⚠️  Status update already run today. Use --force to override.');
            return Command::SUCCESS;
        }

        try {
            // Get current statistics
            $this->showCurrentStatistics();

            if ($dryRun) {
                $this->performDryRun($warningDays);
            } else {
                $this->performActualUpdate();
            }

            // Show final statistics
            $this->newLine();
            $this->showCurrentStatistics();

            // Show records requiring attention
            $this->showRecordsRequiringAction($warningDays);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->info("✅ Process completed in {$duration} seconds");

            if (!$dryRun) {
                $this->recordExecution();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error occurred: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Show current training statistics
     */
    private function showCurrentStatistics(): void
    {
        $stats = $this->statusService->getComplianceStatistics();

        $this->newLine();
        $this->info('📊 Current Training Statistics');
        $this->line('─────────────────────────────');

        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Certificates', $stats['total_certificates'], '100%'],
                ['✅ Active', $stats['active_certificates'], $stats['compliance_rate'] . '%'],
                ['⚠️  Expiring Soon', $stats['expiring_certificates'], $stats['expiring_rate'] . '%'],
                ['❌ Expired', $stats['expired_certificates'], $stats['expired_rate'] . '%'],
            ]
        );
    }

    /**
     * Perform dry run to show what would be updated
     */
    private function performDryRun(int $warningDays): void
    {
        $this->newLine();
        $this->info('🧪 DRY RUN - Analyzing records that would be updated');
        $this->line('─────────────────────────────────────────────────');

        $records = TrainingRecord::whereNotNull('expiry_date')->get();
        $changes = [];

        foreach ($records as $record) {
            $currentStatus = $record->status;
            $newStatus = $this->statusService->calculateStatus($record->expiry_date);

            if ($currentStatus !== $newStatus) {
                $changes[] = [
                    'certificate' => $record->certificate_number,
                    'employee' => $record->employee?->name ?? 'Unknown',
                    'training' => $record->trainingType?->name ?? 'Unknown',
                    'from_status' => $currentStatus,
                    'to_status' => $newStatus,
                    'expiry_date' => $record->expiry_date,
                    'days_until_expiry' => Carbon::parse($record->expiry_date)->diffInDays(Carbon::today(), false)
                ];
            }
        }

        if (empty($changes)) {
            $this->info('✨ No status changes needed - all records are up to date!');
        } else {
            $this->warn("📋 Found " . count($changes) . " records that would be updated:");

            $tableData = array_map(function ($change) {
                return [
                    substr($change['certificate'], 0, 20) . '...',
                    substr($change['employee'], 0, 15),
                    $change['from_status'],
                    $change['to_status'],
                    $change['expiry_date'],
                    $change['days_until_expiry'] >= 0 ? $change['days_until_expiry'] . ' days' : 'Expired ' . abs($change['days_until_expiry']) . ' days ago'
                ];
            }, array_slice($changes, 0, 10)); // Show first 10

            $this->table(
                ['Certificate', 'Employee', 'From', 'To', 'Expiry', 'Status'],
                $tableData
            );

            if (count($changes) > 10) {
                $this->info("... and " . (count($changes) - 10) . " more records");
            }
        }
    }

    /**
     * Perform actual status update
     */
    private function performActualUpdate(): void
    {
        $this->newLine();
        $this->info('🔄 Updating training record statuses...');

        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();

        $results = $this->statusService->updateAllStatuses();

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('✅ Status Update Results');
        $this->line('─────────────────────────');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $results['total_processed']],
                ['Records Updated', $results['updated_count']],
                ['Errors', $results['errors']],
                ['Changed to Active', $results['status_changes']['to_active']],
                ['Changed to Expiring Soon', $results['status_changes']['to_expiring_soon']],
                ['Changed to Expired', $results['status_changes']['to_expired']],
            ]
        );

        if ($results['errors'] > 0) {
            $this->warn("⚠️  {$results['errors']} errors occurred during update. Check logs for details.");
        }
    }

    /**
     * Show records requiring immediate attention
     */
    private function showRecordsRequiringAction(int $warningDays): void
    {
        $this->newLine();
        $this->info('🚨 Records Requiring Attention');
        $this->line('─────────────────────────────');

        $records = $this->statusService->getRecordsRequiringAction($warningDays);

        if ($records['total_requiring_action'] === 0) {
            $this->info('✨ No records require immediate attention!');
            return;
        }

        // Show expired records
        if ($records['expired']->isNotEmpty()) {
            $this->error("❌ {$records['expired']->count()} EXPIRED certificates:");

            $expiredData = $records['expired']->take(5)->map(function ($record) {
                return [
                    substr($record->certificate_number, 0, 20),
                    substr($record->employee?->name ?? 'Unknown', 0, 15),
                    substr($record->trainingType?->name ?? 'Unknown', 0, 20),
                    $record->expiry_date,
                    Carbon::parse($record->expiry_date)->diffInDays(Carbon::today(), false) . ' days ago'
                ];
            })->toArray();

            $this->table(
                ['Certificate', 'Employee', 'Training', 'Expired', 'Days Ago'],
                $expiredData
            );

            if ($records['expired']->count() > 5) {
                $this->line("... and " . ($records['expired']->count() - 5) . " more expired certificates");
            }
        }

        // Show expiring records
        if ($records['expiring_soon']->isNotEmpty()) {
            $this->warn("⚠️  {$records['expiring_soon']->count()} certificates expiring within {$warningDays} days:");

            $expiringData = $records['expiring_soon']->take(5)->map(function ($record) {
                return [
                    substr($record->certificate_number, 0, 20),
                    substr($record->employee?->name ?? 'Unknown', 0, 15),
                    substr($record->trainingType?->name ?? 'Unknown', 0, 20),
                    $record->expiry_date,
                    Carbon::today()->diffInDays(Carbon::parse($record->expiry_date), false) . ' days'
                ];
            })->toArray();

            $this->table(
                ['Certificate', 'Employee', 'Training', 'Expires', 'In Days'],
                $expiringData
            );

            if ($records['expiring_soon']->count() > 5) {
                $this->line("... and " . ($records['expiring_soon']->count() - 5) . " more expiring certificates");
            }
        }

        $this->newLine();
        $this->info("💡 Tip: Use 'php artisan training:report-expiry' for detailed expiry reports");
    }

    /**
     * Check if command was already run today
     */
    private function wasRunToday(): bool
    {
        $cacheKey = 'training_status_update_' . Carbon::today()->format('Y-m-d');
        return cache()->has($cacheKey);
    }

    /**
     * Record command execution
     */
    private function recordExecution(): void
    {
        $cacheKey = 'training_status_update_' . Carbon::today()->format('Y-m-d');
        cache()->put($cacheKey, [
            'executed_at' => Carbon::now(),
            'executed_by' => 'console'
        ], Carbon::tomorrow());
    }
}
