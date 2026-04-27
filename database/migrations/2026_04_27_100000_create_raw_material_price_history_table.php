<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raw_material_id');
            $table->decimal('old_price', 12, 4)->nullable();
            $table->decimal('new_price', 12, 4);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('raw_material_id')->references('id')->on('raw_materials')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_price_history');
    }
};
