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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // prosthetics, devices, furniture, instruments, medications, consumables
            $table->json('materials')->nullable(); // array of material strings
            $table->text('description')->nullable();
            $table->boolean('track_stock')->default(false);
            $table->boolean('requires_batch')->default(false);
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('is_consumable')->default(false);
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('depth', 10, 2)->nullable();
            $table->boolean('is_sterile')->default(false);
            $table->date("expire_date")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
