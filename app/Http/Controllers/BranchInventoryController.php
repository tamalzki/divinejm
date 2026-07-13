<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCustomer;
use App\Models\BranchInventory;
use App\Models\FinishedProduct;
use App\Models\FinishedProductBranchPrice;
use App\Models\ProductionMix;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\DeliveryBatchReversalService;
use App\Services\DrNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BranchInventoryController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // INDEX — list areas (drill down to customers → DRs)
    // ──────────────────────────────────────────────────────────────────
    public function index()
    {
        $branches = Branch::withCount('branchCustomers')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $deliveryCounts = StockMovement::where('movement_type', 'transfer_out')
            ->select('branch_id', DB::raw('COUNT(DISTINCT reference_number) as c'))
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id');

        $branches->each(function ($branch) use ($deliveryCounts) {
            $branch->delivery_count = $deliveryCounts[$branch->id] ?? 0;
        });

        return view('branch-inventory.index', compact('branches'));
    }

    // ──────────────────────────────────────────────────────────────────
    // ALL DELIVERIES — flat, searchable list of every delivery (grouped by DR)
    // ──────────────────────────────────────────────────────────────────
    public function allDeliveries(Request $request)
    {
        $search = $request->get('search');

        $query = $this->baseDeliveriesQuery();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $rawDeliveries = $query->orderBy('movement_date', 'desc')->orderBy('reference_number', 'desc')->paginate(20);
        $rawDeliveries->setCollection(collect($this->enrichDeliveryRows($rawDeliveries->items())));
        $deliveries = $rawDeliveries;

        return view('branch-inventory.all', compact('deliveries'));
    }

    // ──────────────────────────────────────────────────────────────────
    // SHOW — area detail: list of that area's customers
    // ──────────────────────────────────────────────────────────────────
    public function show(Branch $branch)
    {
        $customers = $branch->branchCustomers;

        $deliveryCounts = StockMovement::where('movement_type', 'transfer_out')
            ->where('branch_id', $branch->id)
            ->select('notes', DB::raw('COUNT(DISTINCT reference_number) as c'))
            ->groupBy('notes')
            ->get();

        $countsByCustomer = [];
        foreach ($deliveryCounts as $row) {
            $cust = $this->parseCustomerNameFromMovementNotes($row->notes);
            if ($cust === null) {
                continue;
            }
            $countsByCustomer[$cust] = ($countsByCustomer[$cust] ?? 0) + $row->c;
        }

        $customers->each(function ($customer) use ($countsByCustomer) {
            $customer->delivery_count = $countsByCustomer[$customer->name] ?? 0;
        });

        return view('branch-inventory.show', compact('branch', 'customers'));
    }

    // ──────────────────────────────────────────────────────────────────
    // CUSTOMER DELIVERIES — DR list for one customer of one area
    // ──────────────────────────────────────────────────────────────────
    public function customerDeliveries(Branch $branch, BranchCustomer $branchCustomer)
    {
        abort_if($branchCustomer->branch_id !== $branch->id, 404);

        $rows = $this->baseDeliveriesQuery()
            ->where('branch_id', $branch->id)
            ->orderBy('movement_date', 'desc')
            ->orderBy('reference_number', 'desc')
            ->get()
            ->filter(fn ($row) => $this->parseCustomerNameFromMovementNotes($row->notes) === $branchCustomer->name)
            ->values();

        $deliveries = collect($this->enrichDeliveryRows($rows));

        return view('branch-inventory.customer-deliveries', compact('branch', 'branchCustomer', 'deliveries'));
    }

    // ──────────────────────────────────────────────────────────────────
    // CREATE DELIVERY — standalone form (branch selected on page)
    // ──────────────────────────────────────────────────────────────────
    public function createDelivery(Request $request)
    {
        $branches = Branch::with(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // ONE stock card per product — total available.
        // FIFO batch resolution happens on backend at storeDelivery time.
        $products = FinishedProduct::with('branchPrices')->orderBy('name')->get();

        $prefBranchId = $request->query('branch_id');
        $prefCustomer = $request->query('customer');
        $nextDrNumber = DrNumberService::peek();

        return view('branch-inventory.create', compact('branches', 'products', 'prefBranchId', 'prefCustomer', 'nextDrNumber'));
    }

    // ──────────────────────────────────────────────────────────────────
    // OUTSTANDING BO — JSON list of not-yet-replaced bad-order lines for
    // one area/customer, used by the "Replace BO" picker on the create form
    // ──────────────────────────────────────────────────────────────────
    public function outstandingBo(Request $request)
    {
        $branchId = $request->query('branch_id');
        $customerName = $request->query('customer');

        if (! $branchId || ! $customerName) {
            return response()->json([]);
        }

        $items = SaleItem::with(['finishedProduct', 'sale'])
            ->whereHas('sale', function ($q) use ($branchId, $customerName) {
                $q->where('branch_id', $branchId)->where('customer_name', $customerName);
            })
            ->whereColumn('quantity_bo', '>', 'quantity_replaced')
            ->get()
            ->map(function ($item) {
                return [
                    'sale_item_id' => $item->id,
                    'dr_number' => $item->sale->dr_number,
                    'product_id' => $item->finished_product_id,
                    'product_name' => $item->finishedProduct->name ?? '—',
                    'unit_price' => (float) $item->unit_price,
                    'outstanding_qty' => (float) $item->quantity_bo - (float) $item->quantity_replaced,
                    'sale_date' => optional($item->sale->sale_date)->format('M d, Y'),
                ];
            })
            ->values();

        return response()->json($items);
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
    public function create(Request $request, Branch $branch)
    {
        $branches = Branch::with(['branchCustomers' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $products = FinishedProduct::with('branchPrices')->orderBy('name')->get();

        $prefBranchId = $branch->id;
        $prefCustomer = $request->query('customer');
        $nextDrNumber = DrNumberService::peek();

        return view('branch-inventory.create', compact('branch', 'branches', 'products', 'prefBranchId', 'prefCustomer', 'nextDrNumber'));
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

        $boReplacementMovements = StockMovement::where('reference_number', $drNumber)
            ->where('movement_type', 'bo_replacement')
            ->with(['finishedProduct', 'branch', 'user', 'sourceSaleItem.sale'])
            ->orderBy('finished_product_id')
            ->get();

        abort_if($movements->isEmpty() && $boReplacementMovements->isEmpty(), 404);

        $first = $movements->first() ?? $boReplacementMovements->first();
        $branch = $first->branch;
        $totalQty = $movements->where('movement_type', 'transfer_out')->sum('quantity');
        $custParsed = $this->parseCustomerNameFromMovementNotes($first->notes);
        $customerName = $custParsed ?? '—';
        $saleRecord = $this->saleForDelivery($branch->id, $drNumber, $custParsed);
        $saleRecord?->load('items');

        return view('branch-inventory.show-delivery', compact('movements', 'boReplacementMovements', 'first', 'branch', 'drNumber', 'totalQty', 'customerName', 'saleRecord'));
    }

    // ──────────────────────────────────────────────────────────────────
    // TRANSFER (store delivery) — FIFO batch resolution on backend
    // ──────────────────────────────────────────────────────────────────
    public function transfer(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'customer_name' => 'required|string|max:255',
            'dr_mode' => 'nullable|in:auto,manual',
            'dr_number' => 'nullable|required_if:dr_mode,manual|string|max:255',
            'movement_date' => 'required|date',
            'notes' => 'nullable|string',
            'delivered_by' => 'nullable|string|max:100',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:finished_products,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.extra_quantity' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'bo_replacements' => 'nullable|array',
            'bo_replacements.*.sale_item_id' => 'required_with:bo_replacements|integer|exists:sale_items,id',
            'bo_replacements.*.quantity' => 'required_with:bo_replacements|numeric|min:0',
        ], [
            'branch_id.required' => 'Please select an area for this delivery.',
            'branch_id.exists' => 'The selected area is invalid or no longer available.',
            'customer_name.required' => 'Customer is required — select an area, then choose or type a customer name.',
            'customer_name.max' => 'Customer name must not exceed 255 characters.',
            'movement_date.required' => 'Delivery date is required.',
            'movement_date.date' => 'Delivery date must be a valid date.',
            'items.required' => 'No product lines were submitted. Enter a quantity greater than zero for at least one product.',
            'items.*.product_id.required' => 'Each delivery line must reference a valid product.',
            'items.*.product_id.exists' => 'One or more products are invalid.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity cannot be negative.',
            'items.*.extra_quantity.min' => 'Extra / free quantity cannot be negative.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
            'bo_replacements.*.sale_item_id.exists' => 'One or more BO replacement lines are invalid.',
            'bo_replacements.*.quantity.min' => 'BO replacement quantity cannot be negative.',
            'dr_number.required_if' => 'Enter a DR number, or switch back to auto-generated.',
        ]);

        if ((int) $validated['branch_id'] !== (int) $branch->id) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected area does not match this delivery. Refresh the page and select the correct area again.',
            ]);
        }

        $manualDrNumber = null;
        if (($validated['dr_mode'] ?? 'auto') === 'manual') {
            $manualDrNumber = trim($validated['dr_number']);
            if (DrNumberService::isTaken($manualDrNumber)) {
                throw ValidationException::withMessages([
                    'dr_number' => 'DR# '.$manualDrNumber.' is already used by another delivery. Enter a different DR number.',
                ]);
            }
        }

        $lineItems = collect($validated['items'] ?? [])
            ->filter(fn ($row) => (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        $boReplacements = collect($validated['bo_replacements'] ?? [])
            ->filter(fn ($row) => (float) ($row['quantity'] ?? 0) > 0)
            ->values();

        if ($lineItems->isEmpty() && $boReplacements->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product with a quantity greater than zero, or a BO replacement, to include on this delivery.',
            ]);
        }

        // Encode customer into notes since no dedicated column exists
        $customerPrefix = 'Customer: '.$validated['customer_name'];
        $deliveredBy = ! empty($validated['delivered_by']) ? $validated['delivered_by'] : null;
        $deliveredPart = $deliveredBy ? ' | Delivered By: '.$deliveredBy : '';
        $notesValue = $validated['notes']
            ? $customerPrefix.$deliveredPart.' | '.$validated['notes']
            : $customerPrefix.$deliveredPart;

        // Area-specific prices for this branch — used whenever the
        // submitted line item doesn't carry its own unit_price.
        // Distributor accounts skip area pricing and use distributor_price instead.
        $branchPriceMap = $branch->is_distributor
            ? collect()
            : FinishedProductBranchPrice::where('branch_id', $branch->id)->pluck('price', 'finished_product_id');

        try {
            DB::beginTransaction();

            $drNumber = $manualDrNumber ?? DrNumberService::next();

            $deployedItems = [];

            foreach ($lineItems as $itemData) {
                $productId = (int) $itemData['product_id'];
                $product = FinishedProduct::lockForUpdate()->findOrFail($productId);
                $regularQty = (float) $itemData['quantity'];
                $extraQty = (float) ($itemData['extra_quantity'] ?? 0);
                $totalQty = $regularQty + $extraQty;
                $unitPrice = $this->resolveUnitPrice($itemData, $product, $branch, $branchPriceMap);

                // Allow warehouse stock_on_hand to go negative when needed for sales/deliveries.

                [$usedBatchNumber, $usedExpirationDate] = $this->deductFifoStock($productId, $totalQty);

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
                    'reference_number' => $drNumber,
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
                        'reference_number' => $drNumber,
                        'notes' => 'Extra/Free — '.$notesValue,
                        'user_id' => Auth::id(),
                    ]);
                }

                $deployedItems[] = "{$product->name} ({$regularQty}".($extraQty > 0 ? " + {$extraQty} extra" : '').')';
            }

            // ── BO Replacements — free (never billed), deducts stock like a
            // regular delivery, tagged to this new DR, and marks the original
            // bad-order line as resolved via quantity_replaced. ──
            $boReplacedItems = [];

            foreach ($boReplacements as $boData) {
                $saleItem = SaleItem::with(['finishedProduct', 'sale'])
                    ->lockForUpdate()
                    ->findOrFail((int) $boData['sale_item_id']);
                $requestedQty = (float) $boData['quantity'];
                $outstanding = (float) $saleItem->quantity_bo - (float) $saleItem->quantity_replaced;

                if ($requestedQty > $outstanding + 0.0001) {
                    throw ValidationException::withMessages([
                        'bo_replacements' => 'Cannot replace '.$requestedQty.' unit(s) of '.($saleItem->finishedProduct->name ?? 'product')
                            .' — only '.$outstanding.' outstanding BO remain.',
                    ]);
                }

                $boProductId = (int) $saleItem->finished_product_id;
                $boProduct = FinishedProduct::lockForUpdate()->findOrFail($boProductId);

                [$boBatchNumber, $boExpirationDate] = $this->deductFifoStock($boProductId, $requestedQty);

                $boProduct->decrement('stock_on_hand', $requestedQty);
                $boProduct->increment('stock_out', $requestedQty);

                $boInventory = BranchInventory::where('branch_id', $branch->id)
                    ->where('finished_product_id', $boProductId)
                    ->first();

                if ($boInventory) {
                    $boInventory->increment('quantity', $requestedQty);
                } else {
                    BranchInventory::create([
                        'branch_id' => $branch->id,
                        'finished_product_id' => $boProductId,
                        'quantity' => $requestedQty,
                        'batch_number' => $boBatchNumber,
                        'expiration_date' => $boExpirationDate,
                    ]);
                }

                StockMovement::create([
                    'finished_product_id' => $boProductId,
                    'branch_id' => $branch->id,
                    'movement_type' => 'bo_replacement',
                    'quantity' => $requestedQty,
                    'batch_number' => $boBatchNumber,
                    'expiration_date' => $boExpirationDate,
                    'movement_date' => $validated['movement_date'],
                    'reference_number' => $drNumber,
                    'unit_price' => $saleItem->unit_price,
                    'source_sale_item_id' => $saleItem->id,
                    'notes' => $notesValue.' | BO Replacement — free, original DR# '.($saleItem->sale->dr_number ?? '—'),
                    'user_id' => Auth::id(),
                ]);

                $saleItem->increment('quantity_replaced', $requestedQty);

                $boReplacedItems[] = "{$saleItem->finishedProduct->name} ({$requestedQty})";
            }

            $branch->addCustomer($validated['customer_name']);

            // ── Auto-create Sale record from DR ──────────────────────────
            $sale = Sale::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'customer_name' => $validated['customer_name'],
                    'dr_number' => $drNumber,
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
                $unitPrice = $this->resolveUnitPrice($itemData, $product, $branch, $branchPriceMap);

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

            $successMsg = 'Delivery confirmed — '.$validated['customer_name'].' · DR# '.$drNumber;
            if (! empty($deployedItems)) {
                $successMsg .= ' — '.implode(', ', $deployedItems);
            }
            if (! empty($boReplacedItems)) {
                $successMsg .= ' | BO Replaced (free): '.implode(', ', $boReplacedItems);
            }

            return redirect()->route('branch-inventory.show-delivery', $drNumber)
                ->with('success', $successMsg)
                ->with('delivery_just_saved', true)
                ->with('auto_print', $request->boolean('print_after_save'));

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
            ->whereIn('movement_type', ['transfer_out', 'extra_free', 'bo_replacement'])
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
                ->whereIn('movement_type', ['transfer_out', 'extra_free', 'bo_replacement'])
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

            // Undo BO replacements on this DR — the original bad-order lines become outstanding again.
            foreach ($movements->where('movement_type', 'bo_replacement') as $boMovement) {
                if (! $boMovement->source_sale_item_id) {
                    continue;
                }
                $sourceSaleItem = SaleItem::lockForUpdate()->find($boMovement->source_sale_item_id);
                if ($sourceSaleItem) {
                    $sourceSaleItem->quantity_replaced = max(0, round((float) $sourceSaleItem->quantity_replaced - (float) $boMovement->quantity, 2));
                    $sourceSaleItem->save();
                }
            }

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

    private function baseDeliveriesQuery()
    {
        return StockMovement::query()
            ->whereIn('movement_type', ['transfer_out', 'bo_replacement'])
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
    }

    /**
     * Attach branch name, recorded-by user, DR face value, customer name,
     * delivered-by, and linked sale id to each grouped delivery row.
     *
     * @param  iterable<int, mixed>  $rows
     * @return array<int, mixed>
     */
    private function enrichDeliveryRows(iterable $rows): array
    {
        $rows = collect($rows)->values();

        $branchIds = $rows->pluck('branch_id')->unique();
        $branches = Branch::whereIn('id', $branchIds)->pluck('name', 'id');

        $userIds = $rows->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)->pluck('name', 'id');

        $drNumbers = $rows->pluck('dr_number')->unique()->filter()->values();
        $drTotals = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereIn('sales.dr_number', $drNumbers)
            ->select('sales.dr_number', DB::raw('SUM(sale_items.quantity_deployed * sale_items.unit_price) as deployed_total'))
            ->groupBy('sales.dr_number')
            ->pluck('deployed_total', 'dr_number');

        $saleIdsByKey = $this->bulkResolveSaleIdsForDeliveryRows($rows->all());

        return $rows->map(function ($row) use ($branches, $users, $drTotals, $saleIdsByKey) {
            $row->branch_name = $branches[$row->branch_id] ?? '—';
            $row->recorded_by = $users[$row->user_id] ?? '—';
            $row->total_value = (float) ($drTotals[$row->dr_number] ?? 0);
            $custParsed = $this->parseCustomerNameFromMovementNotes($row->notes ?? null);
            $row->customer_name = $custParsed ?? '—';

            if ($row->notes && str_contains($row->notes, 'Delivered By:')) {
                preg_match('/Delivered By:\s*([^|]+)/', $row->notes, $dm);
                $row->delivered_by = trim($dm[1] ?? '—');
            } else {
                $row->delivered_by = '—';
            }

            $row->sale_id = null;
            if ($custParsed !== null) {
                $key = static::deliverySaleMapKey((int) $row->branch_id, (string) $row->dr_number, $custParsed);
                $row->sale_id = $saleIdsByKey[$key] ?? null;
            }

            return $row;
        })->all();
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

    private static function deliverySaleMapKey(int $branchId, string $drNumber, string $customerName): string
    {
        return $branchId.'|'.$drNumber.'|'.$customerName;
    }

    /** @param  iterable<int|string, mixed>  $items */
    private function bulkResolveSaleIdsForDeliveryRows(iterable $items): array
    {
        $tuplesByKey = [];

        foreach ($items as $row) {
            $cust = $this->parseCustomerNameFromMovementNotes($row->notes ?? null);
            if ($cust === null) {
                continue;
            }

            $tuplesByKey[self::deliverySaleMapKey((int) $row->branch_id, (string) $row->dr_number, $cust)] = [
                'b' => (int) $row->branch_id,
                'd' => (string) $row->dr_number,
                'c' => $cust,
            ];
        }

        $list = array_values($tuplesByKey);

        if ($list === []) {
            return [];
        }

        $query = Sale::query()->select(['id', 'branch_id', 'dr_number', 'customer_name']);

        foreach ($list as $i => $tuple) {
            $method = $i === 0 ? 'where' : 'orWhere';
            $query->$method(function ($q) use ($tuple) {
                $q->where('branch_id', $tuple['b'])
                    ->where('dr_number', $tuple['d'])
                    ->where('customer_name', $tuple['c']);
            });
        }

        $map = [];

        foreach ($query->get() as $sale) {
            $map[self::deliverySaleMapKey((int) $sale->branch_id, (string) $sale->dr_number, (string) $sale->customer_name)] = $sale->id;
        }

        return $map;
    }

    private function saleForDelivery(int $branchId, string $drNumber, ?string $customerName): ?Sale
    {
        $query = Sale::where('branch_id', $branchId)->where('dr_number', $drNumber);

        if ($customerName !== null && $customerName !== '') {
            return $query->where('customer_name', $customerName)->first();
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * FIFO: drain oldest completed production batches first (earliest
     * mix_date / lowest id). Returns [batchNumber, expirationDate] of the
     * primary (first-touched) batch used, for the resulting stock movement.
     *
     * @return array{0: ?string, 1: ?\Illuminate\Support\Carbon}
     */
    private function deductFifoStock(int $productId, float $qty): array
    {
        $remainingQty = $qty;
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

        return [$usedBatchNumber, $usedExpirationDate];
    }

    /**
     * Unit price precedence: explicit price typed on the line > distributor
     * price (for distributor accounts) > area-specific price > selling price.
     */
    private function resolveUnitPrice(array $itemData, FinishedProduct $product, Branch $branch, \Illuminate\Support\Collection $branchPriceMap): float
    {
        if (isset($itemData['unit_price']) && $itemData['unit_price'] !== null && $itemData['unit_price'] !== '') {
            return (float) $itemData['unit_price'];
        }

        if ($branch->is_distributor) {
            return (float) $product->distributor_price;
        }

        return (float) ($branchPriceMap->get($product->id) ?? $product->selling_price ?? 0);
    }
}
