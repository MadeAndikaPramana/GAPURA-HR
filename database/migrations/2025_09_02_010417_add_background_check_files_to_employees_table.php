<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only add columns that don't exist
            if (!Schema::hasColumn('employees', 'background_check_files')) {
                $table->json('background_check_files')->nullable()
                      ->comment('Array of uploaded background check documents');
            }

            if (!Schema::hasColumn('employees', 'background_check_date')) {
                $table->date('background_check_date')->nullable();
            }

            if (!Schema::hasColumn('employees', 'background_check_status')) {
                $table->enum('background_check_status', [
                    'not_started', 'in_progress', 'cleared', 'pending_review',
                    'requires_follow_up', 'expired', 'rejected'
                ])->default('not_started');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'background_check_files',
                'background_check_date',
                'background_check_status'
            ]);
        });
    }
};
