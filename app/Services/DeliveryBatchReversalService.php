<?php

namespace App\Services;

use App\Models\BranchInventory;
use App\Models\FinishedProduct;
use App\Models\ProductionMix;
use Illuminate\Support\Collection;

/**
 * Restores warehouse stock, branch inventory, and production mix outputs
 * for a set of transfer_out / extra_free stock movements (locked rows).
 */
class DeliveryBatchReversalService
{
    /**
     * @param  Collection<int, \App\Models\StockMovement>  $movements
     *
     * @throws \RuntimeException
     */
    public function revertStockBranchAndBatches(Collection $movements, int $branchId): void
    {
        foreach ($movements->groupBy('finished_product_id') as $productId => $productMovements) {
            $productId = (int) $productId;
            $totalQty = (float) $productMovements->sum('quantity');
            $regularQty = (float) $productMovements->where('movement_type', 'transfer_out')->sum('quantity');

            $product = FinishedProduct::lockForUpdate()->findOrFail($productId);
            $product->increment('stock_on_hand', $totalQty);
            $product->decrement('stock_out', $totalQty);

            if ($regularQty > 0) {
                $inventory = BranchInventory::where('branch_id', $branchId)
                    ->where('finished_product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory || (float) $inventory->quantity < $regularQty - 0.0001) {
                    throw new \RuntimeException(
                        'Cannot complete this undo: '.$product->name.' no longer has enough quantity at this area to match the original delivery. '
                        .'That usually means stock left the area after delivery — for example returns to the main warehouse, bad-order returns, recorded sales that reduced area stock, or manual area adjustments. '
                        .'Restore or align area inventory for this product (or reverse those movements first), then try again.'
                    );
                }

                $newBranchQty = round((float) $inventory->quantity - $regularQty, 2);
                if ($newBranchQty <= 0.0001) {
                    $inventory->delete();
                } else {
                    $inventory->update(['quantity' => $newBranchQty]);
                }
            }

            $nullBatchQty = 0.0;
            foreach ($productMovements as $m) {
                $qty = (float) $m->quantity;
                $bn = $m->batch_number;
                if ($bn !== null && $bn !== '') {
                    $mix = ProductionMix::where('batch_number', $bn)
                        ->where('finished_product_id', $productId)
                        ->lockForUpdate()
                        ->first();
                    if ($mix) {
                        $mix->increment('actual_output', $qty);
                    } else {
                        $nullBatchQty += $qty;
                    }
                } else {
                    $nullBatchQty += $qty;
                }
            }

            if ($nullBatchQty > 0.0001) {
                $fallbackMix = ProductionMix::where('finished_product_id', $productId)
                    ->where('status', 'completed')
                    ->orderBy('mix_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();
                if ($fallbackMix) {
                    $fallbackMix->increment('actual_output', $nullBatchQty);
                }
            }
        }
    }
}
