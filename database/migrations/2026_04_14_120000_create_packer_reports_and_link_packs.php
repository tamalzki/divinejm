<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packer_reports', function (Blueprint $table) {
            $table->id();
            $table->date('pack_date');
            $table->date('expiration_date')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('pack_date', 'packer_reports_pack_date_idx');
        });

        Schema::table('packer_packs', function (Blueprint $table) {
            $table->foreignId('packer_report_id')->nullable()->after('id')->constrained('packer_reports')->cascadeOnDelete();
        });

        $dates = DB::table('packer_packs')->distinct()->pluck('packed_date')->filter();

        foreach ($dates as $date) {
            $reportId = DB::table('packer_reports')->insertGetId([
                'pack_date' => $date,
                'expiration_date' => null,
                'user_id' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('packer_packs')
                ->where('packed_date', $date)
                ->update(['packer_report_id' => $reportId]);
        }

        Schema::table('packer_packs', function (Blueprint $table) {
            $table->dropIndex('packer_packs_date_product_idx');
            $table->dropIndex('packer_packs_date_packer_idx');
        });

        Schema::table('packer_packs', function (Blueprint $table) {
            $table->dropColumn('packed_date');
        });

        if (DB::table('packer_packs')->whereNull('packer_report_id')->exists()) {
            throw new \RuntimeException('packer_packs has rows without packer_report_id after backfill.');
        }

        DB::statement('ALTER TABLE packer_packs MODIFY packer_report_id BIGINT UNSIGNED NOT NULL');

        Schema::table('packer_packs', function (Blueprint $table) {
            $table->unique(['packer_report_id', 'finished_product_id', 'packer_name'], 'packer_packs_report_product_packer_uq');
        });
    }

    public function down(): void
    {
        // Intentionally minimal — restoring packed_date denormalization is lossy if multiple reports share a date.
    }
};
