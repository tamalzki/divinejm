<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE expenses MODIFY COLUMN payment_method ENUM('cash', 'card', 'bank_transfer', 'check', 'other') DEFAULT 'cash'"
        );
    }

    public function down(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement(
            "ALTER TABLE expenses MODIFY COLUMN payment_method ENUM('cash', 'card', 'bank_transfer', 'check') DEFAULT 'cash'"
        );
    }
};
