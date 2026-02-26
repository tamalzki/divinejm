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
        Schema::table('sales', function (Blueprint $table) {
            // Drop the unique constraint that prevents multiple sales per DR
            $table->dropUnique(['branch_id', 'customer_name', 'dr_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Re-add the unique constraint if rolling back
            $table->unique(['branch_id', 'customer_name', 'dr_number']);
        });
    }
};