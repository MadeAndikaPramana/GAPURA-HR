<?php
// database/migrations/2025_01_09_100000_create_file_storage_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_storage', function (Blueprint $table) {
            $table->id();

            // Employee relationship
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('certificate_type_id')->constrained('certificate_types')->onDelete('cascade');

            // Version tracking for recurrent certificates
            $table->integer('version_number')->default(1)->comment('Version number for recurrent certificates');

            // Certificate validity period
            $table->date('issue_date')->comment('Certificate issue/start date');
            $table->date('expiry_date')->comment('Certificate expiry date');

            // Google Drive integration
            $table->string('drive_file_id')->unique()->comment('Google Drive file ID');
            $table->string('drive_folder_id')->nullable()->comment('Google Drive parent folder ID');
            $table->text('drive_web_view_link')->nullable()->comment('Google Drive shareable view link');
            $table->text('drive_download_link')->nullable()->comment('Google Drive direct download link');

            // File metadata
            $table->string('original_filename')->comment('Original uploaded filename');
            $table->string('stored_filename')->comment('Stored filename with version');
            $table->string('storage_path')->comment('Path structure in Drive');
            $table->string('mime_type')->comment('File MIME type');
            $table->bigInteger('file_size')->comment('File size in bytes');
            $table->string('file_hash')->nullable()->comment('File hash for duplicate detection');

            // Upload tracking
            $table->timestamp('uploaded_at')->comment('When file was uploaded');
            $table->foreignId('uploaded_by')->constrained('users')->comment('User who uploaded the file');

            // Status tracking
            $table->enum('status', [
                'uploading',      // Currently being uploaded
                'processing',     // Processing/validation
                'stored',         // Successfully stored
                'failed',         // Upload/storage failed
                'archived'        // Archived but kept for audit
            ])->default('uploading');

            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional file metadata (JSON)');
            $table->text('notes')->nullable()->comment('User notes or comments');

            // Audit trail
            $table->timestamps();

            // INDEXES for performance

            // Employee container view (primary use case)
            $table->index(['employee_id', 'status'], 'idx_employee_files');

            // Certificate type queries
            $table->index(['certificate_type_id', 'status'], 'idx_cert_type_files');

            // Recurrent certificate tracking
            $table->index(['employee_id', 'certificate_type_id', 'version_number'], 'idx_recurrent_tracking');

            // Date range queries (expiry tracking, validity periods)
            $table->index(['expiry_date', 'status'], 'idx_expiry_status');
            $table->index(['issue_date', 'expiry_date'], 'idx_validity_period');

            // Google Drive operations
            $table->index('drive_file_id');
            $table->index(['status', 'uploaded_at'], 'idx_upload_status');

            // File duplicate detection
            $table->index(['file_hash', 'file_size'], 'idx_duplicate_detection');

            // Audit and reporting
            $table->index(['uploaded_by', 'uploaded_at'], 'idx_upload_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_storage');
    }
};
