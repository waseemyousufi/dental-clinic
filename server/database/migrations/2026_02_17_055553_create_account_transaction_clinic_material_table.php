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
        Schema::create('account_transaction_clinic_material', function (Blueprint $table) {
            $table->foreignId('clinic_material_id')->constrained('clinic_materials', 'id', 'at_cm_cm_id_f')->cascadeOnDelete();
            $table->foreignId('account_transaction_id')->constrained('account_transactions', 'id', 'at_cm_at_id_f')->cascadeOnDelete();
            $table->unique(
                ['clinic_material_id', 'account_transaction_id'],
                'at_cm_unique'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transaction_clinic_material', function (Blueprint $table) {
            $table->dropUnique('at_cm_unique');
            $table->dropTimestamps();
        });
    }
};
