<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add batch tracking to branch_inventory
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_inventory', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('branch_inventory', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('batch_number');
            }
        });

        // Add batch tracking to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('batch_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiration_date']);
        });
        
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiration_date']);
        });
    }
};