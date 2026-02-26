<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            // Drop the old text column if it exists
            if (Schema::hasColumn('branches', 'customers')) {
                $table->dropColumn('customers');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            // Add the new JSON column
            $table->json('customers')->nullable()->after('phone');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('customers');
        });
    }
};