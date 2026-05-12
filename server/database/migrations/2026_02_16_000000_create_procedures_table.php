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
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Composite Filling"
            $table->string('slug')->unique(); // e.g., "composite_filling"
            $table->string('category'); // e.g., "Endodontics"
            $table->decimal('base_price', 15, 2); // Standard fee in AFN
            $table->decimal('min_price', 15, 2)->nullable();
            $table->integer('appointments_needed', false, true);
            $table->decimal('dentist_commission', 5, 2)->default(0); // Percentage for the doctor
            $table->decimal('assistant_commission', 15, 2)->default(0); // Fixed or % for Dastyar
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
