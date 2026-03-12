<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                    $paidCount         = $sales->where('payment_status', 'paid')->count();

                    if ($uncollectedCount > 0 && $paidCount === 0) {
                        $overallStatus = 'to_be_collected';
                    } elseif ($uncollectedCount > 0) {
                        $overallStatus = 'partial';
                    } else {
                        $overallStatus = 'paid';
                    }

                    return [
                        'customer_name'   => $customerName,
                        'branch_id'       => $branch->id,
                        'dr_count'        => $sales->count(),
                        'total_deployed'  => $totalDeployed,
                        'total_sold'      => $totalSold,
                        'total_balance'   => $totalBalance,
                        'has_uncollected' => $uncollectedCount > 0,
                        'overall_status'  => $overallStatus,
                    ];
                })->values();

            return [
                'branch'    => $branch,
                'customers' => $customers,
            ];
        })->filter(fn($a) => $a['customers']->count() > 0)->values();

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

        abort_if($sales->isEmpty(), 404);

        return view('sales.show', compact('sales', 'branch', 'customerName'));
    }

    // ──────────────────────────────────────────────────────────────────
    // MARK SOLD — update quantity_sold on a single SaleItem
    // ──────────────────────────────────────────────────────────────────
    public function markSold(Request $request, SaleItem $saleItem)
    {
        $validated = $request->validate([
            'quantity_sold' => 'required|numeric|min:0',
            'quantity_bo'   => 'nullable|numeric|min:0',
        ]);

        $deployed = $saleItem->quantity_deployed;
        $sold     = min($validated['quantity_sold'], $deployed);
        $bo       = min($validated['quantity_bo'] ?? $saleItem->quantity_bo, $deployed);
        $unsold   = max(0, $deployed - $sold - $bo);

        $saleItem->quantity_sold   = $sold;
        $saleItem->quantity_bo     = $bo;
        $saleItem->quantity_unsold = $unsold;
        $saleItem->save();

        $sale = $saleItem->sale->fresh();

        return response()->json([
            'quantity_sold'   => $saleItem->quantity_sold,
            'quantity_unsold' => $saleItem->quantity_unsold,
            'quantity_bo'     => $saleItem->quantity_bo,
            'subtotal'        => $saleItem->subtotal,
            'sale_total'      => $sale->total_amount,
            'sale_balance'    => $sale->balance,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // SOLD OUT ROW — max out one SaleItem
    // ──────────────────────────────────────────────────────────────────
    public function soldOutItem(Request $request, SaleItem $saleItem)
    {
        $saleItem->quantity_sold   = $saleItem->quantity_deployed;
        $saleItem->quantity_unsold = 0;
        $saleItem->save();

        if ($request->expectsJson()) {
            return response()->json([
                'quantity_sold'   => $saleItem->quantity_sold,
                'quantity_unsold' => 0,
                'subtotal'        => $saleItem->subtotal,
                'sale_total'      => $saleItem->sale->total_amount,
                'sale_balance'    => $saleItem->sale->balance,
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
                $item->quantity_sold   = $item->quantity_deployed;
                $item->quantity_bo     = 0;
                $item->quantity_unsold = 0;
                $item->save();
                $itemData[] = [
                    'id'              => $item->id,
                    'quantity_sold'   => $item->quantity_sold,
                    'quantity_unsold' => 0,
                    'quantity_bo'     => 0,
                    'subtotal'        => $item->subtotal,
                ];
            }
        });

        $sale->refresh();

        return response()->json([
            'sale_total'   => $sale->total_amount,
            'sale_balance' => $sale->balance,
            'items'        => $itemData,
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
            'amount_paid'             => 'required|numeric|min:0',
            'payment_mode'            => 'required|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference'       => 'nullable|string|max:255',
            'payment_date'            => 'required|date',
            'payment_status_override' => 'nullable|in:paid,partial,to_be_collected',
            'notes'                   => 'nullable|string',
        ]);

        $data = [
            'amount_paid'       => $validated['amount_paid'],
            'payment_mode'      => $validated['payment_mode'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_date'      => $validated['payment_date'],
            'notes'             => $validated['notes'] ?? $sale->notes,
        ];

        // Allow manual override of payment status
        if (!empty($validated['payment_status_override'])) {
            $data['payment_status'] = $validated['payment_status_override'];
            // Bypass the auto-calculation in booted() by setting amount_paid to match
            if ($validated['payment_status_override'] === 'paid') {
                $data['amount_paid'] = $sale->total_amount;
            }
        }

        $sale->update($data);

        return redirect()->route('sales.paymentPage', $sale)
            ->with('success', 'Payment saved for DR# ' . $sale->dr_number);
    }
    // ──────────────────────────────────────────────────────────────────
    // DR DETAIL — single DR edit page (qty sold + payment)
    // ──────────────────────────────────────────────────────────────────
    public function drDetail(Sale $sale)
    {
        $sale->load(['items.finishedProduct', 'branch', 'user']);
        return view('sales.dr', compact('sale'));
    }

    // ──────────────────────────────────────────────────────────────────
    // DR UPDATE — save qty sold, BO, and payment in one shot
    // ──────────────────────────────────────────────────────────────────
    public function drUpdate(Request $request, Sale $sale)
    {
        $status = $request->input('payment_status_override', 'to_be_collected');
        $isCollecting = in_array($status, ['paid', 'partial']);

        $validated = $request->validate([
            'items'                        => 'required|array',
            'items.*.id'                   => 'required|exists:sale_items,id',
            'items.*.quantity_sold'        => 'required|numeric|min:0',
            'items.*.quantity_bo'          => 'nullable|numeric|min:0',
            'amount_paid'                  => ($isCollecting ? 'required' : 'nullable') . '|numeric|min:0',
            'payment_mode'                 => $isCollecting ? 'required|in:cash,gcash,cheque,bank_transfer,other' : 'nullable|in:cash,gcash,cheque,bank_transfer,other',
            'payment_reference'            => 'nullable|string|max:255',
            'payment_date'                 => $isCollecting ? 'required|date' : 'nullable|date',
            'payment_status_override'      => 'nullable|in:paid,partial,to_be_collected',
            'less_amount'                  => 'nullable|numeric|min:0',
            'less_notes'                   => 'nullable|string|max:500',
            'notes'                        => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $sale, $status, $isCollecting) {
            foreach ($validated['items'] as $itemData) {
                $item     = SaleItem::findOrFail($itemData['id']);
                $deployed = $item->quantity_deployed;
                $sold     = min($itemData['quantity_sold'], $deployed);
                $bo       = min($itemData['quantity_bo'] ?? 0, $deployed);
                $unsold   = max(0, $deployed - $sold - $bo);

                $item->quantity_sold   = $sold;
                $item->quantity_bo     = $bo;
                $item->quantity_unsold = $unsold;
                $item->subtotal        = $sold * $item->unit_price;
                $item->save();
            }

            $sale->refresh();

            $lessAmount = max(0, (float) ($validated['less_amount'] ?? 0));
            $sale->less_amount = $lessAmount;
            $sale->less_notes  = $validated['less_notes'] ?? null;

            if ($status === 'to_be_collected') {
                $sale->payment_status = 'to_be_collected';
                $sale->amount_paid    = 0;
                $sale->balance        = max(0, $sale->total_amount - $lessAmount);
                $sale->saveQuietly();
            } elseif ($status === 'paid') {
                $sale->payment_status    = 'paid';
                $sale->amount_paid       = max(0, $sale->total_amount - $lessAmount);
                $sale->balance           = 0;
                $sale->payment_mode      = $validated['payment_mode'] ?? $sale->payment_mode;
                $sale->payment_reference = $validated['payment_reference'] ?? $sale->payment_reference;
                $sale->payment_date      = $validated['payment_date'] ?? $sale->payment_date;
                $sale->notes             = $validated['notes'] ?? $sale->notes;
                $sale->saveQuietly();
            } else {
                // partial — but auto-upgrade to paid if amount_paid covers total
                $amountPaid = $validated['amount_paid'] ?? 0;
                $balance    = max(0, $sale->total_amount - $lessAmount) - $amountPaid;
                $sale->amount_paid       = $amountPaid;
                $sale->balance           = max(0, $balance);
                $sale->payment_status    = $balance <= 0 ? 'paid' : 'partial';
                $sale->payment_mode      = $validated['payment_mode'] ?? $sale->payment_mode;
                $sale->payment_reference = $validated['payment_reference'] ?? $sale->payment_reference;
                $sale->payment_date      = $validated['payment_date'] ?? $sale->payment_date;
                $sale->notes             = $validated['notes'] ?? $sale->notes;
                $sale->saveQuietly();
            }
        });

        return redirect()->route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)])
            ->with('success', 'DR# ' . $sale->dr_number . ' has been updated.');
    }
}