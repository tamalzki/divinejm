<?php

namespace App\Http\Controllers;

use App\Models\ProductionMix;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductionReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $mixes = ProductionMix::with(['finishedProduct', 'user'])
            ->whereBetween('mix_date', [$start, $end])
            ->orderByDesc('mix_date')
            ->orderByDesc('id')
            ->get();

        $byProduct = $mixes->groupBy('finished_product_id')->map(function ($group) {
            $first = $group->first();

            return (object) [
                'finished_product_id' => $first->finished_product_id,
                'product' => $first->finishedProduct,
                'batch_count' => $group->count(),
                'sum_actual' => $group->sum('actual_output'),
                'sum_rejected' => $group->sum('rejected_quantity'),
            ];
        })->sortByDesc('sum_actual')->values();

        $totals = [
            'batches' => $mixes->count(),
            'actual_output' => $mixes->sum('actual_output'),
            'rejected' => $mixes->sum('rejected_quantity'),
            'good' => $mixes->sum(fn ($m) => max(0, (float) $m->actual_output - (float) $m->rejected_quantity)),
        ];

        return view('reports.production', compact(
            'startDate',
            'endDate',
            'mixes',
            'byProduct',
            'totals'
        ));
    }
}
