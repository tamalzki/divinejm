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
            // Free-text original DR# for BO replacement lines that aren't linked
            // to a tracked sale_item (e.g. bad orders from deliveries that predate
            // BO tracking, or ones the system never recorded as outstanding).
            $table->string('bo_original_dr_number')->nullable()->after('source_sale_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('bo_original_dr_number');
        });
    }
};
