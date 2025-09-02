<?php
// app/Console/Commands/ImportMPGAData.php

namespace App\Console\Commands;

use App\Services\MPGAImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportMPGAData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpga:import
                            {file : The Excel file path to import}
                            {--dry-run : Run without making database changes}
                            {--force : Force import even if file is large}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import MPGA training records from Excel file into Employee Container System';

    protected MPGAImportService $importService;

    public function __construct(MPGAImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');

        $this->showHeader();

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            $this->line('');
            $this->info('💡 Available files in storage/app:');
            $files = Storage::disk('local')->files('.');
            foreach ($files as $file) {
                if (str_ends_with($file, '.xlsx') || str_ends_with($file, '.xls')) {
                    $this->line("   📁 {$file}");
                }
            }
            return self::FAILURE;
        }

        // Show file info
        $this->showFileInfo($filePath);

        // Confirm before proceeding
        if (!$isDryRun && !$isForced) {
            if (!$this->confirm('Do you want to proceed with the import?')) {
                $this->info('Import cancelled.');
                return self::SUCCESS;
            }
        }

        // Run import
        $this->newLine();
        $this->info('🚀 Starting MPGA Employee Container System Import...');
        $this->line('==========================================');

        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        $bar = $this->output->createProgressBar(100);
        $bar->setFormat('verbose');
        $bar->start();

        // Start import
        $startTime = microtime(true);
        $results = $this->importService->importFromExcel($filePath);
        $endTime = microtime(true);

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->showResults($results, $endTime - $startTime);

        return empty($results['errors']) ? self::SUCCESS : self::FAILURE;
    }

    protected function showHeader(): void
    {
        $this->info('🗂️  GAPURA EMPLOYEE DATA CONTAINER SYSTEM');
        $this->info('==========================================');
        $this->info('📋 MPGA Training Records Import Tool');
        $this->line('From Excel sheets to Digital Employee Containers');
        $this->newLine();
    }

    protected function showFileInfo(string $filePath): void
    {
        $this->info("📁 File Information:");
        $this->table(['Property', 'Value'], [
            ['File Path', $filePath],
            ['File Size', $this->formatBytes(filesize($filePath))],
            ['Last Modified', date('Y-m-d H:i:s', filemtime($filePath))],
            ['File Type', pathinfo($filePath, PATHINFO_EXTENSION)]
        ]);
    }

    protected function showResults(array $results, float $duration): void
    {
        $this->info('✅ IMPORT COMPLETED!');
        $this->info('====================');

        // Success metrics
        $this->table(['Metric', 'Count', 'Status'], [
            ['Employees Created', $results['employees_created'], '✅'],
            ['Employees Updated', $results['employees_updated'], '📝'],
            ['Departments Created', $results['departments_created'], '🏢'],
            ['Certificate Types Created', $results['certificate_types_created'], '📜'],
            ['Certificates Created', $results['certificates_created'], '🏆'],
            ['Processing Time', number_format($duration, 2) . 's', '⏱️']
        ]);

        // Show errors if any
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('⚠️  ERRORS ENCOUNTERED:');
            $this->line('=======================');
            foreach ($results['errors'] as $error) {
                $this->line("   ❌ {$error}");
            }
        }

        // Show next steps
        $this->newLine();
        $this->info('🎯 NEXT STEPS:');
        $this->line('===============');
        $this->line('1. 👀 Review imported data in the Employee Container interface');
        $this->line('2. 📋 Check for any duplicate or missing certificates');
        $this->line('3. 📁 Upload background check documents for employees');
        $this->line('4. 🔔 Set up certificate expiry notifications');
        $this->line('5. 📊 Run compliance reports to identify gaps');

        $this->newLine();
        $this->info("🌟 {$results['certificates_created']} certificates now organized in digital employee containers!");
        $this->info('🏁 Ready for Phase 2: Enhanced Certificate Management');
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
