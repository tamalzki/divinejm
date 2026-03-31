<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\FinishedProduct;
use App\Models\StockMovement;
use App\Models\ProductionMix;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = Carbon::today();
        $yesterday  = Carbon::yesterday();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $week7Ago   = Carbon::now()->subDays(7);

        // ══════════════════════════════════════════════════════
        // SALES KPIs
        // ══════════════════════════════════════════════════════

        $todaySales     = Sale::whereDate('sale_date', $today)->sum('total_amount');
        $todayCollected = Sale::whereDate('sale_date', $today)->sum('amount_paid');
        $yesterdaySales = Sale::whereDate('sale_date', $yesterday)->sum('total_amount');
        $salesGrowth    = $yesterdaySales > 0
            ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100
            : 0;

        $monthlySales        = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('total_amount');
        $monthlyCollected    = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('amount_paid');
        $monthlyTransactions = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->count();
        $collectionRate      = $monthlySales > 0 ? ($monthlyCollected / $monthlySales) * 100 : 0;

        // ══════════════════════════════════════════════════════
        // P&L THIS MONTH
        // ══════════════════════════════════════════════════════

        // COGS: sum of (qty_sold × cost_price) for items in the month
        $monthlyCOGS = SaleItem::whereHas('sale', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('sale_date', [$monthStart, $monthEnd]);
            })
            ->with('finishedProduct')
            ->get()
            ->sum(function ($item) {
                return $item->quantity_sold * ($item->finishedProduct->cost_price ?? 0);
            });

        $monthlyGrossProfit = $monthlySales - $monthlyCOGS;

        $monthlyExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthlyProfit = $monthlyGrossProfit - $monthlyExpenses;

        // ══════════════════════════════════════════════════════
        // RECEIVABLES
        // ══════════════════════════════════════════════════════

        $totalReceivables = Sale::where('payment_status', '!=', 'paid')
            ->selectRaw('SUM(total_amount - amount_paid) as total')
            ->value('total') ?? 0;

        $overdueReceivables = Sale::where('payment_status', '!=', 'paid')
            ->where('sale_date', '<', $today->copy()->subDays(7))
            ->selectRaw('customer_name, branch_id,
                COUNT(*) as overdue_count,
                SUM(total_amount - amount_paid) as overdue_amount,
                MIN(sale_date) as oldest_sale_date')
            ->groupBy('customer_name', 'branch_id')
            ->having('overdue_amount', '>', 0)
            ->orderByDesc('overdue_amount')
            ->get();

        $overduebranchMap = Branch::whereIn('id', $overdueReceivables->pluck('branch_id')->unique())
            ->get()->keyBy('id');

        $overdueReceivables = $overdueReceivables->map(function ($r) use ($overduebranchMap) {
            $r->branch       = $overduebranchMap->get($r->branch_id);
            $r->days_overdue = Carbon::parse($r->oldest_sale_date)->diffInDays(Carbon::now());
            return $r;
        });

        // ══════════════════════════════════════════════════════
        // INVENTORY
        // ══════════════════════════════════════════════════════

        $allProducts         = FinishedProduct::all();
        $totalStockOnHand    = $allProducts->sum('stock_on_hand');
        $totalStockOut       = $allProducts->sum('stock_out');
        $totalInventory      = $totalStockOnHand + $totalStockOut;
        $warehouseValue      = $allProducts->sum(fn($p) => $p->stock_on_hand * $p->cost_price);
        $branchValue         = $allProducts->sum(fn($p) => $p->stock_out * $p->cost_price);
        $totalInventoryValue = $warehouseValue + $branchValue;
        $zeroStockProducts   = $allProducts->where('stock_on_hand', 0)->count();

        $lowStockFinished = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')
            ->orderBy('stock_on_hand')
            ->limit(10)
            ->get();

        $outOfStockProducts = FinishedProduct::where('stock_on_hand', 0)
            ->orderByDesc('stock_out')
            ->get();

        $expiringProducts = FinishedProduct::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $today->copy()->addDays(30))
            ->where('expiry_date', '>=', $today)
            ->get()
            ->map(function ($p) use ($today) {
                $p->days_until_expiry = $today->diffInDays($p->expiry_date);
                return $p;
            })
            ->sortBy('days_until_expiry');

        $needsProduction = FinishedProduct::whereColumn('stock_on_hand', '<', 'minimum_stock')
            ->orderBy('stock_on_hand')
            ->limit(5)
            ->get();

        // ══════════════════════════════════════════════════════
        // PRODUCTION (last 7 days)
        // ══════════════════════════════════════════════════════

        $recentMixes = ProductionMix::with('finishedProduct')
            ->where('mix_date', '>=', $week7Ago)
            ->where('status', 'completed')
            ->get();

        $productionStats = [
            'batches_completed' => $recentMixes->count(),
            'total_output'      => $recentMixes->sum('actual_output'),
            'total_rejected'    => $recentMixes->sum('rejected_quantity'),
            'rejection_rate'    => $recentMixes->sum('actual_output') > 0
                ? ($recentMixes->sum('rejected_quantity') / $recentMixes->sum('actual_output')) * 100
                : 0,
        ];

        $recentBadOrders = StockMovement::where('movement_type', 'return_bo')
            ->where('movement_date', '>=', $week7Ago)
            ->with('finishedProduct')
            ->get()
            ->groupBy('finished_product_id')
            ->map(function ($movements) {
                return [
                    'product'      => $movements->first()->finishedProduct,
                    'total_bo'     => $movements->sum('quantity'),
                    'dr_numbers'   => $movements->pluck('reference_number')->filter()->unique()->values(),
                    'batch_numbers'=> $movements->pluck('batch_number')->filter()->unique()->values(),
                ];
            })
            ->values();

        // ══════════════════════════════════════════════════════
        // MOVEMENTS TODAY
        // ══════════════════════════════════════════════════════

        $todayDeployments = StockMovement::whereDate('movement_date', $today)
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->sum('quantity');

        $todayReturns = StockMovement::whereDate('movement_date', $today)
            ->whereIn('movement_type', ['return_bo', 'return_unsold'])
            ->sum('quantity');

        // ══════════════════════════════════════════════════════
        // TOP SELLERS & CUSTOMERS (this month)
        // ══════════════════════════════════════════════════════

        $topSellingProducts = SaleItem::with('finishedProduct')
            ->whereHas('sale', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('sale_date', [$monthStart, $monthEnd]);
            })
            ->select(
                'finished_product_id',
                DB::raw('SUM(quantity_sold) as total_sold'),
                DB::raw('SUM(quantity_sold * unit_price) as total_revenue')
            )
            ->groupBy('finished_product_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $topCustomers = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
            ->select(
                'customer_name',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(total_amount) as total_spent')
            )
            ->groupBy('customer_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // ══════════════════════════════════════════════════════
        // BRANCH PERFORMANCE (this month)
        // ══════════════════════════════════════════════════════

        $branchSalesRaw = Sale::whereBetween('sale_date', [$monthStart, $monthEnd])
            ->select(
                'branch_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(amount_paid) as total_collected')
            )
            ->groupBy('branch_id')
            ->orderByDesc('total_sales')
            ->get();

        $branchMap = Branch::whereIn('id', $branchSalesRaw->pluck('branch_id')->unique())
            ->get()->keyBy('id');

        $branchSales = $branchSalesRaw->map(function ($b) use ($branchMap) {
            $b->branch = $branchMap->get($b->branch_id);
            return $b;
        });

        // ══════════════════════════════════════════════════════
        // RECENT SALES
        // ══════════════════════════════════════════════════════

        $recentSales = Sale::with('branch')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            // Sales KPIs
            'todaySales', 'todayCollected', 'salesGrowth',
            'monthlySales', 'monthlyCollected', 'monthlyTransactions', 'collectionRate',
            // P&L
            'monthlyCOGS', 'monthlyGrossProfit', 'monthlyExpenses', 'monthlyProfit',
            // Receivables
            'totalReceivables', 'overdueReceivables',
            // Inventory
            'totalStockOnHand', 'totalStockOut', 'totalInventory',
            'warehouseValue', 'branchValue', 'totalInventoryValue',
            'zeroStockProducts', 'lowStockFinished', 'outOfStockProducts',
            'expiringProducts', 'needsProduction',
            // Production
            'productionStats', 'recentBadOrders',
            // Movements
            'todayDeployments', 'todayReturns',
            // Top lists
            'topSellingProducts', 'topCustomers', 'branchSales',
            // Feed
            'recentSales'
        ));
    }
}