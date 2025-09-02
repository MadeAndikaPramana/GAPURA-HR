<?php
// app/Http/Controllers/FileController.php

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Serve files from local storage
     * Route: /files/serve/{path}
     */
    public function serve(string $path)
    {
        try {
            // Decode the base64 encoded path
            $decodedPath = base64_decode($path);

            // Security check - ensure path is within employee_files directory
            if (!str_starts_with($decodedPath, 'employee_files/')) {
                abort(403, 'Access denied');
            }

            // Check if file exists
            if (!Storage::disk('local')->exists($decodedPath)) {
                abort(404, 'File not found');
            }

            // Get file metadata
            $mimeType = Storage::disk('local')->mimeType($decodedPath);
            $size = Storage::disk('local')->size($decodedPath);
            $fileName = basename($decodedPath);

            // Security check for allowed file types
            $allowedMimes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];

            if (!in_array($mimeType, $allowedMimes)) {
                abort(403, 'File type not allowed');
            }

            // Get file contents
            $fileContents = Storage::disk('local')->get($decodedPath);

            // Return file response with appropriate headers
            return response($fileContents, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                'Content-Length' => $size,
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN'
            ]);

        } catch (\Exception $e) {
            Log::error('File serve error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error serving file');
        }
    }

    /**
     * Stream files for download
     * Route: /files/download/{path}
     */
    public function download(string $path)
    {
        try {
            // Decode the base64 encoded path
            $decodedPath = base64_decode($path);

            // Security check - ensure path is within employee_files directory
            if (!str_starts_with($decodedPath, 'employee_files/')) {
                abort(403, 'Access denied');
            }

            // Check if file exists
            if (!Storage::disk('local')->exists($decodedPath)) {
                abort(404, 'File not found');
            }

            $fileName = basename($decodedPath);

            // Return download response
            return Storage::disk('local')->download($decodedPath, $fileName);

        } catch (\Exception $e) {
            Log::error('File download error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Error downloading file');
        }
    }

    /**
     * Get file information
     * Route: /api/files/info/{path}
     */
    public function getFileInfo(string $path)
    {
        try {
            // Decode the base64 encoded path
            $decodedPath = base64_decode($path);

            // Security check
            if (!str_starts_with($decodedPath, 'employee_files/')) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $metadata = $this->fileUploadService->getFileMetadata($decodedPath);

            if (!$metadata) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->json($metadata);

        } catch (\Exception $e) {
            Log::error('File info error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error getting file info'], 500);
        }
    }

    /**
     * Upload files via API
     * Route: POST /api/files/upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,txt',
            'type' => 'required|in:background_check,certificate',
            'employee_id' => 'required|exists:employees,id',
            'certificate_id' => 'required_if:type,certificate|exists:employee_certificates,id'
        ]);

        try {
            if ($request->type === 'background_check') {
                $uploadedFiles = $this->fileUploadService->uploadBackgroundCheckFiles(
                    $request->employee_id,
                    $request->file('files')
                );
            } else {
                $uploadedFiles = $this->fileUploadService->uploadCertificateFiles(
                    $request->employee_id,
                    $request->certificate_id,
                    $request->file('files')
                );
            }

            Log::info('Files uploaded via API', [
                'type' => $request->type,
                'employee_id' => $request->employee_id,
                'certificate_id' => $request->certificate_id,
                'files_count' => count($uploadedFiles)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' files uploaded successfully',
                'files' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            Log::error('File upload error', [
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete file via API
     * Route: DELETE /api/files/delete
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->path;

            // Security check
            if (!str_starts_with($path, 'employee_files/')) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            $deleted = $this->fileUploadService->deleteFile($path);

            if ($deleted) {
                Log::info('File deleted via API', ['path' => $path]);

                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('File delete error', [
                'path' => $request->path,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics
     * Route: /api/files/stats
     */
    public function getStorageStats()
    {
        try {
            $stats = $this->fileUploadService->getStorageStats();

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Storage stats error', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Error getting storage stats'], 500);
        }
    }

    /**
     * Cleanup old files (admin only)
     * Route: POST /api/files/cleanup
     */
    public function cleanup(Request $request)
    {
        // This should be protected by admin middleware
        $request->validate([
            'days_old' => 'required|integer|min:30|max:365'
        ]);

        try {
            $deletedFiles = $this->fileUploadService->cleanupOldFiles($request->days_old);

            Log::info('File cleanup performed', [
                'days_old' => $request->days_old,
                'files_deleted' => count($deletedFiles)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($deletedFiles) . ' old files cleaned up successfully',
                'deleted_files' => count($deletedFiles)
            ]);

        } catch (\Exception $e) {
            Log::error('File cleanup error', [
                'days_old' => $request->days_old,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if file exists
     * Route: GET /api/files/exists/{path}
     */
    public function exists(string $path)
    {
        try {
            // Decode the base64 encoded path
            $decodedPath = base64_decode($path);

            // Security check
            if (!str_starts_with($decodedPath, 'employee_files/')) {
                return response()->json(['exists' => false, 'error' => 'Access denied'], 403);
            }

            $exists = $this->fileUploadService->fileExists($decodedPath);

            return response()->json(['exists' => $exists]);

        } catch (\Exception $e) {
            Log::error('File exists check error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return response()->json(['exists' => false, 'error' => 'Error checking file'], 500);
        }
    }
}
