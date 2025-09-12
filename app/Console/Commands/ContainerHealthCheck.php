<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ContainerHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'containers:health-check 
                          {--repair : Automatically repair issues found}
                          {--employee= : Check specific employee by ID}
                          {--report= : Save report to file}
                          {--format=table : Output format (table, json, csv)}';

    /**
     * The console command description.
     */
    protected $description = 'Check health of employee containers and optionally repair issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $repair = $this->option('repair');
        $employeeId = $this->option('employee');
        $reportFile = $this->option('report');
        $format = $this->option('format');

        $this->info('🔍 Starting Employee Container Health Check');
        $this->info('==========================================');

        // Query employees
        $query = Employee::query();
        
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
            $this->info("Checking specific employee: {$employeeId}");
        } else {
            $this->info('Checking all employee containers...');
        }

        $employees = $query->get();
        
        if ($employees->isEmpty()) {
            $this->error('No employees found matching criteria');
            return Command::FAILURE;
        }

        $results = [
            'total_checked' => 0,
            'healthy' => 0,
            'warnings' => 0,
            'critical' => 0,
            'errors' => 0,
            'repaired' => 0,
            'repair_errors' => 0,
            'details' => []
        ];

        $progressBar = $this->output->createProgressBar($employees->count());
        $progressBar->start();

        foreach ($employees as $employee) {
            $health = $employee->getContainerHealth();
            $results['total_checked']++;
            
            switch ($health['status']) {
                case 'healthy':
                    $results['healthy']++;
                    break;
                case 'warning':
                    $results['warnings']++;
                    break;
                case 'critical':
                    $results['critical']++;
                    break;
                case 'error':
                    $results['errors']++;
                    break;
            }

            // Store details for reporting
            $results['details'][] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'status' => $health['status'],
                'score' => $health['score'],
                'issues' => $health['issues'],
                'warnings' => $health['warnings'],
                'repaired' => false
            ];

            // Attempt repairs if requested and issues found
            if ($repair && ($health['status'] === 'critical' || $health['status'] === 'warning')) {
                try {
                    $repairResults = $employee->repairContainer();
                    if ($repairResults['success']) {
                        $results['repaired']++;
                        $results['details'][count($results['details']) - 1]['repaired'] = true;
                        $results['details'][count($results['details']) - 1]['repairs_made'] = $repairResults['repairs_made'];
                    } else {
                        $results['repair_errors']++;
                        $results['details'][count($results['details']) - 1]['repair_errors'] = $repairResults['errors'];
                    }
                } catch (\Exception $e) {
                    $results['repair_errors']++;
                    $results['details'][count($results['details']) - 1]['repair_errors'] = [$e->getMessage()];
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');

        // Display results
        $this->displayResults($results, $format);

        // Save report if requested
        if ($reportFile) {
            $this->saveReport($results, $reportFile, $format);
        }

        // Return appropriate exit code
        if ($results['errors'] > 0 || $results['critical'] > 0) {
            return Command::FAILURE;
        } elseif ($results['warnings'] > 0) {
            $this->warn('⚠️  Some containers have warnings');
            return Command::SUCCESS;
        }

        $this->info('✅ All containers are healthy');
        return Command::SUCCESS;
    }

    /**
     * Display health check results
     */
    private function displayResults(array $results, string $format): void
    {
        $this->line('');
        $this->info('Health Check Summary:');
        $this->line(str_repeat('=', 20));

        // Summary table
        $this->table([
            'Metric',
            'Count',
            'Percentage'
        ], [
            ['Total Checked', $results['total_checked'], '100%'],
            ['✅ Healthy', $results['healthy'], $this->percentage($results['healthy'], $results['total_checked'])],
            ['⚠️  Warnings', $results['warnings'], $this->percentage($results['warnings'], $results['total_checked'])],
            ['❌ Critical', $results['critical'], $this->percentage($results['critical'], $results['total_checked'])],
            ['💥 Errors', $results['errors'], $this->percentage($results['errors'], $results['total_checked'])],
        ]);

        if ($results['repaired'] > 0) {
            $this->line('');
            $this->info("🔧 Repairs made: {$results['repaired']}");
            if ($results['repair_errors'] > 0) {
                $this->error("❌ Repair failures: {$results['repair_errors']}");
            }
        }

        // Show detailed issues for non-healthy containers
        $problemContainers = array_filter($results['details'], function($detail) {
            return $detail['status'] !== 'healthy';
        });

        if (!empty($problemContainers) && $format === 'table') {
            $this->line('');
            $this->warn('🚨 Containers requiring attention:');
            $this->line('');

            foreach ($problemContainers as $container) {
                $status = $this->getStatusIcon($container['status']);
                $this->line("{$status} {$container['employee_id']} - {$container['name']} (Score: {$container['score']})");
                
                if (!empty($container['issues'])) {
                    foreach ($container['issues'] as $issue) {
                        $this->line("   ❌ {$issue}");
                    }
                }
                
                if (!empty($container['warnings'])) {
                    foreach ($container['warnings'] as $warning) {
                        $this->line("   ⚠️  {$warning}");
                    }
                }

                if (!empty($container['repairs_made'])) {
                    $this->line("   🔧 Repairs made:");
                    foreach ($container['repairs_made'] as $repair) {
                        $this->line("      ✓ {$repair}");
                    }
                }

                $this->line('');
            }
        }
    }

    /**
     * Save report to file
     */
    private function saveReport(array $results, string $filePath, string $format): void
    {
        try {
            $content = '';
            $timestamp = now()->format('Y-m-d H:i:s');

            switch ($format) {
                case 'json':
                    $content = json_encode([
                        'timestamp' => $timestamp,
                        'summary' => array_except($results, ['details']),
                        'details' => $results['details']
                    ], JSON_PRETTY_PRINT);
                    break;

                case 'csv':
                    $content = "Employee ID,Name,Status,Score,Issues,Warnings,Repaired\n";
                    foreach ($results['details'] as $detail) {
                        $issues = implode('; ', $detail['issues']);
                        $warnings = implode('; ', $detail['warnings']);
                        $repaired = $detail['repaired'] ? 'Yes' : 'No';
                        $content .= "{$detail['employee_id']},{$detail['name']},{$detail['status']},{$detail['score']},\"{$issues}\",\"{$warnings}\",{$repaired}\n";
                    }
                    break;

                default: // table/text format
                    $content = "Employee Container Health Check Report\n";
                    $content .= "Generated: {$timestamp}\n";
                    $content .= str_repeat('=', 50) . "\n\n";
                    $content .= "SUMMARY:\n";
                    $content .= "Total Checked: {$results['total_checked']}\n";
                    $content .= "Healthy: {$results['healthy']}\n";
                    $content .= "Warnings: {$results['warnings']}\n";
                    $content .= "Critical: {$results['critical']}\n";
                    $content .= "Errors: {$results['errors']}\n";
                    if ($results['repaired'] > 0) {
                        $content .= "Repaired: {$results['repaired']}\n";
                    }
                    $content .= "\n" . str_repeat('-', 50) . "\n\n";
                    
                    foreach ($results['details'] as $detail) {
                        if ($detail['status'] !== 'healthy') {
                            $content .= "Employee: {$detail['employee_id']} - {$detail['name']}\n";
                            $content .= "Status: {$detail['status']} (Score: {$detail['score']})\n";
                            
                            if (!empty($detail['issues'])) {
                                $content .= "Issues:\n";
                                foreach ($detail['issues'] as $issue) {
                                    $content .= "  - {$issue}\n";
                                }
                            }
                            
                            if (!empty($detail['warnings'])) {
                                $content .= "Warnings:\n";
                                foreach ($detail['warnings'] as $warning) {
                                    $content .= "  - {$warning}\n";
                                }
                            }
                            
                            $content .= "\n";
                        }
                    }
                    break;
            }

            file_put_contents($filePath, $content);
            $this->info("📄 Report saved to: {$filePath}");

        } catch (\Exception $e) {
            $this->error("Failed to save report: {$e->getMessage()}");
        }
    }

    /**
     * Calculate percentage
     */
    private function percentage(int $value, int $total): string
    {
        if ($total === 0) return '0%';
        return round(($value / $total) * 100, 1) . '%';
    }

    /**
     * Get status icon
     */
    private function getStatusIcon(string $status): string
    {
        return match($status) {
            'healthy' => '✅',
            'warning' => '⚠️ ',
            'critical' => '❌',
            'error' => '💥',
            default => '❓'
        };
    }
}