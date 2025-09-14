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
            // Make nip column nullable and remove unique constraint if it exists
            $table->string('nip', 255)->nullable()->change();
            
            // Drop unique constraint on nip if it exists
            try {
                $table->dropUnique(['nip']);
            } catch (\Exception $e) {
                // Unique constraint might not exist, that's okay
            }
            
            // Re-add unique constraint but allowing nulls
            $table->unique('nip', 'employees_nip_unique_nullable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop the nullable unique constraint
            try {
                $table->dropUnique('employees_nip_unique_nullable');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            // Make nip required again and add unique constraint
            $table->string('nip', 255)->nullable(false)->change();
            $table->unique('nip');
        });
    }
};