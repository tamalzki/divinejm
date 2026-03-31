@extends('layouts.sidebar')
@section('page-title', 'AR — ' . $customerName)
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); }
    .dj-back:hover { background:var(--bg-page); }

    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); flex-wrap:wrap; gap:.5rem; }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }
    .data-table tfoot td { padding:.52rem .9rem; background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.78rem; font-weight:700; }

    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }

    .age-current { color:var(--text-muted); font-size:.72rem; }
    .age-mid     { color:var(--s-warning-text); font-weight:600; font-size:.72rem; }
    .age-overdue { color:var(--s-danger-text);  font-weight:700; font-size:.72rem; }

    .btn-collect { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; padding:.22rem .6rem; border-radius:4px; border:1px solid var(--s-success-text); color:var(--s-success-text); background:transparent; text-decoration:none; white-space:nowrap; }
    .btn-collect:hover { background:var(--s-success-bg); color:var(--s-success-text); }

    .summary-bar { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-bottom:1rem; }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.7rem 1rem; }
    .sum-tile-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .sum-tile-value { font-size:.95rem; font-weight:700; }
    .sum-tile-value.red   { color:var(--s-danger-text); }
    .sum-tile-value.green { color:var(--s-success-text); }
</style>

{{-- Header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('ar.index') }}" class="dj-back">
        <i class="bi bi-arrow-left"></i> AR
    </a>
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-person-circle me-1" style="color:var(--accent)"></i>{{ $customerName }}
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">
            {{ $branch->name }} &middot; {{ $sales->count() }} unpaid {{ Str::plural('DR', $sales->count()) }}
        </span>
    </div>
</div>

{{-- Summary --}}
<div class="summary-bar">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Billed</span>
        <span class="sum-tile-value">&#8369;{{ number_format($sales->sum('total_amount'), 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Amount Paid</span>
        <span class="sum-tile-value green">&#8369;{{ number_format($sales->sum('amount_paid'), 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Outstanding Balance</span>
        <span class="sum-tile-value red">&#8369;{{ number_format($sales->sum('balance'), 2) }}</span>
    </div>
</div>

{{-- Unpaid DRs --}}
<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-file-earmark-text" style="color:var(--accent)"></i>
            Outstanding Delivery Receipts
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>DR #</th>
                    <th>Date</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th>Age</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sales as $sale)
            @php $days = \Carbon\Carbon::parse($sale->sale_date)->diffInDays(now()); @endphp
            <tr>
                <td><span style="font-weight:700;color:var(--accent)">{{ $sale->dr_number }}</span></td>
                <td style="font-size:.77rem;color:var(--text-secondary);white-space:nowrap">
                    {{ $sale->sale_date->format('M d, Y') }}
                </td>
                <td class="text-end" style="font-weight:600">&#8369;{{ number_format($sale->total_amount, 2) }}</td>
                <td class="text-end" style="color:var(--s-success-text)">&#8369;{{ number_format($sale->amount_paid, 2) }}</td>
                <td class="text-end">
                    <strong style="color:var(--s-danger-text)">&#8369;{{ number_format($sale->balance, 2) }}</strong>
                </td>
                <td>
                    @if($sale->payment_status === 'partial')
                        <span class="pill pill-warning">Partial</span>
                    @else
                        <span class="pill pill-danger">To Collect</span>
                    @endif
                </td>
                <td>
                    @if($days <= 30)
                        <span class="age-current">{{ $days }}d</span>
                    @elseif($days <= 60)
                        <span class="age-mid">{{ $days }}d</span>
                    @else
                        <span class="age-overdue">{{ $days }}d overdue</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('sales.dr', $sale->id) }}" class="btn-collect">
                        <i class="bi bi-cash-coin"></i> Collect
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted)">
                All DRs collected for this customer!
            </td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Total Outstanding</td>
                    <td class="text-end">&#8369;{{ number_format($sales->sum('balance'), 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection