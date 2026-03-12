<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\FinishedProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchInventoryController; 
use App\Http\Controllers\BankDepositController;
use App\Http\Controllers\ProductionMixController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\AccountsReceivableController;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes(['register' => false]);

Route::get('/home', function() {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('raw-materials', RawMaterialController::class);
    Route::post('raw-materials/{rawMaterial}/use', [RawMaterialController::class, 'recordUsage'])
        ->name('raw-materials.use');
    Route::post('raw-materials/{rawMaterial}/restock', [RawMaterialController::class, 'restock'])
        ->name('raw-materials.restock');
    
    Route::post('/finished-products/{finishedProduct}/regenerate-barcode',
        [FinishedProductController::class, 'regenerateBarcode'])
        ->name('finished-products.regenerate-barcode');
    
    Route::resource('finished-products', FinishedProductController::class);
    Route::post('finished-products/{finishedProduct}/restock', [FinishedProductController::class, 'restock'])
        ->name('finished-products.restock');
    Route::patch('finished-products/{finishedProduct}/adjust', [FinishedProductController::class, 'adjust'])
        ->name('finished-products.adjust');
    Route::get('/finished-products/calculate-max/{finishedProduct}', [FinishedProductController::class, 'calculateMax'])
        ->name('finished-products.calculate-max');

    // ── Sales ─────────────────────────────────────────────────────────
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');

    // Item-level AJAX routes (must be before wildcards)
    Route::patch('/sales/items/{saleItem}/mark-sold', [SaleController::class, 'markSold'])->name('sales.markSold');
    Route::patch('/sales/items/{saleItem}/sold-out',  [SaleController::class, 'soldOutItem'])->name('sales.soldOutItem');

    // DR detail page (must be before {sale} and {branch} wildcards)
    Route::get   ('/sales/dr/{sale}',        [SaleController::class, 'drDetail'])->name('sales.dr');
    Route::patch ('/sales/dr/{sale}/update', [SaleController::class, 'drUpdate'])->name('sales.drUpdate');

    // Sale-level routes
    Route::patch('/sales/{sale}/sold-out', [SaleController::class, 'soldOutSale'])->name('sales.soldOut');
    Route::get   ('/sales/{sale}/payment', [SaleController::class, 'paymentPage'])->name('sales.paymentPage');
    Route::patch ('/sales/{sale}/payment', [SaleController::class, 'updatePayment'])->name('sales.updatePayment');

    // Sales API endpoints
    Route::get('/api/sales/customers/{branch}',                          [SaleController::class, 'getCustomers'])->name('sales.api.customers');
    Route::get('/api/sales/dr-numbers/{branch}',                         [SaleController::class, 'getDRNumbers'])->name('sales.api.dr-numbers');
    Route::get('/api/sales/dr-products/{branch}/{drNumber}',             [SaleController::class, 'getDRProducts'])->name('sales.api.dr-products');
    Route::get('/api/sales/products/{branch}/{customerName}',            [SaleController::class, 'getProducts'])->name('sales.api.products');
    Route::get('/api/sales/check-dr/{branch}/{customerName}/{drNumber}', [SaleController::class, 'checkDrNumber'])->name('sales.api.check-dr');

    // Wildcard LAST
    Route::get('/sales/{branch}/{customerName}', [SaleController::class, 'show'])->name('sales.show');
    // ──────────────────────────────────────────────────────────────────

    // ── Accounts Receivable ───────────────────────────────────────────
    Route::get('/ar', [AccountsReceivableController::class, 'index'])->name('ar.index');
    Route::get('/ar/{branch}/{customerName}', [AccountsReceivableController::class, 'customer'])->name('ar.customer');
    // ──────────────────────────────────────────────────────────────────

    Route::resource('expenses', ExpenseController::class);
    
    // ── Reports ───────────────────────────────────────────────────────
    Route::get('/reports/inventory', [InventoryReportController::class, 'index'])->name('reports.inventory');
    Route::get('/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales');
    // ──────────────────────────────────────────────────────────────────

    Route::get('/financial-reports', [FinancialReportController::class, 'index'])
        ->name('financial-reports.index');
    
    // Branches / Areas
    Route::resource('branches', BranchController::class);

    // Branch Inventory — Deliveries (named routes before wildcards)
    Route::get('/branch-inventory', [BranchInventoryController::class, 'index'])
        ->name('branch-inventory.index');
    Route::get('/branch-inventory/deliver/new', [BranchInventoryController::class, 'createDelivery'])
        ->name('branch-inventory.create-delivery');
    Route::post('/branch-inventory/deliver/store', [BranchInventoryController::class, 'storeDelivery'])
        ->name('branch-inventory.store-delivery');
    Route::get('/branch-inventory/delivery/{drNumber}', [BranchInventoryController::class, 'showDelivery'])
        ->name('branch-inventory.show-delivery');

    // Branch Inventory — Areas (wildcard routes after named routes)
    Route::get('/branch-inventory/{branch}', [BranchInventoryController::class, 'show'])
        ->name('branch-inventory.show');
    Route::get('/branch-inventory/{branch}/create', [BranchInventoryController::class, 'create'])
        ->name('branch-inventory.create');
    Route::post('/branch-inventory/{branch}/transfer', [BranchInventoryController::class, 'transfer'])
        ->name('branch-inventory.transfer');
    Route::post('/branch-inventory/{branch}/return', [BranchInventoryController::class, 'returnStock'])
        ->name('branch-inventory.return');
    Route::delete('/branch-inventory/{branch}/inventory/{branchInventory}', [BranchInventoryController::class, 'destroy'])
        ->name('branch-inventory.destroy');
    Route::post('/branch-inventory/{branch}/transfer-between-branches', [BranchInventoryController::class, 'transferBetweenBranches'])
        ->name('branch-inventory.transfer-between-branches');

    // Production Batches (Production Mix)
    Route::get('/production-mixes', [ProductionMixController::class, 'index'])
        ->name('production-mixes.index');
    Route::get('/production-mixes/create/{finishedProduct?}', [ProductionMixController::class, 'create'])
        ->name('production-mixes.create');
    Route::post('/production-mixes', [ProductionMixController::class, 'store'])
        ->name('production-mixes.store');
    Route::get('/production-mixes/{productionMix}', [ProductionMixController::class, 'show'])
        ->name('production-mixes.show');
    Route::patch('/production-mixes/{productionMix}/actual-output', [ProductionMixController::class, 'updateActualOutput'])
        ->name('production-mixes.update-actual-output');
    Route::delete('/production-mixes/{productionMix}', [ProductionMixController::class, 'destroy'])
        ->name('production-mixes.destroy');

    Route::resource('bank-deposits', BankDepositController::class);
});