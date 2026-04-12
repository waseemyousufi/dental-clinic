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
        Schema::create('dental_xrays', function (Blueprint $table) {
            $table->id();
            $table->string('xray_type', 40); //['Periaical', 'Bitewing']
            $table->dateTime('xray_timestamp');
            $table->string('tooth_part', 50); // ["Molar", 'Premolar']
            $table->string('side', 40); // ['Left', 'Right']
            $table->string('image_path', 100);
            $table->string('diagnosis_notes', 150)->unique();
            $table->string('payment_status', 50); // ['Included']
            $table->string('results_summery', 150);
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('requestedByEmployee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('takenByEmployee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dental_xrays');
    }
};
