@extends('layouts.sidebar')
@section('page-title', 'Area — Customers')
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.73rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    .area-header { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.9rem 1.1rem; margin:.6rem 0 1rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    .area-title { font-size:1rem; font-weight:700; color:var(--text-primary); }
    .area-meta { font-size:.72rem; color:var(--text-muted); margin-top:.15rem; }
    .area-badge { display:inline-block; background:#fef3c7; color:#92400e; border-radius:4px; padding:.08rem .4rem; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-left:.4rem; }

    .bi-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.55rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text-primary); }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }
    .cust-row { cursor:pointer; }
    .cust-name { font-weight:600; font-size:.83rem; }

    .bi-toolbar { display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; flex-wrap:wrap; }
    .bi-search-wrap { position:relative; display:flex; align-items:center; }
    .bi-search-icon { position:absolute; left:.65rem; color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .bi-search-input { height:32px; padding:0 1rem 0 2rem; border:1px solid var(--border); border-radius:6px; font-size:.79rem; color:var(--text-primary); background:var(--bg-card); width:260px; outline:none; }
    .bi-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .bi-search-input::placeholder { color:var(--text-muted); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2.2rem; display:block; margin-bottom:.5rem; opacity:.25; }
    .empty-state p { font-size:.8rem; margin:.25rem 0 0; }

    @media (max-width: 640px) {
        .bi-search-wrap, .bi-search-input { width:100%; }
        .area-title { font-size:.92rem; }
    }
</style>

<a href="{{ route('branch-inventory.index') }}" class="dj-back">
    <i class="bi bi-arrow-left"></i> Areas
</a>

<div class="area-header">
    <div>
        <div class="area-title">
            <i class="bi bi-geo-alt me-1" style="color:var(--accent)"></i>{{ $branch->name }}
            @if($branch->is_distributor)<span class="area-badge">Distributor</span>@endif
        </div>
        <div class="area-meta">
            Code: {{ $branch->code }}
            @if($branch->address) &middot; {{ $branch->address }} @endif
            @if($branch->phone) &middot; {{ $branch->phone }} @endif
        </div>
    </div>
</div>

<div class="bi-toolbar">
    <div class="bi-search-wrap">
        <i class="bi bi-search bi-search-icon"></i>
        <input type="text" id="customerSearchInput" class="bi-search-input" placeholder="Search customer..." autocomplete="off">
    </div>
</div>

<div class="bi-card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th class="text-end">Deliveries</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center" style="width:10%"></th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
            @forelse($customers as $customer)
            <tr class="cust-row" data-search="{{ strtolower($customer->name) }}" onclick="window.location='{{ route('branch-inventory.customer-deliveries', [$branch, $customer]) }}'">
                <td class="cust-name"><i class="bi bi-person-circle me-1" style="color:var(--accent)"></i>{{ $customer->name }}</td>
                <td style="font-size:.78rem;color:var(--text-secondary)">{{ $customer->phone ?: '—' }}</td>
                <td class="text-end" style="font-size:.8rem">{{ $customer->delivery_count }}</td>
                <td class="text-end" style="font-size:.8rem;font-weight:700;color:{{ $customer->total_balance > 0 ? 'var(--s-danger-text)' : 'var(--text-muted)' }}">
                    @if($customer->total_balance > 0)
                        &#8369;{{ number_format($customer->total_balance, 2) }}
                    @else
                        &#8369;0.00
                    @endif
                </td>
                <td class="text-center">
                    <a href="{{ route('branch-inventory.customer-deliveries', [$branch, $customer]) }}" style="color:var(--accent);font-size:.78rem;text-decoration:none;font-weight:600">
                        View <i class="bi bi-chevron-right"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <p>No customers assigned to this area yet.</p>
                        <p><a href="{{ route('branches.edit', $branch) }}" style="color:var(--accent)">Add customers</a></p>
                    </div>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('customerSearchInput').addEventListener('input', function() {
    var val = this.value.toLowerCase().trim();
    document.querySelectorAll('#customerTableBody .cust-row').forEach(function(row) {
        row.style.display = (!val || row.dataset.search.includes(val)) ? '' : 'none';
    });
});
</script>

@endsection
