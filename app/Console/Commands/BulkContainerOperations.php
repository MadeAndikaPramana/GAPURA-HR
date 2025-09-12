<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BulkContainerOperations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'containers:bulk-operations 
                          {action : Action to perform (create, repair, migrate, cleanup)}
                          {--batch-size=50 : Number of employees to process per batch}
                          {--dry-run : Preview operations without executing}
                          {--department= : Filter by department ID}
                          {--status= : Filter by employee status (active, inactive)}
                          {--force : Force operations even if containers exist}';

    /**
     * The console command description.
     */
    protected $description = 'Perform bulk operations on employee containers efficiently';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');
        $department = $this->option('department');
        $status = $this->option('status');
        $force = $this->option('force');

        $this->info("🚀 Starting Bulk Container Operations: {$action}");
        $this->info('============================================');

        // Build query
        $query = Employee::query();
        
        if ($department) {
            $query->where('department_id', $department);
            $this->info("Filtering by department: {$department}");
        }
        
        if ($status) {
            $query->where('status', $status);
            $this->info("Filtering by status: {$status}");
        }

        // Add action-specific filters
        switch ($action) {
            case 'create':
                if (!$force) {
                    $query->whereNull('container_created_at');
                }
                break;
            case 'repair':
                $query->withContainerIssues();
                break;
        }

        $totalEmployees = $query->count();
        
        if ($totalEmployees === 0) {
            $this->info('No employees found matching criteria');
            return Command::SUCCESS;
        }

        $this->info("Total employees to process: {$totalEmployees}");
        $this->info("Batch size: {$batchSize}");
        
        if ($dryRun) {
            $this->warn('🏃 DRY RUN MODE - No changes will be made');
        }

        if (!$dryRun && !$this->confirm("Proceed with {$action} operation?")) {
            $this->info('Operation cancelled');
            return Command::SUCCESS;
        }

        // Process in batches
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($totalEmployees);
        $progressBar->start();

        $query->chunk($batchSize, function ($employees) use (
            $action, $dryRun, &$processed, &$successful, &$failed, &$skipped, $progressBar
        ) {
            foreach ($employees as $employee) {
                try {
                    $result = $this->processEmployee($employee, $action, $dryRun);
                    
                    switch ($result['status']) {
                        case 'success':
                            $successful++;
                            break;
                        case 'failed':
                            $failed++;
                            Log::error("Bulk container operation failed", [
                                'employee_id' => $employee->employee_id,
                                'action' => $action,
                                'error' => $result['message']
                            ]);
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }
                    
                    $processed++;
                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    $failed++;
                    $progressBar->advance();
                    Log::error("Exception during bulk container operation", [
                        'employee_id' => $employee->employee_id,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Small delay to prevent overwhelming the system
            usleep(100000); // 0.1 seconds
        });

        $progressBar->finish();
        $this->line('');

        // Display results
        $this->displayResults($action, $processed, $successful, $failed, $skipped, $dryRun);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process individual employee based on action
     */
    private function processEmployee(Employee $employee, string $action, bool $dryRun): array
    {
        switch ($action) {
            case 'create':
                return $this->createContainer($employee, $dryRun);
                
            case 'repair':
                return $this->repairContainer($employee, $dryRun);
                
            case 'migrate':
                return $this->migrateContainer($employee, $dryRun);
                
            case 'cleanup':
                return $this->cleanupContainer($employee, $dryRun);
                
            default:
                return ['status' => 'failed', 'message' => 'Unknown action'];
        }
    }

    /**
     * Create container for employee
     */
    private function createContainer(Employee $employee, bool $dryRun): array
    {
        if ($employee->container_created_at && !$this->option('force')) {
            return ['status' => 'skipped', 'message' => 'Container already exists'];
        }

        if ($dryRun) {
            return ['status' => 'success', 'message' => 'Would create container'];
        }

        try {
            $containerPath = $employee->getContainerFolderPath();
            
            // Create directory structure
            $directories = [
                "{$containerPath}/certificates",
                "{$containerPath}/background_checks", 
                "{$containerPath}/documents",
                "{$containerPath}/photos"
            ];

            foreach ($directories as $dir) {
                Storage::disk('private')->makeDirectory($dir);
            }

            // Create metadata
            $this->createContainerMetadata($employee, $containerPath);

            // Update employee record
            $employee->updateQuietly([
                'container_created_at' => now(),
                'container_status' => 'active',
                'container_file_count' => 0,
                'container_last_updated' => now()
            ]);

            return ['status' => 'success', 'message' => 'Container created'];

        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Repair container for employee
     */
    private function repairContainer(Employee $employee, bool $dryRun): array
    {
        $health = $employee->getContainerHealth();
        
        if ($health['status'] === 'healthy') {
            return ['status' => 'skipped', 'message' => 'Container is healthy'];
        }

        if ($dryRun) {
            $issueCount = count($health['issues']) + count($health['warnings']);
            return ['status' => 'success', 'message' => "Would repair {$issueCount} issues"];
        }

        try {
            $repairResults = $employee->repairContainer();
            
            if ($repairResults['success']) {
                $repairCount = count($repairResults['repairs_made']);
                return ['status' => 'success', 'message' => "Repaired {$repairCount} issues"];
            } else {
                return ['status' => 'failed', 'message' => implode(', ', $repairResults['errors'])];
            }

        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Migrate container to new structure
     */
    private function migrateContainer(Employee $employee, bool $dryRun): array
    {
        $containerPath = $employee->getContainerFolderPath();
        
        if (!Storage::disk('private')->exists($containerPath)) {
            return ['status' => 'skipped', 'message' => 'No container to migrate'];
        }

        if ($dryRun) {
            return ['status' => 'success', 'message' => 'Would migrate container structure'];
        }

        try {
            // Add any migration logic here for container structure updates
            // For now, just update metadata to latest version
            $metadataPath = "{$containerPath}/container_metadata.json";
            
            if (Storage::disk('private')->exists($metadataPath)) {
                $metadata = json_decode(Storage::disk('private')->get($metadataPath), true);
                $metadata['container_version'] = '1.1';
                $metadata['migrated_at'] = now()->toISOString();
                $metadata['last_updated'] = now()->toISOString();
                
                Storage::disk('private')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
            }

            return ['status' => 'success', 'message' => 'Container migrated'];

        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Cleanup container orphaned files
     */
    private function cleanupContainer(Employee $employee, bool $dryRun): array
    {
        $containerPath = $employee->getContainerFolderPath();
        
        if (!Storage::disk('private')->exists($containerPath)) {
            return ['status' => 'skipped', 'message' => 'No container to cleanup'];
        }

        if ($dryRun) {
            $fileCount = count(Storage::disk('private')->allFiles($containerPath));
            return ['status' => 'success', 'message' => "Would analyze {$fileCount} files"];
        }

        try {
            $cleaned = 0;
            
            // Remove empty directories
            $directories = Storage::disk('private')->allDirectories($containerPath);
            foreach ($directories as $dir) {
                $files = Storage::disk('private')->allFiles($dir);
                if (empty($files)) {
                    Storage::disk('private')->deleteDirectory($dir);
                    $cleaned++;
                }
            }

            // Update file count
            $actualFileCount = count(Storage::disk('private')->allFiles($containerPath));
            $employee->updateQuietly(['container_file_count' => $actualFileCount]);

            return ['status' => 'success', 'message' => "Cleaned {$cleaned} items"];

        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    /**
     * Create container metadata
     */
    private function createContainerMetadata(Employee $employee, string $containerPath): void
    {
        $metadata = [
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->name,
            'container_created' => now()->toISOString(),
            'container_version' => '1.1',
            'total_files' => 0,
            'total_size' => 0,
            'last_updated' => now()->toISOString(),
            'directories' => [
                'certificates' => ['created' => now()->toISOString(), 'file_count' => 0],
                'background_checks' => ['created' => now()->toISOString(), 'file_count' => 0],
                'documents' => ['created' => now()->toISOString(), 'file_count' => 0],
                'photos' => ['created' => now()->toISOString(), 'file_count' => 0]
            ],
            'bulk_created' => true
        ];

        Storage::disk('private')->put(
            "{$containerPath}/container_metadata.json",
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Display operation results
     */
    private function displayResults(string $action, int $processed, int $successful, int $failed, int $skipped, bool $dryRun): void
    {
        $this->line('');
        $this->info('🎯 Bulk Container Operation Results:');
        $this->line(str_repeat('=', 35));

        $mode = $dryRun ? ' (DRY RUN)' : '';
        $this->table([
            'Metric',
            'Count'
        ], [
            ['Action', ucfirst($action) . $mode],
            ['📊 Total Processed', $processed],
            ['✅ Successful', $successful],
            ['❌ Failed', $failed],
            ['⏭️  Skipped', $skipped],
            ['📈 Success Rate', $processed > 0 ? round(($successful / $processed) * 100, 1) . '%' : '0%']
        ]);

        if ($successful > 0) {
            $this->info("✨ {$successful} containers processed successfully!");
        }
        
        if ($failed > 0) {
            $this->error("❌ {$failed} operations failed. Check logs for details.");
        }

        if (!$dryRun && $successful > 0) {
            $this->line('');
            $this->info('💡 Tip: Run "php artisan containers:health-check" to verify all containers are healthy.');
        }
    }
}