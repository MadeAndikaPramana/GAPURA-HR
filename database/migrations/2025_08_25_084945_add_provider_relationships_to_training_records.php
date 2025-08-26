<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure the training_providers table exists and has proper indexes
        if (Schema::hasTable('training_providers')) {
            Schema::table('training_providers', function (Blueprint $table) {
                // Add indexes for better performance
                if (!Schema::hasColumn('training_providers', 'name')) {
                    $table->string('name')->index();
                } else {
                    try {
                        $table->index('name');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }

                // Add code index
                if (Schema::hasColumn('training_providers', 'code')) {
                    try {
                        $table->index('code');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }

                // Add status indexes for filtering
                if (Schema::hasColumn('training_providers', 'is_active')) {
                    try {
                        $table->index('is_active');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }

                if (Schema::hasColumn('training_providers', 'rating')) {
                    try {
                        $table->index('rating');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }

                // Add composite indexes for date filtering
                if (Schema::hasColumn('training_providers', 'contract_start_date') &&
                    Schema::hasColumn('training_providers', 'contract_end_date')) {
                    try {
                        $table->index(['contract_start_date', 'contract_end_date'], 'idx_contract_period');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }

                if (Schema::hasColumn('training_providers', 'accreditation_expiry')) {
                    try {
                        $table->index('accreditation_expiry');
                    } catch (\Exception $e) {
                        // Index might already exist
                    }
                }
            });
        }

        // Now ensure training_records table has proper provider relationship
        if (Schema::hasTable('training_records')) {
            Schema::table('training_records', function (Blueprint $table) {
                // Ensure training_provider_id column exists
                if (!Schema::hasColumn('training_records', 'training_provider_id')) {
                    $table->foreignId('training_provider_id')->nullable()->constrained('training_providers')->onDelete('set null');
                }

                // Add indexes for provider filtering
                try {
                    $table->index('training_provider_id');
                } catch (\Exception $e) {
                    // Index might already exist
                }

                // Add composite indexes for efficient provider filtering
                try {
                    $table->index(['training_provider_id', 'training_type_id'], 'idx_provider_type');
                } catch (\Exception $e) {
                    // Index might already exist
                }

                try {
                    $table->index(['training_provider_id', 'compliance_status'], 'idx_provider_compliance');
                } catch (\Exception $e) {
                    // Index might already exist
                }

                try {
                    $table->index(['training_provider_id', 'issue_date'], 'idx_provider_issue_date');
                } catch (\Exception $e) {
                    // Index might already exist
                }

                try {
                    $table->index(['training_provider_id', 'expiry_date'], 'idx_provider_expiry');
                } catch (\Exception $e) {
                    // Index might already exist
                }
            });
        }

        // Ensure proper foreign key relationships exist
        if (Schema::hasTable('training_records') && Schema::hasTable('training_providers')) {
            try {
                // Check if foreign key constraint exists, if not, add it
                $foreignKeyExists = collect(DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'training_records'
                    AND COLUMN_NAME = 'training_provider_id'
                    AND REFERENCED_TABLE_NAME = 'training_providers'
                "))->isNotEmpty();

                if (!$foreignKeyExists) {
                    Schema::table('training_records', function (Blueprint $table) {
                        $table->foreign('training_provider_id')
                              ->references('id')
                              ->on('training_providers')
                              ->onUpdate('cascade')
                              ->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {
                // Foreign key might already exist or there might be data integrity issues
                Log::warning('Could not add training_provider foreign key constraint: ' . $e->getMessage());
            }
        }

        // Create a pivot table for training_provider_training_types if needed (for future enhancement)
        if (!Schema::hasTable('training_provider_training_types')) {
            Schema::create('training_provider_training_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_provider_id')->constrained('training_providers')->onDelete('cascade');
                $table->foreignId('training_type_id')->constrained('training_types')->onDelete('cascade');
                $table->boolean('is_certified')->default(true);
                $table->date('certification_date')->nullable();
                $table->date('certification_expiry')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['training_provider_id', 'training_type_id'], 'unique_provider_type');
                $table->index('is_certified');
                $table->index('certification_expiry');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from training_records
        if (Schema::hasTable('training_records')) {
            Schema::table('training_records', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_provider_type');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex('idx_provider_compliance');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex('idx_provider_issue_date');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex('idx_provider_expiry');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['training_provider_id']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
            });
        }

        // Remove indexes from training_providers
        if (Schema::hasTable('training_providers')) {
            Schema::table('training_providers', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_contract_period');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['name']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['code']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['is_active']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['rating']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex(['accreditation_expiry']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
            });
        }

        // Drop the pivot table
        Schema::dropIfExists('training_provider_training_types');
    }
};
