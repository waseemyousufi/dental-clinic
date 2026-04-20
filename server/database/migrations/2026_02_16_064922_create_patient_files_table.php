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
        Schema::create('patient_files', function (Blueprint $table) {
            $table->id();
            $table->string('diagnosis', 200);
            $table->text('notes')->nullable();
            $table->json('odontogram_data')->nullable();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointmentDate_id')->nullable()->unique()->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('allergy_id')->nullable()->constrained('allergies')->cascadeOnDelete();
            $table->foreignId('treatment_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('diagnosis_notes', 150)->nullable();
            $table->foreign('diagnosis_notes')->nullable()->references('diagnosis_notes')->on('dental_xrays');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_files');
    }
};
