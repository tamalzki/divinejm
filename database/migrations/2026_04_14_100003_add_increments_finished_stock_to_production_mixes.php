<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_mixes', function (Blueprint $table) {
            $table->boolean('increments_finished_stock')->default(false)->after('status');
        });

        DB::table('production_mixes')->update(['increments_finished_stock' => true]);
    }

    public function down(): void
    {
        Schema::table('production_mixes', function (Blueprint $table) {
            $table->dropColumn('increments_finished_stock');
        });
    }
};
