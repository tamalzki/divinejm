<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\FinishedProduct;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ProductAlert;
use App\Models\Branch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // SALES & FINANCIAL METRICS
        // ===========================
        
        // Monthly sales
        $monthlySales = Sale::whereMonth('sale_date', Carbon::now()->month)
            ->whereYear('sale_date', Carbon::now()->year)
            ->sum('total_amount');

        // Monthly expenses
        $monthlyExpenses = Expense::whereMonth('expense_date', Carbon::now()->month)
            ->whereYear('expense_date', Carbon::now()->year)
            ->sum('amount');

        // Monthly profit
        $monthlyProfit = $monthlySales - $monthlyExpenses;

        // Today's sales
        $todaySales = Sale::whereDate('sale_date', Carbon::today())->sum('total_amount');

        // Yesterday's sales (for comparison)
        $yesterdaySales = Sale::whereDate('sale_date', Carbon::yesterday())->sum('total_amount');
        
        // Sales growth percentage
        $salesGrowth = $yesterdaySales > 0 
            ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100 
            : 0;

        // ===========================
        // INVENTORY METRICS
        // ===========================
        
        // Total products count
        $totalFinishedProducts = FinishedProduct::count();
        $totalRawMaterials = RawMaterial::count();

        // Stock distribution - UPDATED to use new fields
        $totalStockOnHand = FinishedProduct::sum('stock_on_hand'); // Warehouse stock
        $totalStockOut = FinishedProduct::sum('stock_out'); // Branch stock
        $totalInventory = $totalStockOnHand + $totalStockOut; // Total inventory

        // Inventory value
        $warehouseValue = FinishedProduct::all()->sum(function($product) {
            return $product->stock_on_hand * $product->total_cost;
        });

        $branchValue = FinishedProduct::all()->sum(function($product) {
            return $product->stock_out * $product->total_cost;
        });

        $totalInventoryValue = $warehouseValue + $branchValue;

        // Low stock counts - UPDATED to use stock_on_hand instead of quantity
        $lowStockFinishedProducts = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')->count();
        $lowStockRawMaterials = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock')->count();

        // ===========================
        // RECENT ACTIVITY
        // ===========================
        
        // Recent sales
        $recentSales = Sale::with('finishedProduct')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Recent stock movements (last 5)
        $recentStockMovements = StockMovement::with(['finishedProduct', 'branch', 'toBranch', 'fromBranch'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ===========================
        // LOW STOCK ALERTS
        // ===========================
        
        // Low stock finished products - UPDATED
        $lowStockFinished = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')
            ->orderBy('stock_on_hand', 'asc')
            ->take(5)
            ->get();
        
        $lowStockRaw = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock')
            ->orderBy('quantity', 'asc')
            ->take(5)
            ->get();

        // ===========================
        // BRANCH STATISTICS
        // ===========================
        
        // Branch inventory stats
        $branches = Branch::with(['inventory.finishedProduct'])
            ->where('is_active', true)
            ->get();

        // Top products by branch
        $topProductsByBranch = DB::table('branch_inventory')
            ->join('finished_products', 'branch_inventory.finished_product_id', '=', 'finished_products.id')
            ->join('branches', 'branch_inventory.branch_id', '=', 'branches.id')
            ->where('branch_inventory.quantity', '>', 0)
            ->select('finished_products.name', 'branches.name as branch_name', 'branch_inventory.quantity')
            ->orderBy('branch_inventory.quantity', 'desc')
            ->take(5)
            ->get();

        // Branch with most stock
        $branchStockTotals = DB::table('branch_inventory')
            ->join('branches', 'branch_inventory.branch_id', '=', 'branches.id')
            ->select('branches.name', DB::raw('SUM(branch_inventory.quantity) as total_stock'))
            ->groupBy('branches.id', 'branches.name')
            ->orderBy('total_stock', 'desc')
            ->first();

        // ===========================
        // STOCK MOVEMENTS TODAY
        // ===========================
        
        $todayDeployments = StockMovement::whereDate('movement_date', Carbon::today())
            ->whereIn('movement_type', ['transfer_out', 'branch_transfer_out'])
            ->sum('quantity');

        $todayReturns = StockMovement::whereDate('movement_date', Carbon::today())
            ->whereIn('movement_type', ['return', 'branch_transfer_in'])
            ->sum('quantity');

        return view('dashboard', compact(
            // Alerts
            'alerts',
            
            // Financial
            'monthlySales',
            'monthlyExpenses',
            'monthlyProfit',
            'todaySales',
            'yesterdaySales',
            'salesGrowth',
            
            // Inventory counts
            'totalFinishedProducts',
            'totalRawMaterials',
            'lowStockFinishedProducts',
            'lowStockRawMaterials',
            
            // Stock distribution
            'totalStockOnHand',
            'totalStockOut',
            'totalInventory',
            'warehouseValue',
            'branchValue',
            'totalInventoryValue',
            
            // Recent activity
            'recentSales',
            'recentStockMovements',
            
            // Low stock items
            'lowStockFinished',
            'lowStockRaw',
            
            // Branch stats
            'branches',
            'topProductsByBranch',
            'branchStockTotals',
            
            // Today's movements
            'todayDeployments',
            'todayReturns'
        ));
    }

    private function updateAlerts()
    {
        // Clear old resolved alerts
        ProductAlert::where('is_resolved', true)
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();

        // Check finished products - UPDATED to use stock_on_hand
        $lowStockFinished = FinishedProduct::whereColumn('stock_on_hand', '<=', 'minimum_stock')->get();
        foreach ($lowStockFinished as $product) {
            ProductAlert::updateOrCreate(
                [
                    'product_type' => 'finished_product',
                    'product_id' => $product->id,
                ],
                [
                    'product_name' => $product->name,
                    'current_stock' => $product->stock_on_hand, // UPDATED
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

        // Resolve alerts for products back in stock - UPDATED
        ProductAlert::where('is_resolved', false)->get()->each(function ($alert) {
            if ($alert->product_type === 'finished_product') {
                $product = FinishedProduct::find($alert->product_id);
                if ($product && $product->stock_on_hand > $product->minimum_stock) { // UPDATED
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