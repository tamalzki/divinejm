@extends('layouts.sidebar')
@section('page-title', 'Daily Production Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }
    .tile-row { display:grid; grid-template-columns:repeat(6,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:1100px) { .tile-row { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:600px)  { .tile-row { grid-template-columns:repeat(2,1fr); } }
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
    .dt tfoot td { font-weight:700; background:var(--bg-page); border-top:2px solid var(--border); padding:.38rem .7rem; }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }
    .day-block { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:.65rem; overflow:hidden; }
    .day-header { padding:.4rem .85rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.4rem; }
    .day-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }
    .day-meta  { font-size:.68rem; color:var(--text-muted); }
    @media print { .no-print { display:none!important; } }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-clipboard2-data me-1" style="color:var(--accent)"></i>Daily Production Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.daily-production') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

{{-- Summary tiles --}}
<div class="tile-row">
    <div class="sum-tile">
        <span class="sum-tile-label">Reports</span>
        <span class="sum-tile-value" style="color:var(--accent)">{{ $totals['reports'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Total Mixes</span>
        <span class="sum-tile-value" style="color:#0891b2">{{ number_format($totals['number_of_mix'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Actual Yield</span>
        <span class="sum-tile-value" style="color:#16a34a">{{ number_format($totals['actual_yield'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Packed</span>
        <span class="sum-tile-value" style="color:#7c3aed">{{ number_format($totals['packed'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Unpacked</span>
        <span class="sum-tile-value" style="color:#b45309">{{ number_format($totals['unpacked'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Rejects</span>
        <span class="sum-tile-value" style="color:#dc2626">{{ number_format($totals['rejects'], 0) }}</span>
    </div>
</div>

{{-- Summary by Product --}}
<div class="sec-card">
    <div class="sec-head"><i class="bi bi-box-seam me-1"></i> Summary by Product</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tc">Mixes</th>
                    <th class="tr">Actual Yield</th>
                    <th class="tr">Packed</th>
                    <th class="tr">Unpacked</th>
                    <th class="tr">Rejects</th>
                    <th class="tr">Pack Rate</th>
                </tr>
            </thead>
            <tbody>
            @forelse($byProduct as $row)
                @php $packRate = $row['actual_yield'] > 0 ? ($row['packed'] / $row['actual_yield']) * 100 : 0; @endphp
                <tr>
                    <td style="font-weight:600">{{ $row['product_name'] }}</td>
                    <td class="tc">{{ $row['number_of_mix'] }}</td>
                    <td class="tr" style="font-weight:700">{{ number_format($row['actual_yield'], 0) }}</td>
                    <td class="tr" style="color:#7c3aed;font-weight:700">{{ number_format($row['packed'], 0) }}</td>
                    <td class="tr" style="color:#b45309">{{ number_format($row['unpacked'], 0) }}</td>
                    <td class="tr" style="color:#dc2626">{{ number_format($row['rejects'], 0) }}</td>
                    <td class="tr">
                        <span style="font-weight:600;color:{{ $packRate >= 90 ? '#16a34a' : ($packRate >= 70 ? '#d97706' : '#dc2626') }}">
                            {{ number_format($packRate, 1) }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">No data in this period.</td></tr>
            @endforelse
            </tbody>
            @if($byProduct->count())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="tc">{{ number_format($totals['number_of_mix'], 0) }}</td>
                    <td class="tr">{{ number_format($totals['actual_yield'], 0) }}</td>
                    <td class="tr" style="color:#7c3aed">{{ number_format($totals['packed'], 0) }}</td>
                    <td class="tr" style="color:#b45309">{{ number_format($totals['unpacked'], 0) }}</td>
                    <td class="tr" style="color:#dc2626">{{ number_format($totals['rejects'], 0) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Per-Day Details --}}
@forelse($reports as $report)
<div class="day-block">
    <div class="day-header">
        <span class="day-title">
            <i class="bi bi-calendar3" style="color:var(--accent)"></i>
            {{ $report->production_date->format('l, M d, Y') }}
        </span>
        <span class="day-meta">
            {{ $report->entries->count() }} {{ Str::plural('product', $report->entries->count()) }}
            &middot; Yield: <strong>{{ number_format($report->entries->sum('actual_yield'), 0) }}</strong>
            &middot; Packed: <strong style="color:#7c3aed">{{ number_format($report->entries->sum('packed_quantity'), 0) }}</strong>
            &middot; Rejects: <strong style="color:#dc2626">{{ number_format($report->entries->sum('rejects'), 0) }}</strong>
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tc">Mixes</th>
                    <th class="tr">Std Yield</th>
                    <th class="tr">Actual Yield</th>
                    <th class="tr">Packed</th>
                    <th class="tr">Unpacked</th>
                    <th class="tr">Rejects</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            @foreach($report->entries as $entry)
            <tr>
                <td style="font-weight:600">{{ optional($entry->finishedProduct)->name ?? '—' }}</td>
                <td class="tc">{{ $entry->number_of_mix }}</td>
                <td class="tr" style="color:var(--text-muted)">{{ number_format($entry->standard_yield, 0) }}</td>
                <td class="tr" style="font-weight:700;color:#16a34a">{{ number_format($entry->actual_yield, 0) }}</td>
                <td class="tr" style="font-weight:700;color:#7c3aed">{{ number_format($entry->packed_quantity, 0) }}</td>
                <td class="tr" style="color:#b45309">{{ number_format($entry->unpacked ?? 0, 0) }}</td>
                <td class="tr" style="color:#dc2626">{{ number_format($entry->rejects, 0) }}</td>
                <td style="font-size:.70rem;color:var(--text-muted)">{{ $entry->notes ?: '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div style="text-align:center;color:var(--text-muted);padding:3rem;font-size:.82rem">
    <i class="bi bi-clipboard2-x" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
    No daily production records in this period.
</div>
@endforelse

@endsection
