<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_production_entries', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->foreignId('finished_product_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('number_of_mix')->default(1);
            $table->decimal('standard_yield', 12, 2);
            $table->decimal('actual_yield', 12, 2);
            $table->decimal('rejects', 12, 2)->nullable()->default(0);
            $table->string('unfinished', 500)->nullable();
            $table->decimal('unpacked', 12, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['production_date', 'finished_product_id'], 'dp_entries_date_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_production_entries');
    }
};
