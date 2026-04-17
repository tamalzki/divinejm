<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCustomer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleRecordHistory;
use App\Models\StockMovement;
use App\Services\DeliveryBatchReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SaleController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // INDEX — summary grouped by Area → Customer
    // ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Get all branches that have sales
        $branches = Branch::whereHas('sales')
            ->with(['sales' => function ($q) use ($search) {
                if ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('dr_number', 'like', "%{$search}%");
                    });
                }
            }])
            ->orderBy('name')
            ->get();

        // Per branch → group sales by customer_name
        $areaData = $branches->map(function ($branch) {
            $customers = $branch->sales
                ->groupBy('customer_name')
                ->map(function ($sales, $customerName) use ($branch) {
                    $totalDeployed = SaleItem::whereIn('sale_id', $sales->pluck('id'))
                        ->sum('quantity_deployed');
                    $totalSold = SaleItem::whereIn('sale_id', $sales->pluck('id'))
                        ->sum('quantity_sold');
                    $totalBalance = $sales->sum('balance');

                    $uncollectedCount = $sales->whereIn('payment_status', ['to_be_collected', 'partial'])->count();
                    $paidCount = $sales->where('payment_status', 'paid')->count();

                    if ($uncollectedCount > 0 && $paidCount === 0) {
                        $overallStatus = 'to_be_collected';
                    } elseif ($uncollectedCount > 0) {
                        $overallStatus = 'partial';
                    } else {
                        $overallStatus = 'paid';
                    }

                    return [
                        'customer_name' => $customerName,
                        'branch_id' => $branch->id,
                        'dr_count' => $sales->count(),
                        'total_deployed' => $totalDeployed,
                        'total_sold' => $totalSold,
                        'total_balance' => $totalBalance,
                        'has_uncollected' => $uncollectedCount > 0,
                        'overall_status' => $overallStatus,
                    ];
                })->values();

            return [
                'branch' => $branch,
                'customers' => $customers,
            ];
        })->filter(fn ($a) => $a['customers']->count() > 0)->values();

        return view('sales.index', compact('areaData', 'search'));
    }

    // ──────────────────────────────────────────────────────────────────
    // SHOW — all DRs for a specific branch + customer
    // ──────────────────────────────────────────────────────────────────
    public function show(Request $request, Branch $branch, string $customerName)
    {
        $customerName = rawurldecode($customerName);

        $sales = Sale::where('branch_id', $branch->id)
            ->where('customer_name', $customerName)
            ->with(['items.finishedProduct', 'user'])
            ->orderBy('sale_date', 'desc')
            ->orderBy('dr_number', 'desc')
            ->get();

        return view('sales.show', compact('sales', 'branch', 'customerName'));
    }

    // ──────────────────────────────────────────────────────────────────
    // MARK SOLD — update quantity_sold on a single SaleItem
    // ──────────────────────────────────────────────────────────────────
    public function markSold(Request $request, SaleItem $saleItem)
    {
        $validated = $request->validate([
            'quantity_sold' => 'required|numeric|min:0',
            'quantity_bo' => 'nullable|numeric|min:0',
        ]);

        $deployed = $saleItem->quantity_deployed;
        $sold = min($validated['quantity_sold'], $deployed);
        $bo = min($validated['quantity_bo'] ?? $saleItem->quantity_bo, $deployed);
        $unsold = max(0, $deployed - $sold - $bo);

        $saleItem->quantity_sold = $sold;
        $saleItem->quantity_bo = $bo;
        $saleItem->quantity_unsold = $unsold;
        $saleItem->save();

        $sale = $saleItem->sale->fresh();

        return response()->json([
            'quantity_sold' => $saleItem->quantity_sold,
            'quantity_unsold' => $saleItem->quantity_unsold,
            'quantity_bo' => $saleItem->quantity_bo,
            'subtotal' => $saleItem->subtotal,
            'sale_total' => $sale->total_amount,
            'sale_balance' => $sale->balance,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // SOLD OUT ROW — max out one SaleItem
    // ──────────────────────────────────────────────────────────────────
    public function soldOutItem(Request $request, SaleItem $saleItem)
    {
        $saleItem->quantity_sold = $saleItem->quantity_deployed;
        $saleItem->quantity_unsold = 0;
        $saleItem->save();

        if ($request->expectsJson()) {
            return response()->json([
                'quantity_sold' => $saleItem->quantity_sold,
                'quantity_unsold' => 0,
                'subtotal' => $saleItem->subtotal,
                'sale_total' => $saleItem->sale->total_amount,
                'sale_balance' => $saleItem->sale->balance,
            ]);
        }

        return back()->with('success', 'Item marked as sold out.');
    }

    // ──────────────────────────────────────────────────────────────────
    // SOLD OUT DR — max out all items in a Sale
    // ──────────────────────────────────────────────────────────────────
    public function soldOutSale(Request $request, Sale $sale)
    {
        $itemData = [];

        DB::transaction(function () use ($sale, &$itemData) {
            foreach ($sale->items as $item) {
                $item->quantity_sold = $item->quantity_deployed;
                $item->quantity_bo = 0;
                $item->quantity_unsold = 0;
                $item->save();
                $itemData[] = [
                    'id' => $item->id,
                    'quantity_sold' => $item->quantity_sold,
                    'quantity_unsold' => 0,
                    'quantity_bo' => 0,
                    'subtotal' => $item->subtotal,
                ];
            }
        });

        $sale->refresh();

        return response()->json([
            'sale_total' => $sale->total_amount,
            'sale_balance' => $sale->balance,
            'items' => $itemData,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // PAYMENT PAGE — dedicated page showing DR summary + payment form
    // ──────────────────────────────────────────────────────────────────
    public function paymentPage(Sale $sale)
    {
        $sale->load(['items.finishedProduct', 'branch', 'user']);

        return view('sales.payment', compact('sale'));
    }

    // ──────────────────────────────────────────────────────────────────
    // UPDATE PAYMENT — record payment against a Sale
    // ──────────────────────────────────────────────────────────────────
    public function updatePayment(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'payment_status_override' => 'nullable|in:paid,partial,to_be_collected',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'amount_paid' => $validated['amount_paid'],
            'payment_mode' => $validated['payment_mode'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? $sale->notes,
        ];

        // Allow manual override of payment status
        if (! empty($validated['payment_status_override'])) {
            $data['payment_status'] = $validated['payment_status_override'];
            // Bypass the auto-calculation in booted() by setting amount_paid to match
            if ($validated['payment_status_override'] === 'paid') {
                $data['amount_paid'] = $sale->total_amount;
            }
        }

        $sale->update($data);

        return redirect()->route('sales.paymentPage', $sale)
            ->with('success', 'Payment saved for DR# '.$sale->dr_number);
    }

    // ──────────────────────────────────────────────────────────────────
    // DR DETAIL — single DR edit page (qty sold + payment)
    // ──────────────────────────────────────────────────────────────────
    public function drDetail(Sale $sale)
    {
        $sale->load(['items.finishedProduct', 'branch', 'user']);
        $recordHistories = Schema::hasTable('sale_record_histories')
            ? $sale->recordHistories()->with('user')->limit(25)->get()
            : collect();

        return view('sales.dr', compact('sale', 'recordHistories'));
    }

    // ──────────────────────────────────────────────────────────────────
    // DR UPDATE — save qty sold, BO, and payment in one shot
    // ──────────────────────────────────────────────────────────────────
    public function drUpdate(Request $request, Sale $sale)
    {
        $status = $request->input('payment_status_override', 'to_be_collected');
        $isCollecting = in_array($status, ['paid', 'partial']);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:sale_items,id',
            'items.*.quantity_sold' => 'required|numeric|min:0',
            'items.*.quantity_bo' => 'nullable|numeric|min:0',
            'amount_paid' => ($isCollecting ? 'required' : 'nullable').'|numeric|min:0',
            'payment_mode' => $isCollecting ? 'required|in:cash,gcash,cheque,bank_transfer,other' : 'nullable|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => $isCollecting ? 'required|date' : 'nullable|date',
            'payment_status_override' => 'nullable|in:paid,partial,to_be_collected',
            'less_amount' => 'nullable|numeric|min:0',
            'less_notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $sale, $status) {
            foreach ($validated['items'] as $itemData) {
                // Scope to this sale to prevent cross-sale item manipulation (IDOR)
                $item = $sale->items()->findOrFail($itemData['id']);
                $deployed = $item->quantity_deployed;
                $sold = min($itemData['quantity_sold'], $deployed);
                $bo = min($itemData['quantity_bo'] ?? 0, $deployed);
                $unsold = max(0, $deployed - $sold - $bo);

                $item->quantity_sold = $sold;
                $item->quantity_bo = $bo;
                $item->quantity_unsold = $unsold;
                $item->subtotal = $sold * $item->unit_price;
                $item->save();
            }

            $sale->refresh();

            $lessAmount = max(0, (float) ($validated['less_amount'] ?? 0));
            $sale->less_amount = $lessAmount;
            $sale->less_notes = $validated['less_notes'] ?? null;

            if ($status === 'to_be_collected') {
                $sale->payment_status = 'to_be_collected';
                $sale->amount_paid = 0;
                $sale->balance = max(0, $sale->total_amount - $lessAmount);
                $sale->saveQuietly();
            } elseif ($status === 'paid') {
                $sale->payment_status = 'paid';
                $sale->amount_paid = max(0, $sale->total_amount - $lessAmount);
                $sale->balance = 0;
                $sale->payment_mode = $validated['payment_mode'] ?? $sale->payment_mode;
                $sale->payment_reference = $validated['payment_reference'] ?? $sale->payment_reference;
                $sale->payment_date = $validated['payment_date'] ?? $sale->payment_date;
                $sale->notes = $validated['notes'] ?? $sale->notes;
                $sale->saveQuietly();
            } else {
                // partial — but auto-upgrade to paid if amount_paid covers total
                $amountPaid = $validated['amount_paid'] ?? 0;
                $balance = max(0, $sale->total_amount - $lessAmount) - $amountPaid;
                $sale->amount_paid = $amountPaid;
                $sale->balance = max(0, $balance);
                $sale->payment_status = $balance <= 0 ? 'paid' : 'partial';
                $sale->payment_mode = $validated['payment_mode'] ?? $sale->payment_mode;
                $sale->payment_reference = $validated['payment_reference'] ?? $sale->payment_reference;
                $sale->payment_date = $validated['payment_date'] ?? $sale->payment_date;
                $sale->notes = $validated['notes'] ?? $sale->notes;
                $sale->saveQuietly();
            }
        });

        if (Schema::hasTable('sale_record_histories')) {
            $sale->refresh();
            $sale->load(['items.finishedProduct']);
            $lines = $sale->items->map(fn ($i) => [
                'product' => $i->finishedProduct->name ?? '—',
                'deployed' => (float) $i->quantity_deployed,
                'sold' => (float) $i->quantity_sold,
                'unsold' => (float) $i->quantity_unsold,
                'bo' => (float) $i->quantity_bo,
                'collectible' => (float) $i->subtotal,
            ])->values()->all();

            SaleRecordHistory::create([
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'lines' => $lines,
                'total_amount' => $sale->total_amount,
                'payment_status_snapshot' => $sale->payment_status,
            ]);
        }

        return redirect()->route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)])
            ->with('success', 'DR# '.$sale->dr_number.' has been updated.');
    }

    // ──────────────────────────────────────────────────────────────────
    // DESTROY DR — remove sale + lines; reverse warehouse / area / batches
    // ──────────────────────────────────────────────────────────────────
    public function destroy(Request $request, Sale $sale)
    {
        $redirect = fn () => redirect()->route('sales.show', [
            $sale->branch_id,
            rawurlencode($sale->customer_name),
        ]);

        $sale->load('items');

        $branchId = $sale->branch_id;
        $customerEnc = rawurlencode($sale->customer_name);
        $saleId = $sale->id;
        $drNumber = $sale->dr_number;
        $customerName = $sale->customer_name;
        $allowOrphanDelete = $request->boolean('orphan_delete');

        try {
            DB::beginTransaction();

            $sale = Sale::whereKey($saleId)->lockForUpdate()->firstOrFail();
            $sale->load('items');

            $qtySoldBeforeDelete = (float) $sale->items->sum('quantity_sold');
            $qtyBoBeforeDelete = (float) $sale->items->sum('quantity_bo');

            $movements = $this->lockDeliveryMovementsForSaleDestroy($sale);

            $totalDeployed = (float) $sale->items->sum('quantity_deployed');
            if ($movements->isEmpty() && $totalDeployed > 0.02 && ! $allowOrphanDelete) {
                throw new \RuntimeException(
                    'Could not match a warehouse delivery to this DR to undo stock. The app looks for movements with this DR# and area, first with your customer name in the notes (Customer: …), then by matching transfer quantities to these product lines. None of that matched, so inventory was not changed and the delete was cancelled. '
                    .'Check that delivery movements still use the same DR number and area, and that notes include the correct customer name. If the customer was renamed on the sale, re-deliver or adjust stock movements—or contact support. '
                    .'For a test DR that never had a real delivery (or data is out of sync), check “Test/orphan DR” on the delete form and submit again to remove only this sales record without changing warehouse stock.'
                );
            }

            if ($movements->isNotEmpty()) {
                foreach ($sale->items as $item) {
                    $mQty = (float) $movements
                        ->where('finished_product_id', $item->finished_product_id)
                        ->where('movement_type', 'transfer_out')
                        ->sum('quantity');
                    if (abs($mQty - (float) $item->quantity_deployed) > 0.02) {
                        throw new \RuntimeException(
                            'Warehouse delivery totals do not match this DR in the system. Do not delete automatically — contact support.'
                        );
                    }
                }

                app(DeliveryBatchReversalService::class)->revertStockBranchAndBatches($movements, (int) $branchId);
                StockMovement::whereIn('id', $movements->pluck('id'))->delete();
            }

            $clearedPayment = (float) $sale->amount_paid;

            $sale->delete();

            DB::commit();

            $orphanDeleted = $movements->isEmpty() && $totalDeployed > 0.02 && $allowOrphanDelete;

            Log::info('Sale DR deleted', [
                'sale_id' => $saleId,
                'dr_number' => $drNumber,
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
                'amount_paid_cleared' => $clearedPayment,
                'quantity_sold_cleared' => $qtySoldBeforeDelete,
                'quantity_bo_cleared' => $qtyBoBeforeDelete,
                'orphan_delete' => $orphanDeleted,
            ]);

            $msg = 'DR# '.$drNumber.' was removed.';
            if ($movements->isNotEmpty()) {
                $msg .= ' Warehouse and area stock were restored where delivery records matched.';
            } elseif ($orphanDeleted) {
                $msg .= ' Test/orphan delete: no matching delivery was found, so warehouse and area inventory were not changed. Use this only if this DR never reflected a real transfer.';
            }
            if ($qtySoldBeforeDelete > 0.0001 || $qtyBoBeforeDelete > 0.0001) {
                $msg .= ' Recorded sold / BO quantities on this DR were discarded with the delete.';
            }
            if ($clearedPayment > 0.0001) {
                $msg .= ' Recorded payment of ₱'.number_format($clearedPayment, 2).' on this DR was cleared (the DR no longer exists in the system).';
            }
            $stillHas = Sale::where('branch_id', $branchId)
                ->where('customer_name', $customerName)
                ->exists();

            if ($stillHas) {
                return redirect()->route('sales.show', [$branchId, $customerEnc])->with('success', $msg);
            }

            return redirect()->route('sales.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale destroy failed', ['sale_id' => $saleId, 'message' => $e->getMessage()]);

            return redirect()->route('sales.show', [$branchId, $customerEnc])
                ->with('error', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // API: Get distinct customers for a branch
    // ──────────────────────────────────────────────────────────────────
    public function getCustomers(Branch $branch)
    {
        $fromBranch = BranchCustomer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');

        $fromSales = Sale::query()
            ->where('branch_id', $branch->id)
            ->distinct()
            ->pluck('customer_name');

        $customers = $fromBranch
            ->merge($fromSales)
            ->unique()
            ->sort()
            ->values();

        return response()->json(['customers' => $customers]);
    }

    // ──────────────────────────────────────────────────────────────────
    // API: Get DR numbers for a branch (with product count per DR)
    // ──────────────────────────────────────────────────────────────────
    public function getDRNumbers(Branch $branch)
    {
        $drNumbers = Sale::where('branch_id', $branch->id)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->select('sales.dr_number', DB::raw('COUNT(sale_items.id) as product_count'))
            ->groupBy('sales.dr_number')
            ->orderBy('sales.dr_number', 'desc')
            ->get()
            ->map(fn ($row) => [
                'dr_number' => $row->dr_number,
                'product_count' => (int) $row->product_count,
            ]);

        return response()->json(['dr_numbers' => $drNumbers]);
    }

    // ──────────────────────────────────────────────────────────────────
    // API: Get products from a DR delivery with remaining quantities
    // ──────────────────────────────────────────────────────────────────
    public function getDRProducts(Branch $branch, string $drNumber)
    {
        $drNumber = rawurldecode($drNumber);

        $sale = Sale::where('branch_id', $branch->id)
            ->where('dr_number', $drNumber)
            ->with(['items.finishedProduct'])
            ->first();

        if (! $sale) {
            return response()->json([
                'products' => [],
                'has_previous_sales' => false,
                'previous_sales_count' => 0,
            ]);
        }

        $previousSalesCount = Sale::where('branch_id', $branch->id)
            ->where('dr_number', $drNumber)
            ->count();

        $products = $sale->items->map(function ($item) {
            $remainingQty = max(0,
                (float) $item->quantity_deployed
                - (float) $item->quantity_sold
                - (float) $item->quantity_bo
            );

            return [
                'finished_product_id' => $item->finished_product_id,
                'product_name' => optional($item->finishedProduct)->name ?? 'Unknown',
                'sku' => optional($item->finishedProduct)->sku,
                'batch_number' => $item->batch_number,
                'deployed_qty' => (float) $item->quantity_deployed,
                'already_sold' => (float) $item->quantity_sold,
                'remaining_qty' => $remainingQty,
                'selling_price' => (float) $item->unit_price,
            ];
        });

        return response()->json([
            'products' => $products,
            'has_previous_sales' => $previousSalesCount > 0,
            'previous_sales_count' => $previousSalesCount,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // API: Get products deployed to a branch for a specific customer
    // ──────────────────────────────────────────────────────────────────
    public function getProducts(Branch $branch, string $customerName)
    {
        $customerName = rawurldecode($customerName);

        $products = Sale::where('branch_id', $branch->id)
            ->where('customer_name', $customerName)
            ->with('items.finishedProduct')
            ->get()
            ->flatMap(fn ($sale) => $sale->items)
            ->map(fn ($item) => [
                'finished_product_id' => $item->finished_product_id,
                'product_name' => optional($item->finishedProduct)->name ?? 'Unknown',
                'sku' => optional($item->finishedProduct)->sku,
            ])
            ->unique('finished_product_id')
            ->values();

        return response()->json(['products' => $products]);
    }

    // ──────────────────────────────────────────────────────────────────
    // API: Check whether a DR number already has a sale record
    // ──────────────────────────────────────────────────────────────────
    public function checkDrNumber(Branch $branch, string $customerName, string $drNumber)
    {
        $customerName = rawurldecode($customerName);
        $drNumber = rawurldecode($drNumber);

        $salesCount = Sale::where('branch_id', $branch->id)
            ->where('customer_name', $customerName)
            ->where('dr_number', $drNumber)
            ->count();

        return response()->json([
            'exists' => $salesCount > 0,
            'sales_count' => $salesCount,
        ]);
    }

    /**
     * Lock transfer_out / extra_free movements for this DR and area.
     * Prefers rows whose notes include "Customer: {name}"; if none, accepts rows whose
     * transfer_out quantities match sale line deployments (same products and totals).
     *
     * @return Collection<int, StockMovement>
     */
    private function lockDeliveryMovementsForSaleDestroy(Sale $sale): Collection
    {
        $drNumber = trim((string) $sale->dr_number);
        $branchId = (int) $sale->branch_id;
        $customerName = trim((string) $sale->customer_name);
        $escaped = addcslashes($customerName, '%_\\');

        $strict = StockMovement::query()
            ->where('reference_number', $drNumber)
            ->where('branch_id', $branchId)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->where('notes', 'like', '%Customer: '.$escaped.'%')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        if ($strict->isNotEmpty()) {
            return $strict;
        }

        $candidates = StockMovement::query()
            ->where('reference_number', $drNumber)
            ->where('branch_id', $branchId)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty() || ! $this->deliveryTransferOutsMatchSaleItems($candidates, $sale->items)) {
            return collect();
        }

        Log::info('Sale DR delete matched movements without customer-in-notes filter', [
            'sale_id' => $sale->id,
            'dr_number' => $drNumber,
            'branch_id' => $branchId,
        ]);

        return $candidates;
    }

    /**
     * True when transfer_out lines (grouped by product) match sale line quantity_deployed exactly.
     */
    private function deliveryTransferOutsMatchSaleItems(Collection $movements, Collection $saleItems): bool
    {
        $transferSums = [];
        foreach ($movements->where('movement_type', 'transfer_out') as $m) {
            $pid = (int) $m->finished_product_id;
            $transferSums[$pid] = ($transferSums[$pid] ?? 0) + (float) $m->quantity;
        }

        $deploySums = [];
        foreach ($saleItems as $item) {
            $pid = (int) $item->finished_product_id;
            $deploySums[$pid] = ($deploySums[$pid] ?? 0) + (float) $item->quantity_deployed;
        }

        $pidsTransfer = array_keys(array_filter($transferSums, fn ($q) => $q > 0.0001));
        $pidsDeploy = array_keys(array_filter($deploySums, fn ($q) => $q > 0.0001));
        sort($pidsTransfer);
        sort($pidsDeploy);

        if ($pidsTransfer !== $pidsDeploy) {
            return false;
        }

        foreach ($pidsTransfer as $pid) {
            if (abs($transferSums[$pid] - $deploySums[$pid]) > 0.02) {
                return false;
            }
        }

        return true;
    }
}
