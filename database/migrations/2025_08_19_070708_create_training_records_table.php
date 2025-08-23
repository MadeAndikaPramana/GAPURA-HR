<?php

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
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained('training_types');
            $table->foreignId('training_provider_id')->nullable()->constrained('training_providers');

            // Certificate information
            $table->string('certificate_number', 100)->unique();
            $table->string('issuer', 100);

            // Dates
            $table->date('issue_date');
            $table->date('completion_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('training_date')->nullable();

            // Status tracking
            $table->enum('status', [
                'registered',
                'in_progress',
                'completed',
                'cancelled',
                'active',           // Certificate is active/valid
                'expiring_soon',    // Certificate expires within 30 days
                'expired'           // Certificate has expired
            ])->default('completed');

            $table->enum('compliance_status', [
                'compliant',        // Certificate is valid and active
                'expiring_soon',    // Certificate expires within warning period
                'expired',          // Certificate has expired
                'not_required'      // Training not required for this employee
            ])->default('compliant');

            // Training details
            $table->string('batch_number', 50)->nullable();
            $table->decimal('score', 5, 2)->nullable()->comment('Training score (0-100)');
            $table->decimal('passing_score', 5, 2)->nullable()->comment('Minimum passing score');
            $table->decimal('training_hours', 5, 2)->nullable()->comment('Total training hours');
            $table->decimal('cost', 12, 2)->nullable()->comment('Training cost in IDR');

            // Location and instructor
            $table->string('location', 255)->nullable()->comment('Training location');
            $table->string('instructor_name', 255)->nullable()->comment('Training instructor name');

            // Additional information
            $table->text('notes')->nullable()->comment('Additional notes about the training');

            // Reminder tracking
            $table->timestamp('reminder_sent_at')->nullable()->comment('When last reminder was sent');
            $table->integer('reminder_count')->default(0)->comment('Number of reminders sent');

            // Audit fields
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes for performance
            $table->index(['expiry_date', 'status'], 'idx_expiry_status');
            $table->index(['employee_id', 'training_type_id'], 'idx_employee_training');
            $table->index(['status', 'compliance_status'], 'idx_status_compliance');
            $table->index('issue_date', 'idx_issue_date');

            // Composite index for certificate lookups
            $table->index(['certificate_number', 'status'], 'idx_cert_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};
