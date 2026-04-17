<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packer_packs', function (Blueprint $table) {
            $table->id();
            $table->date('packed_date');
            $table->foreignId('finished_product_id')->constrained()->onDelete('cascade');
            $table->string('packer_name', 64);
            $table->decimal('quantity', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['packed_date', 'finished_product_id'], 'packer_packs_date_product_idx');
            $table->index(['packed_date', 'packer_name'], 'packer_packs_date_packer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packer_packs');
    }
};
