<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPriceHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RawMaterialCostingReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->format('Y-m-d'));
        $search    = $request->get('search', '');

        // All raw materials (filtered)
        $materials = RawMaterial::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        // Price change history in range
        $history = RawMaterialPriceHistory::with(['rawMaterial', 'changedBy'])
            ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->when($search, fn ($q) => $q->whereHas('rawMaterial', fn ($sq) => $sq->where('name', 'like', "%{$search}%")))
            ->orderBy('created_at', 'desc')
            ->get();

        // Group history by raw material
        $historyByMaterial = $history->groupBy('raw_material_id');

        // Materials that had price changes in range
        $changedMaterials = $materials->filter(fn ($m) => isset($historyByMaterial[$m->id]));

        // Totals
        $totals = [
            'total_materials' => $materials->count(),
            'changed_count'   => $changedMaterials->count(),
            'change_events'   => $history->count(),
            'increased'       => $history->filter(fn ($h) => $h->new_price > $h->old_price)->count(),
            'decreased'       => $history->filter(fn ($h) => $h->new_price < $h->old_price)->count(),
        ];

        return view('reports.raw-material-costing', compact(
            'startDate', 'endDate', 'search',
            'materials', 'history', 'historyByMaterial',
            'changedMaterials', 'totals'
        ));
    }
}
