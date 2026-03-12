<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\ProductionMix;
use App\Models\ProductionMixIngredient;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionMixController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionMix::with(['finishedProduct', 'ingredients.rawMaterial', 'user'])
            ->latest();

        // Search: batch number or product name
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('finishedProduct', fn($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        // Filters
        match ($request->input('filter')) {
            'expiring'    => $query->whereDate('expiration_date', '>=', now())
                                   ->whereDate('expiration_date', '<=', now()->addDays(7)),
            'expired'     => $query->whereDate('expiration_date', '<', now()),
            'high_reject' => $query->whereRaw('rejected_quantity / NULLIF(actual_output, 0) * 100 > 5'),
            default       => null,
        };

        $mixes = $query->paginate(15)->withQueryString();

        return view('production-mixes.index', compact('mixes'));
    }

    public function create(FinishedProduct $finishedProduct = null)
    {
        // Load all products with their recipes
        $products = FinishedProduct::with('recipes.rawMaterial')
            ->orderBy('name')
            ->get();

        $allMaterials = RawMaterial::orderBy('category')
            ->orderBy('name')
            ->get();

        // Pre-build recipe map keyed by product ID — avoids arrow functions in Blade
        $productRecipes = [];
        foreach ($products as $p) {
            $productRecipes[$p->id] = $p->recipes->map(function ($recipe) {
                $rm = $recipe->rawMaterial;
                return [
                    'raw_material_id' => $recipe->raw_material_id,
                    'name'            => $rm->name,
                    'unit'            => $rm->unit,
                    'category'        => $rm->category,
                    'stock_quantity'  => $rm->quantity,
                    'cost_per_unit'   => $rm->unit_price,
                    'quantity_needed' => $recipe->quantity_needed,
                ];
            })->values()->toArray();
        }

        // $preselectedProduct is set when coming from Finished Products page
        // Ensure its recipes are loaded
        $preselectedProduct = $finishedProduct;
        if ($preselectedProduct) {
            $preselectedProduct->loadMissing('recipes.rawMaterial');
            // Make sure it's also in the productRecipes map with fresh data
            $productRecipes[$preselectedProduct->id] = $preselectedProduct->recipes->map(function ($recipe) {
                $rm = $recipe->rawMaterial;
                return [
                    'raw_material_id' => $recipe->raw_material_id,
                    'name'            => $rm->name,
                    'unit'            => $rm->unit,
                    'category'        => $rm->category,
                    'stock_quantity'  => $rm->quantity,
                    'cost_per_unit'   => $rm->unit_price,
                    'quantity_needed' => $recipe->quantity_needed,
                ];
            })->values()->toArray();
        }

        return view('production-mixes.create', compact('products', 'allMaterials', 'productRecipes', 'preselectedProduct'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'finished_product_id'     => 'required|exists:finished_products,id',
            'mix_date'                => 'required|date',
            'expected_output'         => 'required|numeric|min:0.01',
            'actual_output'           => 'required|numeric|min:0.01',
            'rejected_quantity'       => 'nullable|numeric|min:0',
            'expiration_date'         => 'nullable|date|after:mix_date',
            'notes'                   => 'nullable|string|max:500',
            'multiplier'              => 'nullable|integer|min:1|max:99',
            'items'                   => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity_used'   => 'required|numeric|min:0.0001',
        ], [
            'finished_product_id.required' => 'Please select a finished product.',
            'mix_date.required'            => 'Mix date is required.',
            'expected_output.required'     => 'Expected output is required.',
            'expected_output.min'          => 'Expected output must be greater than zero.',
            'actual_output.required'       => 'Actual output is required.',
            'actual_output.min'            => 'Actual output must be greater than zero.',
            'expiration_date.after'        => 'Expiry date must be after the mix date.',
            'items.required'               => 'At least one raw material is required.',
            'items.min'                    => 'At least one raw material is required.',
            'items.*.raw_material_id.required' => 'Please select a material for each row.',
            'items.*.quantity_used.required'   => 'Please enter a quantity for each material.',
            'items.*.quantity_used.min'        => 'Quantity used must be greater than zero.',
        ]);

        $multiplier = max(1, (int) ($request->multiplier ?? 1));

        // Scale items by multiplier
        $scaledItems = collect($request->items)->map(function ($item) use ($multiplier) {
            $item['quantity_used'] = $item['quantity_used'] * $multiplier;
            return $item;
        })->all();

        // Check stock availability before touching DB
        $stockErrors = [];
        foreach ($scaledItems as $item) {
            $material = RawMaterial::find($item['raw_material_id']);
            if ($material && $item['quantity_used'] > $material->quantity) {
                $short = round($item['quantity_used'] - $material->quantity, 4);
                $stockErrors[] = "{$material->name}: needs {$item['quantity_used']} {$material->unit}, only {$material->quantity} available (short by {$short})" . ($multiplier > 1 ? " [×{$multiplier} batches]" : "") . ".";
            }
        }

        if (!empty($stockErrors)) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Insufficient stock: ' . implode(' | ', $stockErrors)]);
        }

        try {
            DB::beginTransaction();

            $product          = FinishedProduct::findOrFail($request->finished_product_id);
            // Rejects are standalone documentation — do NOT subtract from actual output
            $rejectedQty = max(0, (float) ($request->rejected_quantity ?? 0));

            // Calculate total cost from ingredients
            $totalCost = 0;
            foreach ($scaledItems as $item) {
                $material   = RawMaterial::find($item['raw_material_id']);
                $totalCost += $item['quantity_used'] * ($material->unit_price ?? 0);
            }

            // Cost per unit based on actual output (not reduced by rejects)
            $costPerUnit = $request->actual_output > 0 ? $totalCost / $request->actual_output : 0;

            // Auto-generate batch number
            $batchNumber = 'MIX-'
                . strtoupper(substr($product->name, 0, 3))
                . '-' . date('Ymd')
                . '-' . str_pad(ProductionMix::count() + 1, 3, '0', STR_PAD_LEFT);

            // Create the mix record
            $mix = ProductionMix::create([
                'finished_product_id' => $request->finished_product_id,
                'batch_number'        => $batchNumber,
                'mix_date'            => $request->mix_date,
                'expected_output'     => round($request->expected_output / $multiplier, 2),
                'actual_output'       => $request->actual_output,
                'rejected_quantity'   => $rejectedQty,
                'expiration_date'     => $request->expiration_date,
                'notes'               => $request->notes,
                'multiplier'          => $multiplier,
                'status'              => 'completed',
                'user_id'             => Auth::id(),
            ]);

            // Save ingredients & deduct stock
            foreach ($scaledItems as $item) {
                ProductionMixIngredient::create([
                    'production_mix_id' => $mix->id,
                    'raw_material_id'   => $item['raw_material_id'],
                    'quantity_used'     => $item['quantity_used'],
                ]);

                DB::table('raw_materials')
                    ->where('id', $item['raw_material_id'])
                    ->decrement('quantity', $item['quantity_used']);
            }

            // Add actual output to finished product stock (rejects are separate documentation)
            DB::table('finished_products')
                ->where('id', $product->id)
                ->increment('stock_on_hand', $request->actual_output);

            DB::commit();

            return redirect()->route('production-mixes.show', $mix)
                ->with('success', "Production batch \"{$batchNumber}\" saved. {$request->actual_output} units added to stock.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to save production batch: ' . $e->getMessage());
        }
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
            'actual_output'    => 'required|numeric|min:0.01',
            'rejected_quantity'=> 'nullable|numeric|min:0',
            'expiration_date'  => 'nullable|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        $oldOutput = (float) $productionMix->actual_output;
        $newOutput = (float) $request->actual_output;
        $diff      = $newOutput - $oldOutput;

        try {
            DB::beginTransaction();

            $productionMix->update([
                'actual_output'    => $newOutput,
                'rejected_quantity'=> max(0, (float) ($request->rejected_quantity ?? 0)),
                'expiration_date'  => $request->expiration_date ?: null,
                'notes'            => $request->notes ?: null,
            ]);

            // Adjust warehouse stock by the difference only
            if ($diff != 0) {
                DB::table('finished_products')
                    ->where('id', $productionMix->finished_product_id)
                    ->increment('stock_on_hand', $diff);
            }

            DB::commit();

            $action = $diff > 0
                ? "+{$diff} units added to stock"
                : (abs($diff) > 0 ? abs($diff) . " units removed from stock" : "no stock change");

            return back()->with('success', "Batch updated — actual output: {$newOutput}. {$action}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Update failed: ' . $e->getMessage());
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

            // 1. Revert finished product stock (remove what this batch added)
            DB::table('finished_products')
                ->where('id', $productionMix->finished_product_id)
                ->decrement('stock_on_hand', $productionMix->actual_output);

            // 2. Restore raw material quantities that were consumed
            foreach ($productionMix->ingredients as $ingredient) {
                DB::table('raw_materials')
                    ->where('id', $ingredient->raw_material_id)
                    ->increment('quantity', $ingredient->quantity_used);
            }

            // 3. Delete ingredients then the mix
            $productionMix->ingredients()->delete();
            $productionMix->delete();

            DB::commit();

            return redirect()->route('production-mixes.index')
                ->with('success', "Batch deleted. Inventory reverted — raw materials restored and finished product stock reduced.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}