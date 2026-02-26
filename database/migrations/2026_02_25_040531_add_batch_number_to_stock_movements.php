<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Add batch_number column if it doesn't exist
            if (!Schema::hasColumn('stock_movements', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('finished_product_id');
                $table->index('batch_number');
            }

            // Add expiration_date column if it doesn't exist
            if (!Schema::hasColumn('stock_movements', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('movement_date');
            }

            // Add production_mix_id column if it doesn't exist
            if (!Schema::hasColumn('stock_movements', 'production_mix_id')) {
                $table->unsignedBigInteger('production_mix_id')->nullable()->after('branch_id');
                $table->foreign('production_mix_id')->references('id')->on('production_mixes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'production_mix_id')) {
                $table->dropForeign(['production_mix_id']);
                $table->dropColumn('production_mix_id');
            }

            if (Schema::hasColumn('stock_movements', 'expiration_date')) {
                $table->dropColumn('expiration_date');
            }

            if (Schema::hasColumn('stock_movements', 'batch_number')) {
                $table->dropColumn('batch_number');
            }
        });
    }
};