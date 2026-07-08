<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            $table->decimal('distributor_price', 10, 2)->default(0)->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('finished_products', function (Blueprint $table) {
            $table->dropColumn('distributor_price');
        });
    }
};
