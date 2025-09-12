<?php
// app/Services/GoogleDriveService.php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private Client $client;
    private Drive $service;
    private string $rootFolderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->setupClient();
        $this->service = new Drive($this->client);
        $this->rootFolderId = config('google.drive.root_folder_id');
    }

    /**
     * Setup Google Client with credentials
     */
    private function setupClient(): void
    {
        $this->client->setApplicationName(config('app.name'));
        $this->client->setScopes([Drive::DRIVE_FILE]);
        $this->client->setAuthConfig(config('google.drive.credentials_path'));
        $this->client->setAccessType('offline');

        // Use service account (recommended for server-side apps)
        $this->client->useApplicationDefaultCredentials();
    }

    /**
     * Create folder structure if not exists
     */
    public function createFolderStructure(string $path): string
    {
        $folders = explode('/', trim($path, '/'));
        $currentFolderId = $this->rootFolderId;

        foreach ($folders as $folderName) {
            $currentFolderId = $this->getOrCreateFolder($folderName, $currentFolderId);
        }

        return $currentFolderId;
    }

    /**
     * Get existing folder or create new one
     */
    private function getOrCreateFolder(string $name, string $parentId): string
    {
        // Search for existing folder
        $query = "name='{$name}' and parents in '{$parentId}' and mimeType='application/vnd.google-apps.folder' and trashed=false";

        try {
            $results = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)'
            ]);

            if (count($results->getFiles()) > 0) {
                return $results->getFiles()[0]->getId();
            }

            // Create new folder
            return $this->createFolder($name, $parentId);

        } catch (\Exception $e) {
            Log::error('Google Drive folder search/create failed', [
                'folder_name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create new folder in Google Drive
     */
    private function createFolder(string $name, string $parentId): string
    {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'parents' => [$parentId],
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        try {
            $folder = $this->service->files->create($fileMetadata, [
                'fields' => 'id'
            ]);

            Log::info('Google Drive folder created', [
                'folder_name' => $name,
                'folder_id' => $folder->getId(),
                'parent_id' => $parentId
            ]);

            return $folder->getId();

        } catch (\Exception $e) {
            Log::error('Google Drive folder creation failed', [
                'folder_name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile(
        UploadedFile $file,
        string $storedFilename,
        string $folderId,
        array $metadata = []
    ): array {
        try {
            // Prepare file metadata
            $fileMetadata = new DriveFile([
                'name' => $storedFilename,
                'parents' => [$folderId],
                'description' => $metadata['description'] ?? 'Certificate uploaded via GAPURA system'
            ]);

            // Upload file content
            $uploadedFile = $this->service->files->create(
                $fileMetadata,
                [
                    'data' => $file->getContent(),
                    'mimeType' => $file->getMimeType(),
                    'uploadType' => 'multipart',
                    'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,parents,createdTime,modifiedTime'
                ]
            );

            Log::info('File uploaded to Google Drive', [
                'file_id' => $uploadedFile->getId(),
                'filename' => $storedFilename,
                'folder_id' => $folderId,
                'size' => $uploadedFile->getSize()
            ]);

            return [
                'success' => true,
                'file_id' => $uploadedFile->getId(),
                'filename' => $uploadedFile->getName(),
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'web_view_link' => $uploadedFile->getWebViewLink(),
                'web_content_link' => $uploadedFile->getWebContentLink(),
                'parent_folder_id' => $folderId,
                'created_time' => $uploadedFile->getCreatedTime(),
                'modified_time' => $uploadedFile->getModifiedTime()
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive file upload failed', [
                'filename' => $storedFilename,
                'folder_id' => $folderId,
                'original_filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Download file from Google Drive
     */
    public function downloadFile(string $fileId): array
    {
        try {
            // Get file metadata
            $file = $this->service->files->get($fileId, [
                'fields' => 'id,name,mimeType,size,webViewLink,webContentLink'
            ]);

            // Get file content
            $content = $this->service->files->get($fileId, [
                'alt' => 'media'
            ]);

            return [
                'success' => true,
                'content' => $content->getBody()->getContents(),
                'filename' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive file download failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get file info from Google Drive
     */
    public function getFileInfo(string $fileId): array
    {
        try {
            $file = $this->service->files->get($fileId, [
                'fields' => 'id,name,mimeType,size,webViewLink,webContentLink,parents,createdTime,modifiedTime,description'
            ]);

            return [
                'success' => true,
                'file_id' => $file->getId(),
                'filename' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'web_view_link' => $file->getWebViewLink(),
                'web_content_link' => $file->getWebContentLink(),
                'parents' => $file->getParents(),
                'created_time' => $file->getCreatedTime(),
                'modified_time' => $file->getModifiedTime(),
                'description' => $file->getDescription()
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive file info failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile(string $fileId): bool
    {
        try {
            $this->service->files->delete($fileId);

            Log::info('File deleted from Google Drive', [
                'file_id' => $fileId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Google Drive file deletion failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Generate shareable link for file
     */
    public function generateShareableLink(string $fileId, string $role = 'reader'): ?string
    {
        try {
            // Create permission for anyone with link
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => $role
            ]);

            $this->service->permissions->create($fileId, $permission);

            // Get the shareable link
            $file = $this->service->files->get($fileId, [
                'fields' => 'webViewLink'
            ]);

            return $file->getWebViewLink();

        } catch (\Exception $e) {
            Log::error('Google Drive shareable link generation failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get storage quota info
     */
    public function getStorageInfo(): array
    {
        try {
            $about = $this->service->about->get([
                'fields' => 'storageQuota,user'
            ]);

            $quota = $about->getStorageQuota();

            return [
                'success' => true,
                'limit' => $quota->getLimit(),
                'usage' => $quota->getUsage(),
                'usage_in_drive' => $quota->getUsageInDrive(),
                'usage_in_drive_trash' => $quota->getUsageInDriveTrash(),
                'user_email' => $about->getUser()->getEmailAddress()
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive storage info failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk upload files
     */
    public function bulkUploadFiles(array $fileData): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($fileData as $index => $data) {
            try {
                $result = $this->uploadFile(
                    $data['file'],
                    $data['stored_filename'],
                    $data['folder_id'],
                    $data['metadata'] ?? []
                );

                $results[] = [
                    'index' => $index,
                    'original_filename' => $data['file']->getClientOriginalName(),
                    'stored_filename' => $data['stored_filename'],
                    'success' => $result['success'],
                    'file_id' => $result['file_id'] ?? null,
                    'error' => $result['error'] ?? null
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

            } catch (\Exception $e) {
                $results[] = [
                    'index' => $index,
                    'original_filename' => $data['file']->getClientOriginalName(),
                    'stored_filename' => $data['stored_filename'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return [
            'total_files' => count($fileData),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Check if service is properly configured and accessible
     */
    public function testConnection(): array
    {
        try {
            $about = $this->service->about->get(['fields' => 'user']);

            return [
                'success' => true,
                'message' => 'Google Drive connection successful',
                'user_email' => $about->getUser()->getEmailAddress()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Google Drive connection failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
