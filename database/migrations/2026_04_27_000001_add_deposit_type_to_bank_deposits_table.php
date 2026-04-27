<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            // 'cash_deposit'  = cash collected from sales deposited to bank
            // 'check_deposit' = check payment received (from sales/expense) deposited to bank
            $table->string('deposit_type')->default('cash_deposit')->after('bank_name');
            $table->string('source_type')->nullable()->after('deposit_type'); // 'sales' | 'expense' | null
            $table->unsignedBigInteger('expense_id')->nullable()->after('source_type');
            $table->foreign('expense_id')->references('id')->on('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn(['deposit_type', 'source_type', 'expense_id']);
        });
    }
};
