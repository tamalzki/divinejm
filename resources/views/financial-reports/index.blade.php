@extends('layouts.sidebar')
@section('page-title', 'Financial Report')
@section('content')

<style>
    /* ── Filter bar ── */
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .period-btn { font-size:.72rem; padding:.2rem .65rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-page); color:var(--text-secondary); cursor:pointer; text-decoration:none; white-space:nowrap; font-weight:600; transition:all .12s; }
    .period-btn:hover { background:var(--accent-faint); color:var(--accent); border-color:var(--accent); }
    .period-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    .rpt-sep  { width:1px; height:18px; background:var(--border); flex-shrink:0; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .rpt-select { padding:.22rem .48rem; font-size:.75rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-apply:hover { background:var(--accent-hover); }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }

    /* ── KPI tiles ── */
    .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    .kpi-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; position:relative; overflow:hidden; }
    .kpi-tile::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--accent); border-radius:var(--radius) var(--radius) 0 0; }
    .kpi-tile.green::before { background:#16a34a; }
    .kpi-tile.amber::before { background:#d97706; }
    .kpi-tile.red::before   { background:#dc2626; }
    .kpi-tile.blue::before  { background:#0369a1; }
    .kpi-tile.purple::before{ background:#7c3aed; }
    .kpi-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .kpi-value { font-size:1.05rem; font-weight:700; color:var(--text-primary); display:block; line-height:1.2; }
    .kpi-sub   { font-size:.67rem; color:var(--text-muted); margin-top:.2rem; display:block; }
    .kpi-value.green { color:#16a34a; }
    .kpi-value.amber { color:#d97706; }
    .kpi-value.red   { color:#dc2626; }
    .kpi-value.blue  { color:#0369a1; }

    /* ── Two-column layout ── */
    .rpt-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
    .rpt-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; margin-bottom:.75rem; }

    /* ── Section card ── */
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.48rem .85rem; display:flex; align-items:center; gap:.4rem; font-size:.74rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .sec-head i { opacity:.7; font-size:.8rem; }
    .sec-body { padding:.65rem .85rem; }
    .sec-scroll { overflow-x:auto; }

    /* ── P&L statement ── */
    .pl-table { width:100%; border-collapse:collapse; font-size:.78rem; }
    .pl-table td { padding:.32rem .5rem; border-bottom:1px solid var(--border); }
    .pl-table tr:last-child td { border-bottom:none; }
    .pl-table .pl-group td { background:var(--bg-page); font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); padding:.28rem .5rem; border-bottom:none; }
    .pl-table .pl-item td:first-child { padding-left:1.5rem; color:var(--text-secondary); }
    .pl-table .pl-sub td { font-weight:700; }
    .pl-table .pl-sub td:last-child { border-top:1px solid var(--border); }
    .pl-table .pl-total td { font-weight:800; font-size:.82rem; background:var(--accent-faint); }
    .pl-table .pl-total.profit td { background:#dcfce7; color:#15622e; }
    .pl-table .pl-total.loss td   { background:#fee2e2; color:#9b1c1c; }
    .pl-table td:last-child { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .pl-neg { color:#dc2626; }
    .pl-pos { color:#16a34a; }

    /* ── Data tables ── */
    .dt { width:100%; border-collapse:collapse; font-size:.74rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .6rem; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .dt tbody td { padding:.36rem .6rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { padding:.36rem .6rem; font-weight:700; font-size:.73rem; background:var(--bg-page); border-top:2px solid var(--border); }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }

    /* ── Pill badges ── */
    .pill { display:inline-flex; align-items:center; padding:.05rem .35rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .pill-green  { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-red    { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-amber  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-blue   { background:var(--s-info-bg);    color:var(--s-info-text); }

    /* ── Progress bar ── */
    .prog-bar { height:4px; background:var(--border); border-radius:2px; margin-top:.3rem; overflow:hidden; }
    .prog-fill { height:100%; background:var(--accent); border-radius:2px; transition:width .3s; }

    /* ── Margin meter ── */
    .margin-badge { font-size:.65rem; font-weight:700; padding:.04rem .32rem; border-radius:3px; }

    /* ── Empty state ── */
    .empty-state { text-align:center; padding:2rem 1rem; color:var(--text-muted); font-size:.78rem; }
    .empty-state i { font-size:1.8rem; display:block; opacity:.25; margin-bottom:.4rem; }

    /* ── Section full width ── */
    .rpt-full { margin-bottom:.75rem; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-bar-chart-line me-1" style="color:var(--accent)"></i>Financial Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ $startDate->format('M d, Y') }} — {{ $endDate->format('M d, Y') }}
            &nbsp;·&nbsp; {{ ucfirst($reportType) }}
        </span>
    </div>
    <button onclick="window.print()" class="btn-apply" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('financial-reports.index') }}" id="frForm">
<div class="rpt-bar">
    <span class="lbl">Period</span>
    <a href="{{ route('financial-reports.index', array_merge(request()->except('report_type'), ['report_type'=>'monthly'])) }}"
       class="period-btn {{ $reportType==='monthly' ? 'active':'' }}">Monthly</a>
    <a href="{{ route('financial-reports.index', array_merge(request()->except('report_type'), ['report_type'=>'weekly'])) }}"
       class="period-btn {{ $reportType==='weekly' ? 'active':'' }}">Weekly</a>
    <a href="{{ route('financial-reports.index', array_merge(request()->except('report_type'), ['report_type'=>'quarterly'])) }}"
       class="period-btn {{ $reportType==='quarterly' ? 'active':'' }}">Quarterly</a>
    <div class="rpt-sep"></div>
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate->format('Y-m-d') }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date"   class="date-input" value="{{ $endDate->format('Y-m-d') }}">
    <input type="hidden" name="report_type" value="{{ $reportType }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
    <button type="button" class="btn-apply" style="background:var(--bg-page);color:var(--text-secondary);border:1px solid var(--border)"
            onclick="resetDates()"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
</div>
</form>

{{-- ── KPI Row 1: P&L ── --}}
<div class="kpi-grid">
    <div class="kpi-tile">
        <span class="kpi-label">Total Revenue</span>
        <span class="kpi-value">&#8369;{{ number_format($totalSales, 2) }}</span>
        <span class="kpi-sub">{{ $totalTransactions }} transactions · avg &#8369;{{ number_format($averageTransactionValue, 0) }}</span>
    </div>
    <div class="kpi-tile amber">
        <span class="kpi-label">Gross Profit</span>
        <span class="kpi-value amber">&#8369;{{ number_format($grossProfit, 2) }}</span>
        <span class="kpi-sub">{{ number_format($grossProfitMargin, 1) }}% gross margin</span>
    </div>
    <div class="kpi-tile red">
        <span class="kpi-label">Total Expenses</span>
        <span class="kpi-value red">&#8369;{{ number_format($totalExpenses, 2) }}</span>
        <span class="kpi-sub">{{ number_format($operatingExpenseRatio, 1) }}% of revenue</span>
    </div>
    <div class="kpi-tile {{ $netProfit >= 0 ? 'green' : 'red' }}">
        <span class="kpi-label">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</span>
        <span class="kpi-value {{ $netProfit >= 0 ? 'green' : 'red' }}">&#8369;{{ number_format(abs($netProfit), 2) }}</span>
        <span class="kpi-sub">{{ number_format($netProfitMargin, 1) }}% net margin</span>
    </div>
</div>

{{-- ── KPI Row 2: Cash & Collections ── --}}
<div class="kpi-grid" style="margin-bottom:.9rem">
    <div class="kpi-tile blue">
        <span class="kpi-label">Cash in Hand</span>
        <span class="kpi-value blue">&#8369;{{ number_format($cashInHand, 2) }}</span>
        <span class="kpi-sub">After expenses &amp; deposits</span>
    </div>
    <div class="kpi-tile blue">
        <span class="kpi-label">Cash in Bank</span>
        <span class="kpi-value blue">&#8369;{{ number_format($cashInBank, 2) }}</span>
        <span class="kpi-sub">Cumulative deposits</span>
    </div>
    <div class="kpi-tile amber">
        <span class="kpi-label">To Be Collected</span>
        <span class="kpi-value amber">&#8369;{{ number_format($totalReceivables, 2) }}</span>
        <span class="kpi-sub">{{ $accountsReceivable->count() }} customers with balance</span>
    </div>
    <div class="kpi-tile green">
        <span class="kpi-label">Collection Rate</span>
        <span class="kpi-value green">{{ number_format($collectionEfficiency, 1) }}%</span>
        <span class="kpi-sub">
            {{ $paymentSummary['paid_in_full'] }} paid · {{ $paymentSummary['partial_payments'] }} partial
        </span>
    </div>
</div>

{{-- ── Row: P&L Statement + Cash Flow ── --}}
<div class="rpt-grid-2">

    {{-- Profit & Loss --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-file-earmark-text"></i> Profit &amp; Loss Statement</div>
        <div class="sec-body" style="padding:.5rem">
            <table class="pl-table">
                <tbody>
                    {{-- Revenue --}}
                    <tr class="pl-group"><td colspan="2">Revenue</td></tr>
                    <tr class="pl-item">
                        <td>Sales Revenue</td>
                        <td>&#8369;{{ number_format($totalSales, 2) }}</td>
                    </tr>
                    <tr class="pl-sub">
                        <td>Total Revenue</td>
                        <td>&#8369;{{ number_format($totalSales, 2) }}</td>
                    </tr>

                    {{-- COGS --}}
                    <tr class="pl-group"><td colspan="2">Cost of Goods Sold</td></tr>
                    <tr class="pl-item">
                        <td>Direct Product Costs</td>
                        <td class="pl-neg">(&#8369;{{ number_format($totalCOGS, 2) }})</td>
                    </tr>
                    <tr class="pl-sub">
                        <td>Gross Profit</td>
                        <td class="{{ $grossProfit >= 0 ? 'pl-pos' : 'pl-neg' }}">&#8369;{{ number_format($grossProfit, 2) }}</td>
                    </tr>

                    {{-- Expenses --}}
                    <tr class="pl-group"><td colspan="2">Operating Expenses</td></tr>
                    @forelse($expensesByCategory as $cat => $amt)
                    <tr class="pl-item">
                        <td>{{ ucfirst(str_replace('_',' ',$cat)) }}</td>
                        <td class="pl-neg">(&#8369;{{ number_format($amt, 2) }})</td>
                    </tr>
                    @empty
                    <tr class="pl-item"><td colspan="2" style="color:var(--text-muted);font-size:.72rem">No expenses recorded</td></tr>
                    @endforelse
                    <tr class="pl-sub">
                        <td>Total Expenses</td>
                        <td class="pl-neg">(&#8369;{{ number_format($totalExpenses, 2) }})</td>
                    </tr>

                    {{-- Net --}}
                    <tr class="pl-total {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                        <td>Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</td>
                        <td>&#8369;{{ number_format($netProfit, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cash Flow --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-arrow-left-right"></i> Cash Flow Summary</div>
        <div class="sec-body" style="padding:.5rem">
            <table class="pl-table">
                <tbody>
                    <tr class="pl-group"><td colspan="2">Operating Activities</td></tr>
                    <tr class="pl-item">
                        <td>Cash Sales Collected</td>
                        <td class="pl-pos">&#8369;{{ number_format($cashSales, 2) }}</td>
                    </tr>
                    <tr class="pl-item">
                        <td>Cash Expenses Paid</td>
                        <td class="pl-neg">(&#8369;{{ number_format($cashExpenses, 2) }})</td>
                    </tr>
                    <tr class="pl-item">
                        <td>Bank Deposits</td>
                        <td class="pl-neg">(&#8369;{{ number_format($bankDeposits, 2) }})</td>
                    </tr>
                    <tr class="pl-sub">
                        <td>Net Cash from Operations</td>
                        <td class="{{ $cashFlow >= 0 ? 'pl-pos':'pl-neg' }}">&#8369;{{ number_format($cashFlow, 2) }}</td>
                    </tr>

                    <tr class="pl-group"><td colspan="2">Balances</td></tr>
                    <tr class="pl-item">
                        <td>Cash in Hand</td>
                        <td>&#8369;{{ number_format($cashInHand, 2) }}</td>
                    </tr>
                    <tr class="pl-item">
                        <td>Cash in Bank</td>
                        <td>&#8369;{{ number_format($cashInBank, 2) }}</td>
                    </tr>
                    <tr class="pl-sub">
                        <td>Total Cash Position</td>
                        <td class="pl-pos">&#8369;{{ number_format($cashInHand + $cashInBank, 2) }}</td>
                    </tr>

                    <tr class="pl-group"><td colspan="2">Receivables</td></tr>
                    <tr class="pl-item">
                        <td>Total Collected</td>
                        <td class="pl-pos">&#8369;{{ number_format($paymentSummary['total_collected'], 2) }}</td>
                    </tr>
                    <tr class="pl-item">
                        <td>Pending Collection</td>
                        <td class="pl-neg">&#8369;{{ number_format($totalReceivables, 2) }}</td>
                    </tr>
                    <tr class="pl-total {{ $totalReceivables > 0 ? 'loss' : 'profit' }}">
                        <td>Collection Rate</td>
                        <td>{{ number_format($collectionEfficiency, 1) }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Sales by Product ── --}}
<div class="rpt-full sec-card" style="margin-bottom:.75rem">
    <div class="sec-head"><i class="bi bi-bar-chart"></i> Sales by Product</div>
    <div class="sec-scroll">
        @if($salesByProduct->count())
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tc">Qty Sold</th>
                    <th class="tr">Revenue</th>
                    <th class="tr">COGS</th>
                    <th class="tr">Gross Profit</th>
                    <th class="tc">Margin</th>
                    <th class="tc">% of Sales</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesByProduct as $p)
                @php $pct = $totalSales > 0 ? ($p['revenue'] / $totalSales) * 100 : 0; @endphp
                <tr>
                    <td style="font-weight:600">{{ $p['product_name'] }}</td>
                    <td class="tc">{{ number_format($p['quantity'], 0) }}</td>
                    <td class="tr">&#8369;{{ number_format($p['revenue'], 2) }}</td>
                    <td class="tr" style="color:var(--text-muted)">&#8369;{{ number_format($p['cost'], 2) }}</td>
                    <td class="tr" style="font-weight:700;color:{{ $p['profit'] >= 0 ? 'var(--s-success-text)' : 'var(--s-danger-text)' }}">
                        &#8369;{{ number_format($p['profit'], 2) }}
                    </td>
                    <td class="tc">
                        <span class="pill {{ $p['margin'] >= 30 ? 'pill-green' : ($p['margin'] >= 15 ? 'pill-amber' : 'pill-red') }}">
                            {{ number_format($p['margin'], 1) }}%
                        </span>
                    </td>
                    <td class="tc" style="min-width:80px">
                        <span style="font-size:.70rem;color:var(--text-muted)">{{ number_format($pct, 1) }}%</span>
                        <div class="prog-bar"><div class="prog-fill" style="width:{{ $pct }}%"></div></div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="tc">{{ number_format($salesByProduct->sum('quantity'), 0) }}</td>
                    <td class="tr">&#8369;{{ number_format($salesByProduct->sum('revenue'), 2) }}</td>
                    <td class="tr">&#8369;{{ number_format($totalCOGS, 2) }}</td>
                    <td class="tr" style="color:{{ $grossProfit >= 0 ? 'var(--s-success-text)':'var(--s-danger-text)' }}">
                        &#8369;{{ number_format($grossProfit, 2) }}
                    </td>
                    <td class="tc"><span class="pill pill-{{ $grossProfitMargin >= 30 ? 'green' : ($grossProfitMargin >= 15 ? 'amber' : 'red') }}">{{ number_format($grossProfitMargin, 1) }}%</span></td>
                    <td class="tc">100%</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="empty-state"><i class="bi bi-inbox"></i>No sales data for this period.</div>
        @endif
    </div>
</div>

{{-- ── Sales by Customer + AR ── --}}
<div class="rpt-grid-2">

    {{-- Sales by Customer --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-people"></i> Sales by Customer</div>
        <div class="sec-scroll">
            @if($salesByCustomer->count())
            <table class="dt">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Area</th>
                        <th class="tc">DRs</th>
                        <th class="tr">Total</th>
                        <th class="tr">Paid</th>
                        <th class="tc">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesByCustomer as $c)
                    <tr>
                        <td style="font-weight:600;font-size:.73rem">{{ $c->customer_name }}</td>
                        <td style="font-size:.70rem;color:var(--text-muted)">{{ $c->branch->name ?? '—' }}</td>
                        <td class="tc">{{ $c->transaction_count }}</td>
                        <td class="tr" style="font-weight:600">&#8369;{{ number_format($c->total_sales, 0) }}</td>
                        <td class="tr" style="color:var(--s-success-text)">&#8369;{{ number_format($c->total_paid, 0) }}</td>
                        <td class="tc">
                            @if($c->balance <= 0)
                                <span class="pill pill-green">PAID</span>
                            @elseif($c->total_paid > 0)
                                <span class="pill pill-amber">PARTIAL</span>
                            @else
                                <span class="pill pill-red">UNPAID</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td class="tc">{{ $salesByCustomer->sum('transaction_count') }}</td>
                        <td class="tr">&#8369;{{ number_format($salesByCustomer->sum('total_sales'), 0) }}</td>
                        <td class="tr" style="color:var(--s-success-text)">&#8369;{{ number_format($salesByCustomer->sum('total_paid'), 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="empty-state"><i class="bi bi-people"></i>No customer data.</div>
            @endif
        </div>
    </div>

    {{-- Accounts Receivable --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-clock-history"></i> Accounts Receivable</div>
        <div class="sec-scroll">
            @if($accountsReceivable->count())
            <table class="dt">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Area</th>
                        <th class="tc">Unpaid</th>
                        <th class="tr">Amount Due</th>
                        <th class="tc">Aging</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accountsReceivable as $ar)
                    <tr>
                        <td style="font-weight:600;font-size:.73rem">{{ $ar->customer_name }}</td>
                        <td style="font-size:.70rem;color:var(--text-muted)">{{ $ar->branch->name ?? '—' }}</td>
                        <td class="tc">{{ $ar->unpaid_count }}</td>
                        <td class="tr" style="font-weight:700;color:var(--s-danger-text)">&#8369;{{ number_format($ar->total_receivable, 2) }}</td>
                        <td class="tc">
                            <span class="pill {{ $ar->days_outstanding > 30 ? 'pill-red' : ($ar->days_outstanding > 7 ? 'pill-amber' : 'pill-blue') }}">
                                {{ $ar->days_outstanding }}d
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td class="tc">{{ $accountsReceivable->sum('unpaid_count') }}</td>
                        <td class="tr" style="color:var(--s-danger-text)">&#8369;{{ number_format($totalReceivables, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="empty-state"><i class="bi bi-check-circle"></i>All collections done!</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Production Performance ── --}}
<div class="rpt-full sec-card" style="margin-bottom:.75rem">
    <div class="sec-head"><i class="bi bi-gear-wide-connected"></i> Production Performance</div>
    <div class="sec-scroll">
        @if($productionSummary->count())
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tc">Batches</th>
                    <th class="tc">Expected</th>
                    <th class="tc">Actual</th>
                    <th class="tc">Rejected</th>
                    <th class="tc">Good Output</th>
                    <th class="tc">Variance</th>
                    <th class="tc">Reject %</th>
                    <th class="tc">Yield %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productionSummary as $prod)
                <tr>
                    <td style="font-weight:600">{{ $prod['product_name'] }}</td>
                    <td class="tc">{{ $prod['batches_count'] }}</td>
                    <td class="tc" style="color:var(--text-muted)">{{ number_format($prod['expected_output']) }}</td>
                    <td class="tc" style="font-weight:600">{{ number_format($prod['actual_output']) }}</td>
                    <td class="tc" style="color:var(--s-danger-text)">{{ number_format($prod['rejected_quantity']) }}</td>
                    <td class="tc" style="font-weight:700;color:var(--s-success-text)">{{ number_format($prod['good_output']) }}</td>
                    <td class="tc">
                        <span class="pill {{ $prod['variance_percent'] >= 0 ? 'pill-green' : 'pill-red' }}">
                            {{ $prod['variance_percent'] > 0 ? '+' : '' }}{{ number_format($prod['variance_percent'], 1) }}%
                        </span>
                    </td>
                    <td class="tc">
                        <span class="pill {{ $prod['rejection_rate'] < 5 ? 'pill-green' : ($prod['rejection_rate'] < 10 ? 'pill-amber' : 'pill-red') }}">
                            {{ number_format($prod['rejection_rate'], 1) }}%
                        </span>
                    </td>
                    <td class="tc">
                        <span class="pill {{ $prod['yield_rate'] >= 90 ? 'pill-green' : ($prod['yield_rate'] >= 80 ? 'pill-amber' : 'pill-red') }}">
                            {{ number_format($prod['yield_rate'], 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="tc">{{ $productionSummary->sum('batches_count') }}</td>
                    <td class="tc">{{ number_format($productionSummary->sum('expected_output')) }}</td>
                    <td class="tc">{{ number_format($productionSummary->sum('actual_output')) }}</td>
                    <td class="tc" style="color:var(--s-danger-text)">{{ number_format($productionSummary->sum('rejected_quantity')) }}</td>
                    <td class="tc" style="color:var(--s-success-text)">{{ number_format($productionSummary->sum('good_output')) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="empty-state"><i class="bi bi-inbox"></i>No production data for this period.</div>
        @endif
    </div>
</div>

{{-- ── Production by Period + Expenses ── --}}
<div class="rpt-grid-2">

    {{-- Expenses by Category --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-wallet2"></i> Expenses by Category</div>
        <div class="sec-scroll">
            @if($expensesByCategory->count())
            <table class="dt">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="tr">Amount</th>
                        <th class="tc">% of Total</th>
                        <th class="tc">% of Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensesByCategory as $cat => $amt)
                    @php
                        $pctOfExp = $totalExpenses > 0 ? ($amt / $totalExpenses) * 100 : 0;
                        $pctOfRev = $totalSales > 0 ? ($amt / $totalSales) * 100 : 0;
                    @endphp
                    <tr>
                        <td style="font-weight:600">{{ ucfirst(str_replace('_',' ',$cat)) }}</td>
                        <td class="tr" style="color:var(--s-danger-text)">&#8369;{{ number_format($amt, 2) }}</td>
                        <td class="tc">
                            <span style="font-size:.70rem">{{ number_format($pctOfExp, 1) }}%</span>
                            <div class="prog-bar"><div class="prog-fill" style="width:{{ $pctOfExp }};background:#dc2626"></div></div>
                        </td>
                        <td class="tc" style="font-size:.70rem;color:var(--text-muted)">{{ number_format($pctOfRev, 1) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>TOTAL</td>
                        <td class="tr" style="color:var(--s-danger-text)">&#8369;{{ number_format($totalExpenses, 2) }}</td>
                        <td class="tc">100%</td>
                        <td class="tc" style="color:var(--text-muted)">{{ number_format($operatingExpenseRatio, 1) }}%</td>
                    </tr>
                </tfoot>
            </table>
            @else
            <div class="empty-state"><i class="bi bi-inbox"></i>No expenses recorded.</div>
            @endif
        </div>
    </div>

    {{-- Production by Period --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-calendar3"></i> Production by {{ ucfirst($reportType) }}</div>
        <div class="sec-scroll">
            @if($productionByPeriod->count())
            <table class="dt">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="tc">Batches</th>
                        <th class="tc">Output</th>
                        <th class="tc">Rejected</th>
                        <th>Top Products</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionByPeriod as $period)
                    <tr>
                        <td style="font-weight:600;white-space:nowrap">{{ $period['period'] }}</td>
                        <td class="tc">{{ $period['batches'] }}</td>
                        <td class="tc" style="font-weight:600">{{ number_format($period['total_output']) }}</td>
                        <td class="tc" style="color:var(--s-danger-text)">{{ number_format($period['total_rejected']) }}</td>
                        <td>
                            @foreach($period['products']->take(2) as $pp)
                                <span class="pill pill-blue" style="margin:.1rem .1rem 0 0">{{ $pp['name'] }}: {{ $pp['quantity'] }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state"><i class="bi bi-inbox"></i>No production data.</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Deliveries by Customer ── --}}
@if($deliveriesByCustomer->count())
<div class="rpt-full sec-card" style="margin-bottom:.75rem">
    <div class="sec-head"><i class="bi bi-truck"></i> Deliveries by Customer</div>
    <div class="sec-scroll">
        <table class="dt">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="tc">DRs</th>
                    <th class="tc">Product Lines</th>
                    <th class="tc">Total Qty Delivered</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveriesByCustomer as $del)
                <tr>
                    <td style="font-weight:600">{{ $del['customer_name'] }}</td>
                    <td class="tc">{{ $del['deliveries_count'] }}</td>
                    <td class="tc">{{ $del['products_count'] }}</td>
                    <td class="tc" style="font-weight:700;color:var(--accent)">{{ number_format($del['total_delivered']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
function resetDates() {
    var now = new Date();
    var first = new Date(now.getFullYear(), now.getMonth(), 1);
    var last  = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    function fmt(d) { return d.toISOString().split('T')[0]; }
    document.querySelector('input[name="start_date"]').value = fmt(first);
    document.querySelector('input[name="end_date"]').value   = fmt(last);
    document.getElementById('frForm').submit();
}
</script>

@endsection