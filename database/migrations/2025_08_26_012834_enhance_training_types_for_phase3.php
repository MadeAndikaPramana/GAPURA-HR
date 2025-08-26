<?php
// database/migrations/2025_08_25_100000_enhance_training_types_for_phase3.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhance training_types table for Phase 3 Analytics
     */
    public function up(): void
    {
        Schema::table('training_types', function (Blueprint $table) {
            // Mandatory and compliance tracking
            $table->boolean('is_mandatory')->default(false)->after('is_active');
            $table->integer('validity_period_months')->default(12)->after('validity_months');
            $table->integer('warning_period_days')->default(30)->after('validity_period_months');

            // Provider and cost information
            $table->foreignId('default_provider_id')->nullable()->after('warning_period_days')
                  ->constrained('training_providers')->onDelete('set null');
            $table->decimal('estimated_cost', 12, 2)->nullable()->after('default_provider_id');
            $table->decimal('estimated_duration_hours', 5, 2)->nullable()->after('estimated_cost');

            // Training requirements and objectives
            $table->text('requirements')->nullable()->after('estimated_duration_hours')
                  ->comment('Prerequisites and requirements for this training');
            $table->text('learning_objectives')->nullable()->after('requirements')
                  ->comment('Learning objectives and outcomes');

            // Certification and renewal
            $table->boolean('requires_certification')->default(true)->after('learning_objectives');
            $table->boolean('auto_renewal_available')->default(false)->after('requires_certification');
            $table->integer('max_participants_per_batch')->nullable()->after('auto_renewal_available');

            // Analytics and priority
            $table->integer('priority_score')->default(0)->after('max_participants_per_batch')
                  ->comment('Calculated priority score for training scheduling');
            $table->decimal('compliance_target_percentage', 5, 2)->default(95.00)->after('priority_score')
                  ->comment('Target compliance percentage for this training');

            // Department and role applicability
            $table->json('applicable_departments')->nullable()->after('compliance_target_percentage')
                  ->comment('JSON array of department IDs this training applies to');
            $table->json('applicable_job_levels')->nullable()->after('applicable_departments')
                  ->comment('JSON array of job levels this training applies to');

            // Additional metadata
            $table->string('certificate_template', 255)->nullable()->after('applicable_job_levels')
                  ->comment('Path to certificate template file');
            $table->json('custom_fields')->nullable()->after('certificate_template')
                  ->comment('Additional custom fields specific to this training type');

            // Audit and tracking
            $table->timestamp('last_analytics_update')->nullable()->after('custom_fields')
                  ->comment('When analytics were last calculated for this training type');
            $table->foreignId('created_by_id')->nullable()->after('last_analytics_update')
                  ->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->after('created_by_id')
                  ->constrained('users')->onDelete('set null');
        });

        // Update validity_months to validity_period_months
        DB::statement('UPDATE training_types SET validity_period_months = validity_months WHERE validity_period_months = 12');

        // Add indexes for performance
        Schema::table('training_types', function (Blueprint $table) {
            $table->index(['is_mandatory', 'is_active'], 'idx_mandatory_active');
            $table->index(['category', 'is_mandatory'], 'idx_category_mandatory');
            $table->index('priority_score', 'idx_priority_score');
            $table->index('validity_period_months', 'idx_validity_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_type_department_requirements');
        Schema::dropIfExists('training_type_statistics');

        Schema::table('training_types', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_mandatory_active');
            $table->dropIndex('idx_category_mandatory');
            $table->dropIndex('idx_priority_score');
            $table->dropIndex('idx_validity_period');

            // Drop new columns
            $table->dropForeign(['created_by_id']);
            $table->dropForeign(['updated_by_id']);
            $table->dropForeign(['default_provider_id']);

            $table->dropColumn([
                'is_mandatory',
                'validity_period_months',
                'warning_period_days',
                'default_provider_id',
                'estimated_cost',
                'estimated_duration_hours',
                'requirements',
                'learning_objectives',
                'requires_certification',
                'auto_renewal_available',
                'max_participants_per_batch',
                'priority_score',
                'compliance_target_percentage',
                'applicable_departments',
                'applicable_job_levels',
                'certificate_template',
                'custom_fields',
                'last_analytics_update',
                'created_by_id',
                'updated_by_id'
            ]);
        });
    }
};
