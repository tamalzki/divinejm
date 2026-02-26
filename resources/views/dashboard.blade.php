@extends('layouts.sidebar')

@section('page-title', 'Dashboard')

@section('content')
<!-- Alerts -->
@if($alerts->count() > 0)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Low Stock Alerts!</strong>
    <ul class="mb-0 mt-2">
        @foreach($alerts as $alert)
        <li>{{ $alert->product_name }} ({{ str_replace('_', ' ', $alert->product_type) }}): {{ $alert->current_stock }} / {{ $alert->minimum_stock }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Main Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Today's Sales</p>
                    <h3>₱{{ number_format($todaySales, 2) }}</h3>
                </div>
                <i class="bi bi-cash-coin fs-1 opacity-25"></i>
            </div>
            @if($salesGrowth != 0)
            <small class="text-white-50">
                <i class="bi bi-{{ $salesGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                {{ number_format(abs($salesGrowth), 1) }}% vs yesterday
            </small>
            @endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Monthly Sales</p>
                    <h3>₱{{ number_format($monthlySales, 2) }}</h3>
                </div>
                <i class="bi bi-graph-up fs-1 opacity-25"></i>
            </div>
            <small class="text-white-50">Net Profit: ₱{{ number_format($monthlyProfit, 2) }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Total Products</p>
                    <h3>{{ number_format($totalFinishedProducts, 0) }}</h3>
                </div>
                <i class="bi bi-box-seam fs-1 opacity-25"></i>
            </div>
            <small class="text-white-50">{{ $lowStockFinishedProducts }} low stock</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Inventory Value</p>
                    <h3>₱{{ number_format($totalInventoryValue/1000, 1) }}K</h3>
                </div>
                <i class="bi bi-currency-dollar fs-1 opacity-25"></i>
            </div>
            <small class="text-white-50">{{ $totalInventory }} units total</small>
        </div>
    </div>
</div>

<!-- Stock Distribution Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-house-fill text-success fs-1 mb-2"></i>
                <h5 class="mb-1">{{ number_format($totalStockOnHand) }} units</h5>
                <p class="text-muted mb-1 small">In Warehouse</p>
                <small class="text-success">₱{{ number_format($warehouseValue, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-shop-window text-info fs-1 mb-2"></i>
                <h5 class="mb-1">{{ number_format($totalStockOut) }} units</h5>
                <p class="text-muted mb-1 small">In Branches</p>
                <small class="text-info">₱{{ number_format($branchValue, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-arrow-left-right text-primary fs-1 mb-2"></i>
                <h5 class="mb-1">{{ number_format($todayDeployments) }} / {{ number_format($todayReturns) }}</h5>
                <p class="text-muted mb-1 small">Today's Movements</p>
                <small class="text-muted">Deployed / Returned</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Sales -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Sales</h5>
            </div>
            <div class="card-body">
                @if($recentSales->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Product</th>
                                <th class="text-end">Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentSales as $sale)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $sale->invoice_number }}</span></td>
                                <td>{{ $sale->finishedProduct->name }}</td>
                                <td class="text-success fw-bold text-end">₱{{ number_format($sale->total_amount, 2) }}</td>
                                <td><small class="text-muted">{{ $sale->sale_date->format('M d, h:i A') }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">No sales yet</p>
                </div>
                @endif
            </div>
            <div class="card-footer bg-white border-0">
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-arrow-right me-2"></i>View All Sales
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Stock Movements -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Recent Stock Movements</h5>
            </div>
            <div class="card-body">
                @if($recentStockMovements->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recentStockMovements as $movement)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $movement->finishedProduct->name }}</h6>
                                <small class="text-muted">
                                    @if($movement->movement_type === 'transfer_out')
                                        <i class="bi bi-arrow-down-circle text-success"></i> Deployed to {{ $movement->branch?->name ?? 'Branch' }}
                                    @elseif($movement->movement_type === 'return')
                                        <i class="bi bi-arrow-up-circle text-warning"></i> Returned from {{ $movement->branch?->name ?? 'Branch' }}
                                    @elseif($movement->movement_type === 'branch_transfer_out')
                                        <i class="bi bi-arrow-right text-info"></i> Transfer to {{ $movement->toBranch?->name ?? 'Branch' }}
                                    @elseif($movement->movement_type === 'branch_transfer_in')
                                        <i class="bi bi-arrow-left text-primary"></i> From {{ $movement->fromBranch?->name ?? 'Branch' }}
                                    @else
                                        <i class="bi bi-arrow-repeat text-secondary"></i> {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                    @endif
                                </small>
                                <br>
                                <small class="text-muted">{{ $movement->movement_date->format('M d, h:i A') }}</small>
                            </div>
                            <div>
                                <span class="badge bg-primary">{{ $movement->quantity }} units</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">No movements yet</p>
                </div>
                @endif
            </div>
            <div class="card-footer bg-white border-0">
                <a href="{{ route('branch-inventory.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-arrow-right me-2"></i>View Branch Inventory
                </a>
            </div>
        </div>
    </div>

    <!-- Low Stock Finished Products -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock - Finished Products
                </h5>
            </div>
            <div class="card-body">
                @if($lowStockFinished->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($lowStockFinished as $product)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">{{ $product->name }}</div>
                            <small class="text-muted">
                                Warehouse: {{ $product->stock_on_hand }} units
                                @if($product->stock_out > 0)
                                    | Branches: {{ $product->stock_out }} units
                                @endif
                            </small>
                        </div>
                        <div>
                            <span class="badge bg-{{ $product->stock_on_hand == 0 ? 'danger' : 'warning' }}">
                                {{ $product->stock_on_hand }} / {{ $product->minimum_stock }}
                            </span>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center text-success py-4">
                    <i class="bi bi-check-circle display-4 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">All products well stocked!</p>
                </div>
                @endif
            </div>
            <div class="card-footer bg-white border-0">
                <a href="{{ route('finished-products.index') }}" class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-plus-circle me-2"></i>Restock Products
                </a>
            </div>
        </div>
    </div>

    <!-- Low Stock Raw Materials -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock - Raw Materials
                </h5>
            </div>
            <div class="card-body">
                @if($lowStockRaw->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($lowStockRaw as $material)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <div class="fw-bold">{{ $material->name }}</div>
                            <small class="text-muted">{{ ucfirst($material->category) }}</small>
                        </div>
                        <span class="badge bg-{{ $material->quantity == 0 ? 'danger' : 'warning' }}">
                            {{ $material->quantity }} {{ $material->unit }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center text-success py-4">
                    <i class="bi bi-check-circle display-4 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">All materials well stocked!</p>
                </div>
                @endif
            </div>
            <div class="card-footer bg-white border-0">
                <a href="{{ route('raw-materials.index') }}" class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-plus-circle me-2"></i>Restock Materials
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Branch Summary -->
@if($branches->count() > 0)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-shop me-2"></i>Branch Inventory Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($branches->take(4) as $branch)
            <div class="col-md-3 mb-3">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-shop text-primary fs-2 mb-2"></i>
                        <h6 class="mb-1">{{ $branch->name }}</h6>
                        <p class="mb-0 small text-muted">
                            {{ $branch->inventory->where('quantity', '>', 0)->count() }} products
                        </p>
                        <a href="{{ route('branch-inventory.show', $branch) }}" class="btn btn-sm btn-outline-primary mt-2">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        @if($topProductsByBranch->count() > 0)
        <hr class="my-3">
        <h6 class="text-muted mb-3">Top Products in Branches</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Branch</th>
                        <th class="text-end">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProductsByBranch as $item)
                    <tr>
                        <td class="fw-bold">{{ $item->name }}</td>
                        <td><span class="badge bg-info">{{ $item->branch_name }}</span></td>
                        <td class="text-end"><strong>{{ $item->quantity }}</strong> units</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif

<style>
.stats-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    height: 100%;
}

.stats-card h3 {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 10px 0 5px 0;
}

.stats-card p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}
</style>
@endsection