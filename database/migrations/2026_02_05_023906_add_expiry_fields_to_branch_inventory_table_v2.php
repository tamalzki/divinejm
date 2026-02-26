<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_inventory', 'batch_expiry_date')) {
                $table->date('batch_expiry_date')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('branch_inventory', 'is_expired')) {
                $table->boolean('is_expired')->default(false)->after('batch_expiry_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branch_inventory', function (Blueprint $table) {
            if (Schema::hasColumn('branch_inventory', 'batch_expiry_date')) {
                $table->dropColumn('batch_expiry_date');
            }
            if (Schema::hasColumn('branch_inventory', 'is_expired')) {
                $table->dropColumn('is_expired');
            }
        });
    }
};