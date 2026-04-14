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
        Schema::create('clinic_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_name')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->integer('amount', false, true);
            $table->integer('total_amount', false, true);
            $table->date('expense_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_materials');
    }
};
