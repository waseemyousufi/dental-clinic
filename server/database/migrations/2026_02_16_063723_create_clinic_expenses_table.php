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
        Schema::create('clinic_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_category'); //['Gas']
            $table->string('unit');
            $table->smallInteger('amount', false, true);
            $table->date('expense_date');
            $table->string('description')->nullable();
            $table->foreignId('paidByEmployee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_expenses');
    }
};
