@extends('layouts.sidebar')
@section('page-title', 'Accounts Receivable')
@section('content')

<style>
    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); flex-wrap:wrap; gap:.5rem; }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .area-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); padding:.45rem 1rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; gap:.4rem; }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }

    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-info    { background:var(--s-info-bg);    color:var(--s-info-text); }

    .age-current  { color:var(--text-muted); font-size:.72rem; }
    .age-mid      { color:var(--s-warning-text); font-weight:600; font-size:.72rem; }
    .age-overdue  { color:var(--s-danger-text);  font-weight:700; font-size:.72rem; }

    .btn-view { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; padding:.22rem .6rem; border-radius:4px; border:1px solid var(--accent); color:var(--accent); background:transparent; text-decoration:none; white-space:nowrap; }
    .btn-view:hover { background:var(--accent); color:#fff; }

    .summary-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1rem; }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.7rem 1rem; }
    .sum-tile-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .sum-tile-value { font-size:1rem; font-weight:700; color:var(--text-primary); }
    .sum-tile-value.red   { color:var(--s-danger-text); }
    .sum-tile-value.amber { color:var(--s-warning-text); }
    .sum-tile-value.green { color:var(--s-success-text); }

    .dj-search-wrap { position:relative; }
    .dj-search-icon { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .dj-search-input { padding:.32rem .7rem .32rem 2rem; font-size:.78rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:220px; outline:none; }
    .dj-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.82rem; }
</style>

@if(session('success'))
<div class="alert-bar success"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
@endif

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-journal-text me-1" style="color:var(--accent)"></i>Accounts Receivable
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">Outstanding balances from deliveries</span>
    </div>
    <div class="dj-search-wrap">
        <i class="bi bi-search dj-search-icon"></i>
        <input type="text" id="searchInput" class="dj-search-input" placeholder="Search customer..." autocomplete="off">
    </div>
</div>

{{-- Summary tiles --}}
<div class="summary-bar">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Outstanding</span>
        <span class="sum-tile-value red">&#8369;{{ number_format($totalOutstanding, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Current (0–30 days)</span>
        <span class="sum-tile-value green">&#8369;{{ number_format($agingCurrent, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">31–60 Days</span>
        <span class="sum-tile-value amber">&#8369;{{ number_format($agingMid, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">60+ Days</span>
        <span class="sum-tile-value red">&#8369;{{ number_format($agingOld, 2) }}</span>
    </div>
</div>

{{-- AR list grouped by area --}}
@forelse($areaData as $area)
<div class="dj-card">
    <div class="area-label">
        <i class="bi bi-geo-alt-fill" style="color:var(--accent)"></i>
        {{ $area['branch']->name }}
        <span style="font-weight:400;color:var(--text-muted)">({{ $area['customers']->count() }} {{ Str::plural('customer', $area['customers']->count()) }})</span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="text-center">Unpaid DRs</th>
                    <th class="text-end">Total Billed</th>
                    <th class="text-end">Amount Paid</th>
                    <th class="text-end">Outstanding</th>
                    <th>Oldest DR</th>
                    <th>Aging</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($area['customers'] as $customer)
            <tr class="ar-row">
                <td><span style="font-weight:600;font-size:.82rem">{{ $customer['customer_name'] }}</span></td>
                <td class="text-center">
                    <span class="pill pill-danger">{{ $customer['unpaid_dr_count'] }}</span>
                </td>
                <td class="text-end">&#8369;{{ number_format($customer['total_billed'], 2) }}</td>
                <td class="text-end" style="color:var(--s-success-text)">&#8369;{{ number_format($customer['total_paid'], 2) }}</td>
                <td class="text-end">
                    <strong style="color:var(--s-danger-text)">&#8369;{{ number_format($customer['total_outstanding'], 2) }}</strong>
                </td>
                <td style="font-size:.76rem;color:var(--text-muted)">
                    {{ $customer['oldest_dr_date'] ? \Carbon\Carbon::parse($customer['oldest_dr_date'])->format('M d, Y') : '—' }}
                </td>
                <td>
                    @php $days = $customer['oldest_days']; @endphp
                    @if($days <= 30)
                        <span class="age-current">{{ $days }}d</span>
                    @elseif($days <= 60)
                        <span class="age-mid">{{ $days }}d</span>
                    @else
                        <span class="age-overdue">{{ $days }}d overdue</span>
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('ar.customer', [$area['branch']->id, rawurlencode($customer['customer_name'])]) }}"
                       class="btn-view">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="empty-state">
    <i class="bi bi-check-circle" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
    No outstanding balances. All DRs are collected!
</div>
@endforelse

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.ar-row').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

@endsection