<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Serve background check file
     */
    public function serveBackgroundCheck(Employee $employee, $fileIndex)
    {
        // Authorization check
        $this->authorize('view', $employee);

        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        return $this->serveFileFromStorage($file);
    }

    /**
     * Serve certificate file
     */
    public function serveCertificate(Employee $employee, EmployeeCertificate $certificate, $fileIndex)
    {
        // Authorization check
        $this->authorize('view', $employee);

        // Ensure certificate belongs to employee
        if ($certificate->employee_id !== $employee->id) {
            abort(403, 'Certificate does not belong to this employee');
        }

        $files = $certificate->certificate_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        return $this->serveFileFromStorage($file);
    }

    /**
     * Preview background check file (inline display)
     */
    public function previewBackgroundCheck(Employee $employee, $fileIndex)
    {
        // Authorization check
        $this->authorize('view', $employee);

        $files = $employee->background_check_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        return $this->serveFilePreview($file);
    }

    /**
     * Preview certificate file (inline display)
     */
    public function previewCertificate(EmployeeCertificate $certificate, $fileIndex)
    {
        // Authorization check
        $this->authorize('view', $certificate->employee);

        $files = $certificate->certificate_files ?? [];

        if (!isset($files[$fileIndex])) {
            abort(404, 'File not found');
        }

        $file = $files[$fileIndex];

        return $this->serveFilePreview($file);
    }

    /**
     * Validate file before upload
     */
    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file = $request->file('file');

        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'info' => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'size_formatted' => $this->formatFileSize($file->getSize()),
                'type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]
        ];

        // Check file type
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $validation['valid'] = false;
            $validation['errors'][] = 'File type not allowed. Only PDF, JPG, and PNG files are permitted.';
        }

        // Check file size (5MB limit)
        if ($file->getSize() > 5242880) { // 5MB in bytes
            $validation['valid'] = false;
            $validation['errors'][] = 'File size exceeds 5MB limit.';
        }

        // Check if file is readable
        if (!$file->isValid()) {
            $validation['valid'] = false;
            $validation['errors'][] = 'File appears to be corrupted or invalid.';
        }

        // Add warnings for large files
        if ($file->getSize() > 2097152) { // 2MB
            $validation['warnings'][] = 'File size is large and may take time to upload.';
        }

        // Check filename length
        if (strlen($file->getClientOriginalName()) > 100) {
            $validation['warnings'][] = 'Filename is very long. Consider shortening it.';
        }

        return response()->json($validation);
    }

    /**
     * Serve file from general storage path
     */
    public function serveFile($path)
    {
        // Security check - ensure path is within containers directory
        if (!str_starts_with($path, 'containers/')) {
            abort(403, 'Access denied');
        }

        $fullPath = $path;

        if (!Storage::disk('private')->exists($fullPath)) {
            abort(404, 'File not found');
        }

        $mimeType = Storage::disk('private')->mimeType($fullPath);
        $fileSize = Storage::disk('private')->size($fullPath);

        // Security check - only serve allowed file types
        if (!$this->isAllowedFileType($mimeType)) {
            abort(403, 'File type not allowed');
        }

        return Storage::disk('private')->response($fullPath, null, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Serve file from storage with proper headers
     */
    private function serveFileFromStorage($fileData)
    {
        if (!Storage::disk('private')->exists($fileData['path'])) {
            abort(404, 'File not found on disk');
        }

        $mimeType = $fileData['mime_type'] ?? Storage::disk('private')->mimeType($fileData['path']);
        $fileSize = $fileData['file_size'] ?? Storage::disk('private')->size($fileData['path']);

        // Security check
        if (!$this->isAllowedFileType($mimeType)) {
            abort(403, 'File type not allowed');
        }

        return Storage::disk('private')->download(
            $fileData['path'],
            $fileData['original_name'],
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
            ]
        );
    }

    /**
     * Serve file preview (inline display)
     */
    private function serveFilePreview($fileData)
    {
        if (!Storage::disk('private')->exists($fileData['path'])) {
            abort(404, 'File not found on disk');
        }

        $mimeType = $fileData['mime_type'] ?? Storage::disk('private')->mimeType($fileData['path']);

        // Security check
        if (!$this->isAllowedFileType($mimeType)) {
            abort(403, 'File type not allowed');
        }

        // Only serve preview for specific types
        if (!$this->isPreviewableType($mimeType)) {
            abort(400, 'File type not previewable');
        }

        return Storage::disk('private')->response($fileData['path'], null, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Check if file type is allowed
     */
    private function isAllowedFileType($mimeType)
    {
        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Check if file type can be previewed inline
     */
    private function isPreviewableType($mimeType)
    {
        $previewableTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ];

        return in_array($mimeType, $previewableTypes);
    }

    /**
     * Format file size for human reading
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Get file icon based on type
     */
    private function getFileIcon($mimeType)
    {
        switch ($mimeType) {
            case 'application/pdf':
                return 'pdf';
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
                return 'image';
            default:
                return 'file';
        }
    }

    /**
     * Create file response with streaming (for large files)
     */
    private function createStreamedResponse($filePath, $filename, $mimeType)
    {
        return new StreamedResponse(function() use ($filePath) {
            $stream = Storage::disk('private')->readStream($filePath);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => Storage::disk('private')->size($filePath),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
