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
        Schema::create('procedure_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_stock_id')->constrained('inventory_stock'); // Links to your shelf items
            /**
             * unit_count represents the specific quantity used.
             * e.g., 1 mask, 2 units of composite.
             */
            $table->integer('unit_count')->default(1);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_inventory');
    }
};
