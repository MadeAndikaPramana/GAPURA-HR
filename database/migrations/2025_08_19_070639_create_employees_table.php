<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); // Internal primary key
            $table->string('nip')->unique(); // Can change, but unique
            $table->string('name');
            $table->foreignId('department_id')->constrained();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            // Background Check fields
            $table->json('background_check_files')->nullable();
            $table->date('background_check_date')->nullable();
            $table->enum('background_check_status', [
                'not_started', 'in_progress', 'completed', 'expired'
            ])->default('not_started');
            $table->text('background_check_notes')->nullable();

            // Container metadata
            $table->timestamp('container_created_at')->nullable();
            $table->integer('total_files_count')->default(0);

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'department_id']);
            $table->index(['nip', 'status']);
            $table->index('container_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
