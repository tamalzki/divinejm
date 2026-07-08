<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_product_branch_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['finished_product_id', 'branch_id'], 'fp_branch_price_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_product_branch_prices');
    }
};
