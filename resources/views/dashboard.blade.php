@extends('layouts.sidebar')

@section('page-title', 'Dashboard')

@section('content')

<!-- CRITICAL ALERTS BANNER -->
@if($overdueReceivables->count() > 0 || $outOfStockProducts->count() > 0 || $expiringProducts->count() > 0 || $recentBadOrders->count() > 0)
<div class="alert alert-danger border-0 shadow-sm mb-4 p-3">
    <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
        <div class="flex-grow-1">
            <strong class="d-block mb-2">CRITICAL ALERTS - Immediate Action Required</strong>
            <div class="d-flex gap-4 flex-wrap">
                @if($overdueReceivables->count() > 0)
                <div class="d-flex align-items-center">
                    <i class="bi bi-clock-history text-white me-2"></i>
                    <span>
                        <strong>{{ $overdueReceivables->count() }}</strong> Overdue Payments
                        <small class="ms-1">(₱{{ number_format($overdueReceivables->sum('overdue_amount'), 0) }})</small>
                    </span>
                </div>
                @endif

                @if($outOfStockProducts->count() > 0)
                <div class="d-flex align-items-center">
                    <i class="bi bi-inbox text-white me-2"></i>
                    <span><strong>{{ $outOfStockProducts->count() }}</strong> Out of Stock</span>
                </div>
                @endif

                @if($expiringProducts->count() > 0)
                <div class="d-flex align-items-center">
                    <i class="bi bi-calendar-x text-white me-2"></i>
                    <span>
                        <strong>{{ $expiringProducts->count() }}</strong> Expiring Soon
                        <small class="ms-1">({{ $expiringProducts->sum(function($p) { return $p->stock_on_hand + $p->stock_out; }) }} units)</small>
                    </span>
                </div>
                @endif

                @if($recentBadOrders->count() > 0)
                <div class="d-flex align-items-center">
                    <i class="bi bi-x-circle text-white me-2"></i>
                    <span>
                        <strong>{{ $recentBadOrders->sum('total_bo') }}</strong> Bad Orders
                        <small class="ms-1">({{ $recentBadOrders->count() }} products)</small>
                    </span>
                </div>
                @endif
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#alertDetails"></button>
    </div>
    
    <!-- Collapsible Details -->
    <div class="collapse mt-3" id="alertDetails">
        <div class="row g-3">
            @if($overdueReceivables->count() > 0)
            <div class="col-md-6">
                <div class="card bg-white">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-clock-history text-danger me-2"></i>
                            Overdue Payments
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="list-group list-group-flush">
                            @foreach($overdueReceivables->take(3) as $receivable)
                            <div class="list-group-item px-0 py-2 small">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong class="text-dark">{{ $receivable->customer_name }}</strong>
                                        <small class="text-muted d-block">{{ $receivable->branch->name ?? 'N/A' }}</small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-danger">₱{{ number_format($receivable->overdue_amount, 0) }}</strong>
                                        <small class="text-muted d-block">{{ $receivable->days_overdue }}d overdue</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <a href="{{ route('sales.index') }}?payment_status=to_be_collected" class="btn btn-sm btn-danger w-100 mt-2">
                            View All Receivables
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if($outOfStockProducts->count() > 0)
            <div class="col-md-6">
                <div class="card bg-white">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-inbox text-danger me-2"></i>
                            Out of Stock
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="list-group list-group-flush">
                            @foreach($outOfStockProducts->take(3) as $product)
                            <div class="list-group-item px-0 py-2 small">
                                <strong class="text-dark">{{ $product->name }}</strong>
                                <small class="text-muted d-block">{{ $product->stock_out }} units in branches</small>
                            </div>
                            @endforeach
                        </div>
                        <a href="{{ route('finished-products.index') }}" class="btn btn-sm btn-warning w-100 mt-2">
                            Restock Now
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if($expiringProducts->count() > 0)
            <div class="col-md-6">
                <div class="card bg-white">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-calendar-x text-warning me-2"></i>
                            Expiring Soon
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="list-group list-group-flush">
                            @foreach($expiringProducts->take(3) as $product)
                            <div class="list-group-item px-0 py-2 small">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong class="text-dark">{{ $product->name }}</strong>
                                        <small class="text-muted d-block">{{ $product->stock_on_hand + $product->stock_out }} units</small>
                                    </div>
                                    <span class="badge bg-warning">{{ $product->days_until_expiry }}d left</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($recentBadOrders->count() > 0)
            <div class="col-md-6">
                <div class="card bg-white">
                    <div class="card-header bg-transparent border-0 py-2">
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-x-circle text-danger me-2"></i>
                            Recent Bad Orders
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-2">
                                <thead>
                                    <tr class="small">
                                        <th>Product</th>
                                        <th>DR#</th>
                                        <th>Batch</th>
                                        <th class="text-center">BO Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentBadOrders->take(5) as $bo)
                                    <tr class="small">
                                        <td><strong>{{ $bo['product']->name }}</strong></td>
                                        <td>
                                            @if($bo['dr_numbers']->count() > 0)
                                                @foreach($bo['dr_numbers']->take(2) as $dr)
                                                    <span class="badge bg-secondary">{{ $dr }}</span>
                                                @endforeach
                                                @if($bo['dr_numbers']->count() > 2)
                                                    <small class="text-muted">+{{ $bo['dr_numbers']->count() - 2 }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($bo['batch_numbers']->count() > 0)
                                                @foreach($bo['batch_numbers']->take(2) as $batch)
                                                    <span class="badge bg-info">{{ $batch }}</span>
                                                @endforeach
                                                @if($bo['batch_numbers']->count() > 2)
                                                    <small class="text-muted">+{{ $bo['batch_numbers']->count() - 2 }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td class="text-center"><span class="badge bg-danger">{{ $bo['total_bo'] }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<!-- KEY PERFORMANCE INDICATORS -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Today's Sales</p>
                    <h3>₱{{ number_format($todaySales, 2) }}</h3>
                    <small class="text-white-50">Collected: ₱{{ number_format($todayCollected, 2) }}</small>
                </div>
                <i class="bi bi-cash-coin fs-1 opacity-25"></i>
            </div>
            @if($salesGrowth != 0)
            <div class="mt-2">
                <span class="badge bg-{{ $salesGrowth >= 0 ? 'success' : 'danger' }}">
                    <i class="bi bi-{{ $salesGrowth >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ number_format(abs($salesGrowth), 1) }}% vs yesterday
                </span>
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Monthly Revenue</p>
                    <h3>₱{{ number_format($monthlySales, 2) }}</h3>
                    <small class="text-white-50">Profit: ₱{{ number_format($monthlyProfit, 2) }}</small>
                </div>
                <i class="bi bi-graph-up fs-1 opacity-25"></i>
            </div>
            <div class="mt-2">
                <span class="badge bg-light text-primary">
                    <i class="bi bi-percent"></i>
                    {{ number_format($collectionRate, 1) }}% collected
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">To Be Collected</p>
                    <h3>₱{{ number_format($totalReceivables, 2) }}</h3>
                    <small class="text-white-50">
                        @if($overdueReceivables->count() > 0)
                            {{ $overdueReceivables->count() }} overdue accounts
                        @else
                            All current
                        @endif
                    </small>
                </div>
                <i class="bi bi-clock-history fs-1 opacity-25"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-2">Inventory Value</p>
                    <h3>₱{{ number_format($totalInventoryValue/1000, 1) }}K</h3>
                    <small class="text-white-50">{{ number_format($totalInventory) }} units</small>
                </div>
                <i class="bi bi-box-seam fs-1 opacity-25"></i>
            </div>
            @if($zeroStockProducts > 0)
            <div class="mt-2">
                <span class="badge bg-danger">{{ $zeroStockProducts }} products out of stock</span>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- CRITICAL ACTIONS NEEDED -->
<div class="row mb-4">
    <!-- Overdue Receivables -->
    @if($overdueReceivables->count() > 0)
    <div class="col-md-6">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    OVERDUE PAYMENTS - Collect Immediately
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($overdueReceivables as $receivable)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">{{ $receivable->customer_name }}</h6>
                                <small class="text-muted">
                                    {{ $receivable->branch->name ?? 'N/A' }} | 
                                    {{ $receivable->overdue_count }} invoice(s)
                                </small>
                            </div>
                            <div class="text-end">
                                <strong class="text-danger">₱{{ number_format($receivable->overdue_amount, 2) }}</strong>
                                <br>
                                <span class="badge bg-danger">{{ $receivable->days_overdue }} days overdue</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('sales.index') }}?payment_status=to_be_collected" class="btn btn-sm btn-danger w-100">
                    <i class="bi bi-cash-stack me-2"></i>View All Receivables
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Products Needing Production -->
    @if($needsProduction->count() > 0)
    <div class="col-md-6">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning">
                <h6 class="mb-0">
                    <i class="bi bi-gear me-2"></i>
                    URGENT PRODUCTION NEEDED
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($needsProduction as $product)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <br>
                                <small class="text-muted">
                                    Warehouse: {{ $product->stock_on_hand }} / Min: {{ $product->minimum_stock }}
                                </small>
                            </div>
                            <span class="badge bg-danger">CRITICAL</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('production-mix.index') }}" class="btn btn-sm btn-warning w-100">
                    <i class="bi bi-plus-circle me-2"></i>Start Production
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- PRODUCTION & QUALITY METRICS -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Production Performance (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <i class="bi bi-box-seam text-primary fs-2"></i>
                        <h4 class="mt-2">{{ $productionStats['batches_completed'] }}</h4>
                        <p class="text-muted mb-0 small">Batches Completed</p>
                    </div>
                    <div class="col-md-3">
                        <i class="bi bi-check-circle text-success fs-2"></i>
                        <h4 class="mt-2">{{ number_format($productionStats['total_output']) }}</h4>
                        <p class="text-muted mb-0 small">Total Output</p>
                    </div>
                    <div class="col-md-3">
                        <i class="bi bi-x-circle text-danger fs-2"></i>
                        <h4 class="mt-2">{{ number_format($productionStats['total_rejected']) }}</h4>
                        <p class="text-muted mb-0 small">Rejected Units</p>
                    </div>
                    <div class="col-md-3">
                        <i class="bi bi-percent text-warning fs-2"></i>
                        <h4 class="mt-2 {{ $productionStats['rejection_rate'] > 10 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($productionStats['rejection_rate'], 1) }}%
                        </h4>
                        <p class="text-muted mb-0 small">Rejection Rate</p>
                    </div>
                </div>

                @if($recentBadOrders->count() > 0)
                <hr>
                <h6 class="text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Products with High Bad Orders (Last 7 Days)
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>DR Numbers</th>
                                <th>Batch Numbers</th>
                                <th class="text-center">Total BO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBadOrders as $bo)
                            <tr>
                                <td><strong>{{ $bo['product']->name }}</strong></td>
                                <td>
                                    @if($bo['dr_numbers']->count() > 0)
                                        @foreach($bo['dr_numbers']->take(3) as $dr)
                                            <span class="badge bg-secondary">{{ $dr }}</span>
                                        @endforeach
                                        @if($bo['dr_numbers']->count() > 3)
                                            <small class="text-muted">+{{ $bo['dr_numbers']->count() - 3 }} more</small>
                                        @endif
                                    @else
                                        <small class="text-muted">No DR</small>
                                    @endif
                                </td>
                                <td>
                                    @if($bo['batch_numbers']->count() > 0)
                                        @foreach($bo['batch_numbers']->take(3) as $batch)
                                            <span class="badge bg-info">{{ $batch }}</span>
                                        @endforeach
                                        @if($bo['batch_numbers']->count() > 3)
                                            <small class="text-muted">+{{ $bo['batch_numbers']->count() - 3 }} more</small>
                                        @endif
                                    @else
                                        <small class="text-muted">No Batch</small>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge bg-danger">{{ $bo['total_bo'] }} units</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- TOP PERFORMERS -->
<div class="row mb-4">
    <!-- Top Selling Products -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Best Selling Products (This Month)</h6>
            </div>
            <div class="card-body">
                @if($topSellingProducts->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($topSellingProducts as $index => $item)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'primary') }} me-2">
                                    #{{ $index + 1 }}
                                </span>
                                <strong>{{ $item->finishedProduct->name }}</strong>
                                <br>
                                <small class="text-muted">{{ number_format($item->total_sold) }} units sold</small>
                            </div>
                            <strong class="text-success">₱{{ number_format($item->total_revenue, 2) }}</strong>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-muted py-4 mb-0">No sales this month</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Top Customers (This Month)</h6>
            </div>
            <div class="card-body">
                @if($topCustomers->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($topCustomers as $index => $customer)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'primary') }} me-2">
                                    #{{ $index + 1 }}
                                </span>
                                <strong>{{ $customer->customer_name }}</strong>
                                <br>
                                <small class="text-muted">{{ $customer->purchase_count }} purchases</small>
                            </div>
                            <strong class="text-success">₱{{ number_format($customer->total_spent, 2) }}</strong>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-muted py-4 mb-0">No customers this month</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- BRANCH PERFORMANCE -->
@if($branchSales->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-shop me-2"></i>Branch Performance (This Month)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Sales Count</th>
                                <th class="text-end">Total Sales</th>
                                <th class="text-end">Collected</th>
                                <th class="text-center">Collection Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchSales as $branch)
                            <tr>
                                <td><strong>{{ $branch->branch->name ?? 'N/A' }}</strong></td>
                                <td class="text-center">{{ $branch->sales_count }}</td>
                                <td class="text-end">₱{{ number_format($branch->total_sales, 2) }}</td>
                                <td class="text-end text-success">₱{{ number_format($branch->total_collected, 2) }}</td>
                                <td class="text-center">
                                    @php
                                        $rate = $branch->total_sales > 0 ? ($branch->total_collected / $branch->total_sales) * 100 : 0;
                                    @endphp
                                    <span class="badge bg-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}">
                                        {{ number_format($rate, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- RECENT ACTIVITY -->
<div class="row mb-4">
    <!-- Recent Sales -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Sales</h6>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @if($recentSales->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recentSales as $sale)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>DR# {{ $sale->dr_number }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $sale->customer_name }} | {{ $sale->branch->name ?? 'N/A' }}
                                    <br>
                                    {{ $sale->sale_date->format('M d, h:i A') }}
                                </small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success">₱{{ number_format($sale->total_amount, 2) }}</strong>
                                <br>
                                <span class="badge bg-{{ $sale->payment_status_badge }}">
                                    {{ $sale->payment_status_label }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-muted py-4 mb-0">No recent sales</p>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    View All Sales
                </a>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="col-md-6">
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alert</h6>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @if($lowStockFinished->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($lowStockFinished as $product)
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <br>
                                <small class="text-muted">
                                    Warehouse: {{ $product->stock_on_hand }} 
                                    @if($product->stock_out > 0)
                                        | Branches: {{ $product->stock_out }}
                                    @endif
                                </small>
                            </div>
                            <span class="badge bg-{{ $product->stock_on_hand == 0 ? 'danger' : 'warning' }}">
                                {{ $product->stock_on_hand }} / {{ $product->minimum_stock }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-success py-4 mb-0">All products well stocked!</p>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('finished-products.index') }}" class="btn btn-sm btn-warning w-100">
                    Manage Inventory
                </a>
            </div>
        </div>
    </div>
</div>

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

.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
@endsection