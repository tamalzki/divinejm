<?php

namespace App\Http\Controllers;

use App\Models\ProductionMix;
use App\Models\ProductionMixIngredient;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionMixController extends Controller
{
    public function index()
    {
        $mixes = ProductionMix::with(['finishedProduct', 'ingredients.rawMaterial', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('production-mixes.index', compact('mixes'));
    }

    public function create($productId = null)
    {
        if ($productId) {
            // Load product WITH recipes AND raw materials
            $product = FinishedProduct::with(['pendingMixes', 'recipes.rawMaterial'])
                ->findOrFail($productId);
            
            // Check for pending MIX
            if ($product->pendingMixes()->exists()) {
                $pendingMix = $product->pendingMixes->first();
                return redirect()->route('finished-products.show', $product)
                    ->with('error', "⚠️ Cannot create new MIX!\n\nProduct '{$product->name}' already has an ongoing MIX: {$pendingMix->batch_number}\n\nPlease complete or delete the existing MIX first.");
            }
            
            return view('production-mixes.create', compact('product'));
        }
        
        return redirect()->route('finished-products.index')
            ->with('error', 'Please select a product first.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'finished_product_id' => 'required|exists:finished_products,id',
            'batch_number' => 'nullable|string|max:255',
            'expected_output' => 'required|numeric|min:0.01',
            'multiplier' => 'required|integer|min:1|max:100',  // NEW: Batch multiplier
            'expiration_date' => 'required|date|after:today',
            'mix_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $product = FinishedProduct::with('recipes.rawMaterial')->findOrFail($validated['finished_product_id']);

            // Check if product has recipe
            if ($product->recipes->count() === 0) {
                DB::rollBack();
                return back()
                    ->withInput()
                    ->with('error', '⚠️ Cannot create MIX!\n\nThis product has no recipe. Please edit the product and add a recipe first.');
            }

            // Check material availability based on PRODUCT RECIPE × MULTIPLIER
            $multiplier = $validated['multiplier'];
            $insufficientMaterials = [];
            
            foreach ($product->recipes as $recipe) {
                $neededPerBatch = $recipe->quantity_needed;
                $totalNeeded = $neededPerBatch * $multiplier;  // Multiply by batch count
                $available = $recipe->rawMaterial->quantity;
                
                if ($totalNeeded > $available) {
                    $insufficientMaterials[] = [
                        'name' => $recipe->rawMaterial->name,
                        'needed_per_batch' => $neededPerBatch,
                        'total_needed' => $totalNeeded,
                        'available' => $available,
                        'shortage' => $totalNeeded - $available,
                        'unit' => $recipe->rawMaterial->unit,
                    ];
                }
            }

            if (count($insufficientMaterials) > 0) {
                DB::rollBack();
                
                $errorMessage = "⚠️ Cannot create MIX - Insufficient raw materials (×{$multiplier} batches):\n\n";
                foreach ($insufficientMaterials as $material) {
                    $errorMessage .= "• {$material['name']}: Need {$material['total_needed']} {$material['unit']} ({$material['needed_per_batch']} × {$multiplier}), only {$material['available']} {$material['unit']} available (short {$material['shortage']} {$material['unit']})\n";
                }
                $errorMessage .= "\n💡 Please restock raw materials first.";
                
                return back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }

            // Auto-generate batch number if not provided
            if (empty($validated['batch_number'])) {
                $validated['batch_number'] = 'MIX-' . strtoupper(substr($product->name, 0, 3)) . '-' . date('Ymd') . '-' . str_pad(ProductionMix::count() + 1, 3, '0', STR_PAD_LEFT);
            }

            // Calculate total expected output
            $totalExpectedOutput = $validated['expected_output'] * $multiplier;

            $validated['user_id'] = Auth::id();
            $validated['status'] = 'pending';
            $mix = ProductionMix::create($validated);

            // Deduct materials based on PRODUCT RECIPE × MULTIPLIER
            foreach ($product->recipes as $recipe) {
                $usedQuantity = $recipe->quantity_needed * $multiplier;  // Multiply ingredients
                
                ProductionMixIngredient::create([
                    'production_mix_id' => $mix->id,
                    'raw_material_id' => $recipe->raw_material_id,
                    'quantity_used' => $usedQuantity,
                ]);

                DB::table('raw_materials')
                    ->where('id', $recipe->raw_material_id)
                    ->decrement('quantity', $usedQuantity);

                if (Schema::hasTable('raw_material_usages')) {
                    DB::table('raw_material_usages')->insert([
                        'raw_material_id' => $recipe->raw_material_id,
                        'quantity_used' => $usedQuantity,
                        'purpose' => "Production MIX: {$validated['batch_number']} for {$product->name} (×{$multiplier})",
                        'usage_date' => $validated['mix_date'],
                        'notes' => "Expected output: {$totalExpectedOutput} units ({$validated['expected_output']} × {$multiplier}). Expires: {$validated['expiration_date']}",
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('finished-products.index')
                ->with('success', "✅ MIX created successfully!\n\nProduct: {$product->name}\nBatch: {$mix->batch_number}\nMultiplier: ×{$multiplier}\nExpected Output: {$totalExpectedOutput} units ({$validated['expected_output']} × {$multiplier})\n\nRaw materials have been deducted. Ready for production!");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Production MIX creation error: " . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create MIX: ' . $e->getMessage());
        }
    }

    public function show(ProductionMix $productionMix)
    {
        $productionMix->load(['finishedProduct', 'ingredients.rawMaterial', 'user']);
        
        return view('production-mixes.show', compact('productionMix'));
    }

    public function complete(Request $request, ProductionMix $productionMix)
    {
        $validated = $request->validate([
            'actual_output' => 'required|numeric|min:0',
            'rejected_quantity' => 'required|numeric|min:0',  // NEW: Rejected/defective products
        ]);

        try {
            DB::beginTransaction();

            // Calculate good output (actual - rejected)
            $goodOutput = $validated['actual_output'] - $validated['rejected_quantity'];

            if ($goodOutput < 0) {
                DB::rollBack();
                return back()
                    ->with('error', '⚠️ Invalid input!\n\nRejected quantity cannot exceed actual output.');
            }

            $productionMix->update([
                'actual_output' => $validated['actual_output'],
                'rejected_quantity' => $validated['rejected_quantity'],
                'status' => 'completed',
            ]);

            // Add only GOOD output to inventory (not rejected items)
            DB::table('finished_products')
                ->where('id', $productionMix->finished_product_id)
                ->increment('stock_on_hand', $goodOutput);

            if (Schema::hasTable('finished_product_restocks')) {
                $totalExpected = $productionMix->expected_output * $productionMix->multiplier;
                $rejectionRate = $validated['actual_output'] > 0 
                    ? round(($validated['rejected_quantity'] / $validated['actual_output']) * 100, 2) 
                    : 0;

                DB::table('finished_product_restocks')->insert([
                    'finished_product_id' => $productionMix->finished_product_id,
                    'quantity_added' => $goodOutput,
                    'batch_number' => $productionMix->batch_number,
                    'production_date' => now()->toDateString(),
                    'expiry_date' => $productionMix->expiration_date,
                    'notes' => "From MIX #{$productionMix->id} (×{$productionMix->multiplier}). Expected: {$totalExpected}, Actual: {$validated['actual_output']}, Rejected: {$validated['rejected_quantity']} ({$rejectionRate}%), Good: {$goodOutput}. Cost per unit: ₱" . number_format($productionMix->cost_per_unit, 2),
                    'user_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $totalExpected = $productionMix->expected_output * $productionMix->multiplier;
            $variance = $validated['actual_output'] - $totalExpected;
            $varianceText = $variance >= 0 ? "+{$variance}" : $variance;
            $rejectionRate = $validated['actual_output'] > 0 
                ? round(($validated['rejected_quantity'] / $validated['actual_output']) * 100, 2) 
                : 0;

            return redirect()->route('finished-products.show', $productionMix->finished_product_id)
                ->with('success', "✅ Production completed!\n\nBatch: {$productionMix->batch_number}\nMultiplier: ×{$productionMix->multiplier}\nExpected: {$totalExpected} units\nActual: {$validated['actual_output']} units\nRejected: {$validated['rejected_quantity']} units ({$rejectionRate}%)\nGood Output: {$goodOutput} units\nVariance: {$varianceText} units\n\nGood products added to inventory!");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Production completion error: " . $e->getMessage());
            
            return back()
                ->with('error', 'Failed to complete production: ' . $e->getMessage());
        }
    }

    public function destroy(ProductionMix $productionMix)
    {
        try {
            if ($productionMix->status === 'completed') {
                return back()->with('error', '⚠️ Cannot delete completed MIX!');
            }

            DB::beginTransaction();

            foreach ($productionMix->ingredients as $ingredient) {
                DB::table('raw_materials')
                    ->where('id', $ingredient->raw_material_id)
                    ->increment('quantity', $ingredient->quantity_used);
                
                if (Schema::hasTable('raw_material_usages')) {
                    DB::table('raw_material_usages')->insert([
                        'raw_material_id' => $ingredient->raw_material_id,
                        'quantity_used' => -$ingredient->quantity_used,
                        'purpose' => "Returned from cancelled MIX: {$productionMix->batch_number}",
                        'usage_date' => now()->toDateString(),
                        'notes' => "MIX deleted - materials returned to inventory",
                        'user_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $productionMix->delete();

            DB::commit();

            return redirect()->route('production-mixes.index')
                ->with('success', "✅ MIX deleted and raw materials returned to inventory!");

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->with('error', 'Failed to delete MIX: ' . $e->getMessage());
        }
    }
}