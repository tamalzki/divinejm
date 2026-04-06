<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $totalAmount = $expenses->sum('amount');

        $byCategory = Expense::whereBetween('expense_date', [$start, $end])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $byPaymentMethod = Expense::whereBetween('expense_date', [$start, $end])
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return view('reports.expenses', compact(
            'startDate',
            'endDate',
            'expenses',
            'totalAmount',
            'byCategory',
            'byPaymentMethod'
        ));
    }
}
