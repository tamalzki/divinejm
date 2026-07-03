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
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .7rem; font-size:.62rem; font-weight:700; text-transform:uppercase; white-space:nowrap; position:sticky; top:0; }
    .dt tbody td { padding:.38rem .7rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { font-weight:700; background:var(--bg-page); border-top:2px solid var(--border); padding:.38rem .7rem; }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }
    .dr-block { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:.5rem; overflow:hidden; }
    .dr-header { padding:.38rem .85rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.3rem; cursor:pointer; list-style:none; }
    .dr-header::-webkit-details-marker { display:none; }
    .dr-block:not([open]) .dr-header { border-bottom:none; }
    .dr-title { font-size:.76rem; font-weight:700; color:var(--accent); display:flex; align-items:center; gap:.4rem; }
    .dr-meta  { font-size:.68rem; color:var(--text-muted); display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
    .summary-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.9rem; }
    @media (max-width:900px) { .summary-row { grid-template-columns:1fr; } }
    .scroll-panel { max-height:280px; overflow-y:auto; }
    .fp-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: .65rem 1rem; border-top: 1px solid var(--border);
        background: var(--bg-page); flex-wrap: wrap; gap: .5rem;
    }
    .fp-footer .page-info { font-size: .73rem; color: var(--text-muted); }
    .fp-pagination { display: flex; align-items: center; gap: .2rem; }
    .pg-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 .55rem;
        font-size: .73rem; font-weight: 600;
        border-radius: 5px; border: 1px solid var(--border);
        color: var(--text-secondary); background: var(--bg-card);
        text-decoration: none !important; transition: all .15s; white-space: nowrap;
    }
    a.pg-btn:hover { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }
    .pg-active     { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; cursor: default; }
    .pg-disabled   { color: var(--text-muted) !important; border-color: var(--border) !important; background: var(--bg-page) !important; cursor: default; pointer-events: none; }
    .pg-ellipsis   { color: var(--text-muted); font-size: .73rem; padding: 0 .2rem; line-height: 28px; }
    @media print { .no-print { display:none!important; } .scroll-panel { max-height:none; overflow:visible; } }
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

{{-- By Branch / Sold per Product summary — scrollable so a large catalog doesn't stretch the page --}}
<div class="summary-row">
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-geo-alt me-1"></i> By Branch</div>
        <div class="scroll-panel">
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
            </table>
        </div>
        @if($byBranch->count())
        <table class="dt"><tfoot>
            <tr>
                <td>Total</td>
                <td class="tc">{{ $totals['dr_count'] }}</td>
                <td class="tr">{{ number_format($totals['total_qty'], 0) }}</td>
            </tr>
        </tfoot></table>
        @endif
    </div>

    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-box-seam me-1"></i> Sold per Product</div>
        <div class="scroll-panel">
            <table class="dt">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="tc">DRs</th>
                        <th class="tr">Qty</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($byProduct as $p)
                    <tr>
                        <td style="font-weight:600">{{ $p['product_name'] }}</td>
                        <td class="tc">{{ $p['dr_count'] }}</td>
                        <td class="tr" style="font-weight:700;color:var(--accent)">{{ number_format($p['total_qty'], 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">—</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($byProduct->count())
        <table class="dt"><tfoot>
            <tr>
                <td>Total</td>
                <td class="tc">—</td>
                <td class="tr">{{ number_format($totals['total_qty'], 0) }}</td>
            </tr>
        </tfoot></table>
        @endif
    </div>
</div>

{{-- DR List (paginated so a long date range doesn't render hundreds of blocks at once) --}}
<div class="sec-card" style="margin-bottom:0">
    <div class="sec-head"><i class="bi bi-file-earmark-text me-1"></i> Delivery Receipts</div>
    <div style="padding:.75rem">
        @forelse($byDr as $dr)
        <details class="dr-block" open>
            <summary class="dr-header">
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
            </summary>
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
        </details>
        @empty
        <div style="text-align:center;color:var(--text-muted);padding:3rem;font-size:.82rem">
            <i class="bi bi-truck" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
            No deliveries found in this period.
        </div>
        @endforelse
    </div>

    @if($drPage->total() > 0)
    <div class="fp-footer no-print">
        <span class="page-info">
            Showing {{ $drPage->firstItem() }}–{{ $drPage->lastItem() }}
            of {{ $drPage->total() }} {{ Str::plural('DR', $drPage->total()) }}
        </span>

        @if($drPage->lastPage() > 1)
        @php $qs = http_build_query(request()->except('page')); @endphp
        <div class="fp-pagination">
            @if($drPage->onFirstPage())
                <span class="pg-btn pg-disabled">&#8592; Prev</span>
            @else
                <a href="{{ $drPage->previousPageUrl() }}&{{ $qs }}" class="pg-btn">&#8592; Prev</a>
            @endif
            @php $current = $drPage->currentPage(); $last = $drPage->lastPage(); $start = max(1,$current-2); $end = min($last,$current+2); @endphp
            @if($start > 1)
                <a href="{{ $drPage->url(1) }}&{{ $qs }}" class="pg-btn">1</a>
                @if($start > 2)<span class="pg-ellipsis">…</span>@endif
            @endif
            @for($p = $start; $p <= $end; $p++)
                @if($p === $current)
                    <span class="pg-btn pg-active">{{ $p }}</span>
                @else
                    <a href="{{ $drPage->url($p) }}&{{ $qs }}" class="pg-btn">{{ $p }}</a>
                @endif
            @endfor
            @if($end < $last)
                @if($end < $last - 1)<span class="pg-ellipsis">…</span>@endif
                <a href="{{ $drPage->url($last) }}&{{ $qs }}" class="pg-btn">{{ $last }}</a>
            @endif
            @if($drPage->hasMorePages())
                <a href="{{ $drPage->nextPageUrl() }}&{{ $qs }}" class="pg-btn">Next &#8594;</a>
            @else
                <span class="pg-btn pg-disabled">Next &#8594;</span>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>

@endsection
