@extends('layouts.sidebar')
@section('page-title', 'Production Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .tile-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:900px) { .tile-row { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:480px) { .tile-row { grid-template-columns:1fr; } }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .sum-tile-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .sum-tile-value { font-size:.95rem; font-weight:700; }
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .dt { width:100%; border-collapse:collapse; font-size:.74rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .6rem; font-size:.62rem; font-weight:700; text-transform:uppercase; }
    .dt tbody td { padding:.36rem .6rem; border-bottom:1px solid var(--border); }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt .tc { text-align:center; }
    .dt .tr { text-align:right; }
    .pill { display:inline-flex; padding:.05rem .35rem; border-radius:3px; font-size:.62rem; font-weight:700; }
    .pill-ok { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-warn { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-muted { background:var(--bg-page); color:var(--text-muted); }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-gear-wide-connected me-1" style="color:var(--accent)"></i>Production Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.production') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

<div class="tile-row">
    <div class="sum-tile">
        <span class="sum-tile-label">Batches</span>
        <span class="sum-tile-value" style="color:var(--accent)">{{ $totals['batches'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Actual Output</span>
        <span class="sum-tile-value" style="color:#16a34a">{{ number_format($totals['actual_output'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Rejected</span>
        <span class="sum-tile-value" style="color:#dc2626">{{ number_format($totals['rejected'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Good Output</span>
        <span class="sum-tile-value" style="color:#0891b2">{{ number_format($totals['good'], 0) }}</span>
    </div>
</div>

<div class="sec-card">
    <div class="sec-head">Summary by Product</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="tc">Batches</th>
                    <th class="tr">Actual</th>
                    <th class="tr">Rejected</th>
                </tr>
            </thead>
            <tbody>
            @forelse($byProduct as $row)
                <tr>
                    <td style="font-weight:600">{{ optional($row->product)->name ?? '—' }}</td>
                    <td class="tc">{{ $row->batch_count }}</td>
                    <td class="tr">{{ number_format($row->sum_actual, 0) }}</td>
                    <td class="tr" style="color:var(--s-danger-text)">{{ number_format($row->sum_rejected, 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem">No production in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="sec-card">
    <div class="sec-head">Batch Detail</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Batch</th>
                    <th>Product</th>
                    <th class="tc">Status</th>
                    <th class="tr">Expected</th>
                    <th class="tr">Actual</th>
                    <th class="tr">Rejected</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($mixes as $m)
                <tr>
                    <td style="white-space:nowrap">{{ $m->mix_date->format('M d, Y') }}</td>
                    <td style="font-weight:600;color:var(--accent)">{{ $m->batch_number }}</td>
                    <td>{{ $m->finishedProduct->name ?? '—' }}</td>
                    <td class="tc">
                        @if($m->status === 'completed')
                            <span class="pill pill-ok">Done</span>
                        @elseif($m->status === 'pending')
                            <span class="pill pill-warn">Pending</span>
                        @else
                            <span class="pill pill-muted">{{ $m->status }}</span>
                        @endif
                    </td>
                    <td class="tr">{{ number_format($m->total_expected_output, 0) }}</td>
                    <td class="tr" style="font-weight:600">{{ number_format($m->actual_output, 0) }}</td>
                    <td class="tr" style="color:var(--s-danger-text)">{{ number_format($m->rejected_quantity, 0) }}</td>
                    <td style="font-size:.70rem;color:var(--text-muted)">{{ $m->user->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">No batches in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
