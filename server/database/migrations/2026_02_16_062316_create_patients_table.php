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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('f_name', 15);
            $table->string('l_name', 15);
            $table->string('gender', 15); // ['Male', 'Female']
            $table->string('phone', 10);
            $table->string('blood_type', 5); // ['O-', 'O+']
            $table->string('emergency_contact', 10);
            $table->date('registeration_date');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
