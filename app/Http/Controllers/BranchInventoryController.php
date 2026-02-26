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
        
        // Get stock movements for this branch
        $stockMovements = StockMovement::where('branch_id', $branch->id)
            ->with(['finishedProduct', 'user'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('branch-inventory.show', compact('branch', 'availableBatches', 'stockMovements'));
    }

    // Deploy stock from warehouse to branch (with batch tracking)
    // Replace the transfer() method in BranchInventoryController with this:

public function transfer(Request $request, Branch $branch)
{
    $validated = $request->validate([
        'customer_name' => 'required|string|max:255',
        'dr_number' => 'required|string|max:255',
        'movement_date' => 'required|date',
        'total_amount' => 'nullable|numeric|min:0',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.production_mix_id' => 'required|exists:production_mixes,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.extra_quantity' => 'nullable|numeric|min:0',
        'items.*.unit_price' => 'nullable|numeric|min:0',
    ]);

    try {
        DB::beginTransaction();

        $deployedItems = [];

        foreach ($validated['items'] as $itemData) {
            $mix = ProductionMix::with('product')->findOrFail($itemData['production_mix_id']);
            
            $regularQty = $itemData['quantity'];
            $extraQty = $itemData['extra_quantity'] ?? 0;
            $totalQty = $regularQty + $extraQty;

            // Check if enough stock in batch
            if ($totalQty > $mix->actual_output) {
                throw new \Exception("Insufficient stock in batch {$mix->batch_number}! Only {$mix->actual_output} units available for {$mix->product->name}.");
            }

            // Deduct from mix and warehouse
            $mix->decrement('actual_output', $totalQty);
            $mix->product->decrement('stock_on_hand', $totalQty);
            $mix->product->increment('stock_out', $totalQty);

            // Add regular quantity to branch inventory
            $inventory = BranchInventory::where('branch_id', $branch->id)
                ->where('finished_product_id', $mix->product->id)
                ->where('batch_number', $mix->batch_number)
                ->first();

            if ($inventory) {
                $inventory->increment('quantity', $regularQty);
            } else {
                BranchInventory::create([
                    'branch_id' => $branch->id,
                    'finished_product_id' => $mix->product->id,
                    'quantity' => $regularQty,
                    'batch_number' => $mix->batch_number,
                    'expiration_date' => $mix->expiration_date,
                ]);
            }

            // Record regular stock movement
            StockMovement::create([
                'finished_product_id' => $mix->product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'transfer_out',
                'quantity' => $regularQty,
                'batch_number' => $mix->batch_number,
                'expiration_date' => $mix->expiration_date,
                'movement_date' => $validated['movement_date'],
                'reference_number' => $validated['dr_number'],
                'customer_name' => $validated['customer_name'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            // Record extra/free products if any
            if ($extraQty > 0) {
                StockMovement::create([
                    'finished_product_id' => $mix->product->id,
                    'branch_id' => $branch->id,
                    'movement_type' => 'extra_free',
                    'quantity' => $extraQty,
                    'batch_number' => $mix->batch_number,
                    'expiration_date' => $mix->expiration_date,
                    'movement_date' => $validated['movement_date'],
                    'reference_number' => $validated['dr_number'],
                    'customer_name' => $validated['customer_name'],
                    'notes' => "Extra/Free (Expense) - " . ($validated['notes'] ?? 'Complimentary'),
                    'user_id' => Auth::id(),
                ]);
            }

            $deployedItems[] = "{$mix->product->name} ({$regularQty}" . ($extraQty > 0 ? " + {$extraQty} extra" : "") . ")";
        }

        // Add customer to branch if not exists
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

    // Return BO (Bad Orders) - Return stock from branch to warehouse
    public function returnStock(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $branchInventory = BranchInventory::findOrFail($validated['branch_inventory_id']);

        // Check if enough stock at branch
        if ($validated['quantity'] > $branchInventory->quantity) {
            return back()->withInput()->with('error', "Insufficient stock at area! Only {$branchInventory->quantity} units available.");
        }

        try {
            DB::beginTransaction();

            $product = $branchInventory->finishedProduct;
            $batchNumber = $branchInventory->batch_number;
            $expirationDate = $branchInventory->expiration_date;

            // If returning full quantity, delete the inventory record
            if ($validated['quantity'] >= $branchInventory->quantity) {
                $branchInventory->delete();
            } else {
                // Partial return - reduce quantity
                $branchInventory->decrement('quantity', $validated['quantity']);
            }

            // Return to warehouse
            $product->increment('stock_on_hand', $validated['quantity']);
            $product->decrement('stock_out', $validated['quantity']);

            // Return to MIX batch if batch number exists
            if ($batchNumber) {
                $mix = ProductionMix::where('batch_number', $batchNumber)->first();
                if ($mix) {
                    $mix->increment('actual_output', $validated['quantity']);
                }
            }

            // Record stock movement
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

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : "";
            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Returned {$validated['quantity']} units of {$product->name}{$batchInfo} to warehouse!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Return failed: ' . $e->getMessage());
        }
    }
}