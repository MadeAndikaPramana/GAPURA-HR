<?php
// database/migrations/xxxx_xx_xx_fix_foreign_key_constraints.php
// Create this migration to fix potential foreign key constraint issues

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix employees table foreign key constraints
        Schema::table('employees', function (Blueprint $table) {
            // Make sure supervisor_id allows null and has proper constraint
            $table->foreignId('supervisor_id')->nullable()->change();

            // Add proper foreign key constraint with cascading
            if (!$this->foreignKeyExists('employees', 'employees_supervisor_id_foreign')) {
                $table->foreign('supervisor_id')
                      ->references('id')->on('employees')
                      ->onUpdate('cascade')
                      ->onDelete('set null'); // Set to null when supervisor is deleted
            }

            // Make sure department_id has proper constraint
            if (!$this->foreignKeyExists('employees', 'employees_department_id_foreign')) {
                $table->foreign('department_id')
                      ->references('id')->on('departments')
                      ->onUpdate('cascade')
                      ->onDelete('set null'); // Set to null when department is deleted
            }
        });

        // Fix training_records table constraints
        Schema::table('training_records', function (Blueprint $table) {
            // Employee foreign key should prevent deletion if training records exist
            if ($this->foreignKeyExists('training_records', 'training_records_employee_id_foreign')) {
                $table->dropForeign('training_records_employee_id_foreign');
            }

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onUpdate('cascade')
                  ->onDelete('restrict'); // Prevent employee deletion if training records exist
        });

        // Fix other tables that might reference employees
        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                // If certificates reference employees directly, fix the constraint
                if (Schema::hasColumn('certificates', 'employee_id')) {
                    if ($this->foreignKeyExists('certificates', 'certificates_employee_id_foreign')) {
                        $table->dropForeign('certificates_employee_id_foreign');
                    }

                    $table->foreign('employee_id')
                          ->references('id')->on('employees')
                          ->onUpdate('cascade')
                          ->onDelete('restrict');
                }
            });
        }

        // Add indexes for better performance
        Schema::table('employees', function (Blueprint $table) {
            if (!$this->indexExists('employees', 'employees_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('employees', 'employees_employee_id_index')) {
                $table->index('employee_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if ($this->foreignKeyExists('employees', 'employees_supervisor_id_foreign')) {
                $table->dropForeign('employees_supervisor_id_foreign');
            }
            if ($this->foreignKeyExists('employees', 'employees_department_id_foreign')) {
                $table->dropForeign('employees_department_id_foreign');
            }
        });

        Schema::table('training_records', function (Blueprint $table) {
            if ($this->foreignKeyExists('training_records', 'training_records_employee_id_foreign')) {
                $table->dropForeign('training_records_employee_id_foreign');
            }
        });

        if (Schema::hasTable('certificates')) {
            Schema::table('certificates', function (Blueprint $table) {
                if (Schema::hasColumn('certificates', 'employee_id')) {
                    if ($this->foreignKeyExists('certificates', 'certificates_employee_id_foreign')) {
                        $table->dropForeign('certificates_employee_id_foreign');
                    }
                }
            });
        }
    }

    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists($table, $key): bool
    {
        return collect(DB::select("SHOW CREATE TABLE {$table}"))->first()->{'Create Table'}
               ? str_contains(collect(DB::select("SHOW CREATE TABLE {$table}"))->first()->{'Create Table'}, $key)
               : false;
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table}"))
               ->where('Key_name', $index)
               ->isNotEmpty();
    }
};
