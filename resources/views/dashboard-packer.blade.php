@extends('layouts.sidebar')
@section('page-title', 'Dashboard')
@section('content')

<style>
    /* ── KPI tiles ── */
    .kpi-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    .kpi-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; position:relative; overflow:hidden; }
    .kpi-tile::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:var(--radius) var(--radius) 0 0; }
    .kpi-tile.t-accent::before { background:var(--accent); }
    .kpi-tile.t-green::before  { background:#16a34a; }
    .kpi-tile.t-amber::before  { background:#d97706; }
    .kpi-tile.t-red::before    { background:#dc2626; }
    .kpi-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.18rem; }
    .kpi-value { font-size:1.05rem; font-weight:800; color:var(--text-primary); display:block; line-height:1.2; }
    .kpi-value.c-accent { color:var(--accent); }
    .kpi-value.c-green  { color:#16a34a; }
    .kpi-value.c-red    { color:#dc2626; }
    .kpi-sub { font-size:.67rem; color:var(--text-muted); margin-top:.2rem; display:block; }

    /* ── Section card ── */
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; display:flex; align-items:center; justify-content:space-between; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
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
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }

    /* ── Pills ── */
    .pill { display:inline-flex; align-items:center; padding:.05rem .32rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .pill-green  { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-red    { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-amber  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-blue   { background:var(--s-info-bg);    color:var(--s-info-text); }
    .pill-purple { background:#ede9fe; color:#6d28d9; }

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
    .btn-sm-blue { font-size:.70rem; font-weight:600; padding:.22rem .65rem; border-radius:4px; background:#0369a1; color:#fff; border:none; cursor:pointer; text-decoration:none; display:inline-block; }

    .sec-scroll { overflow-x:auto; }

    /* ── Empty state ── */
    .empty-sm { text-align:center; padding:1.5rem .5rem; color:var(--text-muted); font-size:.76rem; }
    .empty-sm i { font-size:1.5rem; display:block; opacity:.22; margin-bottom:.3rem; }
    .empty-sm.ok { color:#16a34a; }
    .empty-sm.ok i { opacity:.35; }
    .queue-badge { display:inline-flex; align-items:center; gap:.2rem; padding:.05rem .32rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .queue-badge.overdue { background:#fee2e2; color:#9b1c1c; }
    .queue-badge.today { background:#ffedd5; color:#9a3412; }
    .queue-badge.waiting { background:var(--s-info-bg); color:var(--s-info-text); }

    @media (max-width: 992px) {
        .kpi-grid-4 { grid-template-columns:repeat(2,1fr); }
    }
    @media (max-width: 520px) {
        .kpi-grid-4 { grid-template-columns:1fr; }
    }
</style>

{{-- ══ PAGE HEADER ══ --}}
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-speedometer2 me-1" style="color:var(--accent)"></i>Dashboard
        </h5>
        <p class="mb-0" style="font-size:.71rem;color:var(--text-muted)">Production &amp; finished-goods inventory overview</p>
    </div>
</div>

{{-- ══ KPI STRIP ══ --}}
<div class="kpi-grid-4">
    <div class="kpi-tile t-accent">
        <span class="kpi-label">Warehouse Stock</span>
        <span class="kpi-value c-accent">{{ number_format($totalStockOnHand) }}</span>
        <span class="kpi-sub">units on hand</span>
    </div>
    <div class="kpi-tile t-amber">
        <span class="kpi-label">At Branches</span>
        <span class="kpi-value">{{ number_format($totalStockOut) }}</span>
        <span class="kpi-sub">units delivered out</span>
    </div>
    <div class="kpi-tile {{ $productionStats['rejection_rate'] > 10 ? 't-red' : 't-green' }}">
        <span class="kpi-label">Output (7 days)</span>
        <span class="kpi-value {{ $productionStats['rejection_rate'] > 10 ? 'c-red' : 'c-green' }}">
            {{ number_format($productionStats['total_output']) }}
        </span>
        <span class="kpi-sub">{{ $productionStats['batches_completed'] }} batches &middot; {{ number_format($productionStats['rejection_rate'], 1) }}% reject</span>
    </div>
    <div class="kpi-tile {{ $zeroStockProducts > 0 ? 't-red' : 't-green' }}">
        <span class="kpi-label">Zero Stock Products</span>
        <span class="kpi-value {{ $zeroStockProducts > 0 ? 'c-red' : 'c-green' }}">{{ $zeroStockProducts }}</span>
        <span class="kpi-sub">need production</span>
    </div>
</div>

{{-- ══ URGENT PRODUCTION ══ --}}
@if($needsProduction->count() > 0)
<div class="sec-card mb-3">
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
                    Warehouse: {{ $p->stock_on_hand }} &nbsp;&middot;&nbsp; Min: {{ $p->minimum_stock }}
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
        <a href="{{ route('packer-packs.index') }}" class="btn-sm-amber" style="background:var(--brand-deep)">
            <i class="bi bi-box-seam me-1"></i>Packers Report
        </a>
    </div>
</div>
@endif

{{-- ══ PACKING QUEUE (daily production remaining) ══ --}}
<div class="sec-card mb-3">
    <div class="sec-head amber-head">
        <span><i class="bi bi-box-seam me-1"></i> Packing Queue — Daily Production Remaining</span>
        <span style="font-size:.62rem;opacity:.7;text-transform:none;letter-spacing:0">Compare production date vs latest pack date</span>
    </div>
    <div style="padding:.55rem .85rem; border-bottom:1px solid var(--border); background:var(--bg-page); display:flex; flex-wrap:wrap; gap:.45rem; align-items:center;">
        <span class="pill pill-red">{{ $packingOver24hCount }} open &gt;24h</span>
        <span class="pill pill-amber">{{ $packingDueTodayCount }} from today</span>
        <span class="pill pill-blue">{{ number_format($packingTotalPcs, 0) }} pcs remaining</span>
        <span class="pill pill-purple">{{ number_format($packingTotalGrams, 0) }} g remaining</span>
    </div>
    <div class="sec-scroll">
        @if($packingQueue->count() > 0)
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tr">Remaining</th>
                    <th class="tc">Oldest production</th>
                    <th class="tc">Latest pack</th>
                    <th class="tc">Open</th>
                    <th class="tc">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packingQueue as $row)
                <tr>
                    <td style="font-weight:600">{{ $row->product_name }}</td>
                    <td class="tr" style="font-weight:700;color:var(--accent)">
                        {{ number_format($row->remaining_display, 0) }} {{ $row->remaining_unit }}
                    </td>
                    <td class="tc">
                        {{ $row->oldest_production_date->format('M j, Y') }}
                        <div style="font-size:.62rem;color:var(--text-muted)">{{ $row->days_waiting }}d since prod. day</div>
                    </td>
                    <td class="tc">
                        @if($row->last_pack_date)
                            {{ $row->last_pack_date->format('M j, Y') }}
                        @else
                            <span style="color:var(--text-muted)">No pack yet</span>
                        @endif
                    </td>
                    <td class="tc" style="font-variant-numeric:tabular-nums;font-weight:600">{{ (int) $row->hours_open }}h</td>
                    <td class="tc">
                        @if($row->is_over_24h)
                            <span class="queue-badge overdue" title="Unpacked for more than 24 hours"><i class="bi bi-alarm-fill"></i>&gt;24h</span>
                        @elseif($row->is_due_today)
                            <span class="queue-badge today"><i class="bi bi-calendar-check"></i>Today</span>
                        @else
                            <span class="queue-badge waiting"><i class="bi bi-hourglass-split"></i>&lt;24h</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-sm ok"><i class="bi bi-check-circle"></i>All current production is already packed.</div>
        @endif
    </div>
    <div class="crit-foot" style="display:flex;flex-wrap:wrap;gap:.45rem;align-items:center">
        <a href="{{ route('daily-production.index') }}" class="btn-sm-amber">
            <i class="bi bi-clipboard2-data me-1"></i>Daily Production
        </a>
        <a href="{{ route('packer-packs.index') }}" class="btn-sm-blue">
            <i class="bi bi-box-seam me-1"></i>Packers Report
        </a>
    </div>
</div>

{{-- ══ PRODUCTION (7 days) + LOW STOCK ══ --}}
<div class="kpi-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">

    <div class="sec-card">
        <div class="sec-head">
            <span><i class="bi bi-gear-wide-connected me-1"></i> Production</span>
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
        </div>
    </div>

    <div class="sec-card">
        <div class="sec-head brown-head">
            <span><i class="bi bi-exclamation-triangle me-1"></i> Low Stock Alert</span>
            <a href="{{ route('finished-products.index') }}">Manage &rarr;</a>
        </div>
        <div style="max-height:220px;overflow-y:auto;padding:.5rem .75rem">
            @if($lowStockFinished->count())
            @foreach($lowStockFinished as $p)
            @php $pct = $p->minimum_stock > 0 ? min(100, ($p->stock_on_hand / $p->minimum_stock) * 100) : 0; @endphp
            <div class="dlist-item">
                <div>
                    <div style="font-size:.75rem;font-weight:600">{{ $p->name }}</div>
                    <div style="font-size:.65rem;color:var(--text-muted)">
                        Warehouse: {{ $p->stock_on_hand }}
                        @if($p->stock_out > 0) &nbsp;&middot;&nbsp; Branches: {{ $p->stock_out }} @endif
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

@if($expiringProducts->count() > 0)
<div class="sec-card mt-3">
    <div class="sec-head red-head">
        <span><i class="bi bi-calendar-x me-1"></i> Expiring Soon (30 days)</span>
    </div>
    <div style="max-height:220px;overflow-y:auto;padding:.5rem .75rem">
        @foreach($expiringProducts as $p)
        <div class="dlist-item" style="font-size:.73rem">
            <span style="font-weight:600">{{ $p->name }}</span>
            <span class="pill pill-amber">{{ $p->days_until_expiry }}d left</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
