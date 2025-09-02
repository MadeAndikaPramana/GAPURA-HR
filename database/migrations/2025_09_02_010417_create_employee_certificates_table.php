<?php
// database/migrations/2025_09_01_100001_create_employee_certificates_table.php

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
        Schema::create('employee_certificates', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('certificate_type_id')->constrained();

            // Certificate identification
            $table->string('certificate_number')->unique(); // "GLC/GSEOP-400669/JUN/2024"
            $table->string('issuer')->default('GLC (Gapura Learning Center)');
            $table->string('training_provider')->nullable();

            // Dates
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->date('training_date')->nullable();

            // Status tracking - CRITICAL for recurrent certificates
            $table->enum('status', [
                'pending',          // Training scheduled but not completed
                'completed',        // Training completed, certificate issued
                'active',           // Certificate is currently valid
                'expiring_soon',    // Certificate expires within 30 days
                'expired'           // Certificate has expired
            ])->default('completed');

            $table->enum('compliance_status', [
                'compliant',        // Certificate is valid and active
                'expiring_soon',    // Certificate expires within warning period
                'expired',          // Certificate has expired
                'not_required'      // Training not required for this employee role
            ])->default('compliant');

            // Training details
            $table->decimal('score', 5, 2)->nullable()->comment('Training score (0-100)');
            $table->decimal('passing_score', 5, 2)->nullable()->comment('Minimum passing score');
            $table->decimal('training_hours', 5, 2)->nullable()->comment('Total training hours');
            $table->decimal('cost', 12, 2)->nullable()->comment('Training cost in IDR');

            // Location and instructor
            $table->string('location', 255)->nullable()->comment('Training location');
            $table->string('instructor_name', 255)->nullable()->comment('Training instructor name');

            // File attachments for scanned certificates
            $table->json('certificate_files')->nullable()->comment('Array of uploaded certificate files');

            // Additional information
            $table->text('notes')->nullable()->comment('Additional notes about the certificate');

            // Reminder tracking
            $table->timestamp('reminder_sent_at')->nullable()->comment('When last reminder was sent');
            $table->integer('reminder_count')->default(0)->comment('Number of reminders sent');

            // Audit fields
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->timestamps();

            // Critical indexes for performance and recurrent certificate queries
            $table->index(['employee_id', 'certificate_type_id'], 'idx_employee_cert_type');
            $table->index(['status', 'expiry_date'], 'idx_status_expiry');
            $table->index(['certificate_type_id', 'status'], 'idx_cert_type_status');
            $table->index(['expiry_date', 'status'], 'idx_expiry_status');

            // Index for certificate lookups
            $table->index(['certificate_number', 'status'], 'idx_cert_number_status');

            // Index for employee container view (show all certs for one employee)
            $table->index(['employee_id', 'status', 'expiry_date'], 'idx_employee_container');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_certificates');
    }
};
