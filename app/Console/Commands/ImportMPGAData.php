<?php

namespace App\Console\Commands;

use App\Imports\MPGATrainingImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportMPGAData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mpga:import 
                          {file : Path to the MPGA training Excel file}
                          {--update-existing : Update existing certificates with newer versions}
                          {--create-types : Create certificate types if not found}
                          {--report-only : Generate report without importing}
                          {--output-report= : Output report to file}';

    /**
     * The console command description.
     */
    protected $description = 'Import MPGA training data from Excel file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $updateExisting = $this->option('update-existing');
        $createTypes = $this->option('create-types');
        $reportOnly = $this->option('report-only');
        $outputReport = $this->option('output-report');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Validate file format
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
            $this->error("Invalid file format. Supported formats: xlsx, xls, csv");
            return Command::FAILURE;
        }

        $this->info("MPGA Training Data Import");
        $this->info("========================");
        $this->info("File: {$filePath}");
        $this->info("Update existing: " . ($updateExisting ? 'Yes' : 'No'));
        $this->info("Create certificate types: " . ($createTypes ? 'Yes' : 'No'));
        $this->info("Report only: " . ($reportOnly ? 'Yes' : 'No'));
        $this->line('');

        if (!$reportOnly && !$this->confirm('Do you want to proceed with the import?')) {
            $this->info('Import cancelled.');
            return Command::SUCCESS;
        }

        try {
            // Create import instance
            $import = new MPGATrainingImport($updateExisting, $createTypes);

            // Show progress bar
            $this->info('Processing MPGA training data...');
            
            if ($reportOnly) {
                $this->info('Running in report-only mode...');
            }

            $progressBar = $this->output->createProgressBar();
            $progressBar->start();

            // Import data
            Excel::import($import, $filePath);

            $progressBar->finish();
            $this->line('');

            // Get results
            $results = $import->getImportResults();

            // Display summary
            $this->displaySummary($results);

            // Generate detailed report
            $report = $import->generateReport();
            
            if ($outputReport) {
                $this->saveReport($report, $outputReport);
                $this->info("Detailed report saved to: {$outputReport}");
            } else {
                $this->line('');
                $this->info('Detailed Report:');
                $this->line(str_repeat('-', 50));
                $this->line($report);
            }

            // Return appropriate exit code
            return $results['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Display import summary
     */
    private function displaySummary(array $results): void
    {
        $this->line('');
        $this->info('Import Summary:');
        $this->line(str_repeat('-', 20));
        
        $this->table([
            'Metric',
            'Count'
        ], [
            ['Total Rows', $results['total_rows']],
            ['Successfully Processed', $results['processed']],
            ['Certificates Created', $results['created']],
            ['Certificates Updated', $results['updated']],
            ['Rows Skipped', $results['skipped']],
            ['Errors', $results['errors']],
            ['Employee Matches', $results['employee_matches']],
            ['New Certificate Types', $results['certificate_types_created']]
        ]);

        // Show status
        if ($results['errors'] === 0) {
            $this->info('✅ Import completed successfully!');
        } else {
            $this->warn("⚠️  Import completed with {$results['errors']} errors");
        }

        // Show new certificate types
        if (!empty($results['details']['new_certificate_types'])) {
            $this->line('');
            $this->info('New Certificate Types Created:');
            foreach ($results['details']['new_certificate_types'] as $type) {
                $this->line("• {$type['name']} ({$type['code']})");
            }
        }

        // Show sample errors
        if (!empty($results['details']['errors'])) {
            $this->line('');
            $this->warn('Sample Errors:');
            $errorCount = 0;
            foreach ($results['details']['errors'] as $error) {
                if ($errorCount >= 5) break; // Show only first 5 errors
                $this->line("• Row {$error['row']}: {$error['error']}");
                $errorCount++;
            }
            
            if (count($results['details']['errors']) > 5) {
                $this->line("... and " . (count($results['details']['errors']) - 5) . " more errors");
            }
        }
    }

    /**
     * Save report to file
     */
    private function saveReport(string $report, string $filePath): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        // If no extension provided, add .txt
        if (pathinfo($filePath, PATHINFO_EXTENSION) === '') {
            $filePath .= '.txt';
        }

        // Add timestamp to filename if file exists
        if (file_exists($filePath)) {
            $directory = dirname($filePath);
            $filename = pathinfo($filePath, PATHINFO_FILENAME);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $filePath = "{$directory}/{$filename}_{$timestamp}.{$extension}";
        }

        file_put_contents($filePath, $report);
    }
}