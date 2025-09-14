<?php

namespace App\Http\Controllers;

use App\Models\FileStorage;
use App\Services\SecureFileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    private SecureFileStorageService $fileStorageService;

    public function __construct(SecureFileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
        $this->middleware('auth');
    }

    /**
     * Secure file download with token-based authentication
     */
    public function secureDownload(Request $request, string $token, ?string $filename = null): StreamedResponse
    {
        try {
            // Validate token
            $tokenData = Cache::get("secure_file_token:{$token}");
            
            if (!$tokenData) {
                abort(404, 'Invalid or expired download link');
            }

            // Additional security checks
            if ($tokenData['user_id'] !== Auth::id()) {
                abort(403, 'Access denied - Invalid user');
            }

            if ($tokenData['ip_address'] !== $request->ip()) {
                abort(403, 'Access denied - IP mismatch');
            }

            // Get file storage record
            $fileStorage = FileStorage::find($tokenData['file_storage_id']);
            if (!$fileStorage || $fileStorage->status !== 'stored') {
                abort(404, 'File not found');
            }

            // Get file data
            $fileData = $this->fileStorageService->retrieveFile($fileStorage);

            // Invalidate token after use
            Cache::forget("secure_file_token:{$token}");

            // Return streamed response
            return response()->stream(
                function () use ($fileData) {
                    $stream = $fileData['stream'];
                    while (!feof($stream)) {
                        echo fread($stream, 8192);
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => $fileData['mime_type'],
                    'Content-Disposition' => 'attachment; filename="' . $fileData['filename'] . '"',
                    'Content-Length' => $fileData['size'],
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'X-Robots-Tag' => 'noindex, nofollow',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'DENY',
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $fileData['last_modified']) . ' GMT'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Secure file download failed', [
                'token' => $token,
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'File download failed');
        }
    }

    /**
     * Direct file download for authorized users
     */
    public function download(Request $request, FileStorage $fileStorage): StreamedResponse
    {
        try {
            // Get file data with access control
            $fileData = $this->fileStorageService->retrieveFile($fileStorage);

            return response()->stream(
                function () use ($fileData) {
                    $stream = $fileData['stream'];
                    while (!feof($stream)) {
                        echo fread($stream, 8192);
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => $fileData['mime_type'],
                    'Content-Disposition' => 'attachment; filename="' . $fileData['filename'] . '"',
                    'Content-Length' => $fileData['size'],
                    'Cache-Control' => 'private, max-age=3600', // 1 hour cache for authorized users
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'SAMEORIGIN',
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $fileData['last_modified']) . ' GMT'
                ]
            );

        } catch (\Exception $e) {
            Log::error('File download failed', [
                'file_storage_id' => $fileStorage->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Preview file in browser (for images and PDFs)
     */
    public function preview(Request $request, FileStorage $fileStorage): StreamedResponse
    {
        try {
            // Check if file type supports preview
            if (!in_array($fileStorage->mime_type, [
                'application/pdf',
                'image/jpeg',
                'image/jpg', 
                'image/png'
            ])) {
                abort(415, 'File type does not support preview');
            }

            // Get file data with access control
            $fileData = $this->fileStorageService->retrieveFile($fileStorage);

            return response()->stream(
                function () use ($fileData) {
                    $stream = $fileData['stream'];
                    while (!feof($stream)) {
                        echo fread($stream, 8192);
                        if (ob_get_level()) {
                            ob_flush();
                        }
                        flush();
                    }
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => $fileData['mime_type'],
                    'Content-Disposition' => 'inline; filename="' . $fileData['filename'] . '"',
                    'Content-Length' => $fileData['size'],
                    'Cache-Control' => 'private, max-age=1800', // 30 minutes cache for preview
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'SAMEORIGIN',
                    'Content-Security-Policy' => "default-src 'self'",
                    'Last-Modified' => gmdate('D, d M Y H:i:s', $fileData['last_modified']) . ' GMT'
                ]
            );

        } catch (\Exception $e) {
            Log::error('File preview failed', [
                'file_storage_id' => $fileStorage->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Get file metadata and statistics
     */
    public function metadata(FileStorage $fileStorage): Response
    {
        try {
            // Check access permissions
            $fileData = $this->fileStorageService->retrieveFile($fileStorage);

            return response()->json([
                'id' => $fileStorage->id,
                'employee' => [
                    'id' => $fileStorage->employee->id,
                    'name' => $fileStorage->employee->name,
                    'employee_id' => $fileStorage->employee->employee_id
                ],
                'certificate_type' => [
                    'id' => $fileStorage->certificateType->id,
                    'name' => $fileStorage->certificateType->name,
                    'code' => $fileStorage->certificateType->code
                ],
                'file_info' => [
                    'original_filename' => $fileStorage->original_filename,
                    'mime_type' => $fileStorage->mime_type,
                    'file_size' => $fileStorage->file_size,
                    'formatted_size' => $fileStorage->formatted_file_size,
                    'uploaded_at' => $fileStorage->uploaded_at,
                    'version_number' => $fileStorage->version_number,
                    'is_latest_version' => $fileStorage->is_latest_version
                ],
                'certificate_info' => [
                    'issue_date' => $fileStorage->issue_date,
                    'expiry_date' => $fileStorage->expiry_date,
                    'validity_status' => $fileStorage->validity_status,
                    'days_until_expiry' => $fileStorage->days_until_expiry
                ],
                'security_info' => [
                    'file_hash' => substr($fileStorage->file_hash, 0, 16) . '...', // Partial hash for verification
                    'uploaded_by' => $fileStorage->uploadedBy->name ?? 'Unknown',
                    'status' => $fileStorage->status
                ],
                'download_url' => route('files.download', $fileStorage->id),
                'preview_url' => in_array($fileStorage->mime_type, [
                    'application/pdf', 'image/jpeg', 'image/jpg', 'image/png'
                ]) ? route('files.preview', $fileStorage->id) : null
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Get file versions for a certificate
     */
    public function versions(Request $request, int $employeeId, int $certificateTypeId): Response
    {
        try {
            $versions = FileStorage::getCertificateHistory($employeeId, $certificateTypeId);
            
            // Check access to at least one file
            if ($versions->isNotEmpty()) {
                $this->fileStorageService->retrieveFile($versions->first());
            }

            return response()->json([
                'employee_id' => $employeeId,
                'certificate_type_id' => $certificateTypeId,
                'total_versions' => $versions->count(),
                'versions' => $versions->map(function ($version) {
                    return [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'issue_date' => $version->issue_date,
                        'expiry_date' => $version->expiry_date,
                        'validity_status' => $version->validity_status,
                        'uploaded_at' => $version->uploaded_at,
                        'uploaded_by' => $version->uploadedBy->name ?? 'Unknown',
                        'file_size' => $version->formatted_file_size,
                        'is_latest' => $version->is_latest_version,
                        'download_url' => route('files.download', $version->id)
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Delete file securely
     */
    public function delete(Request $request, FileStorage $fileStorage): Response
    {
        try {
            $reason = [
                'reason' => $request->input('reason', 'User request'),
                'notes' => $request->input('notes')
            ];

            $result = $this->fileStorageService->deleteFile($fileStorage, $reason);

            if ($result) {
                return response()->json([
                    'message' => 'File deleted successfully',
                    'file_id' => $fileStorage->id
                ]);
            } else {
                return response()->json(['error' => 'Failed to delete file'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    /**
     * Get storage statistics for admin users
     */
    public function statistics(Request $request): Response
    {
        try {
            // Check admin permissions
            if (!Auth::user()->hasRole(['admin', 'hr'])) {
                abort(403, 'Insufficient permissions');
            }

            $stats = $this->fileStorageService->getStorageStatistics();

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}