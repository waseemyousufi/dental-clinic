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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->foreignId('employee_id')->primary();
            $table->string('salary_month', 15);
            $table->integer('amount', false, true);
            $table->smallInteger('bonus', false, true);
            $table->integer('total_amount', false, true);
            $table->string('remark', 30); // ['Paid']
            $table->foreignId('paidByAccountTransaction_id')->unique()->constrained('account_transactions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
