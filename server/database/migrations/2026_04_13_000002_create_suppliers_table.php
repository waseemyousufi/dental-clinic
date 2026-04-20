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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('contact_person_name');
            $table->string('organization_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('status')->default('active'); // e.g., active, inactive
            $table->string('address')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
