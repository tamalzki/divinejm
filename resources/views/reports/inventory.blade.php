@extends('layouts.sidebar')
@section('page-title', 'Inventory Report')
@section('content')

<style>
    /* Filter bar */
    .filter-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem 1rem; margin-bottom:1.1rem; display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
    .quick-btn { font-size:.73rem; padding:.22rem .65rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-page); color:var(--text-secondary); cursor:pointer; text-decoration:none; white-space:nowrap; }
    .quick-btn:hover { background:var(--accent-faint); color:var(--accent); border-color:var(--accent); }
    .quick-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    .filter-sep { width:1px; height:20px; background:var(--border); }
    .date-input { padding:.25rem .52rem; font-size:.77rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.75rem; font-weight:600; padding:.26rem .8rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }

    /* Alert banner */
    .alert-low { background:#fef2f2; border:1px solid #fecaca; border-radius:var(--radius); padding:.6rem 1rem; margin-bottom:1rem; font-size:.78rem; color:#991b1b; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .alert-low-tag { background:#dc2626; color:#fff; font-size:.66rem; font-weight:700; padding:.1rem .45rem; border-radius:3px; white-space:nowrap; }

    /* Tabs */
    .tab-nav { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:1rem; }
    .tab-btn { font-size:.78rem; font-weight:600; padding:.45rem 1.1rem; border:none; background:none; cursor:pointer; color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-2px; display:flex; align-items:center; gap:.3rem; white-space:nowrap; }
    .tab-btn:hover { color:var(--text-primary); }
    .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }

    /* Summary tiles */
    .tile-row { display:grid; gap:.75rem; margin-bottom:1rem; }
    .tile-row-4 { grid-template-columns:repeat(4,1fr); }
    .tile-row-3 { grid-template-columns:repeat(3,1fr); }
    .tile-row-5 { grid-template-columns:repeat(5,1fr); }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.7rem 1rem; }
    .sum-tile-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .sum-tile-value { font-size:.95rem; font-weight:700; }
    .c-accent { color:var(--accent); }
    .c-green   { color:var(--s-success-text); }
    .c-red     { color:var(--s-danger-text); }
    .c-amber   { color:var(--s-warning-text); }

    /* Section header */
    .rpt-section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.6rem; flex-wrap:wrap; gap:.4rem; }
    .rpt-section-title { font-size:.80rem; font-weight:700; color:var(--text-primary); }
    .rpt-section-meta  { font-size:.74rem; font-weight:700; color:var(--accent); }

    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }

    /* Tables */
    .rpt-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .rpt-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:.5rem .9rem; white-space:nowrap; border:none; }
    .rpt-table tbody td { padding:.5rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .rpt-table tbody tr:last-child td { border-bottom:none; }
    .rpt-table tbody tr:hover td { background:var(--accent-faint); }
    .rpt-table tfoot td { padding:.5rem .9rem; font-weight:700; font-size:.78rem; border-top:2px solid var(--border); background:var(--bg-page); }

    /* Pills */
    .pill { display:inline-flex; align-items:center; gap:.2rem; padding:.1rem .42rem; border-radius:4px; font-size:.67rem; font-weight:700; }
    .pill-ok      { background:#dcfce7; color:#15622e; }
    .pill-low     { background:#fee2e2; color:#991b1b; }
    .pill-info    { background:var(--s-info-bg);    color:var(--s-info-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }

    .customer-row td { background:var(--accent-faint) !important; font-weight:600; }
    .empty-row td { text-align:center; padding:1.5rem; color:var(--text-muted); font-size:.80rem; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-clipboard-data me-1" style="color:var(--accent)"></i>Inventory Report
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($fromStr)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($toStr)->format('M d, Y') }}
        </span>
    </div>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('reports.inventory') }}">
<div class="filter-bar">
    <a href="{{ route('reports.inventory', ['quick'=>'today']) }}"  class="quick-btn {{ $quick==='today'  ? 'active':'' }}">Today</a>
    <a href="{{ route('reports.inventory', ['quick'=>'week']) }}"   class="quick-btn {{ $quick==='week'   ? 'active':'' }}">This Week</a>
    <a href="{{ route('reports.inventory', ['quick'=>'month']) }}"  class="quick-btn {{ $quick==='month'  ? 'active':'' }}">This Month</a>
    <div class="filter-sep"></div>
    <span style="font-size:.72rem;color:var(--text-muted)">From</span>
    <input type="date" name="from" class="date-input" value="{{ $fromStr }}">
    <span style="font-size:.72rem;color:var(--text-muted)">To</span>
    <input type="date" name="to"   class="date-input" value="{{ $toStr }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

{{-- Low stock alert --}}
@php
    $lowFP  = $warehouseStock->where('low_stock', true);
    $lowRM  = $rawMaterials->where('low_stock', true);
    $allLow = $lowFP->merge($lowRM);
@endphp
@if($allLow->count())
<div class="alert-low">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>{{ $allLow->count() }} item(s) below threshold:</strong>
    @foreach($allLow as $item)
        <span class="alert-low-tag">{{ $item['name'] }} — {{ number_format($item['stock'], 2) }} {{ $item['unit'] }}</span>
    @endforeach
</div>
@endif

{{-- Tabs --}}
<div class="tab-nav">
    <button class="tab-btn active" onclick="switchTab('warehouse', this)">
        <i class="bi bi-building"></i> Warehouse Stock
        <span class="pill pill-info">{{ $warehouseStock->count() }}</span>
    </button>
    <button class="tab-btn" onclick="switchTab('production', this)">
        <i class="bi bi-gear"></i> Production Mix
        <span class="pill pill-info">{{ $productionMixes->count() }}</span>
    </button>
    <button class="tab-btn" onclick="switchTab('branch', this)">
        <i class="bi bi-geo-alt"></i> Branch / Area
        <span class="pill pill-info">{{ $branchStock->count() }}</span>
    </button>
    <button class="tab-btn" onclick="switchTab('rawmat', this)">
        <i class="bi bi-boxes"></i> Raw Materials
        <span class="pill pill-info">{{ $rawMaterials->count() }}</span>
    </button>
</div>

{{-- ── TAB 1: Warehouse Stock ── --}}
<div id="tab-warehouse" class="tab-pane active">
    @php
        $totalStock = $warehouseStock->sum('stock');
        $totalValue = $warehouseStock->sum('total_value');
        $lowCount   = $warehouseStock->where('low_stock', true)->count();
    @endphp
    <div class="tile-row tile-row-4">
        <div class="sum-tile"><span class="sum-tile-label">Total Products</span><span class="sum-tile-value c-accent">{{ $warehouseStock->count() }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Stock (units)</span><span class="sum-tile-value c-accent">{{ number_format($totalStock, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Stock Value</span><span class="sum-tile-value c-green">&#8369;{{ number_format($totalValue, 2) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Low Stock Items</span><span class="sum-tile-value {{ $lowCount > 0 ? 'c-red' : 'c-green' }}">{{ $lowCount }}</span></div>
    </div>
    <div class="rpt-section-header">
        <span class="rpt-section-title">Warehouse Stock — Finished Products</span>
        <span class="rpt-section-meta">Total Value: &#8369;{{ number_format($totalValue, 2) }}</span>
    </div>
    <div class="dj-card">
        <div style="overflow-x:auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Stock</th>
                        <th class="text-end">Avg Cost</th>
                        <th class="text-end">Stock Value</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-end">Margin</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($warehouseStock as $fp)
                <tr>
                    <td style="font-weight:600">{{ $fp['name'] }}</td>
                    <td class="text-end" style="{{ $fp['low_stock'] ? 'color:var(--s-danger-text);font-weight:700' : 'font-weight:600' }}">
                        {{ number_format($fp['stock'], 2) }}
                    </td>
                    <td class="text-end" style="color:var(--text-muted)">&#8369;{{ number_format($fp['avg_cost'], 4) }}</td>
                    <td class="text-end">&#8369;{{ number_format($fp['total_value'], 2) }}</td>
                    <td class="text-end">&#8369;{{ number_format($fp['price'], 2) }}</td>
                    <td class="text-end">
                        @if($fp['price'] > 0)
                            @php $margin = (($fp['price'] - $fp['avg_cost']) / $fp['price']) * 100; @endphp
                            <span class="pill {{ $margin >= 30 ? 'pill-ok' : 'pill-warning' }}">{{ number_format($margin, 1) }}%</span>
                        @else <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($fp['low_stock'])
                            <span class="pill pill-low"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                        @else
                            <span class="pill pill-ok">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">No finished products found.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="text-end">{{ number_format($totalStock, 0) }}</td>
                        <td></td>
                        <td class="text-end">&#8369;{{ number_format($totalValue, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ── TAB 2: Branch / Area ── --}}
<div id="tab-branch" class="tab-pane">
    @php
        $bTotalDeployed = $branchStock->sum(fn($b) => $b['customers']->sum('total_deployed'));
        $bTotalSold     = $branchStock->sum(fn($b) => $b['customers']->sum('total_sold'));
        $bTotalUnsold   = $branchStock->sum(fn($b) => $b['customers']->sum('total_unsold'));
        $bTotalBO       = $branchStock->sum(fn($b) => $b['customers']->sum('total_bo'));
        $bTotalValue    = $branchStock->sum(fn($b) => $b['customers']->sum('total_value'));
    @endphp
    <div class="tile-row tile-row-5">
        <div class="sum-tile"><span class="sum-tile-label">Total Deployed</span><span class="sum-tile-value c-accent">{{ number_format($bTotalDeployed, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Sold</span><span class="sum-tile-value c-green">{{ number_format($bTotalSold, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Unsold</span><span class="sum-tile-value c-amber">{{ number_format($bTotalUnsold, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Bad Order</span><span class="sum-tile-value c-red">{{ number_format($bTotalBO, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Sales Value</span><span class="sum-tile-value c-green">&#8369;{{ number_format($bTotalValue, 2) }}</span></div>
    </div>
    <div class="dj-card">
        <div style="overflow-x:auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th class="text-end">Deployed</th>
                        <th class="text-end">Sold</th>
                        <th class="text-end">Unsold</th>
                        <th class="text-end">BO</th>
                        <th class="text-end">Value</th>
                        <th class="text-center">Payment</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($branchStock as $areaData)
                    @foreach($areaData['customers'] as $customer)
                        <tr class="customer-row">
                            <td>{{ $areaData['branch']->name }}</td>
                            <td colspan="2" style="color:var(--accent)">
                                <i class="bi bi-person-circle"></i> {{ $customer['customer_name'] }}
                                <span class="pill pill-info" style="margin-left:.3rem">{{ $customer['dr_count'] }} {{ Str::plural('DR', $customer['dr_count']) }}</span>
                            </td>
                            <td class="text-end">{{ number_format($customer['total_deployed'], 0) }}</td>
                            <td class="text-end" style="color:var(--s-success-text)">{{ number_format($customer['total_sold'], 0) }}</td>
                            <td class="text-end" style="color:var(--s-warning-text)">{{ number_format($customer['total_unsold'], 0) }}</td>
                            <td class="text-end" style="color:var(--s-danger-text)">{{ number_format($customer['total_bo'], 0) }}</td>
                            <td class="text-end" style="font-weight:700">&#8369;{{ number_format($customer['total_value'], 2) }}</td>
                            <td></td>
                        </tr>
                        @foreach($customer['items'] as $item)
                        <tr>
                            <td style="color:var(--text-muted);font-size:.72rem;padding-left:1.5rem"></td>
                            <td style="font-size:.74rem;color:var(--accent);font-weight:600">DR# {{ $item['dr_number'] }}</td>
                            <td>
                                <span style="font-size:.78rem">{{ $item['product'] }}</span>
                                <span style="font-size:.68rem;color:var(--text-muted);display:block">{{ $item['sale_date'] }}</span>
                            </td>
                            <td class="text-end">{{ number_format($item['qty_deployed'], 0) }}</td>
                            <td class="text-end" style="color:var(--s-success-text)">{{ number_format($item['qty_sold'], 0) }}</td>
                            <td class="text-end" style="color:{{ $item['qty_unsold'] > 0 ? 'var(--s-warning-text)' : 'var(--text-muted)' }}">{{ number_format($item['qty_unsold'], 0) }}</td>
                            <td class="text-end" style="color:{{ $item['qty_bo'] > 0 ? 'var(--s-danger-text)' : 'var(--text-muted)' }}">{{ number_format($item['qty_bo'], 0) }}</td>
                            <td class="text-end" style="font-weight:600">&#8369;{{ number_format($item['subtotal'], 2) }}</td>
                            <td class="text-center">
                                @if($item['payment_status'] === 'paid')
                                    <span class="pill pill-success">Paid</span>
                                @elseif($item['payment_status'] === 'partial')
                                    <span class="pill pill-warning">Partial</span>
                                @else
                                    <span class="pill pill-danger">To Collect</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                @empty
                <tr><td colspan="9" class="empty-row">No branch stock data for this date range.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="text-end">{{ number_format($bTotalDeployed, 0) }}</td>
                        <td class="text-end">{{ number_format($bTotalSold, 0) }}</td>
                        <td class="text-end">{{ number_format($bTotalUnsold, 0) }}</td>
                        <td class="text-end">{{ number_format($bTotalBO, 0) }}</td>
                        <td class="text-end">&#8369;{{ number_format($bTotalValue, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ── TAB 3: Raw Materials ── --}}
<div id="tab-rawmat" class="tab-pane">
    @php
        $rmTotalStock = $rawMaterials->sum('stock');
        $rmTotalValue = $rawMaterials->sum('total_value');
        $rmLowCount   = $rawMaterials->where('low_stock', true)->count();
    @endphp
    <div class="tile-row tile-row-4">
        <div class="sum-tile"><span class="sum-tile-label">Total Materials</span><span class="sum-tile-value c-accent">{{ $rawMaterials->count() }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Stock (units)</span><span class="sum-tile-value c-accent">{{ number_format($rmTotalStock, 2) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Stock Value</span><span class="sum-tile-value c-green">&#8369;{{ number_format($rmTotalValue, 2) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Low Stock Items</span><span class="sum-tile-value {{ $rmLowCount > 0 ? 'c-red' : 'c-green' }}">{{ $rmLowCount }}</span></div>
    </div>
    <div class="rpt-section-header">
        <span class="rpt-section-title">Raw Materials Stock</span>
        <span class="rpt-section-meta">Total Value: &#8369;{{ number_format($rmTotalValue, 2) }}</span>
    </div>
    <div class="dj-card">
        <div style="overflow-x:auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-end">Stock</th>
                        <th>Unit</th>
                        <th class="text-end">Cost/Unit</th>
                        <th class="text-end">Stock Value</th>
                        <th class="text-end">Threshold</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rawMaterials as $rm)
                <tr>
                    <td style="font-weight:600">{{ $rm['name'] }}</td>
                    <td class="text-end" style="{{ $rm['low_stock'] ? 'color:var(--s-danger-text);font-weight:700' : 'font-weight:600' }}">
                        {{ number_format($rm['stock'], 2) }}
                    </td>
                    <td style="color:var(--text-muted);font-size:.76rem">{{ $rm['unit'] }}</td>
                    <td class="text-end">&#8369;{{ number_format($rm['cost'], 2) }}</td>
                    <td class="text-end" style="font-weight:600">&#8369;{{ number_format($rm['total_value'], 2) }}</td>
                    <td class="text-end" style="color:var(--text-muted)">{{ number_format($rm['threshold'], 2) }}</td>
                    <td class="text-center">
                        @if($rm['low_stock'])
                            <span class="pill pill-low"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                        @else
                            <span class="pill pill-ok">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-row">No raw materials found.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="text-end">{{ number_format($rmTotalStock, 2) }}</td>
                        <td colspan="2"></td>
                        <td class="text-end">&#8369;{{ number_format($rmTotalValue, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ── TAB 2: Production Mix ── --}}
<div id="tab-production" class="tab-pane">
    @php
        $pmTotalProduced  = $productionMixes->sum('qty_produced');
        $pmTotalRejected  = $productionMixes->sum('qty_rejected');
        $pmTotalGood      = $productionMixes->sum('good_output');
    @endphp
    <div class="tile-row tile-row-4">
        <div class="sum-tile"><span class="sum-tile-label">Total Batches</span><span class="sum-tile-value c-accent">{{ $productionMixes->count() }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Qty Produced</span><span class="sum-tile-value c-green">{{ number_format($pmTotalProduced, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Total Rejects</span><span class="sum-tile-value {{ $pmTotalRejected > 0 ? 'c-red' : 'c-green' }}">{{ number_format($pmTotalRejected, 0) }}</span></div>
        <div class="sum-tile"><span class="sum-tile-label">Good Output</span><span class="sum-tile-value c-green">{{ number_format($pmTotalGood, 0) }}</span></div>
    </div>
    <div class="dj-card">
        <div style="overflow-x:auto">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Product</th>
                        <th class="text-end">Qty Produced</th>
                        <th class="text-end">Rejects</th>
                        <th class="text-end">Good Output</th>
                        <th>Expiry</th>
                        <th>Mix Date</th>
                        <th>Mixed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($productionMixes as $mix)
                <tr>
                    <td><span class="pill pill-info">{{ $mix['batch_number'] }}</span></td>
                    <td style="font-weight:600">{{ $mix['product'] }}</td>
                    <td class="text-end" style="font-weight:700">{{ number_format($mix['qty_produced'], 0) }}</td>
                    <td class="text-end" style="{{ $mix['qty_rejected'] > 0 ? 'color:var(--s-danger-text);font-weight:700' : 'color:var(--text-muted)' }}">
                        {{ number_format($mix['qty_rejected'], 0) }}
                    </td>
                    <td class="text-end" style="color:var(--s-success-text);font-weight:600">{{ number_format($mix['good_output'], 0) }}</td>
                    <td style="font-size:.76rem;color:var(--text-muted)">{{ $mix['expiry'] }}</td>
                    <td style="font-size:.76rem;color:var(--text-muted)">{{ $mix['date'] }}</td>
                    <td style="font-size:.76rem">{{ $mix['by'] }}</td>
                    <td style="font-size:.74rem;color:var(--text-muted)">{{ $mix['notes'] ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-row">No production mixes in this date range.</td></tr>
                @endforelse
                </tbody>
                @if($productionMixes->count())
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end">{{ number_format($pmTotalProduced, 0) }}</td>
                        <td class="text-end" style="{{ $pmTotalRejected > 0 ? 'color:#fca5a5' : '' }}">{{ number_format($pmTotalRejected, 0) }}</td>
                        <td class="text-end">{{ number_format($pmTotalGood, 0) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b)  { b.classList.remove('active'); });
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

@endsection