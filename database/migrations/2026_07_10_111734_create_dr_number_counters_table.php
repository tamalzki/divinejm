<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dr_number_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('next_number')->default(0);
            $table->timestamps();
        });

        // Single global counter row — DR numbers start at 0000.
        DB::table('dr_number_counters')->insert(['next_number' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dr_number_counters');
    }
};
