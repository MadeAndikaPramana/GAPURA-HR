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
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained('training_types');
            $table->string('certificate_number', 100)->unique();
            $table->string('issuer', 50);
            $table->date('issue_date');
            $table->foreignId('training_provider_id')->nullable()->constrained('training_providers');
            $table->string('batch_number', 50)->nullable();
            $table->date('training_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['registered', 'in_progress', 'completed', 'cancelled'])->default('registered');
            $table->enum('compliance_status', ['compliant', 'expiring_soon', 'expired', 'not_required'])->default('compliant');
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->decimal('training_hours', 5, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('instructor_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['expiry_date', 'status']);
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
