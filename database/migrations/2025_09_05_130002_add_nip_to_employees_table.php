<?php
// database/migrations/2025_09_05_120000_add_nip_to_employees_table.php

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
            // Add NIP field only if it doesn't exist
            if (!Schema::hasColumn('employees', 'nip')) {
                $table->string('nip', 20)->unique()->nullable()->after('employee_id')
                      ->comment('Employee NIP number (can change over time)');
            }

            // Add container metadata only if they don't exist
            if (!Schema::hasColumn('employees', 'container_created_at')) {
                $table->timestamp('container_created_at')->nullable()->after('profile_photo_path')
                      ->comment('When employee container was first created');
            }

            if (!Schema::hasColumn('employees', 'total_files_count')) {
                $table->integer('total_files_count')->default(0)->after('container_created_at')
                      ->comment('Total count of files in employee container');
            }

            // Add index for NIP lookups if it doesn't exist
            if (!Schema::hasIndex('employees', 'idx_nip_status')) {
                $table->index(['nip', 'status'], 'idx_nip_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_nip_status');
            $table->dropColumn(['nip', 'container_created_at', 'total_files_count']);
        });
    }
};
