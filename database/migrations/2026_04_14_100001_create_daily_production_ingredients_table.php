<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_production_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_production_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_used', 14, 6);
            $table->decimal('input_quantity', 14, 6)->nullable();
            $table->string('input_unit', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_production_ingredients');
    }
};
