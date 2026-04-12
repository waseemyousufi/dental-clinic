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
        Schema::create('account_transaction_branch', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_transaction_id')->constrained()->cascadeOnDelete();
            $table->unique(['branch_id', 'account_transaction_id'], 'atb_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transaction_branch', function (Blueprint $table) {
            $table->dropForeign('branch_id');
            $table->dropForeign('account_transaction_id');
            $table->dropUnique('atb_unique');
            $table->dropTimestamps();
        });
    }
};
