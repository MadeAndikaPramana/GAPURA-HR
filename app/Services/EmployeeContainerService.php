<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeContainerService
{
    /**
     * Initialize a new employee container
     */
    public function initializeContainer(Employee $employee): bool
    {
        try {
            // Skip if container already exists
            if ($this->hasContainer($employee)) {
                Log::info("Container already exists for employee: {$employee->employee_id}");
                return true;
            }

            // Create container directory structure
            $this->createContainerDirectories($employee);

            // Create metadata file
            $this->createMetadataFile($employee);

            // Update employee container fields
            $containerPath = $this->getContainerPath($employee);
            $fileCount = count(Storage::disk('private')->allFiles($containerPath));
            
            $employee->update([
                'container_created_at' => now(),
                'container_status' => 'active',
                'container_file_count' => $fileCount,
                'container_last_updated' => now()
            ]);

            Log::info("Container initialized successfully for employee: {$employee->employee_id}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to initialize container for employee {$employee->employee_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if employee has a container
     */
    public function hasContainer(Employee $employee): bool
    {
        return $employee->container_created_at !== null && 
               Storage::disk('private')->exists($this->getContainerPath($employee));
    }

    /**
     * Create container directory structure
     */
    private function createContainerDirectories(Employee $employee): void
    {
        $basePath = $this->getContainerPath($employee);
        
        $directories = [
            $basePath,
            "{$basePath}/certificates",
            "{$basePath}/background_checks", 
            "{$basePath}/documents",
            "{$basePath}/photos"
        ];

        foreach ($directories as $directory) {
            Storage::disk('private')->makeDirectory($directory);
        }
    }

    /**
     * Create container metadata file
     */
    private function createMetadataFile(Employee $employee): void
    {
        $metadata = [
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->name,
            'container_created' => now()->toISOString(),
            'container_version' => '1.0',
            'total_files' => 0,
            'total_size' => 0,
            'last_updated' => now()->toISOString(),
            'directories' => [
                'certificates' => ['created' => now()->toISOString(), 'file_count' => 0],
                'background_checks' => ['created' => now()->toISOString(), 'file_count' => 0],
                'documents' => ['created' => now()->toISOString(), 'file_count' => 0],
                'photos' => ['created' => now()->toISOString(), 'file_count' => 0]
            ]
        ];

        $metadataPath = $this->getContainerPath($employee) . '/container_metadata.json';
        
        Storage::disk('private')->put(
            $metadataPath,
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get container path for employee
     */
    private function getContainerPath(Employee $employee): string
    {
        return "containers/employee-{$employee->id}";
    }

    /**
     * Initialize containers for multiple employees (bulk operation)
     */
    public function bulkInitializeContainers(array $employeeIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $employees = Employee::whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            try {
                if ($this->hasContainer($employee)) {
                    $results['skipped']++;
                    continue;
                }

                if ($this->initializeContainer($employee)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to initialize container for {$employee->name}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error with {$employee->name}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Initialize containers for all employees without containers
     */
    public function initializeAllMissingContainers(): array
    {
        $employeesWithoutContainers = Employee::whereNull('container_created_at')
            ->orWhere('container_status', '!=', 'active')
            ->get();

        $results = [
            'total_processed' => $employeesWithoutContainers->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($employeesWithoutContainers as $employee) {
            try {
                if ($this->initializeContainer($employee)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to initialize container for {$employee->name}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error with {$employee->name}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Repair/rebuild container for employee
     */
    public function repairContainer(Employee $employee): bool
    {
        try {
            // Delete existing container if it exists
            $containerPath = $this->getContainerPath($employee);
            if (Storage::disk('private')->exists($containerPath)) {
                Storage::disk('private')->deleteDirectory($containerPath);
            }

            // Reset container fields
            $employee->update([
                'container_created_at' => null,
                'container_status' => null,
                'container_file_count' => 0,
                'container_last_updated' => null
            ]);

            // Re-initialize
            return $this->initializeContainer($employee);

        } catch (\Exception $e) {
            Log::error("Failed to repair container for employee {$employee->employee_id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get container statistics
     */
    public function getContainerStatistics(): array
    {
        $totalEmployees = Employee::count();
        $withContainers = Employee::whereNotNull('container_created_at')->count();
        $activeContainers = Employee::where('container_status', 'active')->count();

        return [
            'total_employees' => $totalEmployees,
            'with_containers' => $withContainers,
            'without_containers' => $totalEmployees - $withContainers,
            'active_containers' => $activeContainers,
            'coverage_percentage' => $totalEmployees > 0 ? round(($withContainers / $totalEmployees) * 100, 2) : 0
        ];
    }
}