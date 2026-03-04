@extends('layouts.sidebar')

@section('page-title', 'Finished Products')

@section('content')

<style>
    /* ── Page Shell ──────────────────────────────────────────────── */
    .fp-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }
    .fp-header h4 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 .15rem;
        letter-spacing: -.3px;
    }
    .fp-header p { font-size: .78rem; color: #64748b; margin: 0; }

    /* ── Stats Row ───────────────────────────────────────────────── */
    .fp-stats { display: flex; gap: .75rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
    .fp-stat {
        flex: 1 1 150px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: .75rem 1rem;
        display: flex;
        align-items: center;
        gap: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .fp-stat-icon {
        font-size: 1.5rem;
        opacity: .22;
        flex-shrink: 0;
    }
    .fp-stat-label { font-size: .7rem; color: #94a3b8; display: block; }
    .fp-stat-value { font-size: 1.3rem; font-weight: 700; color: #1e293b; line-height: 1.2; }

    /* ── Toolbar ─────────────────────────────────────────────────── */
    .fp-toolbar {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .6rem;
        flex-wrap: wrap;
    }
    .toolbar-label {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .toolbar-divider { color: #e2e8f0; font-size: .9rem; }

    /* Search */
    .fp-search-form { display: flex; }
    .fp-search-wrap { position: relative; display: flex; align-items: center; }
    .fp-search-icon {
        position: absolute; left: .6rem;
        color: #94a3b8; font-size: .8rem; pointer-events: none;
    }
    .fp-search-input {
        height: 30px;
        padding: 0 2rem 0 1.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: .78rem;
        color: #1e293b;
        background: #fff;
        width: 230px;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .fp-search-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13,148,136,.1);
    }
    .fp-search-input::placeholder { color: #cbd5e1; }
    .fp-search-clear {
        position: absolute; right: .5rem;
        color: #94a3b8; font-size: .72rem;
        text-decoration: none !important; line-height: 1;
        transition: color .15s;
    }
    .fp-search-clear:hover { color: #ef4444; }

    /* Search notice */
    .fp-search-notice {
        font-size: .76rem; color: #475569;
        margin-bottom: .75rem;
        padding: .4rem .75rem;
        background: #f0fdfa; border: 1px solid #99f6e4;
        border-radius: 6px;
        display: flex; align-items: center; gap: .25rem;
    }

    /* Filter pills */
    .filter-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .72rem; font-weight: 600;
        text-decoration: none !important;
        border: 1.5px solid transparent;
        transition: all .15s; cursor: pointer;
    }
    .filter-pill.all  { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
    .filter-pill.mfg  { color: #15803d; border-color: #86efac; background: #f0fdf4; }
    .filter-pill.con  { color: #1d4ed8; border-color: #93c5fd; background: #eff6ff; }
    .filter-pill.low  { color: #d97706; border-color: #fcd34d; background: #fffbeb; }
    .filter-pill:hover { filter: brightness(.93); }
    .filter-pill.active { box-shadow: 0 0 0 2px currentColor inset; }

    /* ── Card ────────────────────────────────────────────────────── */
    .fp-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        overflow: hidden;
    }

    /* ── Table ───────────────────────────────────────────────────── */
    .fp-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .fp-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .68rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .6px;
        padding: .6rem .85rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .fp-table tbody td {
        padding: .52rem .85rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }
    .fp-table tbody tr:last-child td { border-bottom: none; }
    .fp-table tbody tr:hover { background: #f8fafc; }

    /* Row tints */
    .row-danger  { background: #fff5f5 !important; }
    .row-warning { background: #fffdf0 !important; }
    .row-danger:hover  { background: #fee2e2 !important; }
    .row-warning:hover { background: #fef9c3 !important; }

    /* Cell helpers */
    .cell-name { font-weight: 600; color: #0f172a; line-height: 1.3; }
    .cell-qty  { font-weight: 700; font-size: .88rem; font-variant-numeric: tabular-nums; }
    .qty-ok    { color: #16a34a; }
    .qty-low   { color: #d97706; }
    .qty-empty { color: #dc2626; }

    mark { background: #fef08a; color: #1e293b; border-radius: 2px; padding: 0 1px; font-style: normal; }

    /* Type tags */
    .type-tag {
        display: inline-block; border-radius: 4px;
        padding: .1rem .45rem; font-size: .69rem; font-weight: 700; letter-spacing: .3px;
    }
    .type-mfg { background: #dcfce7; color: #15803d; }
    .type-con { background: #dbeafe; color: #1d4ed8; }

    /* Status dots */
    .status-dot {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .72rem; font-weight: 600;
    }
    .status-dot::before {
        content: ''; width: 7px; height: 7px;
        border-radius: 50%; flex-shrink: 0;
    }
    .status-ready::before  { background: #16a34a; }
    .status-ready          { color: #16a34a; }
    .status-mix::before    { background: #d97706; }
    .status-mix            { color: #d97706; }
    .status-consign::before { background: #6366f1; }
    .status-consign         { color: #6366f1; }

    /* ── Action Buttons ──────────────────────────────────────────── */
    .act-btn {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .22rem .55rem; border-radius: 5px;
        font-size: .72rem; font-weight: 600;
        border: none; cursor: pointer;
        text-decoration: none !important; white-space: nowrap;
        transition: filter .15s, transform .1s;
    }
    .act-btn:hover  { filter: brightness(.9); transform: translateY(-1px); }
    .act-btn:active { transform: translateY(0); }
    .act-mix     { background: #16a34a; color: #fff; }
    .act-complete { background: #d97706; color: #fff; }
    .act-view    { background: #0891b2; color: #fff; }
    .act-edit    { background: #f59e0b; color: #fff; }
    .act-barcode { background: #1e293b; color: #fff; }
    .act-delete  { background: #ef4444; color: #fff; }

    /* ── Pagination ──────────────────────────────────────────────── */
    .fp-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: .65rem 1rem;
        border-top: 1px solid #f1f5f9; background: #fafafa;
        flex-wrap: wrap; gap: .5rem;
    }
    .fp-footer .page-info { font-size: .73rem; color: #94a3b8; }
    .fp-pagination { display: flex; align-items: center; gap: .2rem; }
    .pg-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 .55rem;
        font-size: .73rem; font-weight: 600;
        border-radius: 5px; border: 1px solid #e2e8f0;
        color: #475569; background: #fff;
        text-decoration: none !important; transition: all .15s; white-space: nowrap;
    }
    a.pg-btn:hover { background: #f0fdfa; border-color: #0d9488; color: #0d9488; }
    .pg-active   { background: #0d9488 !important; border-color: #0d9488 !important; color: #fff !important; cursor: default; }
    .pg-disabled { color: #cbd5e1 !important; border-color: #f1f5f9 !important; background: #fafafa !important; cursor: default; pointer-events: none; }
    .pg-ellipsis { color: #94a3b8; font-size: .73rem; padding: 0 .2rem; line-height: 28px; }

    /* ── Add Button ──────────────────────────────────────────────── */
    .btn-add-product {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .4rem .9rem;
        background: #0d9488; color: #fff;
        border-radius: 7px; font-size: .78rem; font-weight: 700;
        text-decoration: none !important; border: none;
        transition: background .15s, transform .1s;
    }
    .btn-add-product:hover { background: #0f766e; color: #fff; transform: translateY(-1px); }

    /* ── Empty State ─────────────────────────────────────────────── */
    .empty-state { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .empty-state p { font-size: .82rem; margin: 0; }
</style>

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-3"
     style="font-size:.82rem;padding:.6rem .9rem;border-radius:8px">
    <i class="bi bi-check-circle-fill text-success"></i>
    <span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.7rem"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3"
     style="font-size:.82rem;padding:.6rem .9rem;border-radius:8px">
    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
    <span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.7rem"></button>
</div>
@endif

{{-- Page Header --}}
<div class="fp-header">
    <div>
        <h4><i class="bi bi-basket-fill me-2" style="color:#0d9488"></i>Finished Products</h4>
        <p>Manage products and production batches</p>
    </div>
    <a href="{{ route('finished-products.create') }}" class="btn-add-product">
        <i class="bi bi-plus-lg"></i>Add New Product
    </a>
</div>

{{-- Stats --}}
<div class="fp-stats">
    <div class="fp-stat">
        <i class="bi bi-box-seam fp-stat-icon text-primary"></i>
        <div>
            <span class="fp-stat-label">Total Products</span>
            <span class="fp-stat-value">{{ $products->total() }}</span>
        </div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-clock-history fp-stat-icon text-warning"></i>
        <div>
            <span class="fp-stat-label">Ongoing MIX</span>
            <span class="fp-stat-value" style="color:#d97706">{{ $pendingMixes ?? 0 }}</span>
        </div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-exclamation-triangle fp-stat-icon text-danger"></i>
        <div>
            <span class="fp-stat-label">Low Stock</span>
            <span class="fp-stat-value" style="color:#dc2626">{{ $lowStockCount ?? 0 }}</span>
        </div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-check-circle fp-stat-icon text-success"></i>
        <div>
            <span class="fp-stat-label">Completed Today</span>
            <span class="fp-stat-value" style="color:#16a34a">{{ $completedToday ?? 0 }}</span>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="fp-toolbar">
    <form method="GET" action="{{ route('finished-products.index') }}" class="fp-search-form" id="searchForm">
        <input type="hidden" name="filter" value="{{ request('filter') }}">
        <div class="fp-search-wrap">
            <i class="bi bi-search fp-search-icon"></i>
            <input type="text"
                   name="search"
                   id="searchInput"
                   class="fp-search-input"
                   placeholder="Search products…"
                   value="{{ request('search') }}"
                   autocomplete="off">
            @if(request('search'))
            <a href="{{ route('finished-products.index', array_filter(['filter' => request('filter')])) }}"
               class="fp-search-clear" title="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>

    <span class="toolbar-divider">|</span>
    <span class="toolbar-label">Filter:</span>

    <a href="{{ route('finished-products.index', array_filter(['search' => request('search')])) }}"
       class="filter-pill all {{ !request()->filled('filter') ? 'active' : '' }}">
        <i class="bi bi-list" style="font-size:.65rem"></i> All
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'manufactured', 'search' => request('search')])) }}"
       class="filter-pill mfg {{ request('filter') === 'manufactured' ? 'active' : '' }}">
        <i class="bi bi-tools" style="font-size:.65rem"></i> Manufactured
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'consigned', 'search' => request('search')])) }}"
       class="filter-pill con {{ request('filter') === 'consigned' ? 'active' : '' }}">
        <i class="bi bi-shop" style="font-size:.65rem"></i> Consigned
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'low', 'search' => request('search')])) }}"
       class="filter-pill low {{ request('filter') === 'low' ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:.65rem"></i> Low Stock
    </a>
</div>

{{-- Search result notice --}}
@if(request('search'))
<div class="fp-search-notice mb-3">
    <i class="bi bi-search"></i>
    Results for <strong style="margin:0 .25rem">"{{ request('search') }}"</strong> —
    {{ $products->total() }} {{ Str::plural('product', $products->total()) }} found
    <a href="{{ route('finished-products.index', array_filter(['filter' => request('filter')])) }}"
       style="margin-left:auto;color:#64748b;text-decoration:none;font-size:.72rem">
        <i class="bi bi-x"></i> Clear search
    </a>
</div>
@endif

{{-- Table Card --}}
<div class="fp-card">
    <div style="overflow-x:auto">
        <table class="fp-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Min Stock</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Last Production</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $pendingMix       = $product->pendingMixes->first();
                    $lastCompletedMix = $product->productionMixes()
                        ->where('status', 'completed')
                        ->orderBy('updated_at', 'desc')
                        ->first();
                    $isManufactured = $product->product_type === 'manufactured';
                    $isLow  = $product->stock_on_hand > 0 && $product->stock_on_hand <= $product->minimum_stock;
                    $isEmpty = $product->stock_on_hand == 0;
                    $rowClass = $isEmpty ? 'row-danger' : ($isLow ? 'row-warning' : '');
                    $search = request('search');
                @endphp
                <tr class="{{ $rowClass }}">

                    {{-- Product Name --}}
                    <td>
                        <div class="cell-name">
                            @if($search)
                                {!! preg_replace('/(' . preg_quote(e($search), '/') . ')/iu', '<mark>$1</mark>', e($product->name)) !!}
                            @else
                                {{ $product->name }}
                            @endif
                        </div>

                    </td>

                    {{-- Type --}}
                    <td>
                        <span class="type-tag {{ $isManufactured ? 'type-mfg' : 'type-con' }}">
                            {{ $isManufactured ? 'Manufactured' : 'Consigned' }}
                        </span>
                    </td>

                    {{-- Stock --}}
                    <td class="text-end">
                        <span class="cell-qty {{ $isEmpty ? 'qty-empty' : ($isLow ? 'qty-low' : 'qty-ok') }}">
                            {{ number_format($product->stock_on_hand, 0) }}
                        </span>
                    </td>

                    {{-- Min Stock --}}
                    <td class="text-end" style="color:#64748b">
                        {{ number_format($product->minimum_stock, 0) }}
                    </td>

                    {{-- Status --}}
                    <td class="text-center">
                        @if($isManufactured)
                            @if($pendingMix)
                                <span class="status-dot status-mix">Ongoing MIX</span>
                            @else
                                <span class="status-dot status-ready">Ready</span>
                            @endif
                        @else
                            <span class="status-dot status-consign">Consigned</span>
                        @endif
                    </td>

                    {{-- Last Production --}}
                    <td class="text-center" style="font-size:.75rem">
                        @if($lastCompletedMix)
                            <span style="color:#16a34a;font-weight:600">
                                {{ $lastCompletedMix->updated_at->format('M d, Y') }}
                            </span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="text-center" style="white-space:nowrap">
                        @if($isManufactured)
                            @if($pendingMix)
                            <a href="{{ route('production-mixes.show', $pendingMix) }}" class="act-btn act-complete">
                                <i class="bi bi-check-circle"></i> Complete MIX
                            </a>
                            @else
                            <a href="{{ route('production-mixes.create', $product->id) }}" class="act-btn act-mix">
                                <i class="bi bi-plus-circle"></i> New MIX
                            </a>
                            @endif
                        @endif
                        <a href="{{ route('finished-products.show', $product) }}" class="act-btn act-view" style="margin-left:.2rem">
                            <i class="bi bi-eye-fill"></i> View
                        </a>
                        <a href="{{ route('finished-products.edit', $product) }}" class="act-btn act-edit" style="margin-left:.2rem">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        @if($product->barcode)
                        <button type="button" class="act-btn act-barcode" style="margin-left:.2rem"
                                title="Download Barcode"
                                onclick="downloadBarcode('{{ $product->barcode }}', '{{ addslashes($product->name) }}')">
                            <i class="bi bi-upc-scan"></i> Barcode
                        </button>
                        @endif
                        <button type="button" class="act-btn act-delete" style="margin-left:.2rem"
                                onclick="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $pendingMix ? 'true' : 'false' }})">
                            <i class="bi bi-trash-fill"></i> Delete
                        </button>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-{{ request('search') ? 'search' : 'inbox' }}"></i>
                            <p>
                                @if(request('search'))
                                    No products match <strong>"{{ request('search') }}"</strong>.
                                    <a href="{{ route('finished-products.index', array_filter(['filter' => request('filter')])) }}"
                                       style="color:#0d9488">Clear search</a>
                                @else
                                    No products found. <a href="{{ route('finished-products.create') }}" style="color:#0d9488">Add your first product</a>.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer / Pagination --}}
    @if($products->total() > 0)
    <div class="fp-footer">
        <span class="page-info">
            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
            of {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            @if(request('search'))<span style="color:#0d9488;margin-left:.3rem">· filtered</span>@endif
        </span>

        @if($products->lastPage() > 1)
        @php $qs = http_build_query(request()->except('page')); @endphp
        <div class="fp-pagination">
            @if($products->onFirstPage())
                <span class="pg-btn pg-disabled">&#8592; Prev</span>
            @else
                <a href="{{ $products->previousPageUrl() }}&{{ $qs }}" class="pg-btn">&#8592; Prev</a>
            @endif

            @php
                $current = $products->currentPage();
                $last    = $products->lastPage();
                $start   = max(1, $current - 2);
                $end     = min($last, $current + 2);
            @endphp

            @if($start > 1)
                <a href="{{ $products->url(1) }}&{{ $qs }}" class="pg-btn">1</a>
                @if($start > 2)<span class="pg-ellipsis">…</span>@endif
            @endif

            @for($p = $start; $p <= $end; $p++)
                @if($p === $current)
                    <span class="pg-btn pg-active">{{ $p }}</span>
                @else
                    <a href="{{ $products->url($p) }}&{{ $qs }}" class="pg-btn">{{ $p }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)<span class="pg-ellipsis">…</span>@endif
                <a href="{{ $products->url($last) }}&{{ $qs }}" class="pg-btn">{{ $last }}</a>
            @endif

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}&{{ $qs }}" class="pg-btn">Next &#8594;</a>
            @else
                <span class="pg-btn pg-disabled">Next &#8594;</span>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;border:none;box-shadow:0 8px 32px rgba(0,0,0,.18)">
            <div class="modal-header" style="background:#ef4444;padding:.75rem 1rem">
                <h6 class="modal-title text-white mb-0" style="font-size:.85rem;font-weight:700">
                    <i class="bi bi-trash-fill me-2"></i>Confirm Delete
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body" style="padding:1.25rem 1.25rem .75rem">
                    <p style="font-size:.82rem;color:#374151;margin-bottom:.75rem">
                        You are about to delete: <strong id="deleteProductName" style="color:#0f172a"></strong>
                    </p>
                    <div id="pendingMixWarning" style="display:none">
                        <div class="alert alert-danger" style="font-size:.78rem;padding:.6rem .85rem">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <strong>This product has an ongoing MIX!</strong><br>
                            Deleting will cancel production and return all materials.
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0" style="font-size:.78rem;padding:.6rem .85rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        This action is <strong>permanent</strong>.
                    </div>
                </div>
                <div class="modal-footer" style="padding:.6rem 1rem;background:#f8fafc;border-top:1px solid #e2e8f0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash-fill me-1"></i>Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<script>
function confirmDelete(productId, productName, hasPendingMix) {
    document.getElementById('deleteProductName').textContent = productName;
    document.getElementById('deleteForm').action = `/finished-products/${productId}`;
    document.getElementById('pendingMixWarning').style.display = hasPendingMix ? 'block' : 'none';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function downloadBarcode(code, name) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.style.cssText = 'position:absolute;left:-9999px;top:-9999px;visibility:hidden';
    document.body.appendChild(svg);
    JsBarcode(svg, code, {
        format: 'CODE128', lineColor: '#0f172a', background: '#ffffff',
        width: 2.5, height: 70, displayValue: true,
        fontSize: 13, font: 'monospace', fontOptions: '700',
        textMargin: 5, margin: 12,
    });
    const svgData = new XMLSerializer().serializeToString(svg);
    const blob    = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
    const url     = URL.createObjectURL(blob);
    const img     = new Image();
    img.onload = function () {
        const scale = 3;
        const canvas = document.createElement('canvas');
        canvas.width  = img.width  * scale;
        canvas.height = img.height * scale;
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(url);
        document.body.removeChild(svg);
        const a    = document.createElement('a');
        a.href     = canvas.toDataURL('image/png');
        a.download = `barcode-${code}.png`;
        a.click();
    };
    img.src = url;
}

/* Search: debounced auto-submit */
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            document.getElementById('searchForm').submit();
        }, 400);
    });
    searchInput.addEventListener('focus', () => {
        const val = searchInput.value;
        searchInput.value = '';
        searchInput.value = val;
    });
}
</script>

@endsection