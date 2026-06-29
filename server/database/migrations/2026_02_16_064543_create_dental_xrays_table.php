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
            $table->string('xray_type'); //['Periaical', 'Bitewing']
            $table->dateTime('xray_timestamp');
            $table->string('tooth_part'); // ["Molar", 'Premolar']
            $table->string('side'); // ['Left', 'Right']
            $table->string('image_path');
            $table->string('diagnosis_notes')->unique();
            $table->string('payment_status'); // ['Included']
            $table->string('results_summery');
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
