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
            $table->string('blood_type', 5)->nullable(); // ['O-', 'O+']
            $table->string('emergency_contact', 10)->nullable();
            $table->date('registeration_date');
            $table->integer('total_amount_due', false, true)->nullable();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('teeth_reference', function (Blueprint $table) {
            $table->id();
            $table->integer('fdi_code')->unique(); // e.g., 11, 51
            $table->string('universal_code', 2);    // e.g., 1, A
            $table->enum('type', ['permanent', 'primary']);
            $table->integer('quadrant');            // 1-8
            $table->integer('default_position');    // For UI sorting/grid layout
            $table->timestamps();
        });

        Schema::create('condition_library', function (Blueprint $table) {
            $table->id();
            $table->string('label');         // e.g., "Caries"
            $table->string('slug')->unique(); // e.g., "caries"
            $table->enum('category', ['finding', 'procedure', 'restoration', 'prevention']);
            $table->string('ui_color');      // Hex or Tailwind class
            $table->text('svg_path')->nullable(); // For drawing logic
            $table->timestamps();
        });

        Schema::create('tooth_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('tooth_id')->constrained('teeth_reference');
            $table->foreignId('condition_id')->constrained('condition_library');

            $table->jsonb('surfaces')->nullable(); // e.g., ["distal", "occlusal"]
            $table->jsonb('drawing_data')->nullable(); // For canvas coordinates

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true); // For current state filtering
            $table->timestamps();

            // Indexing for performance
            $table->index(['patient_id', 'is_active']);
        });

        Schema::create('patient_tooth_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('tooth_id')->constrained('teeth_reference');
            $table->enum('status', ['present', 'erupting', 'shed', 'extracted', 'missing']);
            $table->timestamps();

            $table->unique(['patient_id', 'tooth_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::dropIfExists('tooth_conditions');
    Schema::dropIfExists('patient_tooth_status');

    Schema::dropIfExists('patients');
    Schema::dropIfExists('teeth_reference');
    Schema::dropIfExists('condition_library');
}
};
