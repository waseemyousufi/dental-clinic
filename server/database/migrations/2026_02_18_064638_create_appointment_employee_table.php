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
        Schema::create('appointment_employee', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unique(['appointment_id', 'employee_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_employee', function (Blueprint $table) {
            $table->dropForeign('appointment_id');
            $table->dropForeign('employee_id');
            $table->dropUnique(['appointment_id', 'employee_id']);
            $table->dropTimestamps();
        });
    }
};
