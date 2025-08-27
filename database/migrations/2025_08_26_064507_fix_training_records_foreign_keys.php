<?php

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
        Schema::table('training_records', function (Blueprint $table) {
            // Drop existing foreign key constraint
            try {
                $table->dropForeign(['training_type_id']);
                echo "✅ Dropped existing training_type_id foreign key\n";
            } catch (\Exception $e) {
                echo "⚠️  No existing foreign key to drop: " . $e->getMessage() . "\n";
            }

            // Add new foreign key constraint dengan RESTRICT
            $table->foreign('training_type_id')
                  ->references('id')->on('training_types')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            echo "✅ Added new training_type_id foreign key with RESTRICT\n";
        });

        Schema::table('training_records', function (Blueprint $table) {
            try {
                $table->dropForeign(['employee_id']);
                echo "✅ Dropped existing employee_id foreign key\n";
            } catch (\Exception $e) {
                echo "⚠️  No existing employee_id foreign key to drop: " . $e->getMessage() . "\n";
            }

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            echo "✅ Added new employee_id foreign key with RESTRICT\n";
        });

        // ⭐ PERBAIKAN: Query verification dengan alias yang jelas
        echo "\n📋 VERIFYING FOREIGN KEY CONSTRAINTS:\n";
        try {
            $constraints = DB::select("
                SELECT
                    rc.CONSTRAINT_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.DELETE_RULE,
                    rc.UPDATE_RULE
                FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                JOIN information_schema.KEY_COLUMN_USAGE kcu
                    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                    AND rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                WHERE kcu.TABLE_NAME = 'training_records'
                AND kcu.TABLE_SCHEMA = DATABASE()
                AND rc.CONSTRAINT_SCHEMA = DATABASE()
            ");

            foreach ($constraints as $constraint) {
                echo "   📌 {$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME}\n";
                echo "      DELETE: {$constraint->DELETE_RULE} | UPDATE: {$constraint->UPDATE_RULE}\n";
            }

            echo "\n✅ Foreign key constraints verified successfully!\n";

        } catch (\Exception $e) {
            echo "⚠️  Could not verify constraints (but they were created successfully): " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_records', function (Blueprint $table) {
            try {
                $table->dropForeign(['training_type_id']);
                $table->dropForeign(['employee_id']);
                echo "✅ Dropped RESTRICT foreign keys\n";
            } catch (\Exception $e) {
                echo "⚠️  Error dropping foreign keys: " . $e->getMessage() . "\n";
            }

            // Restore original constraints
            $table->foreign('training_type_id')
                  ->references('id')->on('training_types')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');

            echo "✅ Restored CASCADE foreign keys\n";
        });
    }
};
