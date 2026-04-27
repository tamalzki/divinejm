<?php

namespace Tests\Feature;

use App\Models\DailyProductionEntry;
use App\Models\DailyProductionReport;
use App\Models\FinishedProduct;
use App\Models\PackerPack;
use App\Models\PackerReport;
use App\Models\User;
use App\Services\ProductionPackingSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionPackingSyncTest extends TestCase
{
    use RefreshDatabase;

    private function seedDiceProductAndProduction(User $user): array
    {
        $product = FinishedProduct::create([
            'name' => 'Test DICE UBE',
            'sku' => 'TEST-DICE-'.uniqid(),
            'product_type' => 'manufactured',
            'quantity' => 0,
            'stock_on_hand' => 0,
            'stock_out' => 0,
            'minimum_stock' => 0,
            'cost_price' => 1,
            'selling_price' => 2,
            'total_cost' => 1,
        ]);

        $report = DailyProductionReport::create([
            'production_date' => '2026-04-20',
            'notes' => null,
            'user_id' => $user->id,
        ]);

        $entry = DailyProductionEntry::create([
            'daily_production_report_id' => $report->id,
            'finished_product_id' => $product->id,
            'number_of_mix' => 1,
            'standard_yield' => 60,
            'actual_yield' => 60,
            'rejects' => 0,
            'packed_quantity' => 0,
            'unpacked' => 0,
            'user_id' => $user->id,
        ]);

        return [$product, $report, $entry];
    }

    public function test_sync_allocates_packed_pieces_from_packer_packs_using_pack_size(): void
    {
        $user = User::factory()->create();
        [$product, , $entry] = $this->seedDiceProductAndProduction($user);

        $packReport = PackerReport::create([
            'pack_date' => '2026-04-21',
            'expiration_date' => '2026-04-23',
            'user_id' => $user->id,
        ]);

        PackerPack::create([
            'packer_report_id' => $packReport->id,
            'finished_product_id' => $product->id,
            'packer_name' => 'Diding',
            'quantity' => 5,
            'user_id' => $user->id,
        ]);

        DB::transaction(function () use ($product) {
            (new ProductionPackingSyncService)->sync([$product->id]);
        });

        $entry->refresh();
        // dice rule: 6 pcs per pack → 5 packs = 30 pieces
        $this->assertEquals(30.0, (float) $entry->packed_quantity);
        $this->assertEquals(30.0, (float) $entry->unpacked);
    }

    public function test_sync_skips_packs_when_pack_date_is_before_production_date(): void
    {
        $user = User::factory()->create();
        [$product, , $entry] = $this->seedDiceProductAndProduction($user);

        $packReport = PackerReport::create([
            'pack_date' => '2026-04-19',
            'expiration_date' => '2026-04-21',
            'user_id' => $user->id,
        ]);

        PackerPack::create([
            'packer_report_id' => $packReport->id,
            'finished_product_id' => $product->id,
            'packer_name' => 'Diding',
            'quantity' => 100,
            'user_id' => $user->id,
        ]);

        DB::transaction(function () use ($product) {
            (new ProductionPackingSyncService)->sync([$product->id]);
        });

        $entry->refresh();
        $this->assertEquals(0.0, (float) $entry->packed_quantity);
        $this->assertEquals(60.0, (float) $entry->unpacked);
    }
}
