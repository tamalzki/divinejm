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
    
    Route::resource('finished-products', FinishedProductController::class);
    Route::post('finished-products/{finishedProduct}/restock', [FinishedProductController::class, 'restock'])
        ->name('finished-products.restock');
    Route::get('/finished-products/calculate-max/{finishedProduct}', [FinishedProductController::class, 'calculateMax'])
        ->name('finished-products.calculate-max');
    
    // ============================================
    // SALES ROUTES - Single Page Create Form
    // ============================================
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
    Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    
    // API endpoints for AJAX
    Route::get('/api/sales/customers/{branch}', [SaleController::class, 'getCustomers'])->name('sales.api.customers');
    Route::get('/api/sales/dr-numbers/{branch}', [SaleController::class, 'getDRNumbers'])->name('sales.api.dr-numbers');
    Route::get('/api/sales/dr-products/{branch}/{drNumber}', [SaleController::class, 'getDRProducts'])->name('sales.api.dr-products');
    Route::get('/api/sales/products/{branch}/{customerName}', [SaleController::class, 'getProducts'])->name('sales.api.products');
    Route::get('/api/sales/check-dr/{branch}/{customerName}/{drNumber}', [SaleController::class, 'checkDrNumber'])->name('sales.api.check-dr');
    // ============================================
    
    Route::resource('expenses', ExpenseController::class);
    
    Route::get('/financial-reports', [FinancialReportController::class, 'index'])
        ->name('financial-reports.index');
    
    // Branches Management
    Route::resource('branches', BranchController::class);

    // Branch Inventory Routes 
    Route::get('/branch-inventory', [BranchInventoryController::class, 'index'])
        ->name('branch-inventory.index');
    Route::get('/branch-inventory/{branch}', [BranchInventoryController::class, 'show'])
        ->name('branch-inventory.show');
    Route::post('/branch-inventory/{branch}/transfer', [BranchInventoryController::class, 'transfer'])
        ->name('branch-inventory.transfer');
    Route::post('/branch-inventory/{branch}/return', [BranchInventoryController::class, 'returnStock'])
        ->name('branch-inventory.return');
    Route::post('/branch-inventory/{branch}/transfer-between-branches', [BranchInventoryController::class, 'transferBetweenBranches'])
        ->name('branch-inventory.transfer-between-branches');

    // Production MIX Routes
    Route::get('/production-mixes', [App\Http\Controllers\ProductionMixController::class, 'index'])
        ->name('production-mixes.index');
    Route::get('/production-mixes/create/{product}', [App\Http\Controllers\ProductionMixController::class, 'create'])
        ->name('production-mixes.create');
    Route::post('/production-mixes', [App\Http\Controllers\ProductionMixController::class, 'store'])
        ->name('production-mixes.store');
    Route::get('/production-mixes/{productionMix}', [App\Http\Controllers\ProductionMixController::class, 'show'])
        ->name('production-mixes.show');
    Route::post('/production-mixes/{productionMix}/complete', [App\Http\Controllers\ProductionMixController::class, 'complete'])
        ->name('production-mixes.complete');
    Route::delete('/production-mixes/{productionMix}', [App\Http\Controllers\ProductionMixController::class, 'destroy'])
        ->name('production-mixes.destroy');

    Route::resource('bank-deposits', BankDepositController::class);
});