<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinishedProduct;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryReportController extends Controller
{
    protected const PER_PAGE = 15;

    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $branchId = $request->get('branch_id', '');
        $search = $request->get('search', '');

        $branches = Branch::orderBy('name')->get();

        // Summary tiles — aggregated in SQL over the whole filtered range, not just the current page
        $totals = [
            'dr_count' => (int) $this->filteredMovements($startDate, $endDate, $branchId, $search)->distinct()->count('reference_number'),
            'total_qty' => (float) $this->filteredMovements($startDate, $endDate, $branchId, $search)->sum('quantity'),
            'branch_count' => (int) $this->filteredMovements($startDate, $endDate, $branchId, $search)->whereNotNull('branch_id')->distinct()->count('branch_id'),
            'product_count' => (int) $this->filteredMovements($startDate, $endDate, $branchId, $search)->whereNotNull('finished_product_id')->distinct()->count('finished_product_id'),
        ];

        // By branch summary
        $byBranch = $this->filteredMovements($startDate, $endDate, $branchId, $search)
            ->selectRaw('branch_id, SUM(quantity) as total_qty, COUNT(DISTINCT reference_number) as dr_count')
            ->groupBy('branch_id')
            ->get()
            ->map(fn ($row) => [
                'branch_name' => optional($branches->firstWhere('id', $row->branch_id))->name ?? '—',
                'dr_count' => (int) $row->dr_count,
                'total_qty' => (float) $row->total_qty,
            ])
            ->sortByDesc('total_qty')
            ->values();

        // Sold (delivered) per product summary
        $productRows = $this->filteredMovements($startDate, $endDate, $branchId, $search)
            ->whereNotNull('finished_product_id')
            ->selectRaw('finished_product_id, SUM(quantity) as total_qty, COUNT(DISTINCT reference_number) as dr_count')
            ->groupBy('finished_product_id')
            ->get();

        $productNames = FinishedProduct::whereIn('id', $productRows->pluck('finished_product_id'))->pluck('name', 'id');

        $byProduct = $productRows
            ->map(fn ($row) => [
                'product_name' => $productNames[$row->finished_product_id] ?? '—',
                'dr_count' => (int) $row->dr_count,
                'total_qty' => (float) $row->total_qty,
            ])
            ->sortByDesc('total_qty')
            ->values();

        // DR list — paginate over distinct reference numbers, then load line items only for the current page
        $drPage = $this->filteredMovements($startDate, $endDate, $branchId, $search)
            ->selectRaw('reference_number, MAX(movement_date) as movement_date')
            ->groupBy('reference_number')
            ->orderByDesc('movement_date')
            ->orderByDesc('reference_number')
            ->paginate(self::PER_PAGE, ['*'], 'page')
            ->withQueryString();

        $items = StockMovement::with(['finishedProduct', 'branch'])
            ->whereIn('reference_number', $drPage->pluck('reference_number'))
            ->where('movement_type', 'transfer_out')
            ->get();

        $byDr = $items
            ->groupBy('reference_number')
            ->map(fn ($rows) => [
                'reference_number' => $rows->first()->reference_number ?? '—',
                'branch' => optional($rows->first()->branch)->name ?? '—',
                'movement_date' => $rows->first()->movement_date,
                'items' => $rows,
                'total_qty' => $rows->sum('quantity'),
                'customer_note' => $rows->first()->notes,
            ])
            ->sortByDesc(fn ($dr) => $dr['movement_date']);

        // Preserve the paginator's DR ordering (the group-by above loses it)
        $byDr = $drPage->pluck('reference_number')
            ->map(fn ($ref) => $byDr->get($ref))
            ->filter()
            ->values();

        return view('reports.delivery', compact(
            'startDate', 'endDate', 'branchId', 'search',
            'branches', 'byDr', 'byBranch', 'byProduct', 'totals', 'drPage'
        ));
    }

    /**
     * Transfer-out movements filtered by the report's date range / branch / search — a fresh
     * query builder each call so it can be aggregated multiple ways without re-fetching rows.
     */
    protected function filteredMovements(string $startDate, string $endDate, $branchId, string $search)
    {
        return StockMovement::query()
            ->where('movement_type', 'transfer_out')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            }));
    }
}
