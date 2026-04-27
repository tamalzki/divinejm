<?php

namespace App\Http\Controllers;

use App\Models\PackerPack;
use App\Models\PackerReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->format('Y-m-d'));

        // All packer reports in range, with their packs + products
        $reports = PackerReport::with(['packs.finishedProduct', 'user'])
            ->whereBetween('pack_date', [$startDate, $endDate])
            ->orderBy('pack_date', 'desc')
            ->get();

        // All packs in range
        $packsInRange = PackerPack::with('finishedProduct')
            ->whereHas('report', fn ($q) => $q->whereBetween('pack_date', [$startDate, $endDate]))
            ->get();

        // Summary by packer name
        $byPacker = $packsInRange
            ->groupBy('packer_name')
            ->map(fn ($packs, $name) => [
                'packer_name'  => $name ?: '—',
                'total_packs'  => $packs->sum('quantity'),
                'product_count' => $packs->pluck('finished_product_id')->unique()->count(),
                'session_count' => $packs->pluck('packer_report_id')->unique()->count(),
            ])
            ->sortByDesc('total_packs')
            ->values();

        // Summary by product
        $byProduct = $packsInRange
            ->groupBy('finished_product_id')
            ->map(fn ($packs) => [
                'product_name' => optional($packs->first()->finishedProduct)->name ?? '—',
                'total_packed' => $packs->sum('quantity'),
                'packer_count' => $packs->pluck('packer_name')->unique()->count(),
            ])
            ->sortByDesc('total_packed')
            ->values();

        $totals = [
            'total_packed'   => $packsInRange->sum('quantity'),
            'total_packers'  => $packsInRange->pluck('packer_name')->unique()->filter()->count(),
            'total_sessions' => $reports->count(),
            'total_products' => $packsInRange->pluck('finished_product_id')->unique()->count(),
        ];

        return view('reports.performance', compact(
            'startDate', 'endDate',
            'reports', 'byPacker', 'byProduct', 'totals'
        ));
    }
}
