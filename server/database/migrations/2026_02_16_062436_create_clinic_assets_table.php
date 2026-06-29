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
        Schema::create('clinic_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('category'); //['Medical Equipment', 'Furniture']
            $table->smallInteger('amount', false, true);
            $table->integer('price', false, true);
            $table->integer('total_amount', false, true);
            $table->date('date_of_purchase');
            $table->string('status'); // ['Active', 'Under Maintenace']
            $table->foreignId('purchasedByEmployee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinic_assets');
    }
};
