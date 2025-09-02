<?php
// database/migrations/2025_09_01_100000_create_certificate_types_table.php

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
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Aircraft Towing Tractor (ATT)"
            $table->string('code', 10)->unique(); // "ATT", "FRM", "LLD", "BCS", "BTT", etc.
            $table->string('category', 50)->default('GSE_OPERATOR'); // Department category
            $table->integer('validity_months')->default(24); // Most MPGA certificates are 24 months
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['category', 'is_active']);
            $table->index(['code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_types');
    }
};
