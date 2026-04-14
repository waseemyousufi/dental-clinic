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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship to ClinicMaterial or ClinicAsset
            $table->morphs('pricable');
            $table->decimal('price', 10, 2);
            $table->dateTime('effective_from');
            $table->float('discount_percentage', 10, 2)->nullable();
            $table->decimal('currency_exchange_rate', 10, 4)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
