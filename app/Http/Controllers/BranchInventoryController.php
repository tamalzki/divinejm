<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCustomer;
use App\Models\FinishedProduct;
use App\Models\BranchInventory;
use App\Models\StockMovement;
use App\Models\ProductionMix;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchInventoryController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // INDEX — list all deliveries (grouped by DR number)
    // ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $search = $request->get('search');

        $query = StockMovement::query()
            ->where('movement_type', 'transfer_out')
            ->with(['branch', 'user'])
            ->select(
                'reference_number as dr_number',
                'branch_id',
                DB::raw('MAX(notes) as notes'),
                'movement_date',
                'user_id',
                DB::raw('COUNT(DISTINCT finished_product_id) as product_count'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('MIN(id) as id')
            )
            ->groupBy('reference_number', 'branch_id', 'movement_date', 'user_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $rawDeliveries = $query->orderBy('movement_date', 'desc')->orderBy('reference_number', 'desc')->paginate(20);

        $branchIds = $rawDeliveries->pluck('branch_id')->unique();
        $branches  = Branch::whereIn('id', $branchIds)->pluck('name', 'id');

        $userIds = $rawDeliveries->pluck('user_id')->unique();
        $users   = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id');

        $deliveries = $rawDeliveries->through(function ($row) use ($branches, $users) {
            $row->branch_name  = $branches[$row->branch_id] ?? '—';
            $row->recorded_by  = $users[$row->user_id] ?? '—';
            $row->total_value  = 0; // no unit_price column — omit or extend later
            // Parse customer from notes: stored as "Customer: John | notes..."
            if ($row->notes && str_contains($row->notes, 'Customer:')) {
                preg_match('/Customer:\s*([^|]+)/', $row->notes, $m);
                $row->customer_name = trim($m[1] ?? '—');
            } else {
                $row->customer_name = '—';
            }
            // Parse delivered_by from notes
            if ($row->notes && str_contains($row->notes, 'Delivered By:')) {
                preg_match('/Delivered By:\s*([^|]+)/', $row->notes, $dm);
                $row->delivered_by = trim($dm[1] ?? '—');
            } else {
                $row->delivered_by = '—';
            }
            return $row;
        });

        return view('branch-inventory.index', compact('deliveries'));
    }

    // ──────────────────────────────────────────────────────────────────
    // AREAS INDEX — list areas (used by sidebar Areas link)
    // ──────────────────────────────────────────────────────────────────
    public function areas()
    {
        $branches = Branch::with(['inventory.finishedProduct'])
            ->where('is_active', true)
            ->get();

        return view('branch-inventory.areas', compact('branches'));
    }

    // ──────────────────────────────────────────────────────────────────
    // SHOW — area detail + movement history
    // ──────────────────────────────────────────────────────────────────
    public function show(Branch $branch)
    {
        $branch->load(['inventory.finishedProduct']);

        $stockMovements = StockMovement::where('branch_id', $branch->id)
            ->with(['finishedProduct', 'user'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('branch-inventory.show', compact('branch', 'stockMovements'));
    }

    // ──────────────────────────────────────────────────────────────────
    // CREATE DELIVERY — standalone form (branch selected on page)
    // ──────────────────────────────────────────────────────────────────
    public function createDelivery()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        // ONE stock card per product — total available.
        // FIFO batch resolution happens on backend at storeDelivery time.
        $products = FinishedProduct::orderBy('name')->get();

        return view('branch-inventory.create', compact('branches', 'products'));
    }

    // ──────────────────────────────────────────────────────────────────
    // STORE DELIVERY — handles submission from createDelivery form
    // ──────────────────────────────────────────────────────────────────
    public function storeDelivery(Request $request)
    {
        $request->validate(['branch_id' => 'required|exists:branches,id']);
        $branch = Branch::findOrFail($request->branch_id);
        return $this->transfer($request, $branch);
    }

    // ──────────────────────────────────────────────────────────────────
    // CREATE — per-branch deliver form (kept for show page "Deliver" btn)
    // ──────────────────────────────────────────────────────────────────
    public function create(Branch $branch)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = FinishedProduct::orderBy('name')->get();
        return view('branch-inventory.create', compact('branch', 'branches', 'products'));
    }

    // ──────────────────────────────────────────────────────────────────
    // SHOW DELIVERY — view a single DR delivery detail
    // ──────────────────────────────────────────────────────────────────
    public function showDelivery($drNumber)
    {
        $movements = StockMovement::where('reference_number', $drNumber)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->with(['finishedProduct', 'branch', 'user'])
            ->orderByRaw("FIELD(movement_type, 'transfer_out', 'extra_free')")
            ->orderBy('finished_product_id')
            ->get();

        abort_if($movements->isEmpty(), 404);

        $first    = $movements->first();
        $branch   = $first->branch;
        $totalQty = $movements->where('movement_type', 'transfer_out')->sum('quantity');

        return view('branch-inventory.show-delivery', compact('movements', 'first', 'branch', 'drNumber', 'totalQty'));
    }

    // ──────────────────────────────────────────────────────────────────
    // TRANSFER (store delivery) — FIFO batch resolution on backend
    // ──────────────────────────────────────────────────────────────────
    public function transfer(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'customer_name'           => 'required|string|max:255',
            'dr_number'               => 'required|string|max:255',
            'movement_date'           => 'required|date',
            'notes'                   => 'nullable|string',
            'delivered_by'            => 'nullable|string|max:100',
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer|exists:finished_products,id',
            'items.*.quantity'        => 'required|numeric|min:0.01',
            'items.*.extra_quantity'  => 'nullable|numeric|min:0',
            'items.*.unit_price'      => 'nullable|numeric|min:0',
        ]);

        // Encode customer into notes since no dedicated column exists
        $customerPrefix = 'Customer: ' . $validated['customer_name'];
        $deliveredBy    = !empty($validated['delivered_by']) ? $validated['delivered_by'] : null;
        $deliveredPart  = $deliveredBy ? ' | Delivered By: ' . $deliveredBy : '';
        $notesValue     = $validated['notes']
            ? $customerPrefix . $deliveredPart . ' | ' . $validated['notes']
            : $customerPrefix . $deliveredPart;

        try {
            DB::beginTransaction();

            $deployedItems = [];

            foreach ($validated['items'] as $itemData) {
                $productId  = (int) $itemData['product_id'];
                $product    = FinishedProduct::lockForUpdate()->findOrFail($productId);
                $regularQty = (float) $itemData['quantity'];
                $extraQty   = (float) ($itemData['extra_quantity'] ?? 0);
                $totalQty   = $regularQty + $extraQty;
                $unitPrice  = (float) ($itemData['unit_price'] ?? $product->selling_price ?? 0);

                if ($totalQty > $product->stock_on_hand) {
                    throw new \Exception("Insufficient stock for {$product->name}! Only {$product->stock_on_hand} units available.");
                }

                // ── FIFO: drain oldest batches first (earliest mix_date / lowest id) ──
                $remainingQty = $totalQty;
                $fifoBatches  = ProductionMix::where('finished_product_id', $productId)
                    ->where('status', 'completed')
                    ->where('actual_output', '>', 0)
                    ->orderBy('mix_date', 'asc')   // oldest first
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $usedBatchNumber    = null;
                $usedExpirationDate = null;

                foreach ($fifoBatches as $mix) {
                    if ($remainingQty <= 0) break;

                    $take = min($remainingQty, $mix->actual_output);
                    $mix->decrement('actual_output', $take);
                    $remainingQty -= $take;

                    // Track the primary batch for stock movement record
                    if ($usedBatchNumber === null) {
                        $usedBatchNumber    = $mix->batch_number;
                        $usedExpirationDate = $mix->expiration_date;
                    }
                }

                // If batches didn't fully cover (edge case), fall back to stock_on_hand pool
                // (no batch to decrement — stock is already tracked at product level)

                // Decrement product totals
                $product->decrement('stock_on_hand', $totalQty);
                $product->increment('stock_out', $totalQty);

                // ── Branch inventory — single row per product per branch ──
                $inventory = BranchInventory::where('branch_id', $branch->id)
                    ->where('finished_product_id', $productId)
                    ->first();

                if ($inventory) {
                    $inventory->increment('quantity', $regularQty);
                } else {
                    BranchInventory::create([
                        'branch_id'           => $branch->id,
                        'finished_product_id' => $productId,
                        'quantity'            => $regularQty,
                        'batch_number'        => $usedBatchNumber,
                        'expiration_date'     => $usedExpirationDate,
                    ]);
                }

                // ── Stock movement (transfer_out) ──
                StockMovement::create([
                    'finished_product_id' => $productId,
                    'branch_id'           => $branch->id,
                    'movement_type'       => 'transfer_out',
                    'quantity'            => $regularQty,
                    'batch_number'        => $usedBatchNumber,
                    'expiration_date'     => $usedExpirationDate,
                    'movement_date'       => $validated['movement_date'],
                    'reference_number'    => $validated['dr_number'],
                    'notes'               => $notesValue,
                    'user_id'             => Auth::id(),
                ]);

                // ── Extra/Free movement ──
                if ($extraQty > 0) {
                    StockMovement::create([
                        'finished_product_id' => $productId,
                        'branch_id'           => $branch->id,
                        'movement_type'       => 'extra_free',
                        'quantity'            => $extraQty,
                        'batch_number'        => $usedBatchNumber,
                        'expiration_date'     => $usedExpirationDate,
                        'movement_date'       => $validated['movement_date'],
                        'reference_number'    => $validated['dr_number'],
                        'notes'               => 'Extra/Free — ' . $notesValue,
                        'user_id'             => Auth::id(),
                    ]);
                }

                $deployedItems[] = "{$product->name} ({$regularQty}" . ($extraQty > 0 ? " + {$extraQty} extra" : '') . ')';
            }

            $branch->addCustomer($validated['customer_name']);

            // ── Auto-create Sale record from DR ──────────────────────────
            $sale = Sale::firstOrCreate(
                [
                    'branch_id'     => $branch->id,
                    'customer_name' => $validated['customer_name'],
                    'dr_number'     => $validated['dr_number'],
                ],
                [
                    'sale_date'      => $validated['movement_date'],
                    'total_amount'   => 0,
                    'amount_paid'    => 0,
                    'balance'        => 0,
                    'payment_status' => 'to_be_collected',
                    'status'         => 'active',
                    'notes'          => $validated['notes'] ?? null,
                    'user_id'        => Auth::id(),
                ]
            );

            foreach ($validated['items'] as $itemData) {
                $productId  = (int) $itemData['product_id'];
                $product    = FinishedProduct::findOrFail($productId);
                $regularQty = (float) $itemData['quantity'];
                $unitPrice  = (float) ($itemData['unit_price'] ?? $product->selling_price ?? 0);

                $existingItem = SaleItem::where('sale_id', $sale->id)
                    ->where('finished_product_id', $productId)
                    ->first();

                if (!$existingItem) {
                    SaleItem::create([
                        'sale_id'             => $sale->id,
                        'finished_product_id' => $productId,
                        'batch_number'        => null,
                        'quantity_deployed'   => $regularQty,
                        'quantity_sold'       => 0,
                        'quantity_unsold'     => $regularQty,
                        'quantity_bo'         => 0,
                        'quantity_replaced'   => 0,
                        'unit_price'          => $unitPrice,
                        'subtotal'            => 0,
                    ]);
                } else {
                    $existingItem->increment('quantity_deployed', $regularQty);
                    $existingItem->increment('quantity_unsold', $regularQty);
                }
            }

            DB::commit();

            return redirect()->route('branch-inventory.index')
                ->with('success', 'Delivered to ' . $validated['customer_name'] . ' — DR#' . $validated['dr_number'] . ': ' . implode(', ', $deployedItems));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Delivery failed: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // RETURN BO — return stock from branch back to warehouse
    // ──────────────────────────────────────────────────────────────────
    public function returnStock(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'quantity'            => 'required|numeric|min:0.01',
            'movement_date'       => 'required|date',
            'notes'               => 'nullable|string',
        ]);

        $branchInventory = BranchInventory::findOrFail($validated['branch_inventory_id']);

        if ($validated['quantity'] > $branchInventory->quantity) {
            return back()->withInput()->with('error', "Insufficient stock at area! Only {$branchInventory->quantity} units available.");
        }

        try {
            DB::beginTransaction();

            $product        = $branchInventory->finishedProduct;
            $batchNumber    = $branchInventory->batch_number;
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
                'branch_id'           => $branch->id,
                'movement_type'       => 'return_bo',
                'quantity'            => $validated['quantity'],
                'batch_number'        => $batchNumber,
                'expiration_date'     => $expirationDate,
                'movement_date'       => $validated['movement_date'],
                'notes'               => $validated['notes'],
                'user_id'             => Auth::id(),
            ]);

            DB::commit();

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : ' (No Batch)';
            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Returned {$validated['quantity']} units of {$product->name}{$batchInfo} to warehouse.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Return failed: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // DESTROY — delete a branch inventory entry and revert to warehouse
    // ──────────────────────────────────────────────────────────────────
    public function destroy(Request $request, Branch $branch, BranchInventory $branchInventory)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        // Safety: make sure this inventory record belongs to this branch
        if ($branchInventory->branch_id !== $branch->id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $product        = $branchInventory->finishedProduct;
            $qty            = $branchInventory->quantity;
            $batchNumber    = $branchInventory->batch_number;
            $expirationDate = $branchInventory->expiration_date;

            // Revert stock to warehouse
            $product->increment('stock_on_hand', $qty);
            $product->decrement('stock_out', $qty);

            // Revert batch output if applicable
            if ($batchNumber) {
                $mix = ProductionMix::where('batch_number', $batchNumber)->first();
                if ($mix) {
                    $mix->increment('actual_output', $qty);
                }
            }

            // Log the removal
            StockMovement::create([
                'finished_product_id' => $product->id,
                'branch_id'           => $branch->id,
                'movement_type'       => 'deleted',
                'quantity'            => $qty,
                'batch_number'        => $batchNumber,
                'expiration_date'     => $expirationDate,
                'movement_date'       => now()->toDateString(),
                'notes'               => 'Entry removed — ' . $request->notes,
                'user_id'             => Auth::id(),
            ]);

            $branchInventory->delete();

            DB::commit();

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : ' (No Batch)';
            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Removed {$qty} units of {$product->name}{$batchInfo} and reverted to warehouse.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}