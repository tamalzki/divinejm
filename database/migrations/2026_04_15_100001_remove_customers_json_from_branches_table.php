<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('branches', 'customers')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('customers');
            });
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->json('customers')->nullable()->after('phone');
        });
    }
};
