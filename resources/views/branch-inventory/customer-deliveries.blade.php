@extends('layouts.sidebar')
@section('page-title', 'Customer — Deliveries')
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.73rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    .cust-header { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.9rem 1.1rem; margin:.6rem 0 1rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    .cust-title { font-size:1rem; font-weight:700; color:var(--text-primary); }
    .cust-meta { font-size:.72rem; color:var(--text-muted); margin-top:.15rem; }

    .btn-new { display:inline-flex; align-items:center; gap:.3rem; padding:.45rem 1rem; background:var(--accent); color:#fff !important; border-radius:6px; font-size:.82rem; font-weight:700; text-decoration:none !important; border:none; cursor:pointer; transition:background .14s; white-space:nowrap; }
    .btn-new:hover { background:var(--accent-hover); }

    .bi-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text-primary); }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }

    .btn-view { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem; border:1px solid var(--accent); color:var(--accent) !important; background:transparent; border-radius:5px; font-size:.72rem; font-weight:600; text-decoration:none !important; transition:all .13s; cursor:pointer; }
    .btn-view:hover { background:var(--accent); color:#fff !important; }
    .prod-badge { display:inline-flex; align-items:center; background:#e2e8f0; color:var(--text-secondary); border-radius:20px; padding:.12rem .6rem; font-size:.71rem; font-weight:600; white-space:nowrap; }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2.2rem; display:block; margin-bottom:.5rem; opacity:.25; }
    .empty-state p { font-size:.8rem; margin:.25rem 0 0; }
</style>

<a href="{{ route('branch-inventory.show', $branch) }}" class="dj-back">
    <i class="bi bi-arrow-left"></i> {{ $branch->name }} — Customers
</a>

<div class="cust-header">
    <div>
        <div class="cust-title"><i class="bi bi-person-circle me-1" style="color:var(--accent)"></i>{{ $branchCustomer->name }}</div>
        <div class="cust-meta">
            {{ $branch->name }}
            @if($branchCustomer->phone) &middot; {{ $branchCustomer->phone }} @endif
            &middot; {{ $deliveries->count() }} {{ Str::plural('delivery', $deliveries->count()) }}
        </div>
    </div>
    <a href="{{ route('branch-inventory.create-delivery', ['branch_id' => $branch->id, 'customer' => $branchCustomer->name]) }}" class="btn-new">
        <i class="bi bi-truck me-1"></i> Deliver to {{ $branchCustomer->name }}
    </a>
</div>

<div class="bi-card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="white-space:nowrap">Date &darr;</th>
                    <th>DR #</th>
                    <th style="white-space:nowrap">Products</th>
                    <th class="text-end">Total Qty</th>
                    <th class="text-end">Total Amount</th>
                    <th>By</th>
                    <th class="text-center" style="width:8%"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($deliveries as $delivery)
            <tr>
                <td style="white-space:nowrap;font-size:.77rem;color:var(--text-secondary)">
                    {{ \Carbon\Carbon::parse($delivery->movement_date)->format('M d, Y') }}
                </td>
                <td>
                    <a href="{{ route('branch-inventory.show-delivery', $delivery->dr_number) }}"
                       style="font-weight:700;color:var(--accent);text-decoration:none;font-size:.82rem">
                        {{ $delivery->dr_number }}
                    </a>
                </td>
                <td class="bi-products-cell">
                    <span class="prod-badge">{{ $delivery->product_count }} {{ Str::plural('product', $delivery->product_count) }}</span>
                </td>
                <td class="text-end" style="font-size:.80rem">{{ number_format($delivery->total_qty, 2) }}</td>
                <td class="text-end" style="font-size:.80rem;font-weight:600;color:var(--s-success-text)">
                    @if($delivery->total_value > 0)
                        &#8369;{{ number_format($delivery->total_value, 2) }}
                    @else
                        <span style="color:var(--text-muted)">—</span>
                    @endif
                </td>
                <td style="font-size:.76rem;color:var(--text-secondary)">{{ $delivery->recorded_by }}</td>
                <td class="text-center">
                    <a href="{{ route('branch-inventory.show-delivery', $delivery->dr_number) }}" class="btn-view">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i class="bi bi-truck"></i>
                        <p>No deliveries yet for {{ $branchCustomer->name }}.</p>
                        <p>
                            <a href="{{ route('branch-inventory.create-delivery', ['branch_id' => $branch->id, 'customer' => $branchCustomer->name]) }}" style="color:var(--accent)">
                                Make the first delivery
                            </a>
                        </p>
                    </div>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
