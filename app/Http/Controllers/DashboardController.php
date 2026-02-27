<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\FinishedProduct;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\ProductAlert;
use App\Models\Branch;
use App\Models\StockMovement;
use App\Models\ProductionMix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Update alerts
        $this->updateAlerts();

        // Get active alerts
        $alerts = ProductAlert::where('is_resolved', false)->get();

        // ===========================
        // CRITICAL ALERTS & PRIORITIES
        // ===========================
        
        // CRITICAL: Accounts Receivable Overdue (>30 days)
        $overdueReceivables = Sale::where('payment_status', '!=', 'paid')
            ->whereDate('sale_date', '<', Carbon::now()->subDays(30))
            ->select(
                'customer_name',
                'branch_id',
                DB::raw('COUNT(*) as overdue_count'),
                DB::raw('SUM(total_amount - amount_paid) as overdue_amount'),
                DB::raw('MIN(sale_date) as oldest_date')
            )
            ->groupBy('customer_name', 'branch_id')
            ->having('overdue_amount', '>', 0)
            ->orderByDesc('overdue_amount')
            ->limit(5)
            ->get()
            ->map(function($item) {
                $item->branch = Branch::find($item->branch_id);
                $item->days_overdue = Carbon::parse($item->oldest_date)->diffInDays(now());
                return $item;
            });

        // CRITICAL: Out of Stock Products (warehouse = 0)
        $outOfStockProducts = FinishedProduct::where('stock_on_hand', '=', 0)
            ->where('stock_out', '>', 0) // Still deployed in branches
            ->get();

        // CRITICAL: Products expiring soon (within 7 days)
        $expiringProducts = FinishedProduct::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->where(function($q) {
                $q->where('stock_on_hand', '>', 0)
                  ->orWhere('stock_out', '>', 0);
            })
            ->get()
            ->map(function($product) {
                $product->days_until_expiry = Carbon::parse($product->expiry_date)->diffInDays(now());
                return $product;
            });

        // CRITICAL: Bad Order Returns (last 7 days)
        $recentBadOrders = StockMovement::where('movement_type', 'return_bo')
            ->whereBetween('movement_date', [Carbon::now()->subDays(7), Carbon::now()])
            ->with(['finishedProduct', 'branch'])
            ->get()
            ->groupBy('finished_product_id')
            ->map(function($movements) {
                return [
                    'product' => $movements->first()->finishedProduct,
                    'total_bo' => $movements->sum('quantity'),
                    'count' => $movements->count(),
                    'dr_numbers' => $movements->pluck('reference_number')->filter()->unique(),
                    'batch_numbers' => $movements->pluck('batch_number')->filter()->unique(),
                ];
            })
            ->sortByDesc('total_bo')
            ->take(5);

        // ===========================
        // SALES & FINANCIAL METRICS
        // ===========================
        
        // Today's sales
        $todaySales = Sale::whereDate('sale_date', Carbon::today())->sum('total_amount');
        $todayCollected = Sale::whereDate('sale_date', Carbon::today())->sum('amount_paid');
        
        // Yesterday's sales
        $yesterdaySales = Sale::whereDate('sale_date', Carbon::yesterday())->sum('total_amount');
        
        // Sales growth
        $salesGrowth = $yesterdaySales > 0 
            ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 
            : 0;

        // Monthly sales & profit
        $monthlySales = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('total_amount');

        $monthlyExpenses = Expense::whereMonth('expense_date', Carbon::now()->month)
            ->whereYear('expense_date', Carbon::now()->year)
            ->sum('amount');

        $monthlyProfit = $monthlySales - $monthlyExpenses;

        // Collections efficiency
        $totalSalesThisMonth = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('total_amount');
        
        $totalCollectedThisMonth = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('amount_paid');

        $collectionRate = $totalSalesThisMonth > 0 
            ? ($totalCollectedThisMonth / $totalSalesThisMonth) * 100 
            : 0;

        // Total receivables
        $totalReceivables = Sale::where('payment_status', '!=', 'paid')
            ->sum(DB::raw('total_amount - amount_paid'));

        // ===========================
        // INVENTORY HEALTH
        // ===========================
        
        // Stock distribution
        $totalStockOnHand = FinishedProduct::sum('stock_on_hand');
        $totalStockOut = FinishedProduct::sum('stock_out');
        $totalInventory = $totalStockOnHand + $totalStockOut;

        // Inventory value
        $warehouseValue = FinishedProduct::all()->sum(function($product) {
            return $product->stock_on_hand * ($product->cost_price ?? 0);
        });

        $branchValue = FinishedProduct::all()->sum(function($product) {
            return $product->stock_out * ($product->cost_price ?? 0);
        });

        $totalInventoryValue = $warehouseValue + $branchValue;

        // Low stock counts
        $lowStockFinishedProducts = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')->count();
        $zeroStockProducts = FinishedProduct::where('stock_on_hand', 0)->count();

        // ===========================
        // PRODUCTION INSIGHTS
        // ===========================
        
        // Recent production (last 7 days)
        $recentProduction = ProductionMix::with('finishedProduct')
            ->whereBetween('mix_date', [Carbon::now()->subDays(7), Carbon::now()])
            ->where('status', 'completed')
            ->get();

        $productionStats = [
            'batches_completed' => $recentProduction->count(),
            'total_output' => $recentProduction->sum('actual_output'),
            'total_rejected' => $recentProduction->sum('rejected_quantity'),
            'rejection_rate' => $recentProduction->sum('actual_output') > 0 
                ? ($recentProduction->sum('rejected_quantity') / $recentProduction->sum('actual_output')) * 100 
                : 0,
        ];

        // Products needing production (low warehouse stock)
        $needsProduction = FinishedProduct::where('stock_on_hand', '<=', DB::raw('minimum_stock * 0.5'))
            ->orderBy('stock_on_hand', 'asc')
            ->limit(5)
            ->get();

        // ===========================
        // TOP PERFORMERS & TRENDS
        // ===========================
        
        // Best selling products (this month) - FIXED: Check if discount column exists
        $hasDiscountColumn = Schema::hasColumn('sale_items', 'discount');
        
        $topSellingQuery = SaleItem::with('finishedProduct')
            ->whereHas('sale', function($q) {
                $q->whereMonth('sale_date', Carbon::now()->month)
                  ->whereYear('sale_date', Carbon::now()->year);
            })
            ->select(
                'finished_product_id',
                DB::raw('SUM(quantity_sold) as total_sold')
            );
        
        if ($hasDiscountColumn) {
            $topSellingQuery->selectRaw('SUM((quantity_sold * unit_price) - COALESCE(discount, 0)) as total_revenue');
        } else {
            $topSellingQuery->selectRaw('SUM(quantity_sold * unit_price) as total_revenue');
        }
        
        $topSellingProducts = $topSellingQuery
            ->groupBy('finished_product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Top customers (this month)
        $topCustomers = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->select(
                'customer_name',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(total_amount) as total_spent')
            )
            ->groupBy('customer_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // ===========================
        // DAILY OPERATIONS
        // ===========================
        
        // Today's movements
        $todayDeployments = StockMovement::whereDate('movement_date', Carbon::today())
            ->whereIn('movement_type', ['transfer_out', 'extra_free'])
            ->sum('quantity');

        $todayReturns = StockMovement::whereDate('movement_date', Carbon::today())
            ->where('movement_type', 'return_bo')
            ->sum('quantity');

        // Recent sales (last 10)
        $recentSales = Sale::with(['branch', 'items.finishedProduct'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent stock movements
        $recentStockMovements = StockMovement::with(['finishedProduct', 'branch'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ===========================
        // LOW STOCK ALERTS
        // ===========================
        
        $lowStockFinished = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')
            ->orderBy('stock_on_hand', 'asc')
            ->limit(10)
            ->get();
        
        $lowStockRaw = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock')
            ->orderBy('quantity', 'asc')
            ->limit(10)
            ->get();

        // ===========================
        // BRANCH PERFORMANCE
        // ===========================
        
        $branches = Branch::with(['inventory.finishedProduct'])
            ->where('is_active', true)
            ->get();

        // Branch sales performance (this month)
        $branchSales = Sale::with('branch')
            ->whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->select(
                'branch_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(amount_paid) as total_collected')
            )
            ->groupBy('branch_id')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($item) {
                $item->branch = Branch::find($item->branch_id);
                return $item;
            });

        return view('dashboard', compact(
            // Critical Alerts
            'alerts',
            'overdueReceivables',
            'outOfStockProducts',
            'expiringProducts',
            'recentBadOrders',
            
            // Financial KPIs
            'todaySales',
            'todayCollected',
            'yesterdaySales',
            'salesGrowth',
            'monthlySales',
            'monthlyExpenses',
            'monthlyProfit',
            'collectionRate',
            'totalReceivables',
            
            // Inventory Health
            'totalStockOnHand',
            'totalStockOut',
            'totalInventory',
            'warehouseValue',
            'branchValue',
            'totalInventoryValue',
            'lowStockFinishedProducts',
            'zeroStockProducts',
            
            // Production
            'productionStats',
            'needsProduction',
            
            // Top Performers
            'topSellingProducts',
            'topCustomers',
            
            // Daily Operations
            'todayDeployments',
            'todayReturns',
            'recentSales',
            'recentStockMovements',
            
            // Alerts
            'lowStockFinished',
            'lowStockRaw',
            
            // Branch Performance
            'branches',
            'branchSales'
        ));
    }

    private function updateAlerts()
    {
        // Clear old resolved alerts
        ProductAlert::where('is_resolved', true)
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();

        // Check finished products
        $lowStockFinished = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')->get();
        foreach ($lowStockFinished as $product) {
            ProductAlert::updateOrCreate(
                [
                    'product_type' => 'finished_product',
                    'product_id' => $product->id,
                ],
                [
                    'product_name' => $product->name,
                    'current_stock' => $product->stock_on_hand,
                    'minimum_stock' => $product->minimum_stock,
                    'is_resolved' => false,
                ]
            );
        }

        // Check raw materials
        $lowStockRaw = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock')->get();
        foreach ($lowStockRaw as $material) {
            ProductAlert::updateOrCreate(
                [
                    'product_type' => 'raw_material',
                    'product_id' => $material->id,
                ],
                [
                    'product_name' => $material->name,
                    'current_stock' => $material->quantity,
                    'minimum_stock' => $material->minimum_stock,
                    'is_resolved' => false,
                ]
            );
        }

        // Resolve alerts for products back in stock
        ProductAlert::where('is_resolved', false)->get()->each(function ($alert) {
            if ($alert->product_type === 'finished_product') {
                $product = FinishedProduct::find($alert->product_id);
                if ($product && $product->stock_on_hand > $product->minimum_stock) {
                    $alert->update(['is_resolved' => true]);
                }
            } else {
                $material = RawMaterial::find($alert->product_id);
                if ($material && $material->quantity > $material->minimum_stock) {
                    $alert->update(['is_resolved' => true]);
                }
            }
        });
    }
}