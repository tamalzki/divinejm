@extends('layouts.sidebar')

@section('page-title', 'Financial Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up-arrow me-2"></i>Financial Reports</h2>
    <button onclick="window.print()" class="btn btn-success">
        <i class="bi bi-printer-fill me-2"></i>Print Report
    </button>
</div>

<!-- Date Filter -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form action="{{ route('financial-reports.index') }}" method="GET" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <label class="form-label fw-bold">Start Date</label>
                <input type="date" 
                       name="start_date" 
                       id="startDate"
                       class="form-control" 
                       value="{{ $startDate instanceof \Carbon\Carbon ? $startDate->format('Y-m-d') : $startDate }}"
                       onchange="submitWithLoading()">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">End Date</label>
                <input type="date" 
                       name="end_date" 
                       id="endDate"
                       class="form-control" 
                       value="{{ $endDate instanceof \Carbon\Carbon ? $endDate->format('Y-m-d') : $endDate }}"
                       onchange="submitWithLoading()">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">Report Type</label>
                <select name="report_type" class="form-select" onchange="submitWithLoading()">
                    <option value="monthly" {{ $reportType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="weekly" {{ $reportType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="quarterly" {{ $reportType == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-secondary w-100" onclick="resetDates()">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                </button>
            </div>
        </form>
        
        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center mt-3" style="display: none;">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2 mb-0">Loading report...</p>
        </div>
    </div>
</div>

<!-- Screen View -->
<div class="screen-only">
    <!-- Summary Cards Row 1 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <p class="mb-2">Total Sales</p>
                <h3>₱{{ number_format($totalSales, 2) }}</h3>
                <small class="text-white-50">{{ $totalTransactions }} transactions</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <p class="mb-2">Gross Profit</p>
                <h3>₱{{ number_format($grossProfit, 2) }}</h3>
                <small class="text-white-50">{{ number_format($grossProfitMargin, 1) }}% margin</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <p class="mb-2">Expenses</p>
                <h3>₱{{ number_format($totalExpenses, 2) }}</h3>
                <small class="text-white-50">{{ number_format($operatingExpenseRatio, 1) }}% of sales</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, {{ $netProfit >= 0 ? '#10b981, #059669' : '#ef4444, #dc2626' }});">
                <p class="mb-2">Net Profit</p>
                <h3>₱{{ number_format($netProfit, 2) }}</h3>
                <small class="text-white-50">{{ number_format($netProfitMargin, 1) }}% margin</small>
            </div>
        </div>
    </div>

    <!-- Summary Cards Row 2 - NEW -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-wallet2 text-success fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($cashInHand, 2) }}</h5>
                    <small class="text-muted">Cash in Hand</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-bank text-primary fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($cashInBank, 2) }}</h5>
                    <small class="text-muted">Cash in Bank</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history text-warning fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($totalReceivables, 2) }}</h5>
                    <small class="text-muted">To Be Collected</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-percent text-info fs-1 mb-2"></i>
                    <h5 class="mb-1">{{ number_format($collectionEfficiency, 1) }}%</h5>
                    <small class="text-muted">Collection Rate</small>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Sales by Customer Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Sales by Customer</h5>
                </div>
                <div class="card-body">
                    @if($salesByCustomer->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Area</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Total Sales</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByCustomer as $customer)
                                <tr>
                                    <td class="fw-bold">{{ $customer->customer_name }}</td>
                                    <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $customer->transaction_count }}</td>
                                    <td class="text-end fw-bold">₱{{ number_format($customer->total_sales, 2) }}</td>
                                    <td class="text-end text-success">₱{{ number_format($customer->total_paid, 2) }}</td>
                                    <td class="text-end {{ $customer->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                        ₱{{ number_format($customer->balance, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($customer->balance <= 0)
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($customer->total_paid > 0)
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="2">TOTAL</td>
                                    <td class="text-center">{{ $salesByCustomer->sum('transaction_count') }}</td>
                                    <td class="text-end">₱{{ number_format($salesByCustomer->sum('total_sales'), 2) }}</td>
                                    <td class="text-end text-success">₱{{ number_format($salesByCustomer->sum('total_paid'), 2) }}</td>
                                    <td class="text-end text-danger">₱{{ number_format($salesByCustomer->sum('balance'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">No customer sales data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Deliveries by Customer -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Deliveries by Customer</h5>
                </div>
                <div class="card-body">
                    @if($deliveriesByCustomer->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-center">DRs</th>
                                    <th class="text-center">Products</th>
                                    <th class="text-center">Total Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deliveriesByCustomer as $delivery)
                                <tr>
                                    <td class="fw-bold">{{ $delivery['customer_name'] }}</td>
                                    <td class="text-center">{{ $delivery['deliveries_count'] }}</td>
                                    <td class="text-center">{{ $delivery['products_count'] }}</td>
                                    <td class="text-center fw-bold">{{ $delivery['total_delivered'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">No delivery data</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- NEW: Accounts Receivable -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Accounts Receivable</h5>
                </div>
                <div class="card-body">
                    @if($accountsReceivable->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-center">Unpaid</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accountsReceivable as $ar)
                                <tr>
                                    <td class="fw-bold">{{ $ar->customer_name }}</td>
                                    <td class="text-center">{{ $ar->unpaid_count }}</td>
                                    <td class="text-end text-danger fw-bold">₱{{ number_format($ar->total_receivable, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $ar->days_outstanding > 30 ? 'danger' : ($ar->days_outstanding > 7 ? 'warning' : 'info') }}">
                                            {{ $ar->days_outstanding }}d
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $accountsReceivable->sum('unpaid_count') }}</td>
                                    <td class="text-end text-danger">₱{{ number_format($totalReceivables, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">All collections completed!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Production Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Production Performance</h5>
                </div>
                <div class="card-body">
                    @if($productionSummary->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Batches</th>
                                    <th class="text-center">Expected</th>
                                    <th class="text-center">Actual</th>
                                    <th class="text-center">Rejected</th>
                                    <th class="text-center">Good Output</th>
                                    <th class="text-center">Variance %</th>
                                    <th class="text-center">Rejection %</th>
                                    <th class="text-center">Yield %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionSummary as $prod)
                                <tr>
                                    <td class="fw-bold">{{ $prod['product_name'] }}</td>
                                    <td class="text-center">{{ $prod['batches_count'] }}</td>
                                    <td class="text-center">{{ number_format($prod['expected_output']) }}</td>
                                    <td class="text-center fw-bold">{{ number_format($prod['actual_output']) }}</td>
                                    <td class="text-center text-danger">{{ number_format($prod['rejected_quantity']) }}</td>
                                    <td class="text-center text-success fw-bold">{{ number_format($prod['good_output']) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $prod['variance_percent'] >= 0 ? 'success' : 'danger' }}">
                                            {{ $prod['variance_percent'] > 0 ? '+' : '' }}{{ number_format($prod['variance_percent'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $prod['rejection_rate'] < 5 ? 'success' : ($prod['rejection_rate'] < 10 ? 'warning' : 'danger') }}">
                                            {{ number_format($prod['rejection_rate'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $prod['yield_rate'] >= 90 ? 'success' : ($prod['yield_rate'] >= 80 ? 'warning' : 'danger') }}">
                                            {{ number_format($prod['yield_rate'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">No production data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Production by Period -->
    @if($productionByPeriod->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Production Summary ({{ ucfirst($reportType) }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th class="text-center">Batches</th>
                                    <th class="text-center">Total Output</th>
                                    <th class="text-center">Rejected</th>
                                    <th>Top Products</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productionByPeriod as $period)
                                <tr>
                                    <td class="fw-bold">{{ $period['period'] }}</td>
                                    <td class="text-center">{{ $period['batches'] }}</td>
                                    <td class="text-center fw-bold">{{ number_format($period['total_output']) }}</td>
                                    <td class="text-center text-danger">{{ number_format($period['total_rejected']) }}</td>
                                    <td>
                                        <small>
                                            @foreach($period['products']->take(3) as $p)
                                                <span class="badge bg-secondary">{{ $p['name'] }}: {{ $p['quantity'] }}</span>
                                            @endforeach
                                        </small>
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

    <!-- Existing sections (Sales by Product & Expenses) -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Sales by Product</h5>
                </div>
                <div class="card-body">
                    @if($salesByProduct->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Profit</th>
                                    <th class="text-center">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesByProduct as $product)
                                <tr>
                                    <td class="fw-bold">{{ $product['product_name'] }}</td>
                                    <td class="text-center">{{ $product['quantity'] }}</td>
                                    <td class="text-end text-success fw-bold">₱{{ number_format($product['revenue'], 2) }}</td>
                                    <td class="text-end fw-bold" style="color: {{ $product['profit'] >= 0 ? '#10b981' : '#ef4444' }}">
                                        ₱{{ number_format($product['profit'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $product['margin'] >= 30 ? 'success' : ($product['margin'] >= 15 ? 'warning' : 'danger') }}">
                                            {{ number_format($product['margin'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-center">{{ $salesByProduct->sum('quantity') }}</td>
                                    <td class="text-end text-success">₱{{ number_format($salesByProduct->sum('revenue'), 2) }}</td>
                                    <td class="text-end" style="color: {{ $salesByProduct->sum('profit') >= 0 ? '#10b981' : '#ef4444' }}">
                                        ₱{{ number_format($salesByProduct->sum('profit'), 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">No sales data</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Expenses by Category</h5>
                </div>
                <div class="card-body">
                    @if($expensesByCategory->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expensesByCategory as $category => $amount)
                                <tr>
                                    <td class="fw-bold">{{ ucfirst(str_replace('_', ' ', $category)) }}</td>
                                    <td class="text-end text-danger fw-bold">₱{{ number_format($amount, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            {{ $totalExpenses > 0 ? number_format(($amount / $totalExpenses) * 100, 1) : 0 }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TOTAL</td>
                                    <td class="text-end text-danger">₱{{ number_format($totalExpenses, 2) }}</td>
                                    <td class="text-center">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted py-4 mb-0">No expense data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Income Statement -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Income Statement</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold">Revenue (Sales)</td>
                        <td class="text-end text-success fw-bold fs-5">₱{{ number_format($totalSales, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted ps-4">Less: Cost of Goods Sold</td>
                        <td class="text-end text-muted">(₱{{ number_format($totalCOGS, 2) }})</td>
                    </tr>
                    <tr class="border-top">
                        <td class="fw-bold">Gross Profit</td>
                        <td class="text-end fw-bold" style="color: #f59e0b">₱{{ number_format($grossProfit, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-danger ps-4">Less: Operating Expenses</td>
                        <td class="text-end text-danger">(₱{{ number_format($totalExpenses, 2) }})</td>
                    </tr>
                    <tr class="border-top border-2">
                        <td class="fw-bold fs-5">Net Income {{ $netProfit >= 0 ? '(Profit)' : '(Loss)' }}</td>
                        <td class="text-end fw-bold fs-4" style="color: {{ $netProfit >= 0 ? '#10b981' : '#ef4444' }}">
                            ₱{{ number_format($netProfit, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print View - Comprehensive Financial Report -->
<div class="print-only">
    <!-- Report Header -->
    <div class="report-header">
        <h1 class="company-name">CHIPSINVENTORY</h1>
        <p class="company-tagline">Food Products Trading</p>
        <h2 class="report-title">COMPREHENSIVE FINANCIAL REPORT</h2>
        <p class="report-period">
            For the Period: 
            {{ $startDate instanceof \Carbon\Carbon ? $startDate->format('F d, Y') : $startDate }} 
            to 
            {{ $endDate instanceof \Carbon\Carbon ? $endDate->format('F d, Y') : $endDate }}
        </p>
        <p class="report-period">Report Type: {{ ucfirst($reportType) }}</p>
        <p class="report-date">Report Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <hr class="report-divider">

    <!-- EXECUTIVE SUMMARY -->
    <div class="financial-section">
        <h3 class="section-title">EXECUTIVE SUMMARY</h3>
        
        <table class="summary-table">
            <tbody>
                <tr>
                    <td><strong>Total Revenue</strong></td>
                    <td class="amount">₱{{ number_format($totalSales, 2) }}</td>
                    <td><strong>Gross Profit</strong></td>
                    <td class="amount">₱{{ number_format($grossProfit, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Expenses</strong></td>
                    <td class="amount">₱{{ number_format($totalExpenses, 2) }}</td>
                    <td><strong>Net Profit</strong></td>
                    <td class="amount"><strong>₱{{ number_format($netProfit, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Cash in Hand</strong></td>
                    <td class="amount">₱{{ number_format($cashInHand, 2) }}</td>
                    <td><strong>Cash in Bank</strong></td>
                    <td class="amount">₱{{ number_format($cashInBank, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>To Be Collected</strong></td>
                    <td class="amount">₱{{ number_format($totalReceivables, 2) }}</td>
                    <td><strong>Collection Rate</strong></td>
                    <td class="amount">{{ number_format($collectionEfficiency, 1) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- INCOME STATEMENT -->
    <div class="financial-section">
        <h3 class="section-title">INCOME STATEMENT</h3>
        
        <table class="financial-table">
            <tbody>
                <tr class="section-header">
                    <td colspan="2"><strong>REVENUE</strong></td>
                </tr>
                
                @if($salesByProduct->count() > 0)
                    <tr>
                        <td style="padding-left: 30px;">Sales Revenue:</td>
                        <td></td>
                    </tr>
                    @foreach($salesByProduct as $product)
                    <tr>
                        <td style="padding-left: 50px;">{{ $product['product_name'] }} ({{ number_format($product['quantity']) }} units)</td>
                        <td class="amount">₱{{ number_format($product['revenue'], 2) }}</td>
                    </tr>
                    @endforeach
                @endif
                
                <tr class="subtotal-row">
                    <td><strong>Total Revenue</strong></td>
                    <td class="amount"><strong>₱{{ number_format($totalSales, 2) }}</strong></td>
                </tr>

                <tr class="spacer"><td colspan="2"></td></tr>

                <tr class="section-header">
                    <td colspan="2"><strong>COST OF GOODS SOLD</strong></td>
                </tr>
                <tr>
                    <td style="padding-left: 30px;">Direct Product Costs</td>
                    <td class="amount">₱{{ number_format($totalCOGS, 2) }}</td>
                </tr>
                <tr class="subtotal-row">
                    <td><strong>Total COGS</strong></td>
                    <td class="amount"><strong>(₱{{ number_format($totalCOGS, 2) }})</strong></td>
                </tr>

                <tr class="subtotal-row" style="background-color: #f9f9f9;">
                    <td><strong>GROSS PROFIT</strong></td>
                    <td class="amount"><strong>₱{{ number_format($grossProfit, 2) }}</strong></td>
                </tr>

                <tr class="spacer"><td colspan="2"></td></tr>

                <tr class="section-header">
                    <td colspan="2"><strong>OPERATING EXPENSES</strong></td>
                </tr>
                
                @if($expensesByCategory->count() > 0)
                    @foreach($expensesByCategory as $category => $amount)
                    <tr>
                        <td style="padding-left: 30px;">{{ ucfirst(str_replace('_', ' ', $category)) }}</td>
                        <td class="amount">₱{{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endif
                
                <tr class="subtotal-row">
                    <td><strong>Total Operating Expenses</strong></td>
                    <td class="amount"><strong>(₱{{ number_format($totalExpenses, 2) }})</strong></td>
                </tr>

                <tr class="spacer"><td colspan="2"></td></tr>

                <tr class="total-row">
                    <td><strong>NET INCOME {{ $netProfit >= 0 ? '(PROFIT)' : '(LOSS)' }}</strong></td>
                    <td class="amount"><strong style="color: {{ $netProfit >= 0 ? '#10b981' : '#dc3545' }}">₱{{ number_format($netProfit, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always;"></div>

    @if($salesByCustomer->count() > 0)
    <div class="financial-section">
        <h3 class="section-title">SALES BY CUSTOMER</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Area</th>
                    <th class="text-center">Trans.</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesByCustomer as $customer)
                <tr>
                    <td>{{ $customer->customer_name }}</td>
                    <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $customer->transaction_count }}</td>
                    <td class="text-right">₱{{ number_format($customer->total_sales, 2) }}</td>
                    <td class="text-right">₱{{ number_format($customer->total_paid, 2) }}</td>
                    <td class="text-right">₱{{ number_format($customer->balance, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="text-center"><strong>{{ $salesByCustomer->sum('transaction_count') }}</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($salesByCustomer->sum('total_sales'), 2) }}</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($salesByCustomer->sum('total_paid'), 2) }}</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($salesByCustomer->sum('balance'), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if($accountsReceivable->count() > 0)
    <div class="financial-section">
        <h3 class="section-title">ACCOUNTS RECEIVABLE (AGING ANALYSIS)</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Area</th>
                    <th class="text-center">Unpaid</th>
                    <th class="text-right">Amount Due</th>
                    <th class="text-center">Days</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accountsReceivable as $ar)
                <tr>
                    <td>{{ $ar->customer_name }}</td>
                    <td>{{ $ar->branch->name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $ar->unpaid_count }}</td>
                    <td class="text-right">₱{{ number_format($ar->total_receivable, 2) }}</td>
                    <td class="text-center">{{ $ar->days_outstanding }}d</td>
                    <td class="text-center">{{ $ar->days_outstanding > 30 ? 'OVERDUE' : ($ar->days_outstanding > 7 ? 'DUE SOON' : 'CURRENT') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2"><strong>TOTAL</strong></td>
                    <td class="text-center"><strong>{{ $accountsReceivable->sum('unpaid_count') }}</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($totalReceivables, 2) }}</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <div style="page-break-before: always;"></div>

    @if($productionSummary->count() > 0)
    <div class="financial-section">
        <h3 class="section-title">PRODUCTION PERFORMANCE ANALYSIS</h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Batches</th>
                    <th class="text-center">Expected</th>
                    <th class="text-center">Actual</th>
                    <th class="text-center">Rejected</th>
                    <th class="text-center">Var %</th>
                    <th class="text-center">Rej %</th>
                    <th class="text-center">Yield %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productionSummary as $prod)
                <tr>
                    <td>{{ $prod['product_name'] }}</td>
                    <td class="text-center">{{ $prod['batches_count'] }}</td>
                    <td class="text-center">{{ number_format($prod['expected_output']) }}</td>
                    <td class="text-center">{{ number_format($prod['actual_output']) }}</td>
                    <td class="text-center">{{ number_format($prod['rejected_quantity']) }}</td>
                    <td class="text-center">{{ $prod['variance_percent'] > 0 ? '+' : '' }}{{ number_format($prod['variance_percent'], 1) }}%</td>
                    <td class="text-center">{{ number_format($prod['rejection_rate'], 1) }}%</td>
                    <td class="text-center">{{ number_format($prod['yield_rate'], 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- CASH FLOW -->
    <div class="financial-section">
        <h3 class="section-title">CASH FLOW STATEMENT</h3>
        
        <table class="financial-table">
            <tbody>
                <tr class="section-header">
                    <td colspan="2"><strong>OPERATING ACTIVITIES</strong></td>
                </tr>
                <tr>
                    <td style="padding-left: 30px;">Cash from Sales</td>
                    <td class="amount">₱{{ number_format($cashSales, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 30px;">Cash for Expenses</td>
                    <td class="amount">(₱{{ number_format($cashExpenses, 2) }})</td>
                </tr>
                <tr>
                    <td style="padding-left: 30px;">Bank Deposits</td>
                    <td class="amount">(₱{{ number_format($bankDeposits, 2) }})</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Cash in Hand</strong></td>
                    <td class="amount"><strong>₱{{ number_format($cashInHand, 2) }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Cash in Bank</strong></td>
                    <td class="amount"><strong>₱{{ number_format($cashInBank, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Report Footer -->
    <div class="report-footer">
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">Prepared By</p>
                <p class="signature-name">{{ Auth::user()->name }}</p>
                <p class="signature-date">{{ now()->format('F d, Y') }}</p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">Reviewed By</p>
                <p class="signature-name">_________________</p>
                <p class="signature-date">Date: ___________</p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">Approved By</p>
                <p class="signature-name">_________________</p>
                <p class="signature-date">Date: ___________</p>
            </div>
        </div>
        
        <div class="footer-note">
            <p><em>This is a system-generated report from ChipsInventory Management System.</em></p>
            <p><em>All amounts are in Philippine Pesos (PHP/₱)</em></p>
        </div>
    </div>
</div>

<script>
function submitWithLoading() {
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('startDate').disabled = true;
    document.getElementById('endDate').disabled = true;
    document.getElementById('filterForm').submit();
}

function resetDates() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
    
    submitWithLoading();
}
</script>

<style>
.stats-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stats-card h3 {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 10px 0 0 0;
}

.stats-card p {
    margin: 0;
    opacity: 0.9;
}

.screen-only {
    display: block;
}

.print-only {
    display: none;
}

@media print {
    .sidebar,
    .topbar,
    .no-print,
    .screen-only {
        display: none !important;
    }

    .print-only {
        display: block !important;
    }

    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }

    .content-area {
        padding: 0 !important;
    }

    @page {
        size: A4;
        margin: 2cm;
    }

    body {
        background: white !important;
        color: black !important;
        font-family: 'Times New Roman', serif;
        font-size: 11pt;
    }

    .report-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .company-name {
        font-size: 24pt;
        font-weight: bold;
        margin: 0;
        letter-spacing: 2px;
    }

    .company-tagline {
        font-size: 10pt;
        margin: 5px 0;
        color: #666;
    }

    .report-title {
        font-size: 16pt;
        font-weight: bold;
        margin: 20px 0 10px 0;
        text-decoration: underline;
    }

    .report-period {
        font-size: 11pt;
        margin: 5px 0;
    }

    .report-date {
        font-size: 9pt;
        color: #666;
        margin: 5px 0;
    }

    .report-divider {
        border: 0;
        border-top: 2px solid #000;
        margin: 20px 0;
    }

    .financial-section {
        margin-bottom: 30px;
        page-break-inside: avoid;
    }

    .section-title {
        font-size: 14pt;
        font-weight: bold;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 2px solid #000;
    }

    .financial-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .financial-table td {
        padding: 8px 10px;
        border: none;
    }

    .financial-table .section-header td {
        padding-top: 15px;
        font-weight: bold;
        border-bottom: 1px solid #000;
    }

    .financial-table .amount {
        text-align: right;
        width: 30%;
        font-family: 'Courier New', monospace;
    }

    .financial-table .subtotal-row {
        border-top: 1px solid #000;
        font-weight: bold;
    }

    .financial-table .subtotal-row td {
        padding-top: 10px;
    }

    .financial-table .total-row {
        border-top: 3px double #000;
        border-bottom: 3px double #000;
        font-size: 12pt;
    }

    .financial-table .total-row td {
        padding: 12px 10px;
    }

    .financial-table .spacer td {
        padding: 10px 0;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .summary-table td {
        padding: 8px;
        border: 1px solid #000;
    }

    .summary-table .amount {
        text-align: right;
        font-family: 'Courier New', monospace;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 10pt;
    }

    .data-table th,
    .data-table td {
        padding: 6px 8px;
        border: 1px solid #000;
    }

    .data-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: left;
    }

    .data-table .text-center {
        text-align: center !important;
    }

    .data-table .text-right {
        text-align: right !important;
    }

    .data-table tfoot {
        border-top: 2px solid #000;
        font-weight: bold;
    }

    .report-footer {
        margin-top: 50px;
        page-break-inside: avoid;
    }

    .signature-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .signature-box {
        width: 30%;
        text-align: center;
    }

    .signature-line {
        border-bottom: 1px solid #000;
        margin: 50px 10px 5px 10px;
    }

    .signature-label {
        font-size: 9pt;
        margin: 5px 0;
        font-weight: bold;
    }

    .signature-name {
        font-size: 10pt;
        margin: 3px 0;
    }

    .signature-date {
        font-size: 9pt;
        margin: 3px 0;
        color: #666;
    }

    .footer-note {
        text-align: center;
        font-size: 9pt;
        color: #666;
        border-top: 1px solid #ccc;
        padding-top: 15px;
    }

    .footer-note p {
        margin: 3px 0;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
@endsection