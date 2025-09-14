<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FileStorage;
use App\Models\CertificateType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class SecureFileStorageService
{
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png'
    ];
    const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
    
    private string $privateStoragePath = 'containers';
    private int $cacheTtl = 3600; // 1 hour

    /**
     * Store file securely with validation and metadata tracking
     */
    public function storeFile(
        UploadedFile $file,
        Employee $employee,
        CertificateType $certificateType,
        array $metadata = []
    ): FileStorage {
        // Validate file
        $this->validateFile($file);

        // Generate file metadata
        $fileHash = hash_file('sha256', $file->getRealPath());
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Check for duplicate files
        $existingFile = $this->checkDuplicateFile($fileHash, $employee->id, $certificateType->id);
        if ($existingFile) {
            throw new Exception("Duplicate file detected. This file already exists as version {$existingFile->version_number}.");
        }

        // Generate version number and paths
        $versionNumber = FileStorage::getNextVersionNumber($employee->id, $certificateType->id);
        $containerPath = $this->generateContainerPath($employee, $certificateType);
        $storedFilename = $this->generateSecureFilename($originalFilename, $versionNumber, $metadata);
        $fullPath = $containerPath . '/' . $storedFilename;

        // Create file storage record
        $fileStorage = FileStorage::create([
            'employee_id' => $employee->id,
            'certificate_type_id' => $certificateType->id,
            'version_number' => $versionNumber,
            'issue_date' => $metadata['issue_date'] ?? now(),
            'expiry_date' => $metadata['expiry_date'] ?? null,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'storage_path' => $fullPath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file_hash' => $fileHash,
            'uploaded_by' => Auth::id(),
            'status' => 'pending',
            'metadata' => array_merge($metadata, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'upload_session_id' => Str::uuid(),
                'security_scan_status' => 'pending'
            ])
        ]);

        try {
            // Create directory structure if it doesn't exist
            Storage::disk('private')->makeDirectory($containerPath);

            // Store file securely
            $stored = Storage::disk('private')->putFileAs(
                $containerPath,
                $file,
                $storedFilename
            );

            if (!$stored) {
                throw new Exception('Failed to store file on disk');
            }

            // Run security scan
            $this->performSecurityScan($fileStorage);

            // Mark as successfully stored
            $fileStorage->update([
                'status' => 'stored',
                'uploaded_at' => now(),
                'metadata' => array_merge($fileStorage->metadata, [
                    'security_scan_status' => 'passed',
                    'storage_confirmed_at' => now()->toISOString()
                ])
            ]);

            // Log successful upload
            $this->logFileActivity('uploaded', $fileStorage, [
                'file_size' => $fileSize,
                'version' => $versionNumber
            ]);

            // Update container metadata
            $this->updateContainerMetadata($employee);

            return $fileStorage;

        } catch (Exception $e) {
            // Mark as failed and clean up
            $fileStorage->markAsFailed($e->getMessage());
            
            // Attempt cleanup
            if (isset($stored)) {
                Storage::disk('private')->delete($fullPath);
            }

            Log::error('File storage failed', [
                'employee_id' => $employee->id,
                'certificate_type' => $certificateType->code,
                'error' => $e->getMessage(),
                'file_storage_id' => $fileStorage->id
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve file securely with access control
     */
    public function retrieveFile(FileStorage $fileStorage, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        // Check access permissions
        if (!$this->canAccessFile($fileStorage, $user)) {
            throw new Exception('Access denied to this file');
        }

        // Check if file exists
        if (!Storage::disk('private')->exists($fileStorage->storage_path)) {
            throw new Exception('File not found on storage');
        }

        // Log access
        $this->logFileActivity('accessed', $fileStorage, [
            'accessed_by' => $user->id,
            'access_method' => 'download'
        ]);

        // Return file stream data
        return [
            'stream' => Storage::disk('private')->readStream($fileStorage->storage_path),
            'filename' => $fileStorage->original_filename,
            'mime_type' => $fileStorage->mime_type,
            'size' => $fileStorage->file_size,
            'last_modified' => Storage::disk('private')->lastModified($fileStorage->storage_path)
        ];
    }

    /**
     * Get file URL for authenticated access
     */
    public function getSecureFileUrl(FileStorage $fileStorage, int $expirationMinutes = 60): string
    {
        $token = Str::random(32);
        $expiresAt = now()->addMinutes($expirationMinutes);

        // Cache the token for secure access
        Cache::put(
            "secure_file_token:{$token}",
            [
                'file_storage_id' => $fileStorage->id,
                'user_id' => Auth::id(),
                'expires_at' => $expiresAt,
                'ip_address' => request()->ip()
            ],
            $expirationMinutes * 60
        );

        return route('files.secure-download', [
            'token' => $token,
            'filename' => $fileStorage->stored_filename
        ]);
    }

    /**
     * Delete file securely with cleanup
     */
    public function deleteFile(FileStorage $fileStorage, array $reason = []): bool
    {
        try {
            // Check permissions
            if (!$this->canDeleteFile($fileStorage, Auth::user())) {
                throw new Exception('Insufficient permissions to delete this file');
            }

            // Archive file instead of permanent deletion
            $archivePath = $this->archiveFile($fileStorage);

            // Update file status
            $fileStorage->update([
                'status' => 'deleted',
                'metadata' => array_merge($fileStorage->metadata ?? [], [
                    'deleted_at' => now()->toISOString(),
                    'deleted_by' => Auth::id(),
                    'deletion_reason' => $reason,
                    'archived_to' => $archivePath
                ])
            ]);

            // Log deletion
            $this->logFileActivity('deleted', $fileStorage, $reason);

            return true;

        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'file_storage_id' => $fileStorage->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Clean up files for deleted employees
     */
    public function cleanupEmployeeFiles(Employee $employee): array
    {
        $results = [
            'archived' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $files = FileStorage::where('employee_id', $employee->id)
            ->where('status', 'stored')
            ->get();

        foreach ($files as $file) {
            try {
                $this->deleteFile($file, [
                    'reason' => 'employee_deleted',
                    'employee_id' => $employee->employee_id
                ]);
                $results['archived']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "File {$file->id}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Backup files to secondary storage
     */
    public function backupEmployeeFiles(Employee $employee): array
    {
        $results = [
            'backed_up' => 0,
            'failed' => 0,
            'total_size' => 0,
            'errors' => []
        ];

        $files = FileStorage::where('employee_id', $employee->id)
            ->where('status', 'stored')
            ->get();

        foreach ($files as $file) {
            try {
                $backupPath = $this->createBackup($file);
                $results['backed_up']++;
                $results['total_size'] += $file->file_size;

                // Update metadata with backup info
                $metadata = $file->metadata ?? [];
                $metadata['backup_path'] = $backupPath;
                $metadata['backed_up_at'] = now()->toISOString();
                $file->update(['metadata' => $metadata]);

            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "File {$file->id}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStatistics(): array
    {
        $stats = [
            'total_files' => FileStorage::where('status', 'stored')->count(),
            'total_size' => FileStorage::where('status', 'stored')->sum('file_size'),
            'total_employees_with_files' => FileStorage::where('status', 'stored')->distinct('employee_id')->count(),
            'file_types' => FileStorage::where('status', 'stored')
                ->select('mime_type', \DB::raw('COUNT(*) as count'))
                ->groupBy('mime_type')
                ->get()
                ->pluck('count', 'mime_type')
                ->toArray(),
            'average_file_size' => FileStorage::where('status', 'stored')->avg('file_size'),
            'oldest_file' => FileStorage::where('status', 'stored')->min('uploaded_at'),
            'newest_file' => FileStorage::where('status', 'stored')->max('uploaded_at')
        ];

        $stats['formatted_total_size'] = $this->formatBytes($stats['total_size']);
        $stats['formatted_average_size'] = $this->formatBytes($stats['average_file_size']);

        return $stats;
    }

    // ===== PRIVATE METHODS =====

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception("File size exceeds maximum allowed size of 5MB");
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new Exception("File type not allowed. Only PDF, JPG, and PNG files are permitted");
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new Exception("Invalid file type. MIME type '{$mimeType}' is not allowed");
        }

        // Check for malicious files
        if ($this->isSuspiciousFile($file)) {
            throw new Exception("File failed security validation");
        }
    }

    /**
     * Check for duplicate files
     */
    private function checkDuplicateFile(string $hash, int $employeeId, int $certificateTypeId): ?FileStorage
    {
        return FileStorage::where('file_hash', $hash)
            ->where('employee_id', $employeeId)
            ->where('certificate_type_id', $certificateTypeId)
            ->where('status', 'stored')
            ->first();
    }

    /**
     * Generate secure container path
     */
    private function generateContainerPath(Employee $employee, CertificateType $certificateType): string
    {
        return $this->privateStoragePath . "/employee-{$employee->id}/{$certificateType->code}";
    }

    /**
     * Generate secure filename
     */
    private function generateSecureFilename(string $originalFilename, int $version, array $metadata = []): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $timestamp = now()->format('Y-m-d_H-i-s');
        $randomHash = substr(hash('sha256', $originalFilename . $timestamp . Str::random(10)), 0, 8);
        
        return "v{$version}_{$timestamp}_{$randomHash}.{$extension}";
    }

    /**
     * Perform basic security scan on file
     */
    private function performSecurityScan(FileStorage $fileStorage): void
    {
        $filePath = Storage::disk('private')->path($fileStorage->storage_path);
        
        // Basic checks for suspicious content
        if ($fileStorage->mime_type === 'application/pdf') {
            $this->scanPdfFile($filePath);
        } else {
            $this->scanImageFile($filePath);
        }
    }

    /**
     * Check if file is suspicious
     */
    private function isSuspiciousFile(UploadedFile $file): bool
    {
        // Check for executable content in file header
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 1024);
        fclose($handle);

        // Look for suspicious patterns
        $suspiciousPatterns = [
            'MZ',           // DOS/Windows executable
            '#!/bin/',      // Shell script
            '<?php',        // PHP code
            '<script',      // JavaScript
            'javascript:',  // JavaScript protocol
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($header, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scan PDF file for security issues
     */
    private function scanPdfFile(string $filePath): void
    {
        // Basic PDF validation - check PDF header
        $handle = fopen($filePath, 'rb');
        $header = fread($handle, 8);
        fclose($handle);

        if (strpos($header, '%PDF-') !== 0) {
            throw new Exception('Invalid PDF file format');
        }
    }

    /**
     * Scan image file for security issues
     */
    private function scanImageFile(string $filePath): void
    {
        // Validate image using GD
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file');
        }
    }

    /**
     * Check if user can access file
     */
    private function canAccessFile(FileStorage $fileStorage, $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin users can access all files
        if ($user->hasRole('admin')) {
            return true;
        }

        // Users can access files they uploaded
        if ($fileStorage->uploaded_by === $user->id) {
            return true;
        }

        // HR users can access all employee files
        if ($user->hasRole('hr')) {
            return true;
        }

        // Employees can access their own files
        if ($fileStorage->employee && $fileStorage->employee->email === $user->email) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete file
     */
    private function canDeleteFile(FileStorage $fileStorage, $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin and HR can delete files
        if ($user->hasRole(['admin', 'hr'])) {
            return true;
        }

        // Users can delete files they uploaded
        if ($fileStorage->uploaded_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Archive file instead of deleting
     */
    private function archiveFile(FileStorage $fileStorage): string
    {
        $archivePath = "archived/" . now()->format('Y/m') . "/{$fileStorage->stored_filename}";
        
        Storage::disk('private')->move($fileStorage->storage_path, $archivePath);
        
        return $archivePath;
    }

    /**
     * Create backup of file
     */
    private function createBackup(FileStorage $fileStorage): string
    {
        $backupPath = "backups/" . now()->format('Y/m/d') . "/employee-{$fileStorage->employee_id}/{$fileStorage->stored_filename}";
        
        Storage::disk('private')->copy($fileStorage->storage_path, $backupPath);
        
        return $backupPath;
    }

    /**
     * Update container metadata
     */
    private function updateContainerMetadata(Employee $employee): void
    {
        $employee->updateQuietly([
            'container_file_count' => FileStorage::where('employee_id', $employee->id)
                ->where('status', 'stored')
                ->count(),
            'container_last_updated' => now()
        ]);
    }

    /**
     * Log file activity
     */
    private function logFileActivity(string $action, FileStorage $fileStorage, array $context = []): void
    {
        Log::info("File {$action}", array_merge([
            'action' => $action,
            'file_storage_id' => $fileStorage->id,
            'employee_id' => $fileStorage->employee_id,
            'certificate_type' => $fileStorage->certificateType->code ?? 'unknown',
            'version' => $fileStorage->version_number,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * Format bytes to human readable size
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}