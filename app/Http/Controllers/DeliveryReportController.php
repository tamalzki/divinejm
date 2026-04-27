<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate  = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate    = $request->get('end_date',   Carbon::now()->format('Y-m-d'));
        $branchId   = $request->get('branch_id', '');
        $search     = $request->get('search', '');

        $branches = Branch::orderBy('name')->get();

        // Transfer-out movements = deliveries to branches
        $movements = StockMovement::with(['finishedProduct', 'branch'])
            ->where('movement_type', 'transfer_out')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('reference_number', 'like', "%{$search}%")
                   ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderBy('movement_date', 'desc')
            ->orderBy('reference_number')
            ->get();

        // Group by DR number for a compact view
        $byDr = $movements
            ->groupBy('reference_number')
            ->map(fn ($items) => [
                'reference_number' => $items->first()->reference_number ?? '—',
                'branch'           => optional($items->first()->branch)->name ?? '—',
                'movement_date'    => $items->first()->movement_date,
                'items'            => $items,
                'total_qty'        => $items->sum('quantity'),
                'customer_note'    => $items->first()->notes,
            ])
            ->sortByDesc(fn ($dr) => $dr['movement_date'])
            ->values();

        // Summary tiles
        $totals = [
            'dr_count'      => $byDr->count(),
            'total_qty'     => $movements->sum('quantity'),
            'branch_count'  => $movements->pluck('branch_id')->unique()->filter()->count(),
            'product_count' => $movements->pluck('finished_product_id')->unique()->filter()->count(),
        ];

        // By branch summary
        $byBranch = $movements
            ->groupBy('branch_id')
            ->map(fn ($items) => [
                'branch_name' => optional($items->first()->branch)->name ?? '—',
                'dr_count'    => $items->pluck('reference_number')->unique()->count(),
                'total_qty'   => $items->sum('quantity'),
            ])
            ->sortByDesc('total_qty')
            ->values();

        return view('reports.delivery', compact(
            'startDate', 'endDate', 'branchId', 'search',
            'branches', 'byDr', 'byBranch', 'totals', 'movements'
        ));
    }
}
