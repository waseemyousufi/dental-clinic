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
        Schema::table('clinic_materials', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('description')->nullable()->after('name');
            $table->string('category')->nullable()->after('description');
            $table->decimal('width', 10, 2)->nullable()->after('category');
            $table->decimal('height', 10, 2)->nullable()->after('width');
            $table->decimal('depth', 10, 2)->nullable()->after('height');
            $table->boolean('is_sterile')->default(false)->after('depth');
            $table->date("expire_date")->nullable()->after('is_sterile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinic_materials', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'category', 'width', 'height', 'depth', 'is_sterile', 'expire_date']);
        });
    }
};
