@extends('layouts.sidebar')
@section('page-title', 'Expense Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }
    .kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:768px) { .kpi-grid { grid-template-columns:1fr; } }
    .kpi-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; }
    .kpi-tile::before { content:''; display:block; height:3px; border-radius:3px 3px 0 0; margin:-.75rem -1rem .5rem -1rem; background:#dc2626; }
    .kpi-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .kpi-value { font-size:1.02rem; font-weight:700; color:var(--text-primary); }
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .dt { width:100%; border-collapse:collapse; font-size:.74rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .6rem; font-size:.62rem; font-weight:700; text-transform:uppercase; }
    .dt tbody td { padding:.36rem .6rem; border-bottom:1px solid var(--border); }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { font-weight:700; background:var(--bg-page); border-top:2px solid var(--border); }
    .dt .tr { text-align:right; }
    .rpt-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    @media (max-width:900px) { .rpt-grid-2 { grid-template-columns:1fr; } }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-wallet2 me-1" style="color:var(--accent)"></i>Expense Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.expenses') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

<div class="kpi-grid">
    <div class="kpi-tile">
        <span class="kpi-label">Total Expenses</span>
        <span class="kpi-value">&#8369;{{ number_format($totalAmount, 2) }}</span>
    </div>
    <div class="kpi-tile">
        <span class="kpi-label">Transactions</span>
        <span class="kpi-value">{{ $expenses->count() }}</span>
    </div>
    <div class="kpi-tile">
        <span class="kpi-label">Categories</span>
        <span class="kpi-value">{{ $byCategory->count() }}</span>
    </div>
</div>

<div class="rpt-grid-2">
    <div class="sec-card">
        <div class="sec-head">By Category</div>
        <div style="overflow-x:auto">
            <table class="dt">
                <thead>
                    <tr><th>Category</th><th class="tr">Amount</th><th class="tr">%</th></tr>
                </thead>
                <tbody>
                @forelse($byCategory as $row)
                    @php $pct = $totalAmount > 0 ? ($row->total / $totalAmount) * 100 : 0; @endphp
                    <tr>
                        <td style="font-weight:600">{{ ucfirst(str_replace('_', ' ', $row->category)) }}</td>
                        <td class="tr">&#8369;{{ number_format($row->total, 2) }}</td>
                        <td class="tr" style="color:var(--text-muted)">{{ number_format($pct, 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">No expenses in range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="sec-card">
        <div class="sec-head">By Payment Method</div>
        <div style="overflow-x:auto">
            <table class="dt">
                <thead>
                    <tr><th>Method</th><th class="tr">Amount</th></tr>
                </thead>
                <tbody>
                @forelse($byPaymentMethod as $row)
                    <tr>
                        <td style="font-weight:600">{{ ucfirst($row->payment_method ?? '—') }}</td>
                        <td class="tr">&#8369;{{ number_format($row->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" style="text-align:center;color:var(--text-muted);padding:1.5rem">No data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="sec-card">
    <div class="sec-head">Expense Lines</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Method</th>
                    <th class="tr">Amount</th>
                </tr>
            </thead>
            <tbody>
            @forelse($expenses as $e)
                <tr>
                    <td style="white-space:nowrap">{{ $e->expense_date->format('M d, Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $e->category)) }}</td>
                    <td style="max-width:280px">{{ str($e->description ?? '—')->limit(80) }}</td>
                    <td>{{ ucfirst($e->payment_method ?? '—') }}</td>
                    <td class="tr" style="font-weight:600">&#8369;{{ number_format($e->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">No expenses in this period.</td></tr>
            @endforelse
            </tbody>
            @if($expenses->count())
            <tfoot>
                <tr>
                    <td colspan="4" class="tr">Total</td>
                    <td class="tr">&#8369;{{ number_format($totalAmount, 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection
