<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\FinishedProduct;
use App\Models\ProductionMix;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\DeliveryBatchReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
        $branches = Branch::whereIn('id', $branchIds)->pluck('name', 'id');

        $userIds = $rawDeliveries->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id');

        $deliveries = $rawDeliveries->through(function ($row) use ($branches, $users) {
            $row->branch_name = $branches[$row->branch_id] ?? '—';
            $row->recorded_by = $users[$row->user_id] ?? '—';
            $row->total_value = 0; // no unit_price column — omit or extend later
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
        $branches = Branch::with(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

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
        $request->validate(
            ['branch_id' => 'required|exists:branches,id'],
            [
                'branch_id.required' => 'Please select an area for this delivery.',
                'branch_id.exists' => 'The selected area is invalid or no longer available.',
            ]
        );
        $branch = Branch::findOrFail($request->branch_id);

        return $this->transfer($request, $branch);
    }

    // ──────────────────────────────────────────────────────────────────
    // CREATE — per-branch deliver form (kept for show page "Deliver" btn)
    // ──────────────────────────────────────────────────────────────────
    public function create(Branch $branch)
    {
        $branches = Branch::with(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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

        $first = $movements->first();
        $branch = $first->branch;
        $totalQty = $movements->where('movement_type', 'transfer_out')->sum('quantity');

        return view('branch-inventory.show-delivery', compact('movements', 'first', 'branch', 'drNumber', 'totalQty'));
    }

    // ──────────────────────────────────────────────────────────────────
    // TRANSFER (store delivery) — FIFO batch resolution on backend
    // ──────────────────────────────────────────────────────────────────
    public function transfer(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'customer_name' => 'required|string|max:255',
            'dr_number' => 'required|string|max:255',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
            'delivered_by' => 'nullable|string|max:100',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:finished_products,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.extra_quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ], [
            'branch_id.required' => 'Please select an area for this delivery.',
            'branch_id.exists' => 'The selected area is invalid or no longer available.',
            'customer_name.required' => 'Customer is required — select an area, then choose or type a customer name.',
            'customer_name.max' => 'Customer name must not exceed 255 characters.',
            'dr_number.required' => 'DR number is required.',
            'movement_date.required' => 'Delivery date is required.',
            'movement_date.date' => 'Delivery date must be a valid date.',
            'items.required' => 'No product lines were submitted. Enter a quantity greater than zero for at least one product.',
            'items.*.product_id.required' => 'Each delivery line must reference a valid product.',
            'items.*.product_id.exists' => 'One or more products are invalid.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity cannot be negative.',
            'items.*.extra_quantity.min' => 'Extra / free quantity cannot be negative.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
        ]);

        if ((int) $validated['branch_id'] !== (int) $branch->id) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected area does not match this delivery. Refresh the page and select the correct area again.',
            ]);
        }

        $lineItems = collect($validated['items'])
            ->filter(fn ($row) => (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($lineItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product with a quantity greater than zero to include on this delivery.',
            ]);
        }

        // Encode customer into notes since no dedicated column exists
        $customerPrefix = 'Customer: '.$validated['customer_name'];
        $deliveredBy = ! empty($validated['delivered_by']) ? $validated['delivered_by'] : null;
        $deliveredPart = $deliveredBy ? ' | Delivered By: '.$deliveredBy : '';
        $notesValue = $validated['notes']
            ? $customerPrefix.$deliveredPart.' | '.$validated['notes']
            : $customerPrefix.$deliveredPart;

        try {
            DB::beginTransaction();

            $deployedItems = [];

            foreach ($lineItems as $itemData) {
                $productId = (int) $itemData['product_id'];
                $product = FinishedProduct::lockForUpdate()->findOrFail($productId);
                $regularQty = (float) $itemData['quantity'];
                $extraQty = (float) ($itemData['extra_quantity'] ?? 0);
                $totalQty = $regularQty + $extraQty;
                $unitPrice = (float) ($itemData['unit_price'] ?? $product->selling_price ?? 0);

                // Allow warehouse stock_on_hand to go negative when needed for sales/deliveries.

                // ── FIFO: drain oldest batches first (earliest mix_date / lowest id) ──
                $remainingQty = $totalQty;
                $fifoBatches = ProductionMix::where('finished_product_id', $productId)
                    ->where('status', 'completed')
                    ->where('actual_output', '>', 0)
                    ->orderBy('mix_date', 'asc')   // oldest first
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $usedBatchNumber = null;
                $usedExpirationDate = null;

                foreach ($fifoBatches as $mix) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    $take = min($remainingQty, $mix->actual_output);
                    $mix->decrement('actual_output', $take);
                    $remainingQty -= $take;

                    // Track the primary batch for stock movement record
                    if ($usedBatchNumber === null) {
                        $usedBatchNumber = $mix->batch_number;
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
                        'branch_id' => $branch->id,
                        'finished_product_id' => $productId,
                        'quantity' => $regularQty,
                        'batch_number' => $usedBatchNumber,
                        'expiration_date' => $usedExpirationDate,
                    ]);
                }

                // ── Stock movement (transfer_out) ──
                StockMovement::create([
                    'finished_product_id' => $productId,
                    'branch_id' => $branch->id,
                    'movement_type' => 'transfer_out',
                    'quantity' => $regularQty,
                    'batch_number' => $usedBatchNumber,
                    'expiration_date' => $usedExpirationDate,
                    'movement_date' => $validated['movement_date'],
                    'reference_number' => $validated['dr_number'],
                    'notes' => $notesValue,
                    'user_id' => Auth::id(),
                ]);

                // ── Extra/Free movement ──
                if ($extraQty > 0) {
                    StockMovement::create([
                        'finished_product_id' => $productId,
                        'branch_id' => $branch->id,
                        'movement_type' => 'extra_free',
                        'quantity' => $extraQty,
                        'batch_number' => $usedBatchNumber,
                        'expiration_date' => $usedExpirationDate,
                        'movement_date' => $validated['movement_date'],
                        'reference_number' => $validated['dr_number'],
                        'notes' => 'Extra/Free — '.$notesValue,
                        'user_id' => Auth::id(),
                    ]);
                }

                $deployedItems[] = "{$product->name} ({$regularQty}".($extraQty > 0 ? " + {$extraQty} extra" : '').')';
            }

            $branch->addCustomer($validated['customer_name']);

            // ── Auto-create Sale record from DR ──────────────────────────
            $sale = Sale::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'customer_name' => $validated['customer_name'],
                    'dr_number' => $validated['dr_number'],
                ],
                [
                    'sale_date' => $validated['movement_date'],
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'balance' => 0,
                    'payment_status' => 'to_be_collected',
                    'status' => 'active',
                    'notes' => $validated['notes'] ?? null,
                    'user_id' => Auth::id(),
                ]
            );

            foreach ($lineItems as $itemData) {
                $productId = (int) $itemData['product_id'];
                $product = FinishedProduct::findOrFail($productId);
                $regularQty = (float) $itemData['quantity'];
                $unitPrice = (float) ($itemData['unit_price'] ?? $product->selling_price ?? 0);

                $existingItem = SaleItem::where('sale_id', $sale->id)
                    ->where('finished_product_id', $productId)
                    ->first();

                if (! $existingItem) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'finished_product_id' => $productId,
                        'batch_number' => null,
                        'quantity_deployed' => $regularQty,
                        'quantity_sold' => 0,
                        'quantity_unsold' => $regularQty,
                        'quantity_bo' => 0,
                        'quantity_replaced' => 0,
                        'unit_price' => $unitPrice,
                        'subtotal' => 0,
                    ]);
                } else {
                    $existingItem->increment('quantity_deployed', $regularQty);
                    $existingItem->increment('quantity_unsold', $regularQty);
                }
            }

            DB::commit();

            return redirect()->route('branch-inventory.index')
                ->with('success', 'Delivered to '.$validated['customer_name'].' — DR#'.$validated['dr_number'].': '.implode(', ', $deployedItems));

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Delivery failed: '.$e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // RETURN BO — return stock from branch back to warehouse
    // ──────────────────────────────────────────────────────────────────
    public function returnStock(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_inventory_id' => 'required|exists:branch_inventory,id',
            'quantity' => 'required|numeric|min:0.01',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $branchInventory = BranchInventory::whereKey($validated['branch_inventory_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $branchInventory->branch_id !== (int) $branch->id) {
                abort(403);
            }

            $qty = (float) $validated['quantity'];
            $product = FinishedProduct::lockForUpdate()->findOrFail($branchInventory->finished_product_id);
            $batchNumber = $branchInventory->batch_number;
            $expirationDate = $branchInventory->expiration_date;

            // Allow area quantity to go negative when return exceeds recorded on-hand (same idea as warehouse over-delivery).
            $newBranchQty = round((float) $branchInventory->quantity - $qty, 2);
            if (abs($newBranchQty) < 0.0001) {
                $branchInventory->delete();
            } else {
                $branchInventory->quantity = $newBranchQty;
                $branchInventory->save();
            }

            $product->increment('stock_on_hand', $qty);
            $product->decrement('stock_out', $qty);

            if ($batchNumber) {
                $mix = ProductionMix::where('batch_number', $batchNumber)->first();
                if ($mix) {
                    $mix->increment('actual_output', $qty);
                }
            }

            StockMovement::create([
                'finished_product_id' => $product->id,
                'branch_id' => $branch->id,
                'movement_type' => 'return_bo',
                'quantity' => $qty,
                'batch_number' => $batchNumber,
                'expiration_date' => $expirationDate,
                'movement_date' => $validated['movement_date'],
                'notes' => $validated['notes'],
                'user_id' => Auth::id(),
            ]);

            DB::commit();

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : ' (No Batch)';

            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Returned {$qty} units of {$product->name}{$batchInfo} to warehouse.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Return failed: '.$e->getMessage());
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

            $product = $branchInventory->finishedProduct;
            $qty = $branchInventory->quantity;
            $batchNumber = $branchInventory->batch_number;
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
                'branch_id' => $branch->id,
                'movement_type' => 'deleted',
                'quantity' => $qty,
                'batch_number' => $batchNumber,
                'expiration_date' => $expirationDate,
                'movement_date' => now()->toDateString(),
                'notes' => 'Entry removed — '.$request->notes,
                'user_id' => Auth::id(),
            ]);

            $branchInventory->delete();

            DB::commit();

            $batchInfo = $batchNumber ? " (Batch: {$batchNumber})" : ' (No Batch)';

            return redirect()->route('branch-inventory.show', $branch)
                ->with('success', "Removed {$qty} units of {$product->name}{$batchInfo} and reverted to warehouse.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // DESTROY DELIVERY BATCH — reverse one grouped delivery from index
    // (same key as index: DR #, area, date, recorded-by user)
    // ──────────────────────────────────────────────────────────────────
    public function destroyDeliveryBatch(Request $request)
    {
        $validated = $request->validate([
            'dr_number' => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
            'movement_date' => 'required|date',
            'user_id' => 'required|integer|exists:users,id',
        ], [
            'dr_number.required' => 'DR number is required to remove a delivery.',
            'branch_id.required' => 'Area is required.',
            'branch_id.exists' => 'The selected area is invalid.',
            'movement_date.required' => 'Delivery date is required.',
            'movement_date.date' => 'Delivery date is invalid.',
            'user_id.required' => 'Recorded user is required.',
            'user_id.exists' => 'The recorded user is invalid.',
        ]);

        $movements = StockMovement::query()
            ->where('reference_number', $validated['dr_number'])
            ->where('branch_id', $validated['branch_id'])
            ->whereDate('movement_date', $validated['movement_date'])
            ->where('user_id', $validated['user_id'])
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->orderBy('id')
            ->get();

        if ($movements->isEmpty()) {
            return redirect()->route('branch-inventory.index')
                ->with('error', 'That delivery was not found or was already removed.');
        }

        $firstNotes = $movements->first()->notes ?? '';
        $customerName = $this->parseCustomerNameFromMovementNotes($firstNotes);

        try {
            DB::beginTransaction();

            $movements = StockMovement::query()
                ->where('reference_number', $validated['dr_number'])
                ->where('branch_id', $validated['branch_id'])
                ->whereDate('movement_date', $validated['movement_date'])
                ->where('user_id', $validated['user_id'])
                ->whereIn('movement_type', ['transfer_out', 'extra_free'])
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($movements->isEmpty()) {
                DB::rollBack();

                return redirect()->route('branch-inventory.index')
                    ->with('error', 'That delivery was not found or was already removed.');
            }

            $sale = null;
            if ($customerName) {
                $sale = Sale::where('branch_id', $validated['branch_id'])
                    ->where('customer_name', $customerName)
                    ->where('dr_number', $validated['dr_number'])
                    ->lockForUpdate()
                    ->first();
            } else {
                $saleCandidates = Sale::where('branch_id', $validated['branch_id'])
                    ->where('dr_number', $validated['dr_number'])
                    ->lockForUpdate()
                    ->get();
                if ($saleCandidates->count() === 1) {
                    $sale = $saleCandidates->first();
                } elseif ($saleCandidates->count() > 1) {
                    throw ValidationException::withMessages([
                        'delivery' => 'This DR has more than one customer on file for this area. Customer could not be read from delivery notes; remove this delivery from the database admin or fix movement notes before deleting.',
                    ]);
                }
            }

            if ($sale) {
                if ((float) $sale->amount_paid > 0) {
                    throw ValidationException::withMessages([
                        'delivery' => 'This delivery cannot be removed because DR# '.$validated['dr_number'].' already has recorded payments. Void or adjust payments first.',
                    ]);
                }

                foreach ($movements->where('movement_type', 'transfer_out')->groupBy('finished_product_id') as $productId => $rows) {
                    $regular = (float) $rows->sum('quantity');
                    $item = SaleItem::where('sale_id', $sale->id)
                        ->where('finished_product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if ($item) {
                        if ((float) $item->quantity_deployed < $regular - 0.0001) {
                            throw new \RuntimeException(
                                'Branch sale lines do not match this delivery (deployed quantity is lower than this delivery). Refusing to delete to protect data integrity.'
                            );
                        }
                    }
                }
            }

            $branchId = (int) $validated['branch_id'];

            app(DeliveryBatchReversalService::class)->revertStockBranchAndBatches($movements, $branchId);

            if ($sale) {
                foreach ($movements->where('movement_type', 'transfer_out')->groupBy('finished_product_id') as $productId => $rows) {
                    $regular = (float) $rows->sum('quantity');
                    $item = SaleItem::where('sale_id', $sale->id)
                        ->where('finished_product_id', $productId)
                        ->first();

                    if (! $item) {
                        continue;
                    }

                    $newDeployed = round((float) $item->quantity_deployed - $regular, 2);
                    $newUnsold = max(0, round((float) $item->quantity_unsold - $regular, 2));

                    if ($newDeployed <= 0.0001) {
                        $item->delete();
                    } else {
                        $item->quantity_deployed = $newDeployed;
                        $item->quantity_unsold = $newUnsold;
                        $item->save();
                    }
                }

                $sale->refresh();
                if ($sale->items()->count() === 0) {
                    $sale->delete();
                } else {
                    $sale->recalculateTotal();
                }
            }

            StockMovement::whereIn('id', $movements->pluck('id'))->delete();

            DB::commit();

            Log::info('Delivery batch removed', [
                'dr_number' => $validated['dr_number'],
                'branch_id' => $validated['branch_id'],
                'movement_date' => $validated['movement_date'],
                'user_id' => $validated['user_id'],
                'removed_by' => Auth::id(),
            ]);

            return redirect()->route('branch-inventory.index')
                ->with('success', 'Delivery DR# '.$validated['dr_number'].' was removed. Warehouse and area stock were restored.');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('destroyDeliveryBatch failed', ['message' => $e->getMessage(), 'input' => $validated]);

            return redirect()->route('branch-inventory.index')
                ->with('error', $e->getMessage());
        }
    }

    private function parseCustomerNameFromMovementNotes(?string $notes): ?string
    {
        if (! $notes || ! str_contains($notes, 'Customer:')) {
            return null;
        }
        if (preg_match('/Customer:\s*([^|]+)/', $notes, $m)) {
            $name = trim($m[1] ?? '');

            return $name !== '' ? $name : null;
        }

        return null;
    }
}
