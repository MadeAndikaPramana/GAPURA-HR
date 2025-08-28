<?php
// database/migrations/2025_08_27_create_certificates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Core identifiers
            $table->string('certificate_number')->unique();
            $table->string('certificate_type')->default('completion'); // completion, competency, compliance
            $table->string('template_type')->nullable(); // for different certificate designs

            // Relationships
            $table->foreignId('training_record_id')->constrained('training_records')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('training_type_id')->constrained('training_types')->onDelete('cascade');
            $table->foreignId('training_provider_id')->nullable()->constrained('training_providers')->onDelete('set null');

            // Certificate Details
            $table->string('issuer_name');
            $table->string('issuer_title')->nullable();
            $table->string('issuer_organization')->nullable();
            $table->text('issuer_signature_path')->nullable(); // path to signature image
            $table->text('issuer_seal_path')->nullable(); // path to official seal

            // Dates
            $table->date('issue_date');
            $table->date('effective_date')->nullable(); // when certificate becomes valid
            $table->date('expiry_date')->nullable();
            $table->timestamp('issued_at'); // exact timestamp when issued

            // Certificate Status
            $table->enum('status', ['draft', 'issued', 'revoked', 'expired', 'renewed'])->default('draft');
            $table->enum('verification_status', ['pending', 'verified', 'invalid', 'under_review'])->default('pending');

            // Security & Verification
            $table->string('qr_code_path')->nullable(); // path to QR code image
            $table->string('verification_code')->unique()->nullable(); // for online verification
            $table->string('blockchain_hash')->nullable(); // for advanced verification
            $table->json('verification_metadata')->nullable(); // additional verification data

            // Achievement Details
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->text('achievements')->nullable(); // skills/competencies achieved
            $table->text('remarks')->nullable(); // special notes or conditions

            // File Management
            $table->string('certificate_file_path')->nullable(); // generated PDF path
            $table->string('original_file_path')->nullable(); // original uploaded file
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('file_hash')->nullable(); // for integrity check

            // Renewal & History
            $table->foreignId('parent_certificate_id')->nullable()->constrained('certificates')->onDelete('set null'); // for renewals
            $table->boolean('is_renewable')->default(true);
            $table->integer('renewal_count')->default(0);
            $table->date('next_renewal_date')->nullable();
            $table->text('renewal_notes')->nullable();

            // Compliance & Audit
            $table->boolean('is_compliance_required')->default(false);
            $table->enum('compliance_status', ['compliant', 'non_compliant', 'pending', 'exempt'])->default('pending');
            $table->timestamp('last_verified_at')->nullable();
            $table->string('verified_by')->nullable(); // external verifier

            // Additional Metadata
            $table->json('custom_fields')->nullable(); // flexible additional data
            $table->text('notes')->nullable();
            $table->enum('print_status', ['not_printed', 'printed', 'reprinted'])->default('not_printed');
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);

            // Audit fields
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes(); // for safe deletion

            // Indexes for performance
            $table->index(['employee_id', 'status', 'expiry_date'], 'idx_employee_status_expiry');
            $table->index(['training_type_id', 'status', 'issue_date'], 'idx_type_status_issue');
            $table->index(['training_provider_id', 'issue_date'], 'idx_provider_issue');
            $table->index(['status', 'expiry_date'], 'idx_status_expiry');
            $table->index(['verification_status', 'last_verified_at'], 'idx_verification');
            $table->index(['is_compliance_required', 'compliance_status'], 'idx_compliance');
            $table->index('next_renewal_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
