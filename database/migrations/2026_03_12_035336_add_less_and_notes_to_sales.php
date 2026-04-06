<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // DR-level Less deduction (reduces total collectible)
            $table->decimal('less_amount', 10, 2)->default(0)->after('total_amount');
            // Reason for the Less deduction
            $table->string('less_notes', 500)->nullable()->after('less_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['less_amount', 'less_notes']);
        });
    }
};
