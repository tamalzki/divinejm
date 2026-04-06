<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_mix_ingredients', function (Blueprint $table) {
            $table->decimal('input_quantity', 12, 4)->nullable()->after('quantity_used');
            $table->string('input_unit', 20)->nullable()->after('input_quantity');
        });

        Schema::table('production_mix_ingredients', function (Blueprint $table) {
            $table->decimal('quantity_used', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_mix_ingredients', function (Blueprint $table) {
            $table->dropColumn(['input_quantity', 'input_unit']);
        });

        Schema::table('production_mix_ingredients', function (Blueprint $table) {
            $table->decimal('quantity_used', 10, 2)->change();
        });
    }
};
