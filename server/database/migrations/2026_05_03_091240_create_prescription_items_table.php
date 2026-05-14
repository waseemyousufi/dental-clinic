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
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->string('describer'); // patient_name, branch_address, branch_name,
            $table->float('font_size');
            $table->integer('pos_x');
            $table->integer('pos_y');
            $table->string('direction',5)->default('ltr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_template_sizes');
    }
};
