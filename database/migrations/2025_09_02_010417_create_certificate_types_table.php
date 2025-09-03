<?php
// database/migrations/2025_09_03_100001_create_certificate_types_table.php

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
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();

            // Basic certificate information
            $table->string('name', 100)->comment('Fire Safety Training');
            $table->string('code', 50)->unique()->comment('FIRE_SAFETY');
            $table->string('category', 50)->nullable()->comment('Safety, Technical, Management, etc');

            // Validity and recurrent rules
            $table->integer('validity_months')->nullable()->comment('Certificate validity period in months');
            $table->integer('warning_days')->default(90)->comment('Days before expiry to show warning');
            $table->boolean('is_mandatory')->default(false)->comment('Required for all employees');
            $table->boolean('is_recurrent')->default(true)->comment('Can be renewed/retaken');

            // Additional information
            $table->text('description')->nullable()->comment('What this certificate is about');
            $table->text('requirements')->nullable()->comment('Requirements to obtain this certificate');
            $table->text('learning_objectives')->nullable()->comment('What employee will learn');

            // Status and metadata
            $table->boolean('is_active')->default(true)->comment('Whether this certificate type is still used');
            $table->decimal('estimated_cost', 10, 2)->nullable()->comment('Estimated cost for this training');
            $table->decimal('estimated_duration_hours', 5, 2)->nullable()->comment('Estimated training duration');

            $table->timestamps();

            // Indexes for better performance
            $table->index(['is_active', 'category'], 'idx_active_category');
            $table->index(['is_mandatory', 'is_active'], 'idx_mandatory_active');
            $table->index('code', 'idx_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_types');
    }
};
