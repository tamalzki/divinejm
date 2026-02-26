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
    public function transfer(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'production_mix_id' => 'required|exists:production_mixes,id',
            'quantity' => 'required|numeric|min:0.01',
            'extra_quantity' => 'nullable|numeric|min:0',  // NEW: Extra/free products
            'movement_date' => 'required|date',
            'reference_number' => 'required|string|max:255',  // CHANGED: Now required (DR Number)
            'notes' => 'nullable|string',
        ]);

        $mix = ProductionMix::with('product')->findOrFail($validated['production_mix_id']);
        
        $totalQuantity = $validated['quantity'] + ($validated['extra_quantity'] ?? 0);

        // Check if enough stock in batch (regular + extra)
        if ($totalQuantity > $mix->actual_output) {
            return back()->withInput()->with('error', "Insufficient stock in batch {$mix->batch_number}! Only {$mix->actual_output} units available.");
        }

        try {
            DB::beginTransaction();

            // Deduct total quantity (regular + extra) from inventory
            $mix->decrement('actual_output', $totalQuantity);
            $mix->product->decrement('stock_on_hand', $totalQuantity);
            $mix->product->increment('stock_out', $totalQuantity);

            // Add ONLY regular quantity to branch inventory (not extra)
            BranchInventory::create([
                'branch_id' => $branch->id,
                'finished_product_id' => $mix->product->id,
                'quantity' => $validated['quantity'],  // Only regular quantity
                'batch_number' => $mix->batch_number,
                'expiration_date' => $mix->expiration_date,
            ]);

            // Record regular stock movement
            StockMovement::create([
                'finished_product_id' => $mix->product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'transfer_out',
                'quantity' => $validated['quantity'],
                'batch_number' => $mix->batch_number,
                'expiration_date' => $mix->expiration_date,
                'movement_date' => $validated['movement_date'],
                'reference_number' => $validated['reference_number'],  // DR Number
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            // Record extra/free products as expense (if any)
            if (isset($validated['extra_quantity']) && $validated['extra_quantity'] > 0) {
                StockMovement::create([
                    'finished_product_id' => $mix->product->id,
                    'branch_id' => $branch->id,
                    'movement_type' => 'extra_free',  // NEW type: Extra/free products (expense)
                    'quantity' => $validated['extra_quantity'],
                    'batch_number' => $mix->batch_number,
                    'expiration_date' => $mix->expiration_date,
                    'movement_date' => $validated['movement_date'],
                    'reference_number' => $validated['reference_number'],
                    'notes' => "Extra/Free products (Expense) - " . ($validated['notes'] ?? 'Complimentary'),
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            $extraInfo = ($validated['extra_quantity'] ?? 0) > 0 
                ? " + {$validated['extra_quantity']} extra (expense)" 
                : "";

            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Delivered {$validated['quantity']} units{$extraInfo} of {$mix->product->name} (Batch: {$mix->batch_number}) to {$branch->name}! DR#: {$validated['reference_number']}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Delivery failed: ' . $e->getMessage());
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