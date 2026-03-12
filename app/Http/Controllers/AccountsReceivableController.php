<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccountsReceivableController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    // INDEX — all outstanding balances grouped by area → customer
    // ──────────────────────────────────────────────────────────────────
    public function index()
    {
        $unpaidSales = Sale::whereIn('payment_status', ['to_be_collected', 'partial'])
            ->with('branch')
            ->get();

        // Summary totals for tiles
        $totalOutstanding = $unpaidSales->sum('balance');
        $agingCurrent     = $unpaidSales->filter(fn($s) => Carbon::parse($s->sale_date)->diffInDays(now()) <= 30)->sum('balance');
        $agingMid         = $unpaidSales->filter(fn($s) => Carbon::parse($s->sale_date)->diffInDays(now()) > 30 && Carbon::parse($s->sale_date)->diffInDays(now()) <= 60)->sum('balance');
        $agingOld         = $unpaidSales->filter(fn($s) => Carbon::parse($s->sale_date)->diffInDays(now()) > 60)->sum('balance');

        // Group by branch → customer
        $branchIds = $unpaidSales->pluck('branch_id')->unique();
        $branches  = Branch::whereIn('id', $branchIds)->orderBy('name')->get();

        $areaData = $branches->map(function ($branch) use ($unpaidSales) {
            $branchSales = $unpaidSales->where('branch_id', $branch->id);

            $customers = $branchSales->groupBy('customer_name')->map(function ($sales, $customerName) use ($branch) {
                $oldestDate = $sales->min('sale_date');
                $oldestDays = $oldestDate ? (int) Carbon::parse($oldestDate)->diffInDays(now()) : 0;

                return [
                    'customer_name'    => $customerName,
                    'branch_id'        => $branch->id,
                    'unpaid_dr_count'  => $sales->count(),
                    'total_billed'     => $sales->sum('total_amount'),
                    'total_paid'       => $sales->sum('amount_paid'),
                    'total_outstanding'=> $sales->sum('balance'),
                    'oldest_dr_date'   => $oldestDate,
                    'oldest_days'      => $oldestDays,
                ];
            })->values();

            return [
                'branch'    => $branch,
                'customers' => $customers,
            ];
        })->filter(fn($a) => $a['customers']->count() > 0)->values();

        return view('ar.index', compact(
            'areaData', 'totalOutstanding', 'agingCurrent', 'agingMid', 'agingOld'
        ));
    }

    // ──────────────────────────────────────────────────────────────────
    // CUSTOMER — all unpaid DRs for a specific branch + customer
    // ──────────────────────────────────────────────────────────────────
    public function customer(Branch $branch, string $customerName)
    {
        $customerName = rawurldecode($customerName);

        $sales = Sale::where('branch_id', $branch->id)
            ->where('customer_name', $customerName)
            ->whereIn('payment_status', ['to_be_collected', 'partial'])
            ->orderBy('sale_date', 'asc') // oldest first for AR
            ->get();

        abort_if($sales->isEmpty(), 404);

        return view('ar.customer', compact('sales', 'branch', 'customerName'));
    }
}