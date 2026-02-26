<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_product_restocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_added', 10, 2);
            $table->string('batch_number')->nullable();
            $table->date('production_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_product_restocks');
    }
};