<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinishedProduct;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        // ── Period resolution ──────────────────────────────────────────
        $period = $request->get('period', 'daily');
        $today = Carbon::today();

        if ($period === 'weekly') {
            $from = $request->get('from')
                ? Carbon::parse($request->get('from'))->startOfDay()
                : $today->copy()->startOfWeek();
            $to = $request->get('to')
                ? Carbon::parse($request->get('to'))->endOfDay()
                : $today->copy()->endOfWeek();
        } elseif ($period === 'quarterly') {
            $from = $request->get('from')
                ? Carbon::parse($request->get('from'))->startOfDay()
                : $today->copy()->firstOfQuarter()->startOfDay();
            $to = $request->get('to')
                ? Carbon::parse($request->get('to'))->endOfDay()
                : $today->copy()->lastOfQuarter()->endOfDay();
        } else {
            // daily (default)
            $from = $request->get('from')
                ? Carbon::parse($request->get('from'))->startOfDay()
                : $today->copy()->startOfDay();
            $to = $request->get('to')
                ? Carbon::parse($request->get('to'))->endOfDay()
                : $today->copy()->endOfDay();
        }

        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        // ── Filters ────────────────────────────────────────────────────
        $areaFilter = $request->get('area');
        $statusFilter = $request->get('status');

        // ── All finished products (columns) ───────────────────────────
        $products = FinishedProduct::orderBy('name')->get();

        // ── Sales query ───────────────────────────────────────────────
        $query = Sale::with(['items.finishedProduct', 'branch', 'user'])
            ->where('status', '!=', 'cancelled')
            ->whereBetween('sale_date', [$fromStr, $toStr])
            ->orderBy('sale_date')
            ->orderBy('dr_number');

        if ($areaFilter) {
            $query->whereHas('branch', function ($q) use ($areaFilter) {
                $q->where('name', $areaFilter);
            });
        }
        if ($statusFilter) {
            $query->where('payment_status', $statusFilter);
        }

        $sales = $query->get();

        // ── Build rows ────────────────────────────────────────────────
        // Each row = one Sale, with qty_sold per product as dynamic columns
        $rows = $sales->map(function ($sale) use ($products) {
            $productQtys = [];
            foreach ($products as $fp) {
                $item = $sale->items->firstWhere('finished_product_id', $fp->id);
                $productQtys[$fp->id] = $item ? $item->quantity_sold : null;
            }

            // Sub total per day grouping key
            $less = (float) ($sale->less_amount ?? 0);
            $collectible = max(0, (float) $sale->total_amount - $less);

            return [
                'id' => $sale->id,
                'date' => $sale->sale_date instanceof \Carbon\Carbon
                                    ? $sale->sale_date->format('m/d/Y')
                                    : \Carbon\Carbon::parse($sale->sale_date)->format('m/d/Y'),
                'date_raw' => $sale->sale_date,
                'dr_number' => $sale->dr_number,
                'customer_name' => $sale->customer_name,
                'total_amount' => $sale->total_amount,
                'less_amount' => $less,
                'collectible' => $collectible,
                'amount_paid' => (float) $sale->amount_paid,
                'balance' => (float) $sale->balance,
                'due_date' => $sale->due_date
                    ? (\Carbon\Carbon::parse($sale->due_date)->format('m/d/Y'))
                    : '',
                'payment_period' => $sale->payment_period ?? 'one_time',
                'area' => $sale->branch->name ?? '—',
                'status' => $sale->payment_status,
                'delivered_by' => $sale->user->name ?? '—',
                'payment_mode' => $sale->payment_mode ?? '—',
                'gcash_ref' => $sale->payment_mode === 'gcash' ? ($sale->payment_reference ?? '') : '',
                'notes' => $sale->notes ?? '',
                'products' => $productQtys,
                'total_items' => $sale->items->sum('quantity_sold'),
            ];
        });

        // ── Sub totals per date ───────────────────────────────────────
        $subTotals = $rows->groupBy('date')->map(function ($group) {
            return $group->sum('total_amount');
        });

        // ── Grand totals ──────────────────────────────────────────────
        $grandTotal = $sales->sum('total_amount');
        $grandLess = $sales->sum(fn ($s) => (float) ($s->less_amount ?? 0));
        $grandPaid = $sales->sum('amount_paid');
        $grandBalance = $sales->sum('balance');
        $grandTotalItems = $rows->sum('total_items');
        $productTotals = [];
        foreach ($products as $fp) {
            $productTotals[$fp->id] = $rows->sum(function ($row) use ($fp) {
                return $row['products'][$fp->id] ?? 0;
            });
        }

        // ── Areas for filter dropdown ─────────────────────────────────
        $areas = Branch::orderBy('name')->pluck('name');

        return view('reports.sales', compact(
            'rows', 'products', 'subTotals', 'grandTotal',
            'grandLess', 'grandPaid', 'grandBalance',
            'grandTotalItems', 'productTotals',
            'fromStr', 'toStr', 'period',
            'areas', 'areaFilter', 'statusFilter'
        ));
    }
}
