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
        Schema::create('employee_treatment', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->unique(['employee_id', 'treatment_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_treatment', function (Blueprint $table) {
            $table->dropForeign('employee_id');
            $table->dropForeign('treatment_id');
            $table->dropUnique(['employee_id', 'treatment_id']);
            $table->dropTimestamps();
        });
    }
};
