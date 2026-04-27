@extends('layouts.sidebar')
@section('page-title', 'Raw Material Costing Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .search-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); min-width:160px; }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }
    .tile-row { display:grid; grid-template-columns:repeat(5,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:1000px) { .tile-row { grid-template-columns:repeat(3,1fr); } }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .sum-tile-label { font-size:.58rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .sum-tile-value { font-size:.90rem; font-weight:700; }
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .dt { width:100%; border-collapse:collapse; font-size:.78rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .7rem; font-size:.62rem; font-weight:700; text-transform:uppercase; white-space:nowrap; }
    .dt tbody td { padding:.38rem .7rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }
    .price-up   { color:#dc2626; font-weight:700; }
    .price-down { color:#16a34a; font-weight:700; }
    .price-same { color:var(--text-muted); }
    .change-arrow-up   { color:#dc2626; }
    .change-arrow-down { color:#16a34a; }
    .pill { display:inline-block; padding:.1rem .4rem; border-radius:3px; font-size:.64rem; font-weight:700; }
    .pill-up   { background:#fee2e2; color:#991b1b; }
    .pill-down { background:#dcfce7; color:#15622e; }
    @media print { .no-print { display:none!important; } }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-currency-exchange me-1" style="color:#b45309"></i>Raw Material Costing Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            Track unit price changes for raw materials
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.raw-material-costing') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <input type="text" name="search" class="search-input" placeholder="Search material..." value="{{ $search }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
    @if($search)<a href="{{ route('reports.raw-material-costing') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn-apply" style="background:var(--text-muted)">Clear</a>@endif
</div>
</form>

{{-- Summary tiles --}}
<div class="tile-row">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Materials</span>
        <span class="sum-tile-value" style="color:var(--accent)">{{ $totals['total_materials'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Changed in Period</span>
        <span class="sum-tile-value" style="color:#b45309">{{ $totals['changed_count'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Change Events</span>
        <span class="sum-tile-value" style="color:#7c3aed">{{ $totals['change_events'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Price Increases</span>
        <span class="sum-tile-value" style="color:#dc2626">{{ $totals['increased'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Price Decreases</span>
        <span class="sum-tile-value" style="color:#16a34a">{{ $totals['decreased'] }}</span>
    </div>
</div>

{{-- Price Change History --}}
<div class="sec-card">
    <div class="sec-head"><i class="bi bi-clock-history me-1"></i> Price Change History</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Material</th>
                    <th>Unit</th>
                    <th class="tr">Old Price</th>
                    <th class="tc">Change</th>
                    <th class="tr">New Price</th>
                    <th class="tr">Difference</th>
                    <th>Changed By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            @forelse($history as $h)
                @php
                    $diff = $h->new_price - $h->old_price;
                    $pct  = $h->old_price > 0 ? ($diff / $h->old_price) * 100 : 0;
                    $isUp = $diff > 0;
                @endphp
                <tr>
                    <td style="white-space:nowrap;font-size:.74rem;color:var(--text-secondary)">
                        {{ $h->created_at->format('M d, Y') }}
                        <div style="font-size:.65rem;color:var(--text-muted)">{{ $h->created_at->format('g:i A') }}</div>
                    </td>
                    <td style="font-weight:600">{{ optional($h->rawMaterial)->name ?? '—' }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ optional($h->rawMaterial)->unit ?? '—' }}</td>
                    <td class="tr" style="color:var(--text-muted)">₱{{ number_format($h->old_price, 4) }}</td>
                    <td class="tc">
                        @if($isUp)
                            <i class="bi bi-arrow-up-circle-fill change-arrow-up"></i>
                        @else
                            <i class="bi bi-arrow-down-circle-fill change-arrow-down"></i>
                        @endif
                    </td>
                    <td class="tr {{ $isUp ? 'price-up' : 'price-down' }}">₱{{ number_format($h->new_price, 4) }}</td>
                    <td class="tr">
                        <span class="pill {{ $isUp ? 'pill-up' : 'pill-down' }}">
                            {{ $isUp ? '+' : '' }}{{ number_format($diff, 4) }}
                            ({{ $isUp ? '+' : '' }}{{ number_format($pct, 1) }}%)
                        </span>
                    </td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ optional($h->changedBy)->name ?? '—' }}</td>
                    <td style="font-size:.70rem;color:var(--text-muted)">{{ $h->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;color:var(--text-muted);padding:2.5rem">
                        No price changes recorded in this period.
                        <div style="font-size:.70rem;margin-top:.3rem">Price changes are tracked automatically when you edit a raw material's unit price.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Current Prices --}}
<div class="sec-card">
    <div class="sec-head"><i class="bi bi-tags me-1"></i> Current Raw Material Prices</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th class="tr">Current Price / Unit</th>
                    <th class="tr">Stock Qty</th>
                    <th class="tr">Stock Value</th>
                    <th class="tc">Changed in Period</th>
                    <th class="tc">Last Change</th>
                </tr>
            </thead>
            <tbody>
            @forelse($materials as $mat)
                @php
                    $histArr = $historyByMaterial[$mat->id] ?? collect();
                    $latest = $histArr->sortByDesc('created_at')->first();
                @endphp
                <tr>
                    <td style="font-weight:600">{{ $mat->name }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ ucfirst($mat->category ?? '—') }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ $mat->unit }}</td>
                    <td class="tr" style="font-weight:700">₱{{ number_format($mat->unit_price, 4) }}</td>
                    <td class="tr">{{ number_format($mat->quantity, 2) }}</td>
                    <td class="tr" style="font-weight:600;color:var(--accent)">₱{{ number_format($mat->quantity * $mat->unit_price, 2) }}</td>
                    <td class="tc">
                        @if($histArr->count())
                            <span class="pill pill-up">{{ $histArr->count() }}×</span>
                        @else
                            <span style="color:var(--text-muted);font-size:.70rem">—</span>
                        @endif
                    </td>
                    <td class="tc" style="font-size:.70rem;color:var(--text-muted)">
                        {{ $latest ? $latest->created_at->format('M d, Y') : '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">No materials found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
