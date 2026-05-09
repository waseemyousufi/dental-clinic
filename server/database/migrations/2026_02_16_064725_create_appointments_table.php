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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->dateTime('appointment_timestamp');
            $table->string('status', 50); // ['Completed', 'Pending']
            $table->string('description')->nullable();
            $table->integer('appointment_cost');
            $table->string('clinical_notes')->nullable();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
