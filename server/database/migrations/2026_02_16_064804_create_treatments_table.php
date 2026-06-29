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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->string('treatment_type'); // ['Crwon', 'Filling']
            $table->string('diagnosis');
            $table->date('treatment_date');
            $table->string('duration');
            $table->integer('cost', false, true);
            $table->string('description');
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->unique()->constrained('treatment_plans', 'id');
            // $table->foreignId('xray_id')->constrained('dental_xrays')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
