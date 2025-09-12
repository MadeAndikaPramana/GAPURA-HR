<?php
// app/Console/Commands/GoogleDriveTestCommand.php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class GoogleDriveTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'google:test-connection {--setup : Run initial setup and create folder structure}';

    /**
     * The console command description.
     */
    protected $description = 'Test Google Drive connection and optionally setup folder structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔗 Testing Google Drive Connection...');
        $this->newLine();

        try {
            $driveService = new GoogleDriveService();

            // Test connection
            $this->info('1. Testing authentication...');
            $connectionTest = $driveService->testConnection();

            if (!$connectionTest['success']) {
                $this->error('❌ Google Drive connection failed!');
                $this->error('Error: ' . $connectionTest['error']);
                $this->newLine();
                $this->warn('Please check your configuration:');
                $this->line('- GOOGLE_DRIVE_CREDENTIALS_PATH points to valid JSON file');
                $this->line('- Service account has proper permissions');
                $this->line('- Google Drive API is enabled');
                return 1;
            }

            $this->info('✅ Authentication successful!');
            $this->line('   Service Account: ' . $connectionTest['user_email']);
            $this->newLine();

            // Test storage info
            $this->info('2. Checking storage quota...');
            $storageInfo = $driveService->getStorageInfo();

            if ($storageInfo['success']) {
                $this->info('✅ Storage info retrieved!');
                $this->line('   Limit: ' . $this->formatBytes($storageInfo['limit'] ?? 0));
                $this->line('   Used: ' . $this->formatBytes($storageInfo['usage'] ?? 0));
                $this->line('   Available: ' . $this->formatBytes(($storageInfo['limit'] ?? 0) - ($storageInfo['usage'] ?? 0)));
            } else {
                $this->warn('⚠️  Could not retrieve storage info: ' . ($storageInfo['error'] ?? 'Unknown error'));
            }
            $this->newLine();

            // Test root folder access
            $this->info('3. Testing root folder access...');
            $rootFolderId = config('google.drive.root_folder_id');

            if (!$rootFolderId) {
                $this->error('❌ GOOGLE_DRIVE_ROOT_FOLDER_ID not configured!');
                $this->warn('Please set GOOGLE_DRIVE_ROOT_FOLDER_ID in your .env file');
                return 1;
            }

            $folderInfo = $driveService->getFileInfo($rootFolderId);

            if (!$folderInfo['success']) {
                $this->error('❌ Cannot access root folder!');
                $this->error('Error: ' . $folderInfo['error']);
                $this->newLine();
                $this->warn('Please check:');
                $this->line('- Root folder ID is correct');
                $this->line('- Folder is shared with service account');
                $this->line('- Service account has Editor permissions');
                return 1;
            }

            $this->info('✅ Root folder accessible!');
            $this->line('   Folder: ' . $folderInfo['filename']);
            $this->line('   ID: ' . $rootFolderId);
            $this->newLine();

            // Setup folder structure if requested
            if ($this->option('setup')) {
                $this->info('4. Setting up folder structure...');
                $this->setupFolderStructure($driveService);
            } else {
                $this->info('🎉 All tests passed!');
                $this->newLine();
                $this->line('Run with --setup flag to create initial folder structure:');
                $this->line('php artisan google:test-connection --setup');
            }

        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Setup initial folder structure
     */
    private function setupFolderStructure(GoogleDriveService $driveService): void
    {
        $folderStructure = config('google.drive.folder_structure');

        $this->line('Creating folder structure...');
        $this->newLine();

        foreach ($folderStructure as $key => $folderName) {
            try {
                $this->line("Creating: {$folderName}");
                $folderId = $driveService->createFolderStructure($folderName);
                $this->info("✅ Created: {$folderName} (ID: {$folderId})");

            } catch (\Exception $e) {
                $this->error("❌ Failed to create {$folderName}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('🎉 Folder structure setup complete!');
        $this->newLine();

        $this->info('📁 Folder Structure Created:');
        $this->line('├── Certificates/');
        $this->line('├── Background-Checks/');
        $this->line('├── Training-Records/');
        $this->line('└── Archived/');
        $this->newLine();

        $this->info('🚀 Google Drive is ready for file storage!');
        $this->line('You can now start uploading certificates through the web interface.');
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);

        return round(pow(1024, $base - floor($base)), 2) . ' ' . $units[floor($base)];
    }
}
