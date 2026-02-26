<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    if (Schema::hasTable('sales')) {

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $indexExists = DB::select("
            SELECT COUNT(1) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = 'sales'
              AND index_name = 'sales_invoice_number_unique'
        ", [$database]);

        if ($indexExists[0]->count > 0) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique('sales_invoice_number_unique');
            });
        }
    }
}

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->unique('invoice_number');
        });
    }
};