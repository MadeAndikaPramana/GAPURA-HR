<?php
// database/migrations/xxxx_xx_xx_fix_training_type_department_requirements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate the table with correct structure
        Schema::dropIfExists('training_type_department_requirements');

        Schema::create('training_type_department_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_type_id')->constrained('training_types')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');

            $table->boolean('is_required')->default(true);
            $table->integer('frequency_months')->nullable()->comment('How often this training is required');
            $table->decimal('target_compliance_rate', 5, 2)->default(95.00);
            $table->text('department_specific_requirements')->nullable();

            $table->timestamps();

            $table->unique(['training_type_id', 'department_id'], 'unique_training_dept');
            $table->index(['department_id', 'is_required'], 'idx_dept_required');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_type_department_requirements');
    }
};
