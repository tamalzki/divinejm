<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('payment_period', ['daily', 'weekly', 'monthly', 'one_time'])->default('one_time')->after('sale_date');
            $table->date('due_date')->nullable()->after('payment_period');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_period', 'due_date']);
        });
    }
};