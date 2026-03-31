@extends('layouts.sidebar')
@section('page-title', $customerName . ' — DRs')
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); cursor:pointer; }

    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-info    { background:var(--s-info-bg);    color:var(--s-info-text); }

    .btn-view { display:inline-flex; align-items:center; gap:.25rem; font-size:.72rem; padding:.22rem .6rem; border-radius:4px; border:1px solid var(--accent); color:var(--accent); background:transparent; text-decoration:none; white-space:nowrap; }
    .btn-view:hover { background:var(--accent); color:#fff; }
    .btn-del-dr { display:inline-flex; align-items:center; gap:.2rem; font-size:.7rem; padding:.2rem .5rem; border-radius:4px; border:1px solid #dc2626; color:#dc2626; background:transparent; cursor:pointer; white-space:nowrap; }
    .btn-del-dr:hover { background:#fef2f2; color:#991b1b; }

    .dj-search-wrap { position:relative; }
    .dj-search-icon { position:absolute; left:.65rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .dj-search-input { padding:.32rem .7rem .32rem 2rem; font-size:.78rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:220px; outline:none; }
    .dj-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.82rem; }

    .balance-red   { color:var(--s-danger-text);  font-weight:700; }
    .balance-green { color:var(--s-success-text); font-weight:700; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('sales.index') }}" class="dj-back">
            <i class="bi bi-arrow-left"></i> Sales
        </a>
        <div>
            <h5 class="fw-bold mb-0" style="font-size:.93rem">
                <i class="bi bi-person-circle me-1" style="color:var(--accent)"></i>{{ $customerName }}
            </h5>
            <span style="font-size:.68rem;color:var(--text-muted)">
                {{ $branch->name }} &middot; {{ $sales->count() }} {{ Str::plural('DR', $sales->count()) }}
            </span>
        </div>
    </div>
    <div class="dj-search-wrap">
        <i class="bi bi-search dj-search-icon"></i>
        <input type="text" id="searchInput" class="dj-search-input" placeholder="Search DR#..." autocomplete="off">
    </div>
</div>

{{-- DR list --}}
<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title"><i class="bi bi-file-earmark-text" style="color:var(--accent)"></i> Delivery Receipts</span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>DR #</th>
                    <th>Date</th>
                    <th class="text-center">Products</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Balance</th>
                    <th>Payment</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="drTableBody">
            @forelse($sales as $sale)
            <tr class="dr-row" onclick="window.location='{{ route('sales.dr', $sale->id) }}'" data-dr="{{ strtolower($sale->dr_number) }}">
                <td>
                    <span style="font-weight:700;color:var(--accent)">{{ $sale->dr_number }}</span>
                </td>
                <td style="font-size:.77rem;color:var(--text-secondary);white-space:nowrap">
                    {{ $sale->sale_date->format('M d, Y') }}
                </td>
                <td class="text-center">
                    <span class="pill pill-info">{{ $sale->items->count() }} {{ Str::plural('item', $sale->items->count()) }}</span>
                </td>
                <td class="text-end" style="font-weight:600">&#8369;{{ number_format($sale->total_amount, 2) }}</td>
                <td class="text-end">
                    <span class="{{ $sale->balance > 0 ? 'balance-red' : 'balance-green' }}">
                        &#8369;{{ number_format($sale->balance, 2) }}
                    </span>
                </td>
                <td>
                    @if($sale->payment_status === 'paid')
                        <span class="pill pill-success">Paid</span>
                    @elseif($sale->payment_status === 'partial')
                        <span class="pill pill-warning">Partial</span>
                    @else
                        <span class="pill pill-danger">To Collect</span>
                    @endif
                </td>
                <td class="text-center" onclick="event.stopPropagation()">
                    <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-center">
                        <a href="{{ route('sales.dr', $sale->id) }}" class="btn-view">
                            <i class="bi bi-pencil-square"></i> Record Sales
                        </a>
                        <form method="POST" action="{{ route('sales.destroy', $sale->id) }}" class="d-inline-flex flex-column align-items-center gap-1"
                              data-dr="{{ $sale->dr_number }}"
                              onsubmit="return confirmDestroySaleForm(this);"
                              onclick="event.stopPropagation();">
                            @csrf
                            @method('DELETE')
                            <label class="form-check m-0" style="font-size:.60rem;line-height:1.2;max-width:9.5rem;text-align:center;cursor:pointer" onclick="event.stopPropagation();">
                                <input type="checkbox" name="orphan_delete" value="1" class="form-check-input" style="float:none;margin:0 .2rem 0 0;vertical-align:middle">
                                <span class="form-check-label">Test/orphan — no inventory undo</span>
                            </label>
                            <button type="submit" class="btn-del-dr"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-state">No DRs found for this customer.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDestroySaleForm(form) {
    var dr = form.getAttribute('data-dr') || '';
    if (form.querySelector('input[name="orphan_delete"]:checked')) {
        return confirm('ORPHAN / TEST DELETE — DR# ' + dr + '\n\nThis removes the DR from sales only. Warehouse and area stock will NOT be changed.\n\nOnly use if this DR never had a matching “Deliver Products” record (or you accept that inventory stays as-is).\n\nContinue?');
    }
    return confirm('Delete DR# ' + dr + '?\n\n• Removes this DR and all sold / BO / payment data stored on it\n• Restores main warehouse stock, production batches, and area inventory using the original delivery movements\n\nIf area stock is lower than this delivery (e.g. after returns), deletion may fail until inventory matches.\n\nThis cannot be undone. Continue?');
}
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.dr-row').forEach(function(row) {
        row.style.display = (!q || row.dataset.dr.includes(q)) ? '' : 'none';
    });
});
</script>

@endsection