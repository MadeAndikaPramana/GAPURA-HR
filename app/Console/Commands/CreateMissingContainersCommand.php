<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Services\EmployeeContainerService;
use Illuminate\Support\Facades\Storage;

class CreateMissingContainersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'containers:create-missing
                            {--employee= : Specific employee ID to create container for}
                            {--repair : Repair/recreate existing containers}
                            {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing employee containers for all employees without containers';

    /**
     * Container service instance
     */
    private EmployeeContainerService $containerService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->containerService = app(EmployeeContainerService::class);

        $this->info('🗂️  Employee Container Creation Tool');
        $this->info('=====================================');

        if ($this->option('employee')) {
            return $this->createForSpecificEmployee();
        }

        if ($this->option('repair')) {
            return $this->repairAllContainers();
        }

        return $this->createMissingContainers();
    }

    /**
     * Create container for specific employee
     */
    private function createForSpecificEmployee(): int
    {
        $employeeId = $this->option('employee');
        $employee = Employee::where('employee_id', $employeeId)->first();

        if (!$employee) {
            $this->error("❌ Employee with ID '{$employeeId}' not found");
            return 1;
        }

        $this->info("Processing employee: {$employee->name} ({$employee->employee_id})");

        if ($this->containerService->hasContainer($employee)) {
            $this->warn("⚠️  Container already exists");

            if ($this->confirm('Do you want to repair this container?')) {
                return $this->repairContainer($employee);
            }

            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("🔍 Would create container for: {$employee->name}");
            return 0;
        }

        if ($this->containerService->initializeContainer($employee)) {
            $this->info("✅ Container created successfully");
            return 0;
        } else {
            $this->error("❌ Failed to create container");
            return 1;
        }
    }

    /**
     * Create missing containers for all employees
     */
    private function createMissingContainers(): int
    {
        $this->info("🔍 Scanning for employees without containers...");

        $employeesWithoutContainers = Employee::whereNull('container_created_at')
            ->orWhere('container_status', '!=', 'active')
            ->get();

        if ($employeesWithoutContainers->isEmpty()) {
            $this->info("✅ All employees already have containers!");
            return 0;
        }

        $count = $employeesWithoutContainers->count();
        $this->warn("Found {$count} employees without containers");

        if ($this->option('dry-run')) {
            $this->table(
                ['Employee ID', 'Name', 'Department', 'Status'],
                $employeesWithoutContainers->map(function ($employee) {
                    return [
                        $employee->employee_id,
                        $employee->name,
                        $employee->department?->name ?? 'N/A',
                        $employee->container_status ?? 'No Container'
                    ];
                })
            );
            $this->info("🔍 Dry run completed. Use without --dry-run to create containers.");
            return 0;
        }

        if (!$this->confirm("Create containers for {$count} employees?")) {
            $this->info("Operation cancelled");
            return 0;
        }

        $this->info("🚀 Creating containers...");
        $progressBar = $this->output->createProgressBar($count);

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($employeesWithoutContainers as $employee) {
            try {
                if ($this->containerService->initializeContainer($employee)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed for {$employee->name} ({$employee->employee_id})";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error with {$employee->name}: " . $e->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        $this->info("📊 RESULTS:");
        $this->info("✅ Successfully created: {$results['success']} containers");

        if ($results['failed'] > 0) {
            $this->error("❌ Failed: {$results['failed']} containers");

            if (!empty($results['errors'])) {
                $this->newLine();
                $this->error("Errors:");
                foreach ($results['errors'] as $error) {
                    $this->line("  • {$error}");
                }
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }

    /**
     * Repair all existing containers
     */
    private function repairAllContainers(): int
    {
        $this->info("🔧 Repairing all employee containers...");

        $employees = Employee::whereNotNull('container_created_at')->get();
        $count = $employees->count();

        if ($count === 0) {
            $this->info("No containers found to repair");
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info("🔍 Would repair {$count} containers");
            return 0;
        }

        if (!$this->confirm("Repair {$count} containers? This will recreate all containers.")) {
            $this->info("Operation cancelled");
            return 0;
        }

        $progressBar = $this->output->createProgressBar($count);
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($employees as $employee) {
            try {
                if ($this->containerService->repairContainer($employee)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to repair {$employee->name}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error repairing {$employee->name}: " . $e->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("📊 REPAIR RESULTS:");
        $this->info("✅ Successfully repaired: {$results['success']} containers");

        if ($results['failed'] > 0) {
            $this->error("❌ Failed repairs: {$results['failed']} containers");

            foreach ($results['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }

    /**
     * Repair specific container
     */
    private function repairContainer(Employee $employee): int
    {
        $this->info("🔧 Repairing container for {$employee->name}...");

        if ($this->option('dry-run')) {
            $this->info("🔍 Would repair container");
            return 0;
        }

        try {
            if ($this->containerService->repairContainer($employee)) {
                $this->info("✅ Container repaired successfully");
                return 0;
            } else {
                $this->error("❌ Failed to repair container");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error repairing container: " . $e->getMessage());
            return 1;
        }
    }
}
