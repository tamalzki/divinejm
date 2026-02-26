<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add batch_number to branch_inventory table
        Schema::table('branch_inventory', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('quantity');
            $table->date('expiration_date')->nullable()->after('batch_number');
        });

        // Add batch_number to stock_movements table
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('quantity');
            $table->date('expiration_date')->nullable()->after('batch_number');
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