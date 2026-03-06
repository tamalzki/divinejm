<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinishedProduct;
use App\Models\BranchInventory;
use App\Models\StockMovement;
use App\Models\ProductionMix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchInventoryController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['inventory.finishedProduct'])
            ->where('is_active', true)
            ->get();
        
        return view('branch-inventory.index', compact('branches'));
    }

    public function show(Branch $branch)
    {
        $branch->load(['inventory.finishedProduct']);
        
        // Get completed MIX batches (available for deployment)
        $availableBatches = ProductionMix::with('product')
            ->where('status', 'completed')
            ->where('actual_output', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Also get finished products with stock (no batch required)
        $availableProducts = FinishedProduct::where('stock_on_hand', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(function($product) {
                // Create a "virtual batch" for products without batch tracking
                return (object)[
                    'id' => 'product_' . $product->id,
                    'product_id' => $product->id,
                    'product' => $product,
                    'batch_number' => null,
                    'actual_output' => $product->stock_on_hand,
                    'expiration_date' => null,
                ];
            });
        
        // Merge batches and products
        $availableBatches = $availableBatches->concat($availableProducts);
        
        // Get stock movements for this branch
        $stockMovements = StockMovement::where('branch_id', $branch->id)
            ->with(['finishedProduct', 'user'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('branch-inventory.show', compact('branch', 'availableBatches', 'stockMovements'));
    }

    public function transfer(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'dr_number' => 'required|string|max:255',
            'movement_date' => 'required|date',
            'total_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.production_mix_id' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.extra_quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $deployedItems = [];

            foreach ($validated['items'] as $itemData) {
                $mixId = $itemData['production_mix_id'];
                
                // Check if it's a direct product or a production batch
                if (str_starts_with($mixId, 'product_')) {
                    // Direct product deployment (no batch)
                    $productId = (int) str_replace('product_', '', $mixId);
                    $product = FinishedProduct::lockForUpdate()->findOrFail($productId);
                    
                    $regularQty = $itemData['quantity'];
                    $extraQty = $itemData['extra_quantity'] ?? 0;
                    $totalQty = $regularQty + $extraQty;
                    
                    if ($totalQty > $product->stock_on_hand) {
                        throw new \Exception("Insufficient stock for {$product->name}! Only {$product->stock_on_hand} units available.");
                    }
                    
                    $product->decrement('stock_on_hand', $totalQty);
                    $product->increment('stock_out', $totalQty);
                    
                    $batchNumber = null;
                    $expirationDate = null;
                    
                } else {
                    // Production batch deployment
                    $mix = ProductionMix::with('product')->lockForUpdate()->findOrFail($mixId);
                    $product = $mix->product;
                    
                    $regularQty = $itemData['quantity'];
                    $extraQty = $itemData['extra_quantity'] ?? 0;
                    $totalQty = $regularQty + $extraQty;
                    
                    if ($totalQty > $mix->actual_output) {
                        throw new \Exception("Insufficient stock in batch {$mix->batch_number}! Only {$mix->actual_output} units available for {$product->name}.");
                    }
                    
                    $mix->decrement('actual_output', $totalQty);
                    $product->decrement('stock_on_hand', $totalQty);
                    $product->increment('stock_out', $totalQty);
                    
                    $batchNumber = $mix->batch_number;
                    $expirationDate = $mix->expiration_date;
                }
                
                // Add to branch inventory
                $inventory = BranchInventory::where('branch_id', $branch->id)
                    ->where('finished_product_id', $product->id)
                    ->where('batch_number', $batchNumber)
                    ->first();

                if ($inventory) {
                    $inventory->increment('quantity', $regularQty);
                } else {
                    BranchInventory::create([
                        'branch_id' => $branch->id,
                        'finished_product_id' => $product->id,
                        'quantity' => $regularQty,
                        'batch_number' => $batchNumber,
                        'expiration_date' => $expirationDate,
                    ]);
                }

                // Record stock movement
                StockMovement::create([
                    'finished_product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'movement_type' => 'transfer_out',
                    'quantity' => $regularQty,
                    'batch_number' => $batchNumber,
                    'expiration_date' => $expirationDate,
                    'movement_date' => $validated['movement_date'],
                    'reference_number' => $validated['dr_number'],
                    'customer_name' => $validated['customer_name'],
                    'notes' => $validated['notes'],
                    'user_id' => Auth::id(),
                ]);

                // Record extra/free if any
                if ($extraQty > 0) {
                    StockMovement::create([
                        'finished_product_id' => $product->id,
                        'branch_id' => $branch->id,
                        'movement_type' => 'extra_free',
                        'quantity' => $extraQty,
                        'batch_number' => $batchNumber,
                        'expiration_date' => $expirationDate,
                        'movement_date' => $validated['movement_date'],
                        'reference_number' => $validated['dr_number'],
                        'customer_name' => $validated['customer_name'],
                        'notes' => "Extra/Free (Expense) - " . ($validated['notes'] ?? 'Complimentary'),
                        'user_id' => Auth::id(),
                    ]);
                }

                $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : " (No Batch)";
                $deployedItems[] = "{$product->name}{$batchInfo} ({$regularQty}" . ($extraQty > 0 ? " + {$extraQty} extra" : "") . ")";
            }

            $branch->addCustomer($validated['customer_name']);

            DB::commit();

            $itemsList = implode(', ', $deployedItems);
            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Deployed to {$validated['customer_name']} - DR#{$validated['dr_number']}: {$itemsList}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Deployment failed: ' . $e->getMessage());
        }
    }

    public function returnStock(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $branchInventory = BranchInventory::findOrFail($validated['branch_inventory_id']);

        if ($validated['quantity'] > $branchInventory->quantity) {
            return back()->withInput()->with('error', "Insufficient stock at area! Only {$branchInventory->quantity} units available.");
        }

        try {
            DB::beginTransaction();

            $product = $branchInventory->finishedProduct;
            $batchNumber = $branchInventory->batch_number;
            $expirationDate = $branchInventory->expiration_date;

            if ($validated['quantity'] >= $branchInventory->quantity) {
                $branchInventory->delete();
            } else {
                $branchInventory->decrement('quantity', $validated['quantity']);
            }

            $product->increment('stock_on_hand', $validated['quantity']);
            $product->decrement('stock_out', $validated['quantity']);

            if ($batchNumber) {
                $mix = ProductionMix::where('batch_number', $batchNumber)->first();
                if ($mix) {
                    $mix->increment('actual_output', $validated['quantity']);
                }
            }

            StockMovement::create([
                'finished_product_id' => $product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'return_bo',
                'quantity' => $validated['quantity'],
                'batch_number' => $batchNumber,
                'expiration_date' => $expirationDate,
                'movement_date' => $validated['movement_date'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : " (No Batch)";
            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Returned {$validated['quantity']} units of {$product->name}{$batchInfo} to warehouse!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Return failed: ' . $e->getMessage());
        }
    }
}