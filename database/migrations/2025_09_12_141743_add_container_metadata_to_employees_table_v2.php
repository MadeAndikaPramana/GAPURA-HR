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
        Schema::table('employees', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('employees', 'container_created_at')) {
                $table->timestamp('container_created_at')->nullable();
            }
            
            if (!Schema::hasColumn('employees', 'container_status')) {
                $table->enum('container_status', ['active', 'inactive', 'archived', 'error'])->default('active');
            }
            
            if (!Schema::hasColumn('employees', 'container_file_count')) {
                $table->integer('container_file_count')->default(0);
            }
            
            if (!Schema::hasColumn('employees', 'container_last_updated')) {
                $table->timestamp('container_last_updated')->nullable();
            }
        });

        // Add indexes safely
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->index(['container_status', 'container_created_at'], 'idx_container_status_created');
                $table->index(['container_last_updated'], 'idx_container_last_updated');
                $table->index(['employee_id', 'container_status'], 'idx_employee_container');
            });
        } catch (\Exception $e) {
            // Indexes might already exist, ignore the error
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop indexes first (ignore errors if they don't exist)
            try {
                $table->dropIndex('idx_container_status_created');
                $table->dropIndex('idx_container_last_updated');
                $table->dropIndex('idx_employee_container');
            } catch (\Exception $e) {
                // Indexes might not exist, ignore
            }
            
            // Drop columns (only if they exist)
            if (Schema::hasColumn('employees', 'container_created_at')) {
                $table->dropColumn('container_created_at');
            }
            
            if (Schema::hasColumn('employees', 'container_status')) {
                $table->dropColumn('container_status');
            }
            
            if (Schema::hasColumn('employees', 'container_file_count')) {
                $table->dropColumn('container_file_count');
            }
            
            if (Schema::hasColumn('employees', 'container_last_updated')) {
                $table->dropColumn('container_last_updated');
            }
        });
    }
};
