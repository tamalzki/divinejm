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
            <div class="col-md-5">
                <label class="form-label fw-bold">Start Date</label>
                <input type="date" 
                       name="start_date" 
                       id="startDate"
                       class="form-control" 
                       value="{{ $startDate instanceof \Carbon\Carbon ? $startDate->format('Y-m-d') : $startDate }}"
                       onchange="submitWithLoading()">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold">End Date</label>
                <input type="date" 
                       name="end_date" 
                       id="endDate"
                       class="form-control" 
                       value="{{ $endDate instanceof \Carbon\Carbon ? $endDate->format('Y-m-d') : $endDate }}"
                       onchange="submitWithLoading()">
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
    <!-- Summary Cards -->
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

    <!-- Additional Metrics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-cash-stack text-success fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($cashFlow, 2) }}</h5>
                    <small class="text-muted">Cash Flow</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-cart-check text-primary fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($averageTransactionValue, 2) }}</h5>
                    <small class="text-muted">Avg Transaction</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam text-warning fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($currentInventoryValue, 2) }}</h5>
                    <small class="text-muted">Inventory Value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-basket text-info fs-1 mb-2"></i>
                    <h5 class="mb-1">₱{{ number_format($totalCOGS, 2) }}</h5>
                    <small class="text-muted">Cost of Goods Sold</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales by Product -->
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
                    <p class="text-center text-muted mb-0 py-4">
                        <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                        No sales data for this period
                    </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Expenses by Category -->
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
                    <p class="text-center text-muted mb-0 py-4">
                        <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                        No expense data for this period
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Income Statement -->
    <div class="card mt-4 shadow-sm">
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

<!-- Print View - Professional Financial Report Layout -->
<div class="print-only">
    <!-- Report Header -->
    <div class="report-header">
        <h1 class="company-name">CHIPSINVENTORY</h1>
        <p class="company-tagline">Food Products Trading</p>
        <h2 class="report-title">FINANCIAL STATEMENT</h2>
        <p class="report-period">
            For the Period: 
            {{ $startDate instanceof \Carbon\Carbon ? $startDate->format('F d, Y') : $startDate }} 
            to 
            {{ $endDate instanceof \Carbon\Carbon ? $endDate->format('F d, Y') : $endDate }}
        </p>
        <p class="report-date">Report Generated: {{ now()->format('F d, Y') }}</p>
    </div>

    <hr class="report-divider">

    <!-- INCOME STATEMENT -->
    <div class="financial-section">
        <h3 class="section-title">INCOME STATEMENT</h3>
        
        <table class="financial-table">
            <tbody>
                <!-- REVENUE SECTION -->
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
                        <td style="padding-left: 50px;">{{ $product['product_name'] }} ({{ $product['quantity'] }} units)</td>
                        <td class="amount">₱{{ number_format($product['revenue'], 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="padding-left: 30px;">Sales Revenue</td>
                        <td class="amount">₱0.00</td>
                    </tr>
                @endif
                
                <tr class="subtotal-row">
                    <td><strong>Total Revenue</strong></td>
                    <td class="amount"><strong>₱{{ number_format($totalSales, 2) }}</strong></td>
                </tr>

                <tr class="spacer"><td colspan="2"></td></tr>

                <!-- COST OF GOODS SOLD -->
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

                <!-- OPERATING EXPENSES -->
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
                @else
                    <tr>
                        <td style="padding-left: 30px;">Operating Expenses</td>
                        <td class="amount">₱0.00</td>
                    </tr>
                @endif
                
                <tr class="subtotal-row">
                    <td><strong>Total Operating Expenses</strong></td>
                    <td class="amount"><strong>(₱{{ number_format($totalExpenses, 2) }})</strong></td>
                </tr>

                <tr class="spacer"><td colspan="2"></td></tr>

                <!-- NET INCOME -->
                <tr class="total-row">
                    <td><strong>NET INCOME {{ $netProfit >= 0 ? '(PROFIT)' : '(LOSS)' }}</strong></td>
                    <td class="amount"><strong style="color: {{ $netProfit >= 0 ? '#10b981' : '#dc3545' }}">₱{{ number_format($netProfit, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always;"></div>

    <!-- CASH FLOW STATEMENT -->
    <div class="financial-section">
        <h3 class="section-title">CASH FLOW STATEMENT</h3>
        
        <table class="financial-table">
            <tbody>
                <tr class="section-header">
                    <td colspan="2"><strong>OPERATING ACTIVITIES</strong></td>
                </tr>
                
                <tr>
                    <td style="padding-left: 30px;">Cash Received from Sales</td>
                    <td class="amount">₱{{ number_format($cashSales, 2) }}</td>
                </tr>
                
                <tr>
                    <td style="padding-left: 30px;">Cash Paid for Expenses</td>
                    <td class="amount">(₱{{ number_format($cashExpenses, 2) }})</td>
                </tr>
                
                <tr class="total-row">
                    <td><strong>Net Cash Flow from Operating Activities</strong></td>
                    <td class="amount"><strong style="color: {{ $cashFlow >= 0 ? '#10b981' : '#dc3545' }}">₱{{ number_format($cashFlow, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FINANCIAL SUMMARY -->
    <div class="financial-section" style="margin-top: 40px;">
        <h3 class="section-title">FINANCIAL SUMMARY & KEY RATIOS</h3>
        
        <table class="summary-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Amount / Ratio</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Revenue</td>
                    <td class="amount">₱{{ number_format($totalSales, 2) }}</td>
                </tr>
                <tr>
                    <td>Cost of Goods Sold</td>
                    <td class="amount">₱{{ number_format($totalCOGS, 2) }}</td>
                </tr>
                <tr>
                    <td>Gross Profit</td>
                    <td class="amount">₱{{ number_format($grossProfit, 2) }}</td>
                </tr>
                <tr>
                    <td>Operating Expenses</td>
                    <td class="amount">₱{{ number_format($totalExpenses, 2) }}</td>
                </tr>
                <tr class="highlight-row">
                    <td><strong>Net Profit/Loss</strong></td>
                    <td class="amount"><strong>₱{{ number_format($netProfit, 2) }}</strong></td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px; border-top: 1px solid #ccc;"><strong>Financial Ratios:</strong></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Gross Profit Margin</td>
                    <td class="amount">{{ number_format($grossProfitMargin, 2) }}%</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Net Profit Margin</td>
                    <td class="amount">{{ number_format($netProfitMargin, 2) }}%</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Operating Expense Ratio</td>
                    <td class="amount">{{ number_format($operatingExpenseRatio, 2) }}%</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 15px; border-top: 1px solid #ccc;"><strong>Additional Metrics:</strong></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Total Transactions</td>
                    <td class="amount">{{ $totalTransactions }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Average Transaction Value</td>
                    <td class="amount">₱{{ number_format($averageTransactionValue, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Current Inventory Value</td>
                    <td class="amount">₱{{ number_format($currentInventoryValue, 2) }}</td>
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
            <p><em>This is a system-generated financial report from ChipsInventory Management System.</em></p>
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
        margin: 2cm 2cm 2cm 2cm;
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
    }

    .summary-table th,
    .summary-table td {
        padding: 10px;
        border: 1px solid #000;
        text-align: left;
    }

    .summary-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .summary-table .amount {
        text-align: right;
        font-family: 'Courier New', monospace;
    }

    .summary-table .highlight-row {
        background-color: #f9f9f9;
        font-weight: bold;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
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