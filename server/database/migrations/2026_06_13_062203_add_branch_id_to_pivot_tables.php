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
        Schema::table('appointment_patient', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('appointment_employee', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        });

        Schema::table('appointment_procedure', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        });

        // Schema::table('account_transaction_branch', function (Blueprint $table) {
        //     $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        // });

        Schema::table('account_transaction_clinic_material', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        });

        // Schema::table('branch_clinic_material', function (Blueprint $table) {
        //     $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        // });

        // Schema::table('branch_position', function (Blueprint $table) {
        //     $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        // });

        Schema::table('treatment_plan_procedure', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_patient', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('appointment_employee', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('appointment_procedure', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        // Schema::table('account_transaction_branch', function (Blueprint $table) {
        //     $table->dropColumn('branch_id');
        // });

        Schema::table('account_transaction_clinic_material', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        // Schema::table('branch_clinic_material', function (Blueprint $table) {
        //     $table->dropColumn('branch_id');
        // });

        // Schema::table('branch_position', function (Blueprint $table) {
        //     $table->dropColumn('branch_id');
        // });

        Schema::table('treatment_plan_procedure', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }
};
