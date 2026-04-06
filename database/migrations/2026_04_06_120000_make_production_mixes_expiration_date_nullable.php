<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('production_mixes', 'expiration_date')) {
            Schema::table('production_mixes', function (Blueprint $table) {
                $table->date('expiration_date')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_mixes', 'expiration_date')) {
            DB::table('production_mixes')->whereNull('expiration_date')->update(['expiration_date' => now()->toDateString()]);
            Schema::table('production_mixes', function (Blueprint $table) {
                $table->date('expiration_date')->nullable(false)->change();
            });
        }
    }
};
