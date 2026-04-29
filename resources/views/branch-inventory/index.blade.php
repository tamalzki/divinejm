@extends('layouts.sidebar')
@section('page-title', 'Deliver Products')
@section('content')

<style>
    .bi-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text-primary); }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }

    .bi-toolbar { display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; flex-wrap:wrap; }
    .bi-search-wrap { position:relative; display:flex; align-items:center; }
    .bi-search-icon { position:absolute; left:.65rem; color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .bi-search-input { height:32px; padding:0 1rem 0 2rem; border:1px solid var(--border); border-radius:6px; font-size:.79rem; color:var(--text-primary); background:var(--bg-card); width:260px; outline:none; }
    .bi-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .bi-search-input::placeholder { color:var(--text-muted); }

    .btn-new { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .85rem; background:var(--accent); color:#fff !important; border-radius:6px; font-size:.78rem; font-weight:600; text-decoration:none !important; border:none; cursor:pointer; transition:background .14s; white-space:nowrap; }
    .btn-new:hover { background:var(--accent-hover); }

    .btn-view { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem; border:1px solid var(--accent); color:var(--accent) !important; background:transparent; border-radius:5px; font-size:.72rem; font-weight:600; text-decoration:none !important; transition:all .13s; cursor:pointer; }
    .btn-view:hover { background:var(--accent); color:#fff !important; }

    .btn-del { display:inline-flex; align-items:center; gap:.2rem; padding:.18rem .55rem; border:1px solid #dc2626; color:#dc2626 !important; background:transparent; border-radius:5px; font-size:.72rem; font-weight:600; text-decoration:none !important; transition:all .13s; cursor:pointer; }
    .btn-del:hover { background:#fef2f2; color:#991b1b !important; border-color:#991b1b; }

    .dr-badge { display:inline-block; background:var(--accent-light); color:var(--accent); border-radius:4px; padding:.1rem .45rem; font-size:.72rem; font-weight:700; }
    .prod-badge { display:inline-block; background:#e2e8f0; color:var(--text-secondary); border-radius:20px; padding:.1rem .55rem; font-size:.71rem; font-weight:600; }
    .val-text { font-weight:700; color:var(--s-success-text); }

    .bi-footer { display:flex; align-items:center; justify-content:space-between; padding:.5rem .9rem; border-top:1px solid var(--border); background:var(--bg-page); font-size:.72rem; color:var(--text-muted); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2.2rem; display:block; margin-bottom:.5rem; opacity:.25; }
    .empty-state p { font-size:.8rem; margin:.25rem 0 0; }

</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-truck me-2" style="color:var(--accent)"></i>Deliveries
        </h5>
        <p class="mb-0" style="font-size:.71rem;color:var(--text-muted)">All product deliveries to customers</p>
    </div>
    <a href="{{ route('branch-inventory.create-delivery') }}" class="btn-new">
        <i class="bi bi-plus-lg"></i> Deliver to Customer
    </a>
</div>

{{-- Toolbar --}}
<div class="bi-toolbar">
    <div class="bi-search-wrap">
        <i class="bi bi-search bi-search-icon"></i>
        <input type="text" id="searchInput" class="bi-search-input" placeholder="Search DR#, customer, area..." autocomplete="off">
    </div>
</div>

{{-- Table --}}
<div class="bi-card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="white-space:nowrap">Date &darr;</th>
                    <th>DR #</th>
                    <th>Flow</th>
                    <th>Products</th>
                    <th class="text-end">Total Qty</th>
                    <th class="text-end">Total Amount</th>
                    <th>By</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
            @forelse($deliveries as $delivery)
            <tr class="delivery-row">
                <td style="white-space:nowrap;font-size:.77rem;color:var(--text-secondary)">
                    {{ \Carbon\Carbon::parse($delivery->movement_date)->format('M d, Y') }}
                </td>
                <td>
                    <a href="{{ route('branch-inventory.show-delivery', $delivery->dr_number) }}"
                       style="font-weight:700;color:var(--accent);text-decoration:none;font-size:.82rem">
                        {{ $delivery->dr_number }}
                    </a>
                </td>
                <td>
                    <span style="color:var(--text-muted);font-size:.73rem">Warehouse</span>
                    <span style="color:var(--text-muted);font-size:.73rem"> &rarr; </span>
                    <span style="color:var(--text-secondary);font-size:.75rem">{{ $delivery->branch_name }}</span>
                    <span style="color:var(--text-muted);font-size:.73rem"> &rarr; </span>
                    <strong style="font-size:.80rem;color:var(--text-primary)">{{ $delivery->customer_name }}</strong>
                </td>
                <td>
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
                    <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-center">
                        <a href="{{ route('branch-inventory.show-delivery', $delivery->dr_number) }}" class="btn-view">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <form method="POST"
                              action="{{ route('branch-inventory.destroy-delivery-batch') }}"
                              class="d-inline"
                              onsubmit="return confirm('Remove this delivery?\n\nDR# {{ $delivery->dr_number }}\n{{ $delivery->branch_name }} → {{ $delivery->customer_name }}\n{{ \Carbon\Carbon::parse($delivery->movement_date)->format('M d, Y') }}\n\nWarehouse stock will be restored and quantities removed from the area. Blocked if this DR has payments or sold/BO quantities.');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="dr_number" value="{{ $delivery->dr_number }}">
                            <input type="hidden" name="branch_id" value="{{ $delivery->branch_id }}">
                            <input type="hidden" name="movement_date" value="{{ \Carbon\Carbon::parse($delivery->movement_date)->format('Y-m-d') }}">
                            <input type="hidden" name="user_id" value="{{ $delivery->user_id }}">
                            <button type="submit" class="btn-del"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8">
                    <div class="empty-state">
                        <i class="bi bi-truck"></i>
                        <p>No deliveries yet.</p>
                        <p><a href="{{ route('branch-inventory.create-delivery') }}" style="color:var(--accent)">Make your first delivery</a></p>
                    </div>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bi-footer">
        <span>Showing {{ $deliveries->firstItem() ?? 0 }}–{{ $deliveries->lastItem() ?? 0 }} of {{ $deliveries->total() }} {{ Str::plural('result', $deliveries->total()) }}</span>
        <div>{{ $deliveries->appends(request()->query())->links() }}</div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function() {
    var val = this.value.toLowerCase().trim();
    if (!val) {
        document.querySelectorAll('.delivery-row').forEach(function(r){ r.style.display=''; });
        return;
    }
    document.querySelectorAll('.delivery-row').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
    });
});
</script>

@endsection