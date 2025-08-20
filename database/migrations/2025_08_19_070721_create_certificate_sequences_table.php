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
        Schema::create('certificate_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_type_id')->constrained('training_types');
            $table->string('issuer', 50);
            $table->year('year');
            $table->tinyInteger('month');
            $table->integer('last_number')->default(0);
            $table->timestamps();

            $table->unique(['training_type_id', 'issuer', 'year', 'month'], 'unique_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_sequences');
    }
};
