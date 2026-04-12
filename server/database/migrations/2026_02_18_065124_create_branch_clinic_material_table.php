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
        Schema::create('branch_clinic_material', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_material_id')->constrained()->cascadeOnDelete();
            $table->unique(['branch_id', 'clinic_material_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_clinic_material', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'clinic_material_id']);
            $table->dropTimestamps();
        });
    }
};
