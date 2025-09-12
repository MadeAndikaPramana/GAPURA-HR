<?php

namespace App\Observers;

use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        $this->createEmployeeContainer($employee);
        $this->logContainerActivity('created', $employee);
    }

    /**
     * Handle the Employee "updating" event.
     */
    public function updating(Employee $employee): void
    {
        // Check if employee_id (NIP) is being changed
        if ($employee->isDirty('employee_id')) {
            $this->handleEmployeeIdChange($employee);
        }
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        $this->updateContainerMetadata($employee);
        
        if ($employee->wasChanged('employee_id')) {
            $this->logContainerActivity('nip_changed', $employee, [
                'old_nip' => $employee->getOriginal('employee_id'),
                'new_nip' => $employee->employee_id
            ]);
        }
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        $this->archiveEmployeeContainer($employee);
        $this->logContainerActivity('deleted', $employee);
    }

    /**
     * Create employee container directory structure and initialize metadata
     */
    private function createEmployeeContainer(Employee $employee): void
    {
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

            // Create container metadata file
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

            Storage::disk('private')->put(
                "{$containerPath}/container_metadata.json",
                json_encode($metadata, JSON_PRETTY_PRINT)
            );

            // Update employee record with container metadata
            $employee->updateQuietly([
                'container_created_at' => now(),
                'container_status' => 'active',
                'container_file_count' => 0,
                'container_last_updated' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create employee container', [
                'employee_id' => $employee->employee_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle employee ID (NIP) changes while maintaining container integrity
     */
    private function handleEmployeeIdChange(Employee $employee): void
    {
        $oldNip = $employee->getOriginal('employee_id');
        $newNip = $employee->employee_id;

        if (!$oldNip || !$newNip) {
            return;
        }

        try {
            $oldPath = "employees/{$oldNip}";
            $newPath = "employees/{$newNip}";

            // Check if old container exists
            if (Storage::disk('private')->exists($oldPath)) {
                // Move container to new location
                $this->moveContainer($oldPath, $newPath);
                
                // Update container metadata
                $this->updateContainerAfterNipChange($newPath, $oldNip, $newNip);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle NIP change', [
                'old_nip' => $oldNip,
                'new_nip' => $newNip,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Move container from old path to new path
     */
    private function moveContainer(string $oldPath, string $newPath): void
    {
        $disk = Storage::disk('private');
        
        // Get all files in old container
        $files = $disk->allFiles($oldPath);
        
        // Create new directory structure
        $disk->makeDirectory($newPath);
        
        // Move each file
        foreach ($files as $file) {
            $relativePath = str_replace($oldPath . '/', '', $file);
            $newFile = $newPath . '/' . $relativePath;
            
            // Ensure directory exists
            $newDir = dirname($newFile);
            if (!$disk->exists($newDir)) {
                $disk->makeDirectory($newDir);
            }
            
            // Move file
            $disk->move($file, $newFile);
        }
        
        // Remove old directory
        $disk->deleteDirectory($oldPath);
    }

    /**
     * Update container metadata after NIP change
     */
    private function updateContainerAfterNipChange(string $containerPath, string $oldNip, string $newNip): void
    {
        $metadataPath = "{$containerPath}/container_metadata.json";
        
        if (Storage::disk('private')->exists($metadataPath)) {
            $metadata = json_decode(Storage::disk('private')->get($metadataPath), true);
            
            $metadata['employee_id'] = $newNip;
            $metadata['nip_change_history'][] = [
                'old_nip' => $oldNip,
                'new_nip' => $newNip,
                'changed_at' => now()->toISOString()
            ];
            $metadata['last_updated'] = now()->toISOString();
            
            Storage::disk('private')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Update container metadata with current stats
     */
    private function updateContainerMetadata(Employee $employee): void
    {
        try {
            $containerPath = $employee->getContainerFolderPath();
            $metadataPath = "{$containerPath}/container_metadata.json";
            
            if (Storage::disk('private')->exists($metadataPath)) {
                $metadata = json_decode(Storage::disk('private')->get($metadataPath), true);
                
                // Update file counts and sizes
                $totalFiles = 0;
                $totalSize = 0;
                
                foreach ($metadata['directories'] as $dir => &$dirData) {
                    $dirPath = "{$containerPath}/{$dir}";
                    if (Storage::disk('private')->exists($dirPath)) {
                        $files = Storage::disk('private')->files($dirPath);
                        $dirData['file_count'] = count($files);
                        $totalFiles += count($files);
                        
                        foreach ($files as $file) {
                            $totalSize += Storage::disk('private')->size($file);
                        }
                    }
                }
                
                $metadata['total_files'] = $totalFiles;
                $metadata['total_size'] = $totalSize;
                $metadata['last_updated'] = now()->toISOString();
                $metadata['employee_name'] = $employee->name; // Update name if changed
                
                Storage::disk('private')->put($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));
                
                // Update employee record
                $employee->updateQuietly([
                    'container_file_count' => $totalFiles,
                    'container_last_updated' => now()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to update container metadata', [
                'employee_id' => $employee->employee_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Archive employee container when employee is deleted
     */
    private function archiveEmployeeContainer(Employee $employee): void
    {
        try {
            $containerPath = $employee->getContainerFolderPath();
            $archivePath = "archived_containers/" . now()->format('Y/m') . "/{$employee->employee_id}_" . now()->format('Ymd_His');
            
            if (Storage::disk('private')->exists($containerPath)) {
                // Create archive metadata
                $archiveMetadata = [
                    'original_employee_id' => $employee->employee_id,
                    'employee_name' => $employee->name,
                    'archived_at' => now()->toISOString(),
                    'archived_by' => auth()->id(),
                    'original_container_path' => $containerPath,
                    'archive_reason' => 'employee_deleted'
                ];
                
                // Move to archive location
                Storage::disk('private')->makeDirectory($archivePath);
                
                $files = Storage::disk('private')->allFiles($containerPath);
                foreach ($files as $file) {
                    $relativePath = str_replace($containerPath . '/', '', $file);
                    Storage::disk('private')->move($file, "{$archivePath}/{$relativePath}");
                }
                
                // Save archive metadata
                Storage::disk('private')->put(
                    "{$archivePath}/archive_metadata.json",
                    json_encode($archiveMetadata, JSON_PRETTY_PRINT)
                );
                
                // Remove original directory
                Storage::disk('private')->deleteDirectory($containerPath);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to archive employee container', [
                'employee_id' => $employee->employee_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log container activity for audit trail
     */
    private function logContainerActivity(string $action, Employee $employee, array $extra = []): void
    {
        Log::info('Employee Container Activity', array_merge([
            'action' => $action,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->name,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ], $extra));
    }
}