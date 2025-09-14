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
            // Add employee_id column only if it doesn't exist
            if (!Schema::hasColumn('employees', 'employee_id')) {
                $table->string('employee_id', 50)->unique()->after('id')
                      ->comment('Unique employee identifier (external ID)');
            }

            // Add index for employee_id with status for performance
            if (!Schema::hasIndex('employees', 'employees_employee_id_status_index')) {
                $table->index(['employee_id', 'status'], 'employees_employee_id_status_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the index first
            if (Schema::hasIndex('employees', 'employees_employee_id_status_index')) {
                $table->dropIndex('employees_employee_id_status_index');
            }

            // Drop the column if it exists
            if (Schema::hasColumn('employees', 'employee_id')) {
                $table->dropColumn('employee_id');
            }
        });
    }
};