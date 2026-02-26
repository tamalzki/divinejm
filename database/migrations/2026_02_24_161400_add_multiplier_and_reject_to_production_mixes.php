<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_mixes', function (Blueprint $table) {
            // Add multiplier (how many batches in one production run)
            $table->integer('multiplier')->default(1)->after('expected_output');
            
            // Add rejected quantity (defective/rejected products)
            $table->decimal('rejected_quantity', 10, 2)->default(0)->after('actual_output');
        });
    }

    public function down(): void
    {
        Schema::table('production_mixes', function (Blueprint $table) {
            $table->dropColumn(['multiplier', 'rejected_quantity']);
        });
    }
};