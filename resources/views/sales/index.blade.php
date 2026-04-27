@extends('layouts.sidebar')
@section('page-title', 'Sales')
@section('content')

<style>
    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .area-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); padding:.45rem 1rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; gap:.4rem; }
    .area-label i { color:var(--accent); }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text-primary); }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }

    .pill { display:inline-block; padding:.1rem .45rem; border-radius:4px; font-size:.68rem; font-weight:600; }
    .pill-danger   { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success  { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-info     { background:var(--s-info-bg);    color:var(--s-info-text); }

    .btn-view { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; padding:.22rem .6rem; border-radius:4px; border:1px solid var(--accent); color:var(--accent); background:transparent; text-decoration:none; white-space:nowrap; }
    .btn-view:hover { background:var(--accent); color:#fff; }

    .dj-search-wrap { position:relative; }
    .dj-search-icon { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .dj-search-input { padding:.32rem .7rem .32rem 2rem; font-size:.78rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:220px; outline:none; }
    .dj-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.82rem; }
    .stat-chip { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; color:var(--text-muted); }
    .summary-bar { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.7rem .9rem; }
    .sum-tile-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .sum-tile-value { font-size:.95rem; font-weight:700; color:var(--text-primary); }
    .sum-tile-value.red { color:var(--s-danger-text); }
    .sum-tile-value.green { color:var(--s-success-text); }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-receipt me-1" style="color:var(--accent)"></i>Sales
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">Deliveries grouped by area and customer</span>
    </div>
    <div class="dj-search-wrap">
        <i class="bi bi-search dj-search-icon"></i>
        <input type="text" id="searchInput" class="dj-search-input" placeholder="Search customer, DR#..." autocomplete="off" value="{{ $search ?? '' }}">
    </div>
</div>

<div class="summary-bar">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Sales</span>
        <span class="sum-tile-value green">&#8369;{{ number_format($summary['total_sales'] ?? 0, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Receivable Balance</span>
        <span class="sum-tile-value red">&#8369;{{ number_format($summary['total_balance'] ?? 0, 2) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Total DRs</span>
        <span class="sum-tile-value">{{ number_format($summary['total_drs'] ?? 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Customers</span>
        <span class="sum-tile-value">{{ number_format($summary['total_customers'] ?? 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Qty Sold</span>
        <span class="sum-tile-value">{{ number_format($summary['total_sold_qty'] ?? 0, 2) }}</span>
    </div>
</div>

@forelse($areaData as $area)
<div class="dj-card">
    {{-- Area header --}}
    <div class="area-label">
        <i class="bi bi-geo-alt-fill"></i>
        {{ $area['branch']->name }}
        <span class="ms-1 stat-chip">({{ $area['customers']->count() }} {{ Str::plural('customer', $area['customers']->count()) }})</span>
    </div>

    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="text-center">DRs</th>
                    <th class="text-end">Deployed</th>
                    <th class="text-end">Sold</th>
                    <th class="text-end">Unsold</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($area['customers'] as $customer)
            @php
                $unsold = $customer['total_deployed'] - $customer['total_sold'];
            @endphp
            <tr>
                <td>
                    <span style="font-weight:600;font-size:.82rem">{{ $customer['customer_name'] }}</span>
                </td>
                <td class="text-center">
                    <span class="pill pill-info">{{ $customer['dr_count'] }}</span>
                </td>
                <td class="text-end" style="font-size:.80rem">{{ number_format($customer['total_deployed'], 2) }}</td>
                <td class="text-end" style="font-size:.80rem;color:var(--s-success-text);font-weight:600">
                    {{ number_format($customer['total_sold'], 2) }}
                </td>
                <td class="text-end" style="font-size:.80rem;color:{{ $unsold > 0 ? 'var(--s-warning-text)' : 'var(--text-muted)' }}">
                    {{ number_format($unsold, 2) }}
                </td>
                <td class="text-end" style="font-size:.80rem;font-weight:600;color:{{ $customer['total_balance'] > 0 ? 'var(--s-danger-text)' : 'var(--s-success-text)' }}">
                    &#8369;{{ number_format($customer['total_balance'], 2) }}
                </td>

                <td class="text-center">
                    <a href="{{ route('sales.show', [$area['branch']->id, rawurlencode($customer['customer_name'])]) }}"
                       class="btn-view">
                        <i class="bi bi-eye"></i> View DRs
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
    <i class="bi bi-receipt" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
    No sales yet. Deliveries will appear here automatically.
</div>
@endforelse

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.dj-card').forEach(function(card) {
        var hasMatch = false;
        card.querySelectorAll('.data-table tbody tr').forEach(function(row) {
            var match = !q || row.textContent.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            if (match) hasMatch = true;
        });
        // Hide the entire card (area) if no rows matched
        card.style.display = (q && !hasMatch) ? 'none' : '';
    });
});
</script>

@endsection