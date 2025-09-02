<?php
// app/Services/FileUploadService.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FileUploadService
{
    protected string $basePath = 'employee_files';
    protected array $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/jpg',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];

    protected int $maxFileSize = 10 * 1024 * 1024; // 10MB

    /**
     * Upload background check files for an employee
     */
    public function uploadBackgroundCheckFiles(int $employeeId, array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            try {
                $this->validateFile($file);
                $fileData = $this->storeFile($file, "background_checks/{$employeeId}");
                $uploadedFiles[] = $fileData;

                Log::info("Background check file uploaded", [
                    'employee_id' => $employeeId,
                    'file' => $fileData['original_name']
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to upload background check file", [
                    'employee_id' => $employeeId,
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $uploadedFiles;
    }

    /**
     * Upload certificate files for an employee certificate
     */
    public function uploadCertificateFiles(int $employeeId, int $certificateId, array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            try {
                $this->validateFile($file);
                $fileData = $this->storeFile($file, "certificates/{$employeeId}/{$certificateId}");
                $uploadedFiles[] = $fileData;

                Log::info("Certificate file uploaded", [
                    'employee_id' => $employeeId,
                    'certificate_id' => $certificateId,
                    'file' => $fileData['original_name']
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to upload certificate file", [
                    'employee_id' => $employeeId,
                    'certificate_id' => $certificateId,
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        return $uploadedFiles;
    }

    /**
     * Store a single file and return file metadata
     */
    protected function storeFile(UploadedFile $file, string $directory): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Generate unique filename
        $timestamp = Carbon::now()->format('YmdHis');
        $randomString = Str::random(8);
        $storedName = "{$timestamp}_{$randomString}.{$extension}";

        // Store file
        $path = $file->storeAs(
            "{$this->basePath}/{$directory}",
            $storedName,
            'local'
        );

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $path,
            'directory' => $directory,
            'size' => $fileSize,
            'size_formatted' => $this->formatBytes($fileSize),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'uploaded_at' => Carbon::now()->toISOString(),
            'uploaded_by' => auth()->id()
        ];
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception("File size exceeds maximum allowed size of " . $this->formatBytes($this->maxFileSize));
        }

        // Check mime type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \Exception("File type '{$file->getMimeType()}' is not allowed");
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception("File upload failed or file is corrupted");
        }
    }

    /**
     * Delete a file
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
                Log::info("File deleted", ['path' => $path]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to delete file", ['path' => $path, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get file download response
     */
    public function downloadFile(string $path, string $originalName = null)
    {
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        $fileName = $originalName ?: basename($path);

        return Storage::disk('local')->download($path, $fileName);
    }

    /**
     * Get file URL for display
     */
    public function getFileUrl(string $path): string
    {
        // For local storage, we'll need to create a route that serves the file
        return route('files.serve', ['path' => base64_encode($path)]);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('local')->exists($path);
    }

    /**
     * Get file metadata
     */
    public function getFileMetadata(string $path): ?array
    {
        if (!$this->fileExists($path)) {
            return null;
        }

        $size = Storage::disk('local')->size($path);
        $lastModified = Storage::disk('local')->lastModified($path);

        return [
            'path' => $path,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'last_modified' => Carbon::createFromTimestamp($lastModified)->toISOString(),
            'exists' => true
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get allowed file types for validation
     */
    public function getAllowedFileTypes(): array
    {
        return [
            'PDF Documents' => ['pdf'],
            'Images' => ['jpg', 'jpeg', 'png'],
            'Word Documents' => ['doc', 'docx'],
            'Text Files' => ['txt']
        ];
    }

    /**
     * Get max file size in MB
     */
    public function getMaxFileSizeMB(): int
    {
        return $this->maxFileSize / (1024 * 1024);
    }

    /**
     * Clean up old files (for maintenance)
     */
    public function cleanupOldFiles(int $daysOld = 365): array
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        $deletedFiles = [];

        try {
            $allFiles = Storage::disk('local')->allFiles($this->basePath);

            foreach ($allFiles as $file) {
                $lastModified = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file));

                if ($lastModified->lt($cutoffDate)) {
                    if ($this->deleteFile($file)) {
                        $deletedFiles[] = $file;
                    }
                }
            }

            Log::info("File cleanup completed", [
                'days_old' => $daysOld,
                'files_deleted' => count($deletedFiles)
            ]);

        } catch (\Exception $e) {
            Log::error("File cleanup failed", ['error' => $e->getMessage()]);
        }

        return $deletedFiles;
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $totalFiles = 0;
        $totalSize = 0;

        try {
            $allFiles = Storage::disk('local')->allFiles($this->basePath);
            $totalFiles = count($allFiles);

            foreach ($allFiles as $file) {
                $totalSize += Storage::disk('local')->size($file);
            }

        } catch (\Exception $e) {
            Log::error("Failed to get storage stats", ['error' => $e->getMessage()]);
        }

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'base_path' => $this->basePath,
            'disk' => 'local'
        ];
    }
}
