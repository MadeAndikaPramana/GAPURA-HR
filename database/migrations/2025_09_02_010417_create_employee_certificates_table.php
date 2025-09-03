<?php
// database/migrations/2025_09_03_100002_create_employee_certificates_table.php

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
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('certificate_type_id')->constrained('certificate_types')->onDelete('cascade');

            // Certificate identification
            $table->string('certificate_number', 100)->comment('Unique certificate identifier');
            $table->string('issuer', 100)->comment('Organization that issued the certificate');
            $table->string('training_provider', 255)->nullable()->comment('Company/organization that provided training');

            // Critical dates for recurrent certificate tracking
            $table->date('issue_date')->comment('When certificate was issued');
            $table->date('expiry_date')->nullable()->comment('When certificate expires');
            $table->date('completion_date')->nullable()->comment('When training was completed');
            $table->date('training_date')->nullable()->comment('When training was conducted');

            // Status lifecycle for container management
            $table->enum('status', [
                'pending',          // Training scheduled but not completed
                'completed',        // Training completed, certificate issued
                'active',           // Certificate is currently valid
                'expiring_soon',    // Certificate expiring within warning period
                'expired'           // Certificate has expired
            ])->default('pending')->comment('Current status of certificate');

            // File attachments (PDF/JPG storage)
            $table->json('certificate_files')->nullable()->comment('Array of uploaded certificate files');

            // Training details
            $table->decimal('training_hours', 5, 2)->nullable()->comment('Duration of training in hours');
            $table->decimal('cost', 10, 2)->nullable()->comment('Cost of training/certification');
            $table->string('score', 10)->nullable()->comment('Test/evaluation score');
            $table->string('location', 255)->nullable()->comment('Where training was conducted');
            $table->string('instructor_name', 255)->nullable()->comment('Name of instructor/trainer');
            $table->text('notes')->nullable()->comment('Additional notes about certificate');

            // Reminder and notification tracking
            $table->timestamp('reminder_sent_at')->nullable()->comment('When last expiry reminder was sent');
            $table->integer('reminder_count')->default(0)->comment('Number of reminders sent');

            // Audit trail
            $table->foreignId('created_by_id')->nullable()->constrained('users')->comment('Who added this certificate');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->comment('Who last updated this certificate');
            $table->timestamps();

            // CRITICAL INDEXES FOR CONTAINER SYSTEM PERFORMANCE

            // For employee container view (show all certificates for one employee)
            $table->index(['employee_id', 'status', 'expiry_date'], 'idx_employee_container');

            // For recurrent certificate queries (same employee + same type)
            $table->index(['employee_id', 'certificate_type_id', 'issue_date'], 'idx_recurrent_lookup');

            // For certificate status management and automated updates
            $table->index(['status', 'expiry_date'], 'idx_status_expiry');

            // For certificate type analysis
            $table->index(['certificate_type_id', 'status'], 'idx_cert_type_status');

            // For certificate lookup and validation
            $table->index(['certificate_number', 'status'], 'idx_cert_number_status');

            // For audit and compliance reporting
            $table->index(['created_at', 'status'], 'idx_created_status');
            $table->index(['expiry_date', 'status'], 'idx_expiry_status_lookup');
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
