@extends('layouts.sidebar')
@section('page-title', 'Delivery Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .select-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); min-width:140px; }
    .search-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); min-width:140px; }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }
    .tile-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:900px) { .tile-row { grid-template-columns:repeat(2,1fr); } }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .sum-tile-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .sum-tile-value { font-size:.95rem; font-weight:700; }
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .dt { width:100%; border-collapse:collapse; font-size:.78rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .7rem; font-size:.62rem; font-weight:700; text-transform:uppercase; white-space:nowrap; }
    .dt tbody td { padding:.38rem .7rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { font-weight:700; background:var(--bg-page); border-top:2px solid var(--border); padding:.38rem .7rem; }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }
    .dr-block { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:.5rem; overflow:hidden; }
    .dr-header { padding:.38rem .85rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.3rem; }
    .dr-title { font-size:.76rem; font-weight:700; color:var(--accent); display:flex; align-items:center; gap:.4rem; }
    .dr-meta  { font-size:.68rem; color:var(--text-muted); display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
    .grid-2 { display:grid; grid-template-columns:2fr 1fr; gap:.75rem; margin-bottom:.75rem; }
    @media (max-width:900px) { .grid-2 { grid-template-columns:1fr; } }
    @media print { .no-print { display:none!important; } }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-truck me-1" style="color:var(--accent)"></i>Delivery Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.delivery') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <select name="branch_id" class="select-input">
        <option value="">All Branches</option>
        @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
    </select>
    <input type="text" name="search" class="search-input" placeholder="DR# or customer..." value="{{ $search }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

{{-- Summary tiles --}}
<div class="tile-row">
    <div class="sum-tile">
        <span class="sum-tile-label">Total DRs</span>
        <span class="sum-tile-value" style="color:var(--accent)">{{ $totals['dr_count'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Total Qty Delivered</span>
        <span class="sum-tile-value" style="color:#16a34a">{{ number_format($totals['total_qty'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Branches Served</span>
        <span class="sum-tile-value" style="color:#0891b2">{{ $totals['branch_count'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Products Delivered</span>
        <span class="sum-tile-value" style="color:#7c3aed">{{ $totals['product_count'] }}</span>
    </div>
</div>

<div class="grid-2">
    {{-- DR List --}}
    <div>
        @forelse($byDr as $dr)
        <div class="dr-block">
            <div class="dr-header">
                <span class="dr-title">
                    <i class="bi bi-file-earmark-text"></i>
                    {{ $dr['reference_number'] }}
                </span>
                <div class="dr-meta">
                    <span><i class="bi bi-geo-alt"></i> {{ $dr['branch'] }}</span>
                    <span><i class="bi bi-calendar3"></i> {{ $dr['movement_date']?->format('M d, Y') ?? '—' }}</span>
                    <span style="font-weight:700;color:var(--accent)">{{ number_format($dr['total_qty'], 0) }} pcs</span>
                    @if($dr['customer_note'])
                        <span style="font-size:.64rem">{{ Str::limit(str_replace('Customer: ', '', $dr['customer_note']), 30) }}</span>
                    @endif
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="dt">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th class="tr">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($dr['items'] as $item)
                    <tr>
                        <td style="font-weight:600">{{ optional($item->finishedProduct)->name ?? '—' }}</td>
                        <td style="font-size:.68rem;color:var(--text-muted)">{{ $item->batch_number ?: '—' }}</td>
                        <td class="tr" style="font-weight:700">{{ number_format($item->quantity, 0) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div style="text-align:center;color:var(--text-muted);padding:3rem;font-size:.82rem">
            <i class="bi bi-truck" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
            No deliveries found in this period.
        </div>
        @endforelse
    </div>

    {{-- By Branch Summary --}}
    <div>
        <div class="sec-card">
            <div class="sec-head"><i class="bi bi-geo-alt me-1"></i> By Branch</div>
            <div style="overflow-x:auto">
                <table class="dt">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="tc">DRs</th>
                            <th class="tr">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($byBranch as $b)
                        <tr>
                            <td style="font-weight:600">{{ $b['branch_name'] }}</td>
                            <td class="tc">{{ $b['dr_count'] }}</td>
                            <td class="tr" style="font-weight:700;color:var(--accent)">{{ number_format($b['total_qty'], 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">—</td></tr>
                    @endforelse
                    </tbody>
                    @if($byBranch->count())
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="tc">{{ $totals['dr_count'] }}</td>
                            <td class="tr">{{ number_format($totals['total_qty'], 0) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
