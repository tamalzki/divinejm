<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->decimal('number_of_mix', 8, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->unsignedInteger('number_of_mix')->default(1)->change();
        });
    }
};
