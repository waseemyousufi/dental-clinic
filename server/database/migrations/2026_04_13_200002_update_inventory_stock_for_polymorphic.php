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
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship to ClinicMaterial or ClinicAsset
            $table->morphs('stockable');
            // shelf_id is NULLABLE to handle "Pending Distribution"
            $table->foreignId('shelf_id')->nullable()->constrained('shelves')->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('status')->default('placed'); // 'placed' or 'pending'
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
};
