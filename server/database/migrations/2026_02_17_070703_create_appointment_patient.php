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
        Schema::create('appointment_patient', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained('appointments', 'id', 'ap_pt_app_id_f')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients', 'id', 'ap_pt_patient_id_f')->cascadeOnDelete();
            $table->unique(['appointment_id', 'patient_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_patient', function (Blueprint $table) {
            $table->dropForeign('appointment_id');
            $table->dropForeign('patient_id');
            $table->dropUnique(['appointment_id', 'patient_id']);
            $table->dropTimestamps();
        });
    }
};
