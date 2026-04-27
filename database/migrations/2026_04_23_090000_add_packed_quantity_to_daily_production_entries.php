<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->decimal('packed_quantity', 12, 2)->default(0)->after('rejects');
        });

        DB::statement('UPDATE daily_production_entries SET packed_quantity = 0 WHERE packed_quantity IS NULL');
    }

    public function down(): void
    {
        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->dropColumn('packed_quantity');
        });
    }
};
