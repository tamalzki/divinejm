<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('finished_product_id')->constrained('finished_products')->onDelete('cascade');
            $table->string('batch_number')->nullable();
            
            // Quantities
            $table->decimal('quantity_deployed', 10, 2)->default(0); // What was sent
            $table->decimal('quantity_sold', 10, 2)->default(0); // What was sold
            $table->decimal('quantity_unsold', 10, 2)->default(0); // Returned/unsold
            $table->decimal('quantity_bo', 10, 2)->default(0); // Bad orders
            $table->decimal('quantity_replaced', 10, 2)->default(0); // Replaced items
            
            // Pricing
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 12, 2); // quantity_sold * unit_price
            
            $table->text('notes')->nullable(); // BO reasons, etc.
            $table->timestamps();
            
            $table->index(['sale_id', 'finished_product_id']);
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};