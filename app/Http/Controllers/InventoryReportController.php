<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinishedProduct;
use App\Models\ProductionMix;
use App\Models\RawMaterial;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function index(Request $request)
    {
        // ── Date range resolution ──────────────────────────────────────
        $quick = $request->get('quick');
        $today = Carbon::today();

        if ($quick === 'today') {
            $from = $today->copy()->startOfDay();
            $to = $today->copy()->endOfDay();
        } elseif ($quick === 'week') {
            $from = $today->copy()->startOfWeek();
            $to = $today->copy()->endOfWeek();
        } elseif ($quick === 'month') {
            $from = $today->copy()->startOfMonth();
            $to = $today->copy()->endOfMonth();
        } else {
            $from = $request->get('from')
                ? Carbon::parse($request->get('from'))->startOfDay()
                : $today->copy()->startOfMonth();
            $to = $request->get('to')
                ? Carbon::parse($request->get('to'))->endOfDay()
                : $today->copy()->endOfDay();
        }

        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        // ── 1. Warehouse Stock (Finished Products) ─────────────────────
        $warehouseStock = FinishedProduct::orderBy('name')->get()->map(function ($fp) {
            $stock = $fp->stock_quantity ?? 0;
            $cost = $fp->cost_per_unit ?? $fp->avg_cost ?? 0;
            $price = $fp->selling_price ?? 0;

            return [
                'id' => $fp->id,
                'name' => $fp->name,
                'sku' => $fp->sku ?? '—',
                'stock' => $stock,
                'unit' => $fp->unit ?? 'pcs',
                'low_stock' => $stock <= ($fp->low_stock_threshold ?? 10),
                'price' => $price,
                'avg_cost' => $cost,
                'total_value' => $stock * $cost,
                'threshold' => $fp->low_stock_threshold ?? 10,
            ];
        });

        // ── 2. Raw Materials ───────────────────────────────────────────
        $rawMaterials = RawMaterial::orderBy('name')->get()->map(function ($rm) {
            $stock = $rm->quantity ?? $rm->stock_quantity ?? 0;
            $cost = $rm->cost_per_unit ?? 0;
            $threshold = $rm->low_stock_threshold ?? $rm->reorder_level ?? 5;

            return [
                'id' => $rm->id,
                'name' => $rm->name,
                'stock' => $stock,
                'unit' => $rm->unit ?? '—',
                'low_stock' => $stock <= $threshold,
                'cost' => $cost,
                'total_value' => $stock * $cost,
                'threshold' => $threshold,
            ];
        });

        // ── 3. Production Mix History ──────────────────────────────────
        $productionMixes = ProductionMix::with(['finishedProduct', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($mix) {
                $produced = $mix->actual_output ?? $mix->quantity_produced ?? 0;
                $rejected = $mix->rejected_quantity ?? 0;
                $goodOutput = max(0, $produced - $rejected);

                return [
                    'id' => $mix->id,
                    'batch_number' => $mix->batch_number ?? '—',
                    'product' => $mix->finishedProduct->name ?? '—',
                    'qty_produced' => $produced,
                    'qty_rejected' => $rejected,
                    'good_output' => $goodOutput,
                    'expiry' => $mix->expiration_date ? \Carbon\Carbon::parse($mix->expiration_date)->format('M d, Y') : '—',
                    'date' => Carbon::parse($mix->created_at)->format('M d, Y'),
                    'by' => $mix->user->name ?? '—',
                    'notes' => $mix->notes ?? '',
                ];
            });

        // ── 4. Branch/Area Stock — per customer ────────────────────────
        $branches = Branch::orderBy('name')->get();

        $branchStock = $branches->map(function ($branch) use ($from, $to) {
            // Get all sales items in range for this branch
            $sales = Sale::where('branch_id', $branch->id)
                ->whereBetween('sale_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
                ->with(['items.finishedProduct'])
                ->get();

            if ($sales->isEmpty()) {
                return null;
            }

            $customers = $sales->groupBy('customer_name')->map(function ($custSales, $customerName) {
                $items = $custSales->flatMap(function ($sale) {
                    return $sale->items->map(function ($item) use ($sale) {
                        return [
                            'dr_number' => $sale->dr_number,
                            'product' => $item->finishedProduct->name ?? '—',
                            'qty_deployed' => $item->quantity_deployed,
                            'qty_sold' => $item->quantity_sold,
                            'qty_unsold' => $item->quantity_unsold,
                            'qty_bo' => $item->quantity_bo,
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                            'payment_status' => $sale->payment_status,
                            'sale_date' => $sale->sale_date->format('M d, Y'),
                        ];
                    });
                });

                return [
                    'customer_name' => $customerName,
                    'dr_count' => $custSales->count(),
                    'total_deployed' => $items->sum('qty_deployed'),
                    'total_sold' => $items->sum('qty_sold'),
                    'total_unsold' => $items->sum('qty_unsold'),
                    'total_bo' => $items->sum('qty_bo'),
                    'total_value' => $items->sum('subtotal'),
                    'items' => $items->values(),
                ];
            })->values();

            return [
                'branch' => $branch,
                'customers' => $customers,
            ];
        })->filter()->values();

        return view('reports.inventory', compact(
            'warehouseStock', 'rawMaterials', 'productionMixes',
            'branchStock', 'fromStr', 'toStr', 'quick'
        ));
    }
}
