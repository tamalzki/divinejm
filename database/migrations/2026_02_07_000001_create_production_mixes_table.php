<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_mixes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_product_id')->constrained('finished_products')->onDelete('cascade');
            $table->string('batch_number')->nullable();
            $table->decimal('expected_output', 10, 2); // Expected packs/units
            $table->decimal('actual_output', 10, 2)->nullable(); // Actual produced
            $table->date('expiration_date'); // REQUIRED
            $table->string('barcode')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->date('mix_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_mixes');
    }
};