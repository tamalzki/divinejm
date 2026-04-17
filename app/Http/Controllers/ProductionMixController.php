<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\ProductionMix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionMixController extends Controller
{
    /** @deprecated Production Mix UI retired; use Daily Production + Packers Report. */
    protected function retiredRedirect()
    {
        return redirect()->route('daily-production.index')
            ->with('success', 'Production Mix is retired. Use Daily Production for the mix grid (raw materials), and Packers Report for finished-goods packing.');
    }

    public function index()
    {
        return $this->retiredRedirect();
    }

    public function create(?FinishedProduct $finishedProduct = null)
    {
        return $this->retiredRedirect();
    }

    public function store(Request $request)
    {
        return $this->retiredRedirect();
    }

    public function show(ProductionMix $productionMix)
    {
        $productionMix->load(['finishedProduct', 'ingredients.rawMaterial', 'user']);

        return view('production-mixes.show', ['mix' => $productionMix]);
    }

    // ── Update Actual Output (inline from index) ──────────────────────
    public function updateActualOutput(Request $request, ProductionMix $productionMix)
    {
        $request->validate([
            'actual_output' => 'required|numeric|min:0.01',
            'rejected_quantity' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldOutput = (float) $productionMix->actual_output;
        $newOutput = (float) $request->actual_output;
        $diff = $newOutput - $oldOutput;

        try {
            DB::beginTransaction();

            $productionMix->update([
                'actual_output' => $newOutput,
                'rejected_quantity' => max(0, (float) ($request->rejected_quantity ?? 0)),
                'expiration_date' => $request->expiration_date ?: null,
                'notes' => $request->notes ?: null,
            ]);

            if ($productionMix->increments_finished_stock && $diff != 0) {
                DB::table('finished_products')
                    ->where('id', $productionMix->finished_product_id)
                    ->increment('stock_on_hand', $diff);
            }

            DB::commit();

            $action = ! $productionMix->increments_finished_stock
                ? 'stock unchanged (use Packers Report for inventory)'
                : ($diff > 0
                    ? "+{$diff} units added to stock"
                    : (abs($diff) > 0 ? abs($diff).' units removed from stock' : 'no stock change'));

            return back()->with('success', "Batch updated — actual output: {$newOutput}. {$action}.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Update failed: '.$e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // DESTROY — delete batch and revert inventory changes
    // ──────────────────────────────────────────────────────────────────
    public function destroy(ProductionMix $productionMix)
    {
        $productionMix->load('ingredients.rawMaterial');

        try {
            DB::beginTransaction();

            if ($productionMix->increments_finished_stock) {
                DB::table('finished_products')
                    ->where('id', $productionMix->finished_product_id)
                    ->decrement('stock_on_hand', $productionMix->actual_output);
            }

            // Restore raw material quantities that were consumed
            foreach ($productionMix->ingredients as $ingredient) {
                DB::table('raw_materials')
                    ->where('id', $ingredient->raw_material_id)
                    ->increment('quantity', $ingredient->quantity_used);
            }

            // Delete ingredients then the mix
            $productionMix->ingredients()->delete();
            $productionMix->delete();

            DB::commit();

            return redirect()->route('daily-production.index')
                ->with('success', 'Legacy batch deleted. Inventory reverted — raw materials restored and finished product stock reduced.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }
}
