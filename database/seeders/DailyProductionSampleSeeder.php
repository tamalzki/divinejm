<?php

namespace Database\Seeders;

use App\Models\DailyProductionEntry;
use App\Models\DailyProductionIngredient;
use App\Models\DailyProductionReport;
use App\Models\FinishedProduct;
use App\Models\User;
use App\Support\RawMaterialUnit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for Daily Production (index + grid). Safe to run multiple times.
 * Run: php artisan db:seed --class=DailyProductionSampleSeeder
 */
class DailyProductionSampleSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->orderBy('id')->value('id');

        $products = FinishedProduct::query()
            ->with('recipes.rawMaterial')
            ->whereHas('recipes')
            ->orderBy('name')
            ->get();

        $skipIngredients = false;
        if ($products->isEmpty()) {
            $products = FinishedProduct::query()
                ->with('recipes.rawMaterial')
                ->orderBy('name')
                ->limit(4)
                ->get();
            $skipIngredients = true;
            if ($products->isEmpty()) {
                $this->command->warn('No finished products in the database — create products first.');

                return;
            }
            $this->command->warn('No recipes on products — sample lines only (no raw material deductions). Add recipes and re-run for full demo.');
        }

        /** @var list<array{production_date: Carbon, notes: string, lines: list<array{mix:int, std:float, actual:float, rejects:float, unfinished: ?string, unpacked: ?float}>}> $reportsDef */
        $reportsDef = [
            [
                'production_date' => Carbon::today()->subDay(),
                'notes' => 'Sample data — morning line (demo)',
                'lines' => [
                    ['mix' => 2, 'std' => 100, 'actual' => 198, 'rejects' => 4, 'unfinished' => null, 'unpacked' => 12],
                    ['mix' => 1, 'std' => 80, 'actual' => 76.5, 'rejects' => 0, 'unfinished' => 'Cooling rack B', 'unpacked' => null],
                ],
            ],
            [
                'production_date' => Carbon::today()->subDays(3),
                'notes' => 'Sample data — short day (demo)',
                'lines' => [
                    ['mix' => 1, 'std' => 100, 'actual' => 102, 'rejects' => 1, 'unfinished' => null, 'unpacked' => 0],
                    ['mix' => 3, 'std' => 50, 'actual' => 148, 'rejects' => 2, 'unfinished' => null, 'unpacked' => 5],
                ],
            ],
        ];

        foreach ($reportsDef as $block) {
            DB::transaction(function () use ($block, $products, $userId, $skipIngredients) {
                $report = DailyProductionReport::create([
                    'production_date' => $block['production_date'],
                    'notes' => $block['notes'],
                    'user_id' => $userId,
                ]);

                foreach ($block['lines'] as $lineIdx => $row) {
                    $product = $products->get($lineIdx);
                    if (! $product) {
                        break;
                    }

                    $mix = (int) $row['mix'];

                    $entry = DailyProductionEntry::create([
                        'daily_production_report_id' => $report->id,
                        'finished_product_id' => $product->id,
                        'number_of_mix' => $mix,
                        'standard_yield' => $row['std'],
                        'actual_yield' => $row['actual'],
                        'rejects' => $row['rejects'],
                        'unfinished' => $row['unfinished'],
                        'unpacked' => $row['unpacked'],
                        'notes' => null,
                        'user_id' => $userId,
                    ]);

                    if (! $skipIngredients) {
                        foreach ($product->recipes as $recipe) {
                            $rm = $recipe->rawMaterial;
                            if (! $rm) {
                                continue;
                            }

                            $qtyStorage = round((float) $recipe->quantity_needed * $mix, 6);
                            if ($qtyStorage <= 0) {
                                continue;
                            }

                            $canonical = RawMaterialUnit::resolveToCanonical($rm->unit) ?? strtoupper(trim((string) $rm->unit));

                            DailyProductionIngredient::create([
                                'daily_production_entry_id' => $entry->id,
                                'raw_material_id' => (int) $recipe->raw_material_id,
                                'quantity_used' => $qtyStorage,
                                'input_quantity' => $qtyStorage,
                                'input_unit' => $canonical,
                            ]);

                            DB::table('raw_materials')
                                ->where('id', $recipe->raw_material_id)
                                ->decrement('quantity', $qtyStorage);
                        }
                    }
                }
            });
        }

        $this->command->info('Daily Production sample: '.count($reportsDef).' report(s) created. Open Daily Production in the app.');
    }
}
