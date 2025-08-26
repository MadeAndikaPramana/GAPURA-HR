<?php
// app/Console/Commands/UpdateTrainingTypeStatistics.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrainingType;
use App\Services\TrainingTypeAnalyticsService;
use Carbon\Carbon;

class UpdateTrainingTypeStatistics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'training-types:update-statistics
                            {--type-id= : Update specific training type ID}
                            {--force : Force update even if recently updated}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update cached statistics for training types';

    protected $analyticsService;

    public function __construct(TrainingTypeAnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Updating Training Type Statistics...');
        $startTime = microtime(true);

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $specificTypeId = $this->option('type-id');

        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }

        try {
            if ($specificTypeId) {
                $this->updateSpecificTrainingType($specificTypeId, $force, $dryRun);
            } else {
                $this->updateAllTrainingTypes($force, $dryRun);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Statistics update completed in {$duration}s");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error updating statistics: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Update statistics for all training types
     */
    private function updateAllTrainingTypes(bool $force, bool $dryRun): void
    {
        $query = TrainingType::active()->with('statistics');

        if (!$force) {
            // Only update types that haven't been updated in the last hour
            $query->where(function ($q) {
                $q->whereNull('last_analytics_update')
                  ->orWhere('last_analytics_update', '<', Carbon::now()->subHour());
            });
        }

        $trainingTypes = $query->get();

        if ($trainingTypes->isEmpty()) {
            $this->info('✨ All training type statistics are up to date!');
            return;
        }

        $this->info("📊 Found {$trainingTypes->count()} training types to update");

        $progressBar = $this->output->createProgressBar($trainingTypes->count());
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        foreach ($trainingTypes as $trainingType) {
            try {
                if ($dryRun) {
                    $stats = $trainingType->calculateComplianceStatistics();
                    $this->line("\n📋 {$trainingType->name}:");
                    $this->line("   Compliance Rate: {$stats['compliance_rate']}%");
                    $this->line("   Risk Level: {$stats['risk_level']}");
                    $this->line("   Priority Score: {$stats['priority_score']}");
                } else {
                    $trainingType->updateStatistics();
                    $updated++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $errors++;
                $this->error("\n❌ Error updating {$trainingType->name}: {$e->getMessage()}");
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        if (!$dryRun) {
            $this->info("✅ Successfully updated {$updated} training types");
            if ($errors > 0) {
                $this->warn("⚠️  {$errors} errors occurred during update");
            }
        }
    }

    /**
     * Update statistics for a specific training type
     */
    private function updateSpecificTrainingType(int $typeId, bool $force, bool $dryRun): void
    {
        $trainingType = TrainingType::find($typeId);

        if (!$trainingType) {
            $this->error("❌ Training type with ID {$typeId} not found");
            return;
        }

        if (!$force && $trainingType->last_analytics_update &&
            $trainingType->last_analytics_update->diffInHours(now()) < 1) {
            $this->warn("⏱️  Training type '{$trainingType->name}' was updated recently. Use --force to override.");
            return;
        }

        $this->info("🔄 Updating statistics for: {$trainingType->name}");

        if ($dryRun) {
            $stats = $trainingType->calculateComplianceStatistics();
            $costStats = $trainingType->getCostAnalytics();

            $this->table(
                ['Metric', 'Current Value'],
                [
                    ['Total Certificates', $stats['total_certificates']],
                    ['Active Certificates', $stats['active_certificates']],
                    ['Expiring Certificates', $stats['expiring_certificates']],
                    ['Expired Certificates', $stats['expired_certificates']],
                    ['Compliance Rate', $stats['compliance_rate'] . '%'],
                    ['Risk Level', $stats['risk_level']],
                    ['Priority Score', $stats['priority_score']],
                    ['Total Cost YTD', 'Rp ' . number_format($costStats['total_cost'], 0, ',', '.')]
                ]
            );
        } else {
            $trainingType->updateStatistics();
            $this->info("✅ Statistics updated successfully");
        }
    }
}
