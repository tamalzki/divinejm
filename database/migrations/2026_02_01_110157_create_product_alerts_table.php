<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('product_type'); // raw_material, finished_product
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->decimal('current_stock', 10, 2);
            $table->decimal('minimum_stock', 10, 2);
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_alerts');
    }
};