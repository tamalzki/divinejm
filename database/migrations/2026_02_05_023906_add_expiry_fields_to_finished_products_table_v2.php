<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            if (!Schema::hasColumn('finished_products', 'manufacturing_date')) {
                $table->date('manufacturing_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('finished_products', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('manufacturing_date');
            }
            if (!Schema::hasColumn('finished_products', 'is_expired')) {
                $table->boolean('is_expired')->default(false)->after('expiry_date');
            }
            if (!Schema::hasColumn('finished_products', 'shelf_life_days')) {
                $table->integer('shelf_life_days')->nullable()->after('is_expired');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            $columns = ['manufacturing_date', 'expiry_date', 'is_expired', 'shelf_life_days'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('finished_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};