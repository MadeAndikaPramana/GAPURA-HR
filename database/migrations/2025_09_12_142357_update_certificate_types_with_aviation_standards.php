<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CertificateType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First ensure any missing columns exist
        Schema::table('certificate_types', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_types', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('learning_objectives');
            }
            
            if (!Schema::hasColumn('certificate_types', 'estimated_duration_hours')) {
                $table->decimal('estimated_duration_hours', 5, 2)->nullable()->after('estimated_cost');
            }
            
            if (!Schema::hasColumn('certificate_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('estimated_duration_hours');
            }
        });

        // Update existing certificate types with aviation industry standards
        $this->updateExistingCertificateTypes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't remove the updates to existing data as this could cause data loss
        // Only remove columns if they were added by this migration
        Schema::table('certificate_types', function (Blueprint $table) {
            // Check if columns were added by this migration before dropping
            // In practice, these columns should already exist from previous migrations
            // This is just for safety
        });
    }

    /**
     * Update existing certificate types with proper aviation standards
     */
    private function updateExistingCertificateTypes(): void
    {
        // Update ATT (Aircraft Towing Tractor) if it exists
        CertificateType::where('code', 'ATT')->update([
            'category' => 'Ground Support Equipment',
            'validity_months' => 24,
            'warning_days' => 60,
            'description' => 'Certificate for operating aircraft towing tractors',
            'requirements' => 'Ground support equipment operators, practical assessment required',
            'learning_objectives' => 'Safe operation of aircraft towing equipment, pre-flight checks, emergency procedures',
            'estimated_cost' => 500000,
            'estimated_duration_hours' => 24,
            'is_active' => true
        ]);

        // Update FRM (Fork Lift Management) if it exists
        CertificateType::where('code', 'FRM')->update([
            'name' => 'Fork Lift Management (FRM)',
            'category' => 'Ground Support Equipment',
            'validity_months' => 36,
            'warning_days' => 90,
            'description' => 'Forklift operation and management certification',
            'requirements' => 'Cargo handling and warehouse operations',
            'learning_objectives' => 'Safe forklift operation, load handling, maintenance procedures',
            'estimated_cost' => 450000,
            'estimated_duration_hours' => 20,
            'is_active' => true
        ]);

        // Update LLD (Leadership Development) if it exists
        CertificateType::where('code', 'LLD')->update([
            'name' => 'Leadership Development (LLD)',
            'category' => 'Management',
            'validity_months' => null,
            'warning_days' => null,
            'is_mandatory' => false,
            'is_recurrent' => false,
            'description' => 'Comprehensive leadership development program for supervisors and managers',
            'requirements' => 'For employees in or aspiring to leadership positions',
            'learning_objectives' => 'Develop leadership skills, manage teams effectively, strategic thinking',
            'estimated_cost' => 2000000,
            'estimated_duration_hours' => 40,
            'is_active' => true
        ]);

        // Ensure all existing types have is_active set to true
        CertificateType::whereNull('is_active')->update(['is_active' => true]);

        // Update any generic or outdated categories to proper aviation standards
        $this->updateCategoriesToAviationStandards();

        echo "✅ Updated existing certificate types with aviation industry standards\n";
    }

    /**
     * Update categories to aviation standards
     */
    private function updateCategoriesToAviationStandards(): void
    {
        // Update generic categories to specific aviation categories
        CertificateType::where('category', 'Safety')->update(['category' => 'Aviation Safety']);
        CertificateType::where('category', 'GSE_Operator')->update(['category' => 'Ground Support Equipment']);
        CertificateType::where('category', 'Technical')->update(['category' => 'Ground Support Equipment']);
        CertificateType::where('category', 'Aviation')->update(['category' => 'Aviation Security']);
    }
};
