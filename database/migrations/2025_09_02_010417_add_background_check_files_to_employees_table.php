<?php
// database/migrations/2025_09_01_100002_add_background_check_files_to_employees_table.php

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
            // Add JSON field for storing background check file attachments
            $table->json('background_check_files')->nullable()->after('background_check_notes')
                  ->comment('Array of uploaded background check documents');

            // Add general notes field for employee container
            $table->text('notes')->nullable()->after('background_check_files')
                  ->comment('General notes about the employee');

            // Enhance existing background_check_status with more options
            $table->dropColumn('background_check_status');
        });

        // Add the enhanced background_check_status column
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('background_check_status', [
                'not_started',      // Background check not yet initiated
                'in_progress',      // Background check in progress
                'cleared',          // Background check completed and cleared
                'pending_review',   // Background check completed, awaiting review
                'requires_follow_up', // Issues found, requires follow-up
                'expired',          // Background check has expired, needs renewal
                'rejected'          // Background check rejected
            ])->nullable()->after('background_check_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['background_check_files', 'notes']);
            $table->dropColumn('background_check_status');
        });

        // Restore original background_check_status
        Schema::table('employees', function (Blueprint $table) {
            $table->string('background_check_status')->nullable()->after('background_check_notes');
        });
    }
};
