<?php

// app/Console/Commands/SetupPhase3.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\TrainingType;
use App\Services\TrainingTypeAnalyticsService;

class SetupPhase3 extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mpga:setup-phase3
                            {--fresh : Fresh setup with new data}
                            {--update-analytics : Update analytics after setup}';

    /**
     * The console command description.
     */
    protected $description = 'Setup MPGA Training Type Management System (Phase 3)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        $fresh = $this->option('fresh');
        $updateAnalytics = $this->option('update-analytics');

        try {
            if ($fresh) {
                $this->info('🔄 Running fresh Phase 3 setup...');

                // Run migrations
                $this->info('📂 Running Phase 3 migrations...');
                Artisan::call('migrate', ['--force' => true]);

                // Seed training types and providers
                $this->info('🌱 Seeding training types and providers...');
                Artisan::call('db:seed', ['--class' => 'TrainingTypeSeeder']);
            }

            // Update analytics for all training types
            if ($updateAnalytics || $fresh) {
                $this->info('📊 Updating training type analytics...');
                Artisan::call('training-types:update-statistics', ['--force' => true]);
            }

            // Display system summary
            $this->displaySystemSummary();

            $this->info('✅ Phase 3 setup completed successfully!');
            $this->displayNextSteps();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Phase 3 setup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayHeader(): void
    {
        $this->info('');
        $this->info('🚀 ========================================');
        $this->info('   MPGA TRAINING TYPE MANAGEMENT');
        $this->info('   Phase 3 Setup and Configuration');
        $this->info('🚀 ========================================');
        $this->newLine();
    }

    private function displaySystemSummary(): void
    {
        $this->info('📋 PHASE 3 SYSTEM SUMMARY');
        $this->info('─────────────────────────');

        // Training Types Statistics
        $totalTypes = TrainingType::count();
        $mandatoryTypes = TrainingType::where('is_mandatory', true)->count();
        $activeTypes = TrainingType::where('is_active', true)->count();

        $stats = [
            'Total Training Types' => $totalTypes,
            'Mandatory Types' => $mandatoryTypes,
            'Active Types' => $activeTypes,
            'Categories' => TrainingType::distinct('category')->count('category')
        ];

        foreach ($stats as $label => $value) {
            $this->line(sprintf('   %-25s: %s', $label, number_format($value)));
        }

        $this->newLine();

        // Category breakdown
        $this->info('📂 Training Categories:');
        $categories = TrainingType::select('category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($categories as $category) {
            $this->line(sprintf('   %-25s: %d types', $category->category, $category->count));
        }
    }

    private function displayNextSteps(): void
    {
        $this->newLine();
        $this->info('🎯 NEXT STEPS:');
        $this->newLine();

        $steps = [
            '1. Access Training Types Dashboard' => [
                '   • Navigate to /training-types in your application',
                '   • Review training type configurations',
                '   • Check compliance analytics and statistics'
            ],
            '2. Configure Department Requirements' => [
                '   • Review department-specific training requirements',
                '   • Adjust compliance target percentages as needed',
                '   • Add any missing training types for your organization'
            ],
            '3. Set Up Automated Analytics' => [
                '   • Schedule `training-types:update-statistics` command',
                '   • Configure notifications for compliance alerts',
                '   • Set up regular compliance reporting'
            ],
            '4. Phase 4 Preparation' => [
                '   • Review certificate management requirements',
                '   • Plan detailed certificate lifecycle tracking',
                '   • Prepare for advanced reporting features'
            ]
        ];

        foreach ($steps as $category => $items) {
            $this->comment($category);
            foreach ($items as $item) {
                $this->line($item);
            }
            $this->newLine();
        }

        $this->info('🚀 Phase 3 Training Type Management is ready for production use!');
    }
}
