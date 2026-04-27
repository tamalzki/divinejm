<?php

namespace App\Services;

use App\Models\DailyProductionEntry;
use App\Models\FinishedProduct;
use App\Models\PackerPack;
use Illuminate\Support\Collection;

class ProductionPackingSyncService
{
    /**
     * Recompute packed and unpacked pieces per daily production entry using FIFO
     * allocation by production date and pack date.
     *
     * @param  list<int>|null  $finishedProductIds
     */
    public function sync(?array $finishedProductIds = null): void
    {
        $products = FinishedProduct::query()
            ->when($finishedProductIds !== null, fn ($q) => $q->whereIn('id', $finishedProductIds))
            ->get(['id', 'name'])
            ->keyBy('id');

        if ($products->isEmpty()) {
            return;
        }

        $entries = DailyProductionEntry::query()
            ->select('daily_production_entries.*', 'daily_production_reports.production_date as report_production_date')
            ->join('daily_production_reports', 'daily_production_reports.id', '=', 'daily_production_entries.daily_production_report_id')
            ->whereIn('daily_production_entries.finished_product_id', $products->keys()->all())
            ->orderBy('daily_production_reports.production_date')
            ->orderBy('daily_production_entries.id')
            ->lockForUpdate()
            ->get();

        $entriesByProduct = [];
        foreach ($entries as $entry) {
            $available = max(0, (float) $entry->actual_yield - (float) $entry->rejects);
            $entriesByProduct[$entry->finished_product_id][] = [
                'entry' => $entry,
                'production_date' => (string) $entry->report_production_date,
                'available' => round($available, 4),
                'packed' => 0.0,
            ];
        }

        $packSizes = $this->resolvePackSizes($products);
        $allocations = $this->allocatePackedPieces($products, $packSizes, $entriesByProduct);

        foreach ($entriesByProduct as $productId => $rows) {
            $allocatedRows = $allocations[$productId] ?? [];
            foreach ($rows as $idx => $row) {
                $packed = round((float) ($allocatedRows[$idx]['packed'] ?? 0), 4);
                $unpacked = round(max(0, (float) $row['available'] - $packed), 4);
                /** @var \App\Models\DailyProductionEntry $entry */
                $entry = $row['entry'];
                $entry->packed_quantity = $packed;
                $entry->unpacked = $unpacked;
                $entry->save();
            }
        }
    }

    /**
     * @param  Collection<int, FinishedProduct>  $products
     * @return array<int, int>
     */
    protected function resolvePackSizes(Collection $products): array
    {
        $resolved = [];
        foreach ($products as $product) {
            $resolved[$product->id] = $this->packSizeForProductName((string) $product->name);
        }

        return $resolved;
    }

    protected function packSizeForProductName(string $productName): int
    {
        $name = strtolower(trim($productName));
        $rules = config('pack_standards.rules', []);

        foreach ($rules as $rule) {
            $keywords = array_values(array_filter($rule['keywords'] ?? [], fn ($k) => is_string($k) && trim($k) !== ''));
            $pcs = (int) ($rule['pcs_per_pack'] ?? 0);
            if ($keywords === [] || $pcs <= 0) {
                continue;
            }

            $matches = true;
            foreach ($keywords as $keyword) {
                if (! str_contains($name, strtolower($keyword))) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $pcs;
            }
        }

        return 0;
    }

    /**
     * @param  Collection<int, FinishedProduct>  $products
     * @param  array<int, int>  $packSizes
     * @param  array<int, array<int, array{entry:DailyProductionEntry, production_date:string, available:float, packed:float}>>  $entriesByProduct
     * @return array<int, array<int, array{packed:float}>>
     */
    protected function allocatePackedPieces(Collection $products, array $packSizes, array $entriesByProduct): array
    {
        $allocations = [];
        foreach ($entriesByProduct as $productId => $rows) {
            $allocations[$productId] = [];
            foreach ($rows as $idx => $row) {
                $allocations[$productId][$idx] = ['packed' => 0.0];
            }
        }

        $packs = PackerPack::query()
            ->select('packer_packs.finished_product_id', 'packer_packs.quantity', 'packer_reports.pack_date')
            ->join('packer_reports', 'packer_reports.id', '=', 'packer_packs.packer_report_id')
            ->whereIn('packer_packs.finished_product_id', $products->keys()->all())
            ->orderBy('packer_reports.pack_date')
            ->orderBy('packer_packs.id')
            ->lockForUpdate()
            ->get();

        foreach ($packs as $pack) {
            $productId = (int) $pack->finished_product_id;
            $size = (int) ($packSizes[$productId] ?? 0);
            if ($size <= 0 || ! isset($entriesByProduct[$productId])) {
                continue;
            }

            $packDate = (string) $pack->pack_date;
            $remainingPieces = round((float) $pack->quantity * $size, 4);
            if ($remainingPieces <= 0) {
                continue;
            }

            foreach ($entriesByProduct[$productId] as $idx => $row) {
                if ($remainingPieces <= 0) {
                    break;
                }

                if ($row['production_date'] > $packDate) {
                    continue;
                }

                $available = (float) $row['available'] - (float) $allocations[$productId][$idx]['packed'];
                if ($available <= 0) {
                    continue;
                }

                $consume = min($available, $remainingPieces);
                $allocations[$productId][$idx]['packed'] += $consume;
                $remainingPieces -= $consume;
            }
        }

        return $allocations;
    }
}
