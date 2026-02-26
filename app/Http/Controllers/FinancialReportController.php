<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\FinishedProduct;
use App\Models\StockMovement;
use App\Models\BankDeposit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate);
        }
        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate);
        }

        // ======================
        // SALES DATA
        // ======================
        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->sum('total_amount');

        $salesByProduct = Sale::with('finishedProduct')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->get()
            ->groupBy('finished_product_id')
            ->map(function ($sales) {
                $product = $sales->first()->finishedProduct;
                $quantity = $sales->sum('quantity');
                $revenue = $sales->sum('total_amount');
                $cost = $product->total_cost * $quantity;
                $profit = $revenue - $cost;
                
                return [
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'margin' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                ];
            })
            ->sortByDesc('revenue');

        $totalCOGS = $salesByProduct->sum('cost');
        $grossProfit = $totalSales - $totalCOGS;

        // ======================
        // EXPENSE DATA
        // ======================
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $expensesByCategory = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->get()
            ->groupBy('category')
            ->map(function ($expenses) {
                return $expenses->sum('amount');
            })
            ->sortByDesc(function ($value) {
                return $value;
            });

        $netProfit = $grossProfit - $totalExpenses;

        // ======================
        // CASH FLOW DATA
        // ======================
        $cashSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $cashExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('payment_method', 'cash')
            ->sum('amount');

        // NEW: Bank Deposits within period
        $bankDeposits = BankDeposit::whereBetween('deposit_date', [$startDate, $endDate])
            ->sum('amount');

        // UPDATED cash flow formula
        $cashFlow = $cashSales - $cashExpenses - $bankDeposits;

        // ======================
        // INVENTORY METRICS
        // ======================
        $currentInventoryValue = FinishedProduct::all()->sum(function ($product) {
            return ($product->stock_on_hand + $product->stock_out) * $product->total_cost;
        });

        $stockDeployed = StockMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->whereIn('movement_type', ['transfer_out', 'branch_transfer_out'])
            ->sum('quantity');

        $stockReturned = StockMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->whereIn('movement_type', ['return', 'branch_transfer_in'])
            ->sum('quantity');

        // ======================
        // RATIOS
        // ======================
        $grossProfitMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
        $netProfitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;
        $operatingExpenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0;

        // ======================
        // ADDITIONAL METRICS
        // ======================
        $totalTransactions = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();
        $averageTransactionValue = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return view('financial-reports.index', compact(
            'startDate',
            'endDate',
            'totalSales',
            'totalCOGS',
            'grossProfit',
            'totalExpenses',
            'netProfit',
            'salesByProduct',
            'expensesByCategory',
            'cashFlow',
            'cashSales',
            'cashExpenses',
            'bankDeposits', // NEW
            'currentInventoryValue',
            'stockDeployed',
            'stockReturned',
            'grossProfitMargin',
            'netProfitMargin',
            'operatingExpenseRatio',
            'totalTransactions',
            'averageTransactionValue'
        ));
    }
}