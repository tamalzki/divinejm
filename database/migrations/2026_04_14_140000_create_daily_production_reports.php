<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_production_reports', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('production_date', 'dp_reports_prod_date_idx');
        });

        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->foreignId('daily_production_report_id')->nullable()->after('id')->constrained('daily_production_reports')->cascadeOnDelete();
        });

        $dates = DB::table('daily_production_entries')->distinct()->pluck('production_date')->filter();

        foreach ($dates as $date) {
            $reportId = DB::table('daily_production_reports')->insertGetId([
                'production_date' => $date,
                'user_id' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('daily_production_entries')
                ->where('production_date', $date)
                ->update(['daily_production_report_id' => $reportId]);
        }

        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->dropIndex('dp_entries_date_product_idx');
        });

        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->dropColumn('production_date');
        });

        if (DB::table('daily_production_entries')->whereNull('daily_production_report_id')->exists()) {
            throw new \RuntimeException('daily_production_entries has rows without daily_production_report_id after backfill.');
        }

        DB::statement('ALTER TABLE daily_production_entries MODIFY daily_production_report_id BIGINT UNSIGNED NOT NULL');

        Schema::table('daily_production_entries', function (Blueprint $table) {
            $table->unique(['daily_production_report_id', 'finished_product_id'], 'dp_entries_report_product_uq');
        });
    }

    public function down(): void
    {
        //
    }
};
