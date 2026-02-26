<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, ensure branches table exists
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('address')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default branches
            DB::table('branches')->insert([
                [
                    'name' => 'Mers Main',
                    'code' => 'MAIN',
                    'address' => 'Main Branch Address',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Mers Badiang',
                    'code' => 'BADIANG',
                    'address' => 'Badiang Branch Address',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        // Now create branch_inventory
        Schema::create('branch_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('finished_product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamps();

            // Unique constraint - one record per product per branch
            $table->unique(['branch_id', 'finished_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_inventory');
        // Don't drop branches here as it might be used by other tables
    }
};