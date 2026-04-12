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
            $table->string('materials_name', 30);
            $table->unsignedSmallInteger('quantity');
            $table->integer('amount', false, true);
            $table->integer('total_amount', false, true);
            $table->date('expense_date');
            $table->string('description', 250);
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
