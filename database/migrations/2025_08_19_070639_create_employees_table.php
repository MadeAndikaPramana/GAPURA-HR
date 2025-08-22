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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 20)->unique();
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('position_level', 50)->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->date('hire_date')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('background_check_date')->nullable();
            $table->string('background_check_status', 50)->nullable();
            $table->text('background_check_notes')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
