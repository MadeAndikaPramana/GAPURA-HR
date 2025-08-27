<?php
// database/migrations/2025_08_27_100000_create_enhanced_certificates_table.php

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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Core Relationships
            $table->foreignId('training_record_id')->constrained('training_records')->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained('training_types');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('training_provider_id')->nullable()->constrained('training_providers');

            // Certificate Identity & Numbering
            $table->string('certificate_number', 100)->unique()->index();
            $table->string('certificate_series', 20)->nullable()->comment('Certificate series/batch identifier');
            $table->string('verification_code', 20)->unique()->index();
            $table->string('digital_signature', 255)->nullable()->comment('Digital signature hash');

            // Issuing Information
            $table->string('issued_by', 255)->comment('Organization/authority that issued certificate');
            $table->string('issuer_name', 255)->nullable()->comment('Name of person who issued certificate');
            $table->string('issuer_title', 255)->nullable()->comment('Title of issuer');
            $table->string('issuer_license', 100)->nullable()->comment('Issuer license/accreditation number');

            // Certificate Dates & Validity
            $table->date('issue_date');
            $table->date('valid_from')->nullable()->comment('Date from which certificate becomes valid');
            $table->date('expiry_date')->nullable();
            $table->date('original_expiry_date')->nullable()->comment('Original expiry before any extensions');
            $table->integer('validity_period_days')->nullable()->comment('Validity period in days');

            // Certificate Status & Lifecycle
            $table->enum('status', [
                'draft',          // Certificate being prepared
                'active',         // Valid and active certificate
                'expiring_soon',  // Certificate expires within warning period
                'expired',        // Certificate has expired
                'revoked',        // Certificate has been revoked
                'suspended',      // Certificate temporarily suspended
                'renewed',        // Certificate has been renewed (superseded)
                'cancelled'       // Certificate cancelled
            ])->default('active')->index();

            $table->enum('lifecycle_stage', [
                'issued',         // Freshly issued
                'active',         // In active use
                'renewal_due',    // Due for renewal
                'under_review',   // Under compliance review
                'archived'        // Archived/historical
            ])->default('issued');

            // Certificate Details
            $table->text('certificate_description')->nullable();
            $table->json('competencies_achieved')->nullable()->comment('List of competencies/skills achieved');
            $table->json('assessment_results')->nullable()->comment('Detailed assessment scores');
            $table->decimal('final_score', 5, 2)->nullable()->comment('Final certificate score');
            $table->decimal('passing_score', 5, 2)->nullable()->comment('Minimum passing score');
            $table->string('grade', 10)->nullable()->comment('Certificate grade (A, B, C, etc.)');

            // Certificate Files & Documents
            $table->string('certificate_file_path')->nullable()->comment('Path to PDF certificate file');
            $table->string('certificate_template')->nullable()->comment('Template used for certificate');
            $table->string('qr_code_path')->nullable()->comment('Path to QR code image');
            $table->json('additional_documents')->nullable()->comment('Additional supporting documents');
            $table->integer('file_size_kb')->nullable()->comment('Certificate file size in KB');
            $table->string('file_hash', 64)->nullable()->comment('File integrity hash');

            // Verification & Security
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verification_date')->nullable();
            $table->foreignId('verified_by_id')->nullable()->constrained('users');
            $table->text('verification_notes')->nullable();
            $table->integer('verification_attempts')->default(0);
            $table->timestamp('last_verification_attempt')->nullable();
            $table->string('blockchain_hash', 128)->nullable()->comment('Blockchain verification hash');

            // Renewal & Extension Tracking
            $table->boolean('is_renewable')->default(true);
            $table->date('renewal_due_date')->nullable();
            $table->date('renewal_reminder_sent')->nullable();
            $table->integer('renewal_reminder_count')->default(0);
            $table->foreignId('renewed_from_id')->nullable()->constrained('certificates')->comment('Previous certificate if renewed');
            $table->foreignId('renewed_to_id')->nullable()->constrained('certificates')->comment('New certificate if this was renewed');
            $table->integer('renewal_generation')->default(1)->comment('Generation number (1=original, 2=first renewal, etc.)');

            // Compliance & Monitoring
            $table->date('last_compliance_check')->nullable();
            $table->enum('compliance_status', [
                'compliant',
                'non_compliant',
                'under_review',
                'exempted'
            ])->default('compliant');
            $table->text('compliance_notes')->nullable();
            $table->json('compliance_checklist')->nullable()->comment('Compliance requirements checklist');

            // Revocation & Suspension
            $table->date('revocation_date')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->foreignId('revoked_by_id')->nullable()->constrained('users');
            $table->text('revocation_notes')->nullable();
            $table->date('suspension_start')->nullable();
            $table->date('suspension_end')->nullable();
            $table->text('suspension_reason')->nullable();

            // Analytics & Tracking
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded')->nullable();
            $table->json('usage_statistics')->nullable()->comment('Certificate usage analytics');
            $table->decimal('cost_per_certificate', 10, 2)->nullable()->comment('Cost to issue this certificate');

            // Metadata & Audit Trail
            $table->json('metadata')->nullable()->comment('Additional certificate metadata');
            $table->text('internal_notes')->nullable()->comment('Internal notes not shown on certificate');
            $table->string('language', 5)->default('id')->comment('Certificate language');
            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['employee_id', 'status']);
            $table->index(['training_type_id', 'status']);
            $table->index(['issue_date', 'expiry_date']);
            $table->index(['status', 'lifecycle_stage']);
            $table->index(['renewal_due_date', 'is_renewable']);
            $table->index('compliance_status');

            // Composite indexes
            $table->index(['training_provider_id', 'issue_date'], 'idx_provider_issue');
            $table->index(['status', 'expiry_date'], 'idx_status_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
