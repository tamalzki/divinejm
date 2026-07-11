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

    .btn-new { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .85rem; background:var(--accent); color:#fff !important; border-radius:6px; font-size:.78rem; font-weight:600; text-decoration:none !important; border:none; cursor:pointer; transition:background .14s; white-space:nowrap; }
    .btn-new:hover { background:var(--accent-hover); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2.2rem; display:block; margin-bottom:.5rem; opacity:.25; }
    .empty-state p { font-size:.8rem; margin:.25rem 0 0; }
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
    <a href="{{ route('branch-inventory.create-delivery', ['branch_id' => $branch->id]) }}" class="btn-new">
        <i class="bi bi-plus-lg"></i> Deliver to Customer
    </a>
</div>

<div class="bi-card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th class="text-end">Deliveries</th>
                    <th class="text-center" style="width:10%"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($customers as $customer)
            <tr class="cust-row" onclick="window.location='{{ route('branch-inventory.customer-deliveries', [$branch, $customer]) }}'">
                <td class="cust-name"><i class="bi bi-person-circle me-1" style="color:var(--accent)"></i>{{ $customer->name }}</td>
                <td style="font-size:.78rem;color:var(--text-secondary)">{{ $customer->phone ?: '—' }}</td>
                <td class="text-end" style="font-size:.8rem">{{ $customer->delivery_count }}</td>
                <td class="text-center">
                    <a href="{{ route('branch-inventory.customer-deliveries', [$branch, $customer]) }}" style="color:var(--accent);font-size:.78rem;text-decoration:none;font-weight:600">
                        View <i class="bi bi-chevron-right"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4">
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

@endsection
