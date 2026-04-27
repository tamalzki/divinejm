<?php

namespace App\Http\Controllers;

use App\Models\DailyProductionReport;
use App\Models\DailyProductionEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyProductionReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->get('end_date',   Carbon::now()->format('Y-m-d'));

        $reports = DailyProductionReport::with(['entries.finishedProduct'])
            ->whereBetween('production_date', [$startDate, $endDate])
            ->orderBy('production_date', 'desc')
            ->get();

        // All entries in range
        $allEntries = $reports->flatMap->entries;

        // Summary tiles
        $totals = [
            'reports'       => $reports->count(),
            'actual_yield'  => $allEntries->sum('actual_yield'),
            'packed'        => $allEntries->sum('packed_quantity'),
            'unpacked'      => $allEntries->sum('unpacked'),
            'rejects'       => $allEntries->sum('rejects'),
            'number_of_mix' => $allEntries->sum('number_of_mix'),
        ];

        // By product summary
        $byProduct = $allEntries
            ->groupBy('finished_product_id')
            ->map(fn ($entries) => [
                'product_name'  => optional($entries->first()->finishedProduct)->name ?? '—',
                'actual_yield'  => $entries->sum('actual_yield'),
                'packed'        => $entries->sum('packed_quantity'),
                'unpacked'      => $entries->sum('unpacked'),
                'rejects'       => $entries->sum('rejects'),
                'number_of_mix' => $entries->sum('number_of_mix'),
            ])
            ->sortByDesc('actual_yield')
            ->values();

        return view('reports.daily-production', compact(
            'startDate', 'endDate',
            'reports', 'byProduct', 'totals'
        ));
    }
}
