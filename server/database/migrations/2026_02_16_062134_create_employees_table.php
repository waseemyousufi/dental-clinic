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
            $table->string('f_name', 15);
            $table->string('l_name', 15);
            $table->string('gender', 15);
            $table->date('hire_date');
            $table->string('qualification', 50);
            $table->string('speciality', 25);
            $table->string('medical_license_number', 13);
            $table->time('work_start_time');
            $table->time('work_end_time');
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
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
