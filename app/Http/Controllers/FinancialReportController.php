<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\FinishedProduct;
use App\Models\StockMovement;
use App\Models\BankDeposit;
use App\Models\ProductionMix;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        // Date filters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $reportType = $request->input('report_type', 'monthly'); // monthly, quarterly, weekly

        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate);
        }
        if (!$endDate instanceof Carbon) {
            $endDate = Carbon::parse($endDate);
        }

        // ======================
        // SALES DATA
        // ======================
        $salesQuery = Sale::with(['items.finishedProduct', 'branch'])
            ->whereBetween('sale_date', [$startDate, $endDate]);
        
        $totalSales = $salesQuery->sum('total_amount');
        $allSales = $salesQuery->get();

        // Sales by Product with proper cost calculation
        $salesByProduct = SaleItem::with(['finishedProduct', 'sale'])
            ->whereHas('sale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('finished_product_id')
            ->map(function ($items) {
                $product = $items->first()->finishedProduct;
                $quantity = $items->sum('quantity_sold');
                
                // Check if discount column exists
                $hasDiscountColumn = \Illuminate\Support\Facades\Schema::hasColumn('sale_items', 'discount');
                
                $revenue = $items->sum(function($item) use ($hasDiscountColumn) {
                    $subtotal = $item->quantity_sold * $item->unit_price;
                    if ($hasDiscountColumn && isset($item->discount)) {
                        return $subtotal - $item->discount;
                    }
                    return $subtotal;
                });
                
                $cost = $product->cost_price * $quantity;
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
        // SALES BY CUSTOMER (NEW)
        // ======================
        $salesByCustomer = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select(
                'customer_name',
                'branch_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(amount_paid) as total_paid'),
                DB::raw('SUM(total_amount - amount_paid) as balance')
            )
            ->groupBy('customer_name', 'branch_id')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($sale) {
                $sale->branch = Branch::find($sale->branch_id);
                return $sale;
            });

        // Deliveries per customer (from sales + stock movements)
        // Get unique DR numbers from sales first
        $salesDRs = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select('dr_number', 'customer_name', 'branch_id')
            ->get()
            ->unique(function($sale) {
                return $sale->dr_number . '-' . $sale->customer_name;
            });

        $deliveriesByCustomer = collect();
        
        foreach ($salesDRs as $saleDR) {
            $movements = StockMovement::where('reference_number', $saleDR->dr_number)
                ->whereIn('movement_type', ['transfer_out', 'extra_free'])
                ->get();
            
            if ($movements->count() > 0) {
                $existing = $deliveriesByCustomer->get($saleDR->customer_name);
                
                if ($existing) {
                    $deliveriesByCustomer->put($saleDR->customer_name, [
                        'customer_name' => $saleDR->customer_name,
                        'total_delivered' => $existing['total_delivered'] + $movements->sum('quantity'),
                        'deliveries_count' => $existing['deliveries_count'] + 1,
                        'products_count' => $existing['products_count'] + $movements->count(),
                    ]);
                } else {
                    $deliveriesByCustomer->put($saleDR->customer_name, [
                        'customer_name' => $saleDR->customer_name,
                        'total_delivered' => $movements->sum('quantity'),
                        'deliveries_count' => 1,
                        'products_count' => $movements->count(),
                    ]);
                }
            }
        }
        
        $deliveriesByCustomer = $deliveriesByCustomer->sortByDesc('total_delivered');

        // ======================
        // PRODUCTION ANALYSIS (NEW)
        // ======================
        $productionMixes = ProductionMix::with('finishedProduct')
            ->whereBetween('mix_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $productionSummary = $productionMixes->groupBy('finished_product_id')
            ->map(function($mixes) {
                $product = $mixes->first()->finishedProduct;
                $totalExpected = $mixes->sum('total_expected_output');
                $totalActual = $mixes->sum('actual_output');
                $totalRejected = $mixes->sum('rejected_quantity');
                $variance = $totalActual - $totalExpected;
                $variancePercent = $totalExpected > 0 ? ($variance / $totalExpected) * 100 : 0;
                $rejectionRate = $totalActual > 0 ? ($totalRejected / $totalActual) * 100 : 0;
                $yieldRate = $totalExpected > 0 ? (($totalActual - $totalRejected) / $totalExpected) * 100 : 0;

                return [
                    'product_name' => $product->name,
                    'batches_count' => $mixes->count(),
                    'expected_output' => $totalExpected,
                    'actual_output' => $totalActual,
                    'rejected_quantity' => $totalRejected,
                    'good_output' => $totalActual - $totalRejected,
                    'variance' => $variance,
                    'variance_percent' => $variancePercent,
                    'rejection_rate' => $rejectionRate,
                    'yield_rate' => $yieldRate,
                ];
            })
            ->sortByDesc('actual_output');

        // Production by period (weekly/monthly)
        $productionByPeriod = $this->getProductionByPeriod($productionMixes, $reportType);

        // ======================
        // ACCOUNTS RECEIVABLE (NEW)
        // ======================
        $accountsReceivable = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('payment_status', '!=', 'paid')
            ->select(
                'customer_name',
                'branch_id',
                DB::raw('COUNT(*) as unpaid_count'),
                DB::raw('SUM(total_amount - amount_paid) as total_receivable'),
                DB::raw('MIN(sale_date) as oldest_sale'),
                DB::raw('MAX(sale_date) as latest_sale')
            )
            ->groupBy('customer_name', 'branch_id')
            ->having('total_receivable', '>', 0)
            ->orderByDesc('total_receivable')
            ->get()
            ->map(function($ar) {
                $ar->branch = Branch::find($ar->branch_id);
                $ar->days_outstanding = Carbon::parse($ar->oldest_sale)->diffInDays(now());
                return $ar;
            });

        $totalReceivables = $accountsReceivable->sum('total_receivable');

        // Payment Status Summary
        $paymentSummary = [
            'total_sales' => $totalSales,
            'total_collected' => $allSales->sum('amount_paid'),
            'to_be_collected' => $allSales->where('payment_status', 'to_be_collected')->sum(function($s) {
                return $s->total_amount - $s->amount_paid;
            }),
            'partial_payments' => $allSales->where('payment_status', 'partial')->count(),
            'paid_in_full' => $allSales->where('payment_status', 'paid')->count(),
        ];

        // ======================
        // EXPENSE DATA
        // ======================
        $totalExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $expensesByCategory = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'category');

        $netProfit = $grossProfit - $totalExpenses;

        // ======================
        // CASH FLOW DATA
        // ======================
        $cashSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('payment_mode', 'cash')
            ->sum('amount_paid');

        $cashExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('payment_method', 'cash')
            ->sum('amount');

        $bankDeposits = BankDeposit::whereBetween('deposit_date', [$startDate, $endDate])
            ->sum('amount');

        // Cash in hand (not deposited)
        $cashInHand = $cashSales - $cashExpenses - $bankDeposits;
        
        // Cash in bank (cumulative deposits)
        $cashInBank = BankDeposit::where('deposit_date', '<=', $endDate)->sum('amount');

        $cashFlow = $cashInHand;

        // ======================
        // INVENTORY METRICS
        // ======================
        $currentInventoryValue = FinishedProduct::all()->sum(function ($product) {
            return ($product->stock_on_hand + $product->stock_out) * $product->cost_price;
        });

        $stockDeployed = StockMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->sum('quantity');

        $stockReturned = StockMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->where('movement_type', 'return_bo')
            ->sum('quantity');

        // ======================
        // RATIOS
        // ======================
        $grossProfitMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
        $netProfitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;
        $operatingExpenseRatio = $totalSales > 0 ? ($totalExpenses / $totalSales) * 100 : 0;
        $collectionEfficiency = $totalSales > 0 ? ($paymentSummary['total_collected'] / $totalSales) * 100 : 0;

        // ======================
        // ADDITIONAL METRICS
        // ======================
        $totalTransactions = $allSales->count();
        $averageTransactionValue = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        return view('financial-reports.index', compact(
            'startDate',
            'endDate',
            'reportType',
            'totalSales',
            'totalCOGS',
            'grossProfit',
            'totalExpenses',
            'netProfit',
            'salesByProduct',
            'salesByCustomer',
            'deliveriesByCustomer',
            'productionSummary',
            'productionByPeriod',
            'accountsReceivable',
            'totalReceivables',
            'paymentSummary',
            'expensesByCategory',
            'cashFlow',
            'cashSales',
            'cashExpenses',
            'bankDeposits',
            'cashInHand',
            'cashInBank',
            'currentInventoryValue',
            'stockDeployed',
            'stockReturned',
            'grossProfitMargin',
            'netProfitMargin',
            'operatingExpenseRatio',
            'collectionEfficiency',
            'totalTransactions',
            'averageTransactionValue'
        ));
    }

    /**
     * Get production summary grouped by period
     */
    private function getProductionByPeriod($productionMixes, $reportType)
    {
        if ($reportType === 'weekly') {
            return $productionMixes->groupBy(function($mix) {
                return $mix->mix_date->format('Y-W'); // Year-Week
            })->map(function($mixes, $week) {
                return [
                    'period' => 'Week ' . substr($week, -2) . ', ' . substr($week, 0, 4),
                    'batches' => $mixes->count(),
                    'total_output' => $mixes->sum('actual_output'),
                    'total_rejected' => $mixes->sum('rejected_quantity'),
                    'products' => $mixes->groupBy('finished_product_id')->map(function($p) {
                        return [
                            'name' => $p->first()->finishedProduct->name,
                            'quantity' => $p->sum('actual_output'),
                        ];
                    })->values(),
                ];
            });
        } elseif ($reportType === 'quarterly') {
            return $productionMixes->groupBy(function($mix) {
                return $mix->mix_date->format('Y') . '-Q' . $mix->mix_date->quarter;
            })->map(function($mixes, $quarter) {
                return [
                    'period' => str_replace('-', ' ', $quarter),
                    'batches' => $mixes->count(),
                    'total_output' => $mixes->sum('actual_output'),
                    'total_rejected' => $mixes->sum('rejected_quantity'),
                    'products' => $mixes->groupBy('finished_product_id')->map(function($p) {
                        return [
                            'name' => $p->first()->finishedProduct->name,
                            'quantity' => $p->sum('actual_output'),
                        ];
                    })->values(),
                ];
            });
        } else { // monthly
            return $productionMixes->groupBy(function($mix) {
                return $mix->mix_date->format('Y-m');
            })->map(function($mixes, $month) {
                return [
                    'period' => Carbon::parse($month . '-01')->format('F Y'),
                    'batches' => $mixes->count(),
                    'total_output' => $mixes->sum('actual_output'),
                    'total_rejected' => $mixes->sum('rejected_quantity'),
                    'products' => $mixes->groupBy('finished_product_id')->map(function($p) {
                        return [
                            'name' => $p->first()->finishedProduct->name,
                            'quantity' => $p->sum('actual_output'),
                        ];
                    })->values(),
                ];
            });
        }
    }
}