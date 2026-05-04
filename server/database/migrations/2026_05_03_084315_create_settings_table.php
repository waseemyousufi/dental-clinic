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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');

            $table->foreignId('position_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('can_edit_whatsapp')->default(false);
            $table->boolean('can_view_phones')->default(true);
            $table->boolean('show_kpi')->default(false);
            $table->boolean('show_suppliers')->default(false);
            $table->boolean('can_void_transactions')->default(false);
            $table->boolean('can_edit_assets')->default(false);
            $table->boolean('issue_prescriptions')->default(false);

            // Composite index for fast lookups
            $table->unique(['branch_id', 'position_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
