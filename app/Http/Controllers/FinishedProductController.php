<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\FinishedProductRestock;
use App\Models\RawMaterial;
use App\Models\ProductRecipe;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinishedProductController extends Controller
{
    public function index()
    {
        $products = FinishedProduct::with(['recipes.rawMaterial', 'pendingMixes', 'productionMixes'])
            ->orderBy('name')
            ->paginate(15);
        
        $pendingMixes = \App\Models\ProductionMix::where('status', 'pending')->count();
        $lowStockCount = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')->count();
        $completedToday = \App\Models\ProductionMix::where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();
        
        return view('finished-products.index', compact('products', 'pendingMixes', 'lowStockCount', 'completedToday'));
    }

    public function create()
    {
        $lastProduct = FinishedProduct::latest('id')->first();
        $nextNumber = $lastProduct ? ($lastProduct->id + 1) : 1;
        $suggestedSku = 'PROD-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        $rawMaterials = RawMaterial::orderBy('category')->orderBy('name')->get();
        
        return view('finished-products.create', compact('suggestedSku', 'rawMaterials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:finished_products',
            'product_type' => 'required|in:manufactured,consigned',
            'quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255',
            
            // Recipe validation
            'ingredients' => 'required_if:product_type,manufactured|array',
            'ingredients.*.id' => 'required_with:ingredients|exists:raw_materials,id',
            'ingredients.*.quantity' => 'required_with:ingredients|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $validated['stock_on_hand'] = $validated['quantity'];
            $validated['stock_out'] = 0;
            $validated['total_cost'] = $validated['cost_price'];
            
            // Save units per batch if provided
            if ($request->has('units_per_batch')) {
                $validated['units_per_batch'] = $request->input('units_per_batch');
            }
            
            $product = FinishedProduct::create($validated);

            // Save recipe for manufactured products
            if ($validated['product_type'] === 'manufactured' && !empty($request->ingredients)) {
                foreach ($request->ingredients as $ingredient) {
                    if (!empty($ingredient['id']) && !empty($ingredient['quantity'])) {
                        $rawMaterial = RawMaterial::find($ingredient['id']);
                        
                        ProductRecipe::create([
                            'finished_product_id' => $product->id,
                            'raw_material_id' => $ingredient['id'],
                            'quantity_needed' => $ingredient['quantity'],
                            'cost_per_unit' => $rawMaterial->unit_price,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('finished-products.index')
                ->with('success', '✅ Product created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Product creation error: " . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    public function show(FinishedProduct $finishedProduct)
    {
        $finishedProduct->load(['recipes.rawMaterial', 'branchInventory.branch', 'productionMixes', 'pendingMixes']);
        
        $restockHistory = $finishedProduct->restocks()
            ->with('user')
            ->orderBy('production_date', 'desc')
            ->paginate(10, ['*'], 'restocks');
        
        $salesHistory = $finishedProduct->saleItems()
            ->with('sale') // load parent sale
            ->latest()
            ->paginate(10, ['*'], 'sales');
        
        $stockMovements = $finishedProduct->stockMovements()
            ->with(['branch', 'user'])
            ->orderBy('movement_date', 'desc')
            ->paginate(10, ['*'], 'movements');
        
        return view('finished-products.show', compact(
            'finishedProduct', 
            'restockHistory', 
            'salesHistory',
            'stockMovements'
        ));
    }

    public function edit(FinishedProduct $finishedProduct)
    {
        $finishedProduct->load('recipes.rawMaterial');
        
        $ingredients = RawMaterial::where('category', 'ingredient')
    ->orderBy('name')
    ->get();

$packaging = RawMaterial::where('category', 'packaging')
    ->orderBy('name')
    ->get();

return view('finished-products.edit', compact(
    'finishedProduct',
    'ingredients',
    'packaging'
));
        
        return view('finished-products.edit', compact('finishedProduct', 'rawMaterials'));
    }

    public function update(Request $request, FinishedProduct $finishedProduct)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:finished_products,sku,' . $finishedProduct->id,
            'minimum_stock' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255',
            
            'ingredients' => 'required_if:product_type,manufactured|array',
            'ingredients.*.id' => 'required_with:ingredients|exists:raw_materials,id',
            'ingredients.*.quantity' => 'required_with:ingredients|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $finishedProduct->update($validated);

            // Update recipe if manufactured
            if ($finishedProduct->product_type === 'manufactured') {
                $finishedProduct->recipes()->delete();

                if (!empty($request->ingredients)) {
                    foreach ($request->ingredients as $ingredient) {
                        if (!empty($ingredient['id']) && !empty($ingredient['quantity'])) {
                            $rawMaterial = RawMaterial::find($ingredient['id']);
                            
                            ProductRecipe::create([
                                'finished_product_id' => $finishedProduct->id,
                                'raw_material_id' => $ingredient['id'],
                                'quantity_needed' => $ingredient['quantity'],
                                'cost_per_unit' => $rawMaterial->unit_price,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('finished-products.index')
                ->with('success', 'Product updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    public function destroy(FinishedProduct $finishedProduct)
    {
        try {
            DB::beginTransaction();

            $productName = $finishedProduct->name;
            
            $pendingMix = $finishedProduct->pendingMixes()->with('ingredients')->first();
            
            if ($pendingMix) {
                foreach ($pendingMix->ingredients as $ingredient) {
                    DB::table('raw_materials')
                        ->where('id', $ingredient->raw_material_id)
                        ->increment('quantity', $ingredient->quantity_used);
                    
                    if (Schema::hasTable('raw_material_usages')) {
                        DB::table('raw_material_usages')->insert([
                            'raw_material_id' => $ingredient->raw_material_id,
                            'quantity_used' => -$ingredient->quantity_used,
                            'purpose' => "Returned from deleted product: {$productName}",
                            'usage_date' => now()->toDateString(),
                            'user_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $finishedProduct->delete();

            DB::commit();

            $message = "✅ Product '{$productName}' deleted successfully!";
            if ($pendingMix) {
                $message .= "\n\nPending MIX was cancelled and materials returned.";
            }

            return redirect()->route('finished-products.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Product deletion error: " . $e->getMessage());
            
            return redirect()->route('finished-products.index')
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
}