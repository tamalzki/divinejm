<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            $table->enum('product_type', ['manufactured', 'consigned'])->default('manufactured')->after('sku');
            $table->decimal('total_cost', 10, 2)->default(0)->after('cost_price')->comment('Auto-calculated from raw materials');
            $table->decimal('stock_on_hand', 10, 2)->default(0)->after('quantity')->comment('Undelivered stock');
            $table->decimal('stock_out', 10, 2)->default(0)->after('stock_on_hand')->comment('Delivered to branches');
        });
    }

    public function down(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'total_cost', 'stock_on_hand', 'stock_out']);
        });
    }
};