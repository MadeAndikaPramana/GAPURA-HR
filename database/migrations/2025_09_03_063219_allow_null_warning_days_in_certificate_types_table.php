<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->integer('warning_days')->nullable()->change();
            $table->integer('validity_months')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->integer('warning_days')->nullable(false)->change();
            $table->integer('validity_months')->nullable(false)->change();
        });
    }
};
