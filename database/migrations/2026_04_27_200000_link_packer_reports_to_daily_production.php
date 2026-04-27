<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link packer_reports to the daily production report that auto-created it
        Schema::table('packer_reports', function (Blueprint $table) {
            $table->foreignId('daily_production_report_id')
                ->nullable()
                ->after('id')
                ->constrained('daily_production_reports')
                ->nullOnDelete();

            $table->index('daily_production_report_id', 'packer_reports_dp_report_idx');
        });

        // History log – one row per "save" on a production-linked packer sheet
        Schema::create('packer_session_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packer_report_id')->constrained('packer_reports')->cascadeOnDelete();
            $table->json('snapshot');       // [{finished_product_id, product_name, packer_name, quantity}]
            $table->foreignId('saved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('packer_report_id', 'psl_packer_report_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packer_session_logs');

        Schema::table('packer_reports', function (Blueprint $table) {
            $table->dropForeign(['daily_production_report_id']);
            $table->dropIndex('packer_reports_dp_report_idx');
            $table->dropColumn('daily_production_report_id');
        });
    }
};
