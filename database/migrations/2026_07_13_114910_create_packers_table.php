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
        Schema::create('packers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed from the existing config so behavior is unchanged until someone edits.
        $names = config('packers.names', ['Diding', 'Josephine', 'Rose', 'Liza', 'Joy', 'Neneng', 'Others']);
        foreach (array_values($names) as $i => $name) {
            DB::table('packers')->insert([
                'name' => $name,
                'is_active' => true,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packers');
    }
};
