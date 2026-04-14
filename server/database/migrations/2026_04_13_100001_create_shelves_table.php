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
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('shelf_name', 100)->nullable();
            $table->string('shelf_type', 20); // 'glass', 'metal', 'wood', 'refrigerator'
            $table->string('access_pin', 4)->nullable(); // 4-digit pin
            $table->decimal('total_capacity_cm3', 15, 2)->nullable(); // The physical volume
            $table->string('category_restriction', 100)->nullable(); // Optional: only allow 'Surgical' here
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelves');
    }
};
