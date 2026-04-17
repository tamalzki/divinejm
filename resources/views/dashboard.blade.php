@extends('layouts.sidebar')
@section('page-title', 'Dashboard')
@section('content')

<style>
    /* ── KPI tiles ── */
    .kpi-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    .kpi-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
    .kpi-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; position:relative; overflow:hidden; }
    .kpi-tile::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:var(--radius) var(--radius) 0 0; }
    .kpi-tile.t-accent::before { background:var(--accent); }
    .kpi-tile.t-green::before  { background:#16a34a; }
    .kpi-tile.t-amber::before  { background:#d97706; }
    .kpi-tile.t-red::before    { background:#dc2626; }
    .kpi-tile.t-blue::before   { background:#0369a1; }
    .kpi-tile.t-purple::before { background:#7c3aed; }
    .kpi-tile.t-brown::before  { background:#92400e; }
    .kpi-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.18rem; }
    .kpi-value { font-size:1.05rem; font-weight:800; color:var(--text-primary); display:block; line-height:1.2; }
    .kpi-value.c-accent { color:var(--accent); }
    .kpi-value.c-green  { color:#16a34a; }
    .kpi-value.c-amber  { color:#d97706; }
    .kpi-value.c-red    { color:#dc2626; }
    .kpi-value.c-blue   { color:#0369a1; }
    .kpi-value.c-purple { color:#7c3aed; }
    .kpi-sub { font-size:.67rem; color:var(--text-muted); margin-top:.2rem; display:block; }
    .kpi-growth { font-size:.64rem; font-weight:700; margin-top:.28rem; display:inline-flex; align-items:center; gap:.18rem; padding:.04rem .28rem; border-radius:3px; }
    .kpi-growth.up      { background:#dcfce7; color:#15622e; }
    .kpi-growth.down    { background:#fee2e2; color:#9b1c1c; }
    .kpi-growth.neutral { background:var(--bg-page); color:var(--text-muted); }

    /* ── Alert banner ── */
    .alert-banner { background:#fee2e2; border:1px solid #fca5a5; border-radius:var(--radius); padding:.65rem .9rem; margin-bottom:.9rem; }
    .alert-banner-head { display:flex; align-items:center; justify-content:space-between; }
    .alert-banner-title { display:flex; align-items:center; gap:.45rem; font-size:.76rem; font-weight:700; color:#9b1c1c; }
    .alert-chips { display:flex; gap:.35rem; flex-wrap:wrap; margin-top:.5rem; }
    .alert-chip { display:inline-flex; align-items:center; gap:.28rem; padding:.18rem .52rem; border-radius:4px; font-size:.70rem; font-weight:600; background:#fff; border:1px solid #fca5a5; color:#9b1c1c; cursor:pointer; user-select:none; transition:background .1s; }
    .alert-chip:hover { background:#fee2e2; }
    .alert-chip i { font-size:.72rem; }
    .alert-detail { margin-top:.55rem; padding:.55rem .7rem; background:#fff; border:1px solid #fca5a5; border-radius:var(--radius); display:none; }
    .alert-detail.open { display:block; }
    .alert-dismiss { background:none; border:none; cursor:pointer; color:#9b1c1c; font-size:.8rem; padding:.1rem .2rem; opacity:.7; }
    .alert-dismiss:hover { opacity:1; }

    /* ── Section card ── */
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; display:flex; align-items:center; justify-content:space-between; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .sec-head i { opacity:.65; }
    .sec-head a { font-size:.66rem; font-weight:600; color:rgba(255,255,255,.6); text-decoration:none; letter-spacing:0; text-transform:none; }
    .sec-head a:hover { color:#fff; }
    .sec-head.red-head   { background:#dc2626; }
    .sec-head.amber-head { background:#b45309; }
    .sec-head.brown-head { background:#92400e; }

    /* ── Data table ── */
    .dt { width:100%; border-collapse:collapse; font-size:.74rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.36rem .65rem; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
    .dt tbody td { padding:.35rem .65rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { padding:.35rem .65rem; font-weight:700; font-size:.72rem; background:var(--bg-page); border-top:2px solid var(--border); }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }

    /* ── Pills ── */
    .pill { display:inline-flex; align-items:center; padding:.05rem .32rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .pill-green  { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-red    { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-amber  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-blue   { background:var(--s-info-bg);    color:var(--s-info-text); }
    .pill-purple { background:#ede9fe; color:#6d28d9; }

    /* ── Rank badge ── */
    .rank { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; font-size:.60rem; font-weight:800; flex-shrink:0; }
    .rank-1 { background:#fef3c7; color:#92400e; }
    .rank-2 { background:#f3f4f6; color:#374151; }
    .rank-3 { background:#fff7ed; color:#9a3412; }
    .rank-n { background:var(--bg-page); color:var(--text-muted); border:1px solid var(--border); }

    /* ── Progress bar ── */
    .prog-bar { height:3px; background:var(--border); border-radius:2px; margin-top:.25rem; overflow:hidden; }
    .prog-fill { height:100%; border-radius:2px; }
    .prog-fill.green  { background:#16a34a; }
    .prog-fill.accent { background:var(--accent); }
    .prog-fill.red    { background:#dc2626; }
    .prog-fill.amber  { background:#d97706; }

    /* ── List row ── */
    .dlist-item { display:flex; justify-content:space-between; align-items:center; padding:.38rem 0; border-bottom:1px solid var(--border); }
    .dlist-item:last-child { border-bottom:none; }

    /* ── Production strip ── */
    .prod-strip { display:grid; grid-template-columns:repeat(4,1fr); gap:.5rem; margin-bottom:.6rem; }
    .prod-metric { text-align:center; padding:.48rem .4rem; background:var(--bg-page); border-radius:var(--radius); border:1px solid var(--border); }
    .prod-metric-val { font-size:.92rem; font-weight:800; display:block; }
    .prod-metric-lbl { font-size:.59rem; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); display:block; margin-top:.1rem; }

    /* ── Action card footer ── */
    .crit-foot { padding:.4rem .85rem; background:var(--bg-page); border-top:1px solid var(--border); }
    .btn-sm-red   { font-size:.70rem; font-weight:600; padding:.22rem .65rem; border-radius:4px; background:#dc2626; color:#fff; border:none; cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-sm-amber { font-size:.70rem; font-weight:600; padding:.22rem .65rem; border-radius:4px; background:#b45309; color:#fff; border:none; cursor:pointer; text-decoration:none; display:inline-block; }

    /* ── Scrollable section body ── */
    .sec-scroll-body { max-height:320px; overflow-y:auto; padding:.5rem .75rem; }
    .sec-scroll { overflow-x:auto; }

    /* ── Empty state ── */
    .empty-sm { text-align:center; padding:1.5rem .5rem; color:var(--text-muted); font-size:.76rem; }
    .empty-sm i { font-size:1.5rem; display:block; opacity:.22; margin-bottom:.3rem; }
    .empty-sm.ok { color:#16a34a; }
    .empty-sm.ok i { opacity:.35; }

    /* ── Quick links (reports) ── */
    .dash-quick { display:flex; flex-wrap:wrap; gap:.35rem; justify-content:flex-end; align-items:center; }
    .dash-quick a {
        font-size:.68rem; font-weight:600; padding:.22rem .55rem; border-radius:4px;
        background:var(--bg-card); border:1px solid var(--border); color:var(--text-secondary);
        text-decoration:none; white-space:nowrap; transition:background .12s,border-color .12s,color .12s;
    }
    .dash-quick a:hover { background:var(--accent-faint); border-color:var(--accent); color:var(--accent); }

    @media (max-width: 992px) {
        .kpi-grid-4 { grid-template-columns:repeat(2,1fr); }
    }
    @media (max-width: 520px) {
        .kpi-grid-4 { grid-template-columns:1fr; }
    }
</style>

{{-- ══ PAGE HEADER ══ --}}
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-speedometer2 me-1" style="color:var(--accent)"></i>Dashboard
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">{{ now()->format('l, F d, Y') }}</span>
    </div>
    <div class="dash-quick no-print">
        <a href="{{ route('reports.index') }}"><i class="bi bi-graph-up-arrow me-1"></i>Reports</a>
    </div>
</div>

{{-- ══ CRITICAL ALERTS BANNER ══ --}}
@if($overdueReceivables->count() > 0 || $outOfStockProducts->count() > 0 || $expiringProducts->count() > 0 || $recentBadOrders->count() > 0)
<div class="alert-banner" id="alertBanner">
    <div class="alert-banner-head">
        <div class="alert-banner-title">
            <i class="bi bi-exclamation-triangle-fill"></i> Action Required
        </div>
        <button class="alert-dismiss" onclick="document.getElementById('alertBanner').style.display='none'">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="alert-chips">
        @if($overdueReceivables->count() > 0)
        <div class="alert-chip" onclick="toggleAlert('al-overdue')">
            <i class="bi bi-clock-history"></i>
            {{ $overdueReceivables->count() }} Overdue &middot; &#8369;{{ number_format($overdueReceivables->sum('overdue_amount'), 0) }}
        </div>
        @endif
        @if($outOfStockProducts->count() > 0)
        <div class="alert-chip" onclick="toggleAlert('al-oos')">
            <i class="bi bi-inbox"></i> {{ $outOfStockProducts->count() }} Out of Stock
        </div>
        @endif
        @if($expiringProducts->count() > 0)
        <div class="alert-chip" onclick="toggleAlert('al-expiry')">
            <i class="bi bi-calendar-x"></i> {{ $expiringProducts->count() }} Expiring Soon
        </div>
        @endif
        @if($recentBadOrders->count() > 0)
        <div class="alert-chip" onclick="toggleAlert('al-bo')">
            <i class="bi bi-x-circle"></i> {{ $recentBadOrders->sum('total_bo') }} Bad Orders
        </div>
        @endif
    </div>

    @if($overdueReceivables->count() > 0)
    <div class="alert-detail" id="al-overdue">
        <table class="dt" style="font-size:.71rem">
            <thead>
                <tr><th>Customer</th><th>Area</th><th class="tc">Invoices</th><th class="tr">Amount Due</th><th class="tc">Days</th></tr>
            </thead>
            <tbody>
                @foreach($overdueReceivables as $r)
                <tr>
                    <td style="font-weight:600">{{ $r->customer_name }}</td>
                    <td style="color:var(--text-muted)">{{ $r->branch->name ?? '—' }}</td>
                    <td class="tc">{{ $r->overdue_count }}</td>
                    <td class="tr" style="font-weight:700;color:#dc2626">&#8369;{{ number_format($r->overdue_amount, 0) }}</td>
                    <td class="tc"><span class="pill pill-red">{{ $r->days_overdue }}d</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($outOfStockProducts->count() > 0)
    <div class="alert-detail" id="al-oos">
        @foreach($outOfStockProducts as $p)
        <div class="dlist-item" style="font-size:.73rem">
            <span style="font-weight:600">{{ $p->name }}</span>
            <span style="font-size:.68rem;color:var(--text-muted)">{{ $p->stock_out }} units still in branches</span>
        </div>
        @endforeach
    </div>
    @endif

    @if($expiringProducts->count() > 0)
    <div class="alert-detail" id="al-expiry">
        @foreach($expiringProducts as $p)
        <div class="dlist-item" style="font-size:.73rem">
            <div>
                <span style="font-weight:600">{{ $p->name }}</span>
                <span style="font-size:.67rem;color:var(--text-muted);margin-left:.35rem">
                    {{ $p->stock_on_hand + $p->stock_out }} units total
                </span>
            </div>
            <span class="pill pill-amber">{{ $p->days_until_expiry }}d left</span>
        </div>
        @endforeach
    </div>
    @endif

    @if($recentBadOrders->count() > 0)
    <div class="alert-detail" id="al-bo">
        <table class="dt" style="font-size:.71rem">
            <thead>
                <tr><th>Product</th><th>DR #s</th><th>Batch #s</th><th class="tc">BO Qty</th></tr>
            </thead>
            <tbody>
                @foreach($recentBadOrders as $bo)
                <tr>
                    <td style="font-weight:600">{{ $bo['product']->name }}</td>
                    <td>
                        @foreach($bo['dr_numbers']->take(3) as $dr)
                            <span class="pill pill-blue" style="margin:.05rem">{{ $dr }}</span>
                        @endforeach
                        @if($bo['dr_numbers']->count() > 3)
                            <span style="font-size:.63rem;color:var(--text-muted)">+{{ $bo['dr_numbers']->count()-3 }}</span>
                        @endif
                    </td>
                    <td>
                        @foreach($bo['batch_numbers']->take(3) as $b)
                            <span class="pill pill-purple" style="margin:.05rem">{{ $b }}</span>
                        @endforeach
                        @if($bo['batch_numbers']->count() > 3)
                            <span style="font-size:.63rem;color:var(--text-muted)">+{{ $bo['batch_numbers']->count()-3 }}</span>
                        @endif
                    </td>
                    <td class="tc"><span class="pill pill-red">{{ $bo['total_bo'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

{{-- ══ KPI ROW 1 — Financial ══ --}}
<div class="kpi-grid-4">
    <div class="kpi-tile t-accent">
        <span class="kpi-label">Today's Sales</span>
        <span class="kpi-value c-accent">&#8369;{{ number_format($todaySales, 2) }}</span>
        <span class="kpi-sub">Collected: &#8369;{{ number_format($todayCollected, 2) }}</span>
        @if($salesGrowth != 0)
        <span class="kpi-growth {{ $salesGrowth >= 0 ? 'up' : 'down' }}">
            <i class="bi bi-arrow-{{ $salesGrowth >= 0 ? 'up' : 'down' }}"></i>
            {{ number_format(abs($salesGrowth), 1) }}% vs yesterday
        </span>
        @else
        <span class="kpi-growth neutral"><i class="bi bi-dash"></i> Same as yesterday</span>
        @endif
    </div>
    <div class="kpi-tile t-green">
        <span class="kpi-label">Monthly Revenue</span>
        <span class="kpi-value c-green">&#8369;{{ number_format($monthlySales, 2) }}</span>
        <span class="kpi-sub">Net profit: &#8369;{{ number_format($monthlyProfit, 2) }}</span>
        @if(($monthlyLineDiscounts ?? 0) > 0 || ($monthlyDrLess ?? 0) > 0)
        <span class="kpi-sub" style="font-size:.60rem;line-height:1.35;color:var(--text-muted)">
            @if(($monthlyLineDiscounts ?? 0) > 0)
                Item discounts −&#8369;{{ number_format($monthlyLineDiscounts, 0) }}
            @endif
            @if(($monthlyDrLess ?? 0) > 0)
                @if(($monthlyLineDiscounts ?? 0) > 0)<span> · </span>@endif
                DR Less −&#8369;{{ number_format($monthlyDrLess, 0) }}
            @endif
        </span>
        @endif
        <span class="kpi-growth {{ $collectionRate >= 80 ? 'up' : 'down' }}">
            <i class="bi bi-percent"></i> {{ number_format($collectionRate, 1) }}% collected
        </span>
    </div>
    <div class="kpi-tile t-amber">
        <span class="kpi-label">To Be Collected</span>
        <span class="kpi-value c-amber">&#8369;{{ number_format($totalReceivables, 2) }}</span>
        <span class="kpi-sub">
            @if($overdueReceivables->count() > 0)
                <span style="color:#dc2626;font-weight:600">{{ $overdueReceivables->count() }} overdue accounts</span>
            @else
                All current
            @endif
        </span>
    </div>
    <div class="kpi-tile t-purple">
        <span class="kpi-label">Inventory Value</span>
        <span class="kpi-value c-purple">&#8369;{{ number_format($totalInventoryValue/1000, 1) }}K</span>
        <span class="kpi-sub">{{ number_format($totalInventory) }} total units</span>
        @if($zeroStockProducts > 0)
        <span class="kpi-growth down"><i class="bi bi-inbox"></i> {{ $zeroStockProducts }} out of stock</span>
        @endif
    </div>
</div>

{{-- ══ KPI ROW 2 — Operations ══ --}}
<div class="kpi-grid-4" style="margin-bottom:.9rem">
    <div class="kpi-tile t-blue">
        <span class="kpi-label">Warehouse Stock</span>
        <span class="kpi-value c-blue">{{ number_format($totalStockOnHand) }}</span>
        <span class="kpi-sub">units on hand &nbsp;·&nbsp; &#8369;{{ number_format($warehouseValue/1000, 1) }}K</span>
    </div>
    <div class="kpi-tile t-blue">
        <span class="kpi-label">Branch Stock</span>
        <span class="kpi-value c-blue">{{ number_format($totalStockOut) }}</span>
        <span class="kpi-sub">units deployed &nbsp;·&nbsp; &#8369;{{ number_format($branchValue/1000, 1) }}K</span>
    </div>
    <div class="kpi-tile {{ $productionStats['rejection_rate'] > 10 ? 't-red' : 't-green' }}">
        <span class="kpi-label">Legacy mix output (7d)</span>
        <span class="kpi-value {{ $productionStats['rejection_rate'] > 10 ? 'c-red' : 'c-green' }}">
            {{ number_format($productionStats['total_output']) }}
        </span>
        <span class="kpi-sub">
            {{ $productionStats['batches_completed'] }} completed batches
            &nbsp;·&nbsp; {{ number_format($productionStats['rejection_rate'], 1) }}% reject
        </span>
    </div>
    <div class="kpi-tile t-accent">
        <span class="kpi-label">Today's Movements</span>
        <span class="kpi-value c-accent">{{ number_format($todayDeployments) }}</span>
        <span class="kpi-sub">deployed &nbsp;·&nbsp; {{ number_format($todayReturns) }} returned / BO</span>
    </div>
</div>

{{-- ══ CRITICAL ACTIONS ══ --}}
@if($overdueReceivables->count() > 0 || $needsProduction->count() > 0)
<div class="kpi-grid-2">

    @if($overdueReceivables->count() > 0)
    <div class="sec-card">
        <div class="sec-head red-head">
            <span><i class="bi bi-exclamation-triangle-fill me-1"></i> Overdue Payments — Collect Immediately</span>
        </div>
        <div>
            @foreach($overdueReceivables as $r)
            <div class="dlist-item" style="padding:.42rem .85rem">
                <div>
                    <span style="font-size:.76rem;font-weight:600">{{ $r->customer_name }}</span>
                    <span style="font-size:.67rem;color:var(--text-muted);margin-left:.35rem">
                        {{ $r->branch->name ?? '—' }} &middot; {{ $r->overdue_count }} invoice(s)
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:.4rem">
                    <span style="font-weight:700;color:#dc2626;font-size:.78rem">&#8369;{{ number_format($r->overdue_amount, 0) }}</span>
                    <span class="pill pill-red">{{ $r->days_overdue }}d</span>
                </div>
            </div>
            @endforeach
        </div>
        <div class="crit-foot">
            <a href="{{ route('ar.index') }}" class="btn-sm-red">
                <i class="bi bi-cash-stack me-1"></i>View All Receivables
            </a>
        </div>
    </div>
    @endif

    @if($needsProduction->count() > 0)
    <div class="sec-card">
        <div class="sec-head amber-head">
            <span><i class="bi bi-gear-fill me-1"></i> Urgent Production Needed</span>
        </div>
        <div>
            @foreach($needsProduction as $p)
            @php $pct = $p->minimum_stock > 0 ? min(100, ($p->stock_on_hand / $p->minimum_stock) * 100) : 0; @endphp
            <div class="dlist-item" style="padding:.42rem .85rem">
                <div>
                    <span style="font-size:.76rem;font-weight:600">{{ $p->name }}</span>
                    <div style="font-size:.67rem;color:var(--text-muted)">
                        Warehouse: {{ $p->stock_on_hand }} &nbsp;·&nbsp; Min: {{ $p->minimum_stock }}
                    </div>
                    <div class="prog-bar" style="width:80px">
                        <div class="prog-fill red" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                <span class="pill pill-red">CRITICAL</span>
            </div>
            @endforeach
        </div>
        <div class="crit-foot" style="display:flex;flex-wrap:wrap;gap:.45rem;align-items:center">
            <a href="{{ route('daily-production.create') }}" class="btn-sm-amber">
                <i class="bi bi-clipboard2-data me-1"></i>Daily Production
            </a>
            <a href="{{ route('packer-packs.create') }}" class="btn-sm-amber" style="background:var(--brand-deep)">
                <i class="bi bi-box-seam me-1"></i>Packers Report
            </a>
        </div>
    </div>
    @endif

</div>
@endif

{{-- ══ TOP SELLERS + TOP CUSTOMERS ══ --}}
<div class="kpi-grid-2">

    <div class="sec-card">
        <div class="sec-head">
            <span><i class="bi bi-trophy me-1"></i> Best Selling Products</span>
            <span style="font-size:.62rem;opacity:.55;text-transform:none;letter-spacing:0">This Month</span>
        </div>
        <div class="sec-scroll-body">
            @if($topSellingProducts->count())
            @php $maxRev = $topSellingProducts->max('total_revenue'); @endphp
            @foreach($topSellingProducts as $i => $item)
            <div class="dlist-item">
                <div style="display:flex;align-items:center;gap:.5rem;min-width:0">
                    <span class="rank {{ $i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n')) }}">#{{ $i+1 }}</span>
                    <div style="min-width:0">
                        <div style="font-size:.75rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $item->finishedProduct->name }}
                        </div>
                        <div style="font-size:.65rem;color:var(--text-muted)">{{ number_format($item->total_sold) }} units sold</div>
                        <div class="prog-bar" style="width:90px">
                            <div class="prog-fill accent" style="width:{{ $maxRev > 0 ? ($item->total_revenue/$maxRev)*100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
                <span style="font-size:.78rem;font-weight:700;color:#16a34a;white-space:nowrap">
                    &#8369;{{ number_format($item->total_revenue, 0) }}
                </span>
            </div>
            @endforeach
            @else
            <div class="empty-sm"><i class="bi bi-trophy"></i>No sales this month.</div>
            @endif
        </div>
    </div>

    <div class="sec-card">
        <div class="sec-head">
            <span><i class="bi bi-people me-1"></i> Top Customers</span>
            <span style="font-size:.62rem;opacity:.55;text-transform:none;letter-spacing:0">This Month</span>
        </div>
        <div class="sec-scroll-body">
            @if($topCustomers->count())
            @php $maxSpent = $topCustomers->max('total_spent'); @endphp
            @foreach($topCustomers as $i => $c)
            <div class="dlist-item">
                <div style="display:flex;align-items:center;gap:.5rem;min-width:0">
                    <span class="rank {{ $i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n')) }}">#{{ $i+1 }}</span>
                    <div style="min-width:0">
                        <div style="font-size:.75rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $c->customer_name }}
                        </div>
                        <div style="font-size:.65rem;color:var(--text-muted)">{{ $c->purchase_count }} purchases</div>
                        <div class="prog-bar" style="width:90px">
                            <div class="prog-fill green" style="width:{{ $maxSpent > 0 ? ($c->total_spent/$maxSpent)*100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
                <span style="font-size:.78rem;font-weight:700;color:#16a34a;white-space:nowrap">
                    &#8369;{{ number_format($c->total_spent, 0) }}
                </span>
            </div>
            @endforeach
            @else
            <div class="empty-sm"><i class="bi bi-people"></i>No customers this month.</div>
            @endif
        </div>
    </div>

</div>

{{-- ══ PRODUCTION PERFORMANCE ══ --}}
<div class="sec-card" style="margin-bottom:.75rem">
    <div class="sec-head">
        <span><i class="bi bi-gear-wide-connected me-1"></i> Legacy production mix</span>
        <span style="font-size:.62rem;opacity:.55;text-transform:none;letter-spacing:0">Last 7 days</span>
    </div>
    <div style="padding:.65rem .85rem">
        <div class="prod-strip">
            <div class="prod-metric">
                <span class="prod-metric-val" style="color:var(--accent)">{{ $productionStats['batches_completed'] }}</span>
                <span class="prod-metric-lbl">Batches</span>
            </div>
            <div class="prod-metric">
                <span class="prod-metric-val" style="color:#16a34a">{{ number_format($productionStats['total_output']) }}</span>
                <span class="prod-metric-lbl">Total Output</span>
            </div>
            <div class="prod-metric">
                <span class="prod-metric-val" style="color:#dc2626">{{ number_format($productionStats['total_rejected']) }}</span>
                <span class="prod-metric-lbl">Rejected</span>
            </div>
            <div class="prod-metric">
                <span class="prod-metric-val" style="color:{{ $productionStats['rejection_rate'] > 10 ? '#dc2626' : '#16a34a' }}">
                    {{ number_format($productionStats['rejection_rate'], 1) }}%
                </span>
                <span class="prod-metric-lbl">Reject Rate</span>
            </div>
        </div>

        @if($recentBadOrders->count() > 0)
        <div style="font-size:.68rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.4rem">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>Products with Bad Orders (Last 7 Days)
        </div>
        <div class="sec-scroll">
            <table class="dt">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>DR Numbers</th>
                        <th>Batch Numbers</th>
                        <th class="tc">BO Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBadOrders as $bo)
                    <tr>
                        <td style="font-weight:600">{{ $bo['product']->name }}</td>
                        <td>
                            @if($bo['dr_numbers']->count() > 0)
                                @foreach($bo['dr_numbers']->take(3) as $dr)
                                    <span class="pill pill-blue" style="margin:.05rem">{{ $dr }}</span>
                                @endforeach
                                @if($bo['dr_numbers']->count() > 3)
                                    <span style="font-size:.63rem;color:var(--text-muted)">+{{ $bo['dr_numbers']->count()-3 }}</span>
                                @endif
                            @else
                                <span style="color:var(--text-muted);font-size:.68rem">—</span>
                            @endif
                        </td>
                        <td>
                            @if($bo['batch_numbers']->count() > 0)
                                @foreach($bo['batch_numbers']->take(3) as $b)
                                    <span class="pill pill-purple" style="margin:.05rem">{{ $b }}</span>
                                @endforeach
                                @if($bo['batch_numbers']->count() > 3)
                                    <span style="font-size:.63rem;color:var(--text-muted)">+{{ $bo['batch_numbers']->count()-3 }}</span>
                                @endif
                            @else
                                <span style="color:var(--text-muted);font-size:.68rem">—</span>
                            @endif
                        </td>
                        <td class="tc"><span class="pill pill-red">{{ $bo['total_bo'] }} units</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ══ BRANCH PERFORMANCE ══ --}}
@if($branchSales->count() > 0)
<div class="sec-card" style="margin-bottom:.75rem">
    <div class="sec-head">
        <span><i class="bi bi-shop me-1"></i> Branch Performance</span>
        <a href="{{ route('reports.sales') }}">Sales Report →</a>
    </div>
    <div class="sec-scroll">
        <table class="dt">
            <thead>
                <tr>
                    <th>Branch / Area</th>
                    <th class="tc">DRs</th>
                    <th class="tr">Total Sales</th>
                    <th class="tr">Collected</th>
                    <th class="tr">Balance</th>
                    <th class="tc">Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($branchSales as $b)
                @php $rate = $b->total_sales > 0 ? ($b->total_collected / $b->total_sales) * 100 : 0; @endphp
                <tr>
                    <td style="font-weight:600">{{ $b->branch->name ?? '—' }}</td>
                    <td class="tc">{{ $b->sales_count }}</td>
                    <td class="tr" style="font-weight:600">&#8369;{{ number_format($b->total_sales, 0) }}</td>
                    <td class="tr" style="color:#16a34a">&#8369;{{ number_format($b->total_collected, 0) }}</td>
                    <td class="tr" style="color:{{ ($b->total_sales - $b->total_collected) > 0 ? '#dc2626' : 'var(--text-muted)' }}">
                        &#8369;{{ number_format($b->total_sales - $b->total_collected, 0) }}
                    </td>
                    <td class="tc">
                        <span class="pill {{ $rate >= 80 ? 'pill-green' : ($rate >= 50 ? 'pill-amber' : 'pill-red') }}">
                            {{ number_format($rate, 1) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td class="tc">{{ $branchSales->sum('sales_count') }}</td>
                    <td class="tr">&#8369;{{ number_format($branchSales->sum('total_sales'), 0) }}</td>
                    <td class="tr" style="color:#16a34a">&#8369;{{ number_format($branchSales->sum('total_collected'), 0) }}</td>
                    <td class="tr" style="color:#dc2626">
                        &#8369;{{ number_format($branchSales->sum('total_sales') - $branchSales->sum('total_collected'), 0) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ══ RECENT SALES + LOW STOCK ══ --}}
<div class="kpi-grid-2">

    <div class="sec-card">
        <div class="sec-head">
            <span><i class="bi bi-receipt me-1"></i> Recent Sales</span>
            <a href="{{ route('sales.index') }}">View All →</a>
        </div>
        <div class="sec-scroll-body">
            @if($recentSales->count())
            @foreach($recentSales as $sale)
            <div class="dlist-item">
                <div>
                    <div style="font-size:.76rem;font-weight:700;color:var(--accent)">DR# {{ $sale->dr_number }}</div>
                    <div style="font-size:.69rem;color:var(--text-secondary)">{{ $sale->customer_name }}</div>
                    <div style="font-size:.64rem;color:var(--text-muted)">
                        {{ $sale->branch->name ?? '—' }} &middot; {{ $sale->sale_date->format('M d') }}
                        @if($sale->payment_mode)
                            &middot; <span style="text-transform:uppercase;font-size:.62rem">{{ $sale->payment_mode }}</span>
                        @endif
                    </div>
                    @if($sale->due_date)
                    <div style="font-size:.61rem;color:#b45309;margin-top:.12rem">
                        <i class="bi bi-calendar-event"></i> Due {{ $sale->due_date->format('M j') }}
                    </div>
                    @endif
                </div>
                <div style="text-align:right">
                    <div style="font-size:.79rem;font-weight:700;color:#16a34a">&#8369;{{ number_format($sale->total_amount, 0) }}</div>
                    @if(($sale->less_amount ?? 0) > 0)
                    <div style="font-size:.62rem;color:var(--text-muted)">Less −&#8369;{{ number_format($sale->less_amount, 0) }}</div>
                    @endif
                    @if($sale->balance > 0)
                    <div style="font-size:.65rem;font-weight:600;color:#dc2626">Bal. &#8369;{{ number_format($sale->balance, 0) }}</div>
                    @endif
                    @if($sale->payment_status === 'paid')
                        <span class="pill pill-green">PAID</span>
                    @elseif($sale->payment_status === 'partial')
                        <span class="pill pill-amber">PARTIAL</span>
                    @else
                        <span class="pill pill-red">COLLECT</span>
                    @endif
                </div>
            </div>
            @endforeach
            @else
            <div class="empty-sm"><i class="bi bi-receipt"></i>No recent sales.</div>
            @endif
        </div>
    </div>

    <div class="sec-card">
        <div class="sec-head brown-head">
            <span><i class="bi bi-exclamation-triangle me-1"></i> Low Stock Alert</span>
            <a href="{{ route('finished-products.index') }}" style="color:rgba(255,255,255,.6)">Manage →</a>
        </div>
        <div class="sec-scroll-body">
            @if($lowStockFinished->count())
            @foreach($lowStockFinished as $p)
            @php $pct = $p->minimum_stock > 0 ? min(100, ($p->stock_on_hand / $p->minimum_stock) * 100) : 0; @endphp
            <div class="dlist-item">
                <div>
                    <div style="font-size:.75rem;font-weight:600">{{ $p->name }}</div>
                    <div style="font-size:.65rem;color:var(--text-muted)">
                        Warehouse: {{ $p->stock_on_hand }}
                        @if($p->stock_out > 0) &nbsp;·&nbsp; Branches: {{ $p->stock_out }} @endif
                    </div>
                    <div class="prog-bar" style="width:80px">
                        <div class="prog-fill {{ $p->stock_on_hand == 0 ? 'red' : 'amber' }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                <span class="pill {{ $p->stock_on_hand == 0 ? 'pill-red' : 'pill-amber' }}">
                    {{ $p->stock_on_hand }} / {{ $p->minimum_stock }}
                </span>
            </div>
            @endforeach
            @else
            <div class="empty-sm ok"><i class="bi bi-check-circle"></i>All products well stocked!</div>
            @endif
        </div>
    </div>

</div>

<script>
function toggleAlert(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('open');
}
</script>

@endsection