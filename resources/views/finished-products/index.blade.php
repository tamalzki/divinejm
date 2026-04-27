@extends('layouts.sidebar')

@section('page-title', 'Finished Products')

@section('content')

<style>
    .fp-header {
        display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 1.25rem;
    }
    .fp-header h4 { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin: 0 0 .15rem; letter-spacing: -.3px; }
    .fp-header p  { font-size: .78rem; color: var(--text-muted); margin: 0; }

    .fp-stats { display: flex; gap: .75rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
    .fp-stat {
        flex: 1 1 150px; background: var(--bg-card);
        border: 1px solid var(--border); border-radius: var(--radius);
        padding: .75rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .fp-stat-icon { font-size: 1.5rem; opacity: .2; flex-shrink: 0; }
    .fp-stat-label { font-size: .7rem; color: var(--text-muted); display: block; }
    .fp-stat-value { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }

    .fp-toolbar { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; flex-wrap: wrap; }
    .toolbar-label { font-size: .72rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
    .toolbar-divider { color: var(--border); }

    .fp-search-form { display: flex; }
    .fp-search-wrap { position: relative; display: flex; align-items: center; }
    .fp-search-icon { position: absolute; left: .6rem; color: var(--text-muted); font-size: .8rem; pointer-events: none; }
    .fp-search-input {
        height: 30px; padding: 0 2rem 0 1.85rem;
        border: 1px solid var(--border); border-radius: 6px;
        font-size: .78rem; color: var(--text-primary); background: var(--bg-card);
        width: 230px; outline: none; transition: border-color .15s, box-shadow .15s;
    }
    .fp-search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(30,77,123,.12); }
    .fp-search-input::placeholder { color: var(--text-muted); }
    .fp-search-clear { position: absolute; right: .5rem; color: var(--text-muted); font-size: .72rem; text-decoration: none !important; transition: color .15s; }
    .fp-search-clear:hover { color: var(--s-danger-text); }

    .fp-search-notice {
        font-size: .76rem; color: var(--text-secondary); margin-bottom: .75rem;
        padding: .4rem .75rem; background: var(--accent-light); border: 1px solid var(--border);
        border-radius: 6px; display: flex; align-items: center; gap: .25rem;
    }

    /* Filter pills — all use single teal accent when active */
    .filter-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .65rem; border-radius: 999px;
        font-size: .72rem; font-weight: 600;
        text-decoration: none !important;
        border: 1.5px solid var(--border);
        color: var(--text-secondary); background: var(--bg-card);
        transition: all .15s;
    }
    .filter-pill:hover, .filter-pill.active {
        border-color: var(--accent);
        color: var(--accent);
        background: var(--accent-light);
        box-shadow: 0 0 0 1.5px var(--accent) inset;
    }

    .fp-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow: hidden; }

    .fp-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .fp-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
        padding: .6rem .85rem; border-bottom: none; white-space: nowrap;
    }
    .fp-table tbody td {
        padding: .52rem .85rem; border-bottom: 1px solid var(--border);
        vertical-align: middle; color: var(--text-primary);
    }
    .fp-table tbody tr:last-child td { border-bottom: none; }
    .fp-table tbody tr:hover td { background: var(--accent-light); }

    .row-danger td  { background: var(--s-danger-bg) !important; }
    .row-warning td { background: var(--s-warning-bg) !important; }
    .row-danger:hover td  { background: #fae8e8 !important; }
    .row-warning:hover td { background: #f5eedf !important; }

    .cell-name { font-weight: 600; color: var(--text-primary); line-height: 1.3; }
    .cell-qty  { font-weight: 700; font-size: .88rem; font-variant-numeric: tabular-nums; }
    .qty-ok    { color: var(--s-success-text); }
    .qty-low   { color: var(--s-warning-text); }
    .qty-empty { color: var(--s-danger-text); }
    .cell-unpacked-dp { font-weight: 700; font-variant-numeric: tabular-nums; font-size: .82rem; color: var(--text-muted); }
    .cell-unpacked-dp.has-open { color: #b45309; }

    mark { background: #fef3b0; color: var(--text-primary); border-radius: 2px; padding: 0 1px; font-style: normal; }

    .type-tag { display: inline-block; border-radius: 4px; padding: .1rem .45rem; font-size: .69rem; font-weight: 700; letter-spacing: .3px; }
    .type-mfg { background: var(--s-success-bg); color: var(--s-success-text); }
    .type-con { background: var(--s-info-bg); color: var(--s-info-text); }

    .act-btn {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .22rem .55rem; border-radius: 5px;
        font-size: .72rem; font-weight: 600;
        border: none; cursor: pointer;
        text-decoration: none !important; white-space: nowrap;
        transition: filter .15s;
    }
    .act-btn:hover { filter: brightness(.88); }
    .act-legacy   { background: #92400e; color: #fff; }
    .act-complete { background: var(--s-warning-text); color: #fff; }
    .act-view     { background: var(--accent); color: #fff; }
    .act-edit     { background: #7a6030; color: #fff; }
    .act-barcode  { background: var(--brand-deep); color: #fff; }
    .act-barcode-icon { padding: .22rem .45rem; min-width: 2rem; justify-content: center; }
    .act-delete   { background: var(--s-danger-text); color: #fff; }

    .fp-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: .65rem 1rem; border-top: 1px solid var(--border);
        background: var(--bg-page); flex-wrap: wrap; gap: .5rem;
    }
    .fp-footer .page-info { font-size: .73rem; color: var(--text-muted); }
    .fp-pagination { display: flex; align-items: center; gap: .2rem; }
    .pg-btn {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 28px; padding: 0 .55rem;
        font-size: .73rem; font-weight: 600;
        border-radius: 5px; border: 1px solid var(--border);
        color: var(--text-secondary); background: var(--bg-card);
        text-decoration: none !important; transition: all .15s; white-space: nowrap;
    }
    a.pg-btn:hover { background: var(--accent-light); border-color: var(--accent); color: var(--accent); }
    .pg-active     { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; cursor: default; }
    .pg-disabled   { color: var(--text-muted) !important; border-color: var(--border) !important; background: var(--bg-page) !important; cursor: default; pointer-events: none; }
    .pg-ellipsis   { color: var(--text-muted); font-size: .73rem; padding: 0 .2rem; line-height: 28px; }

    .btn-add-product {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .4rem .9rem; background: var(--accent); color: #fff;
        border-radius: 7px; font-size: .78rem; font-weight: 700;
        text-decoration: none !important; border: none; transition: background .15s;
    }
    .btn-add-product:hover { background: var(--accent-hover); color: #fff; }

    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; opacity: .35; }
    .empty-state p { font-size: .82rem; margin: 0; }

    /* Adjust Modal */
    .dj-modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); z-index: 1055;
        align-items: center; justify-content: center;
    }
    .dj-modal-overlay.show { display: flex; }
    .dj-modal-box {
        background: var(--bg-card); border-radius: 10px;
        box-shadow: 0 8px 40px rgba(0,0,0,.18);
        width: 100%; max-width: 380px; padding: 1.5rem;
    }
    .dj-modal-title { font-size: .92rem; font-weight: 700; color: var(--text-primary); margin-bottom: .25rem; }
    .dj-modal-sub   { font-size: .75rem; color: var(--text-muted); margin-bottom: 1.1rem; }
    .adj-current {
        display: flex; align-items: center; justify-content: space-between;
        padding: .55rem .75rem; margin-bottom: 1rem;
        background: var(--bg-page); border: 1px solid var(--border); border-radius: 6px;
        font-size: .8rem; color: var(--text-secondary);
    }
    .adj-current strong { font-size: 1.1rem; color: var(--text-primary); }
    .adj-field-label { font-size: .75rem; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: .3rem; }
    .adj-input {
        width: 100%; font-size: 1.1rem; font-weight: 700; text-align: center;
        padding: .5rem; border: 2px solid var(--border); border-radius: 6px;
        color: var(--accent); background: var(--bg-card); outline: none;
        transition: border-color .15s;
    }
    .adj-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-faint); }
    .adj-diff {
        margin-top: .5rem; text-align: center; font-size: .78rem; font-weight: 600;
        min-height: 1.2em;
    }
    .adj-actions { display: flex; gap: .5rem; margin-top: 1.1rem; }
    .btn-adj-save {
        flex: 1; padding: .42rem; border: none; border-radius: 6px;
        background: var(--accent); color: #fff; font-size: .82rem; font-weight: 700;
        cursor: pointer; transition: background .15s;
    }
    .btn-adj-save:hover { background: var(--accent-hover); }
    .btn-adj-cancel {
        padding: .42rem .9rem; border: 1px solid var(--border); border-radius: 6px;
        background: var(--bg-page); color: var(--text-secondary);
        font-size: .82rem; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-adj-cancel:hover { background: var(--border); }
    .act-adjust { background: #2d6a4f; color: #fff; }
</style>

<div class="fp-header">
    <div>
        <h4><i class="bi bi-basket-fill me-2" style="color:var(--accent)"></i>Finished Products</h4>
        <p>Manage products, stock, and production entry points</p>
    </div>
    <a href="{{ route('finished-products.create') }}" class="btn-add-product">
        <i class="bi bi-plus-lg"></i>Add New Product
    </a>
</div>

<div class="fp-stats">
    <div class="fp-stat">
        <i class="bi bi-box-seam fp-stat-icon"></i>
        <div><span class="fp-stat-label">Total Products</span><span class="fp-stat-value">{{ $products->total() }}</span></div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-clock-history fp-stat-icon"></i>
        <div><span class="fp-stat-label">Legacy batch open</span><span class="fp-stat-value" style="color:var(--s-warning-text)">{{ $pendingMixes ?? 0 }}</span></div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-exclamation-triangle fp-stat-icon"></i>
        <div><span class="fp-stat-label">Low Stock</span><span class="fp-stat-value" style="color:var(--s-danger-text)">{{ $lowStockCount ?? 0 }}</span></div>
    </div>
    <div class="fp-stat">
        <i class="bi bi-check-circle fp-stat-icon"></i>
        <div><span class="fp-stat-label">Legacy completed today</span><span class="fp-stat-value" style="color:var(--s-success-text)">{{ $completedToday ?? 0 }}</span></div>
    </div>
</div>

<div class="fp-toolbar">
    <form method="GET" action="{{ route('finished-products.index') }}" class="fp-search-form" id="searchForm">
        <input type="hidden" name="filter" value="{{ request('filter') }}">
        <div class="fp-search-wrap">
            <i class="bi bi-search fp-search-icon"></i>
            <input type="text" name="search" id="searchInput" class="fp-search-input"
                   placeholder="Search products…" value="{{ request('search') }}" autocomplete="off">
            @if(request('search'))
            <a href="{{ route('finished-products.index', array_filter(['filter' => request('filter')])) }}" class="fp-search-clear">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>

    <span class="toolbar-divider">|</span>
    <span class="toolbar-label">Filter:</span>

    <a href="{{ route('finished-products.index', array_filter(['search' => request('search')])) }}"
       class="filter-pill {{ !request()->filled('filter') ? 'active' : '' }}">
        <i class="bi bi-list" style="font-size:.65rem"></i> All
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'manufactured', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'manufactured' ? 'active' : '' }}">
        <i class="bi bi-tools" style="font-size:.65rem"></i> Manufactured
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'consigned', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'consigned' ? 'active' : '' }}">
        <i class="bi bi-shop" style="font-size:.65rem"></i> Consigned
    </a>
    <a href="{{ route('finished-products.index', array_filter(['filter' => 'low', 'search' => request('search')])) }}"
       class="filter-pill {{ request('filter') === 'low' ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:.65rem"></i> Low Stock
    </a>
</div>

@if(request('search'))
<div class="fp-search-notice mb-3">
    <i class="bi bi-search"></i>
    Results for <strong style="margin:0 .25rem">"{{ request('search') }}"</strong> —
    {{ $products->total() }} {{ Str::plural('product', $products->total()) }} found
    <a href="{{ route('finished-products.index', array_filter(['filter' => request('filter')])) }}"
       style="margin-left:auto;color:var(--text-muted);text-decoration:none;font-size:.72rem">
        <i class="bi bi-x"></i> Clear search
    </a>
</div>
@endif

<div class="fp-card">
    <div style="overflow-x:auto">
        <table class="fp-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end" title="Pieces still to pack from Daily Production">Unpacked (DP)</th>
                    <th class="text-end">Min Stock</th>
                    <th class="text-center">Last legacy batch</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $pendingMix       = $product->pendingMixes->first();
                    $lastCompletedMix = $product->productionMixes()->where('status','completed')->orderBy('updated_at','desc')->first();
                    $isManufactured = $product->product_type === 'manufactured';
                    $isLow   = $product->stock_on_hand > 0 && $product->stock_on_hand <= $product->minimum_stock;
                    $isEmpty = $product->stock_on_hand == 0;
                    $dpUnpacked = (float) ($product->dp_unpacked_total ?? 0);
                    $rowClass = $isEmpty ? 'row-danger' : ($isLow ? 'row-warning' : '');
                    $search = request('search');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <div class="cell-name">
                            @if($search)
                                {!! preg_replace('/(' . preg_quote(e($search), '/') . ')/iu', '<mark>$1</mark>', e($product->name)) !!}
                            @else
                                {{ $product->name }}
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="type-tag {{ $isManufactured ? 'type-mfg' : 'type-con' }}">
                            {{ $isManufactured ? 'Manufactured' : 'Consigned' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="cell-qty {{ $isEmpty ? 'qty-empty' : ($isLow ? 'qty-low' : 'qty-ok') }}">
                            {{ number_format($product->stock_on_hand, 0) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="cell-unpacked-dp {{ $dpUnpacked > 0 ? 'has-open' : '' }}" title="Sum of unpacked qty across daily production entries">
                            {{ $dpUnpacked > 0 ? number_format($dpUnpacked, 0) : '—' }}
                        </span>
                    </td>
                    <td class="text-end" style="color:var(--text-muted)">
                        {{ number_format($product->minimum_stock, 0) }}
                    </td>
                    <td class="text-center" style="font-size:.75rem">
                        @if($lastCompletedMix)
                            <span style="color:var(--s-success-text);font-weight:600">
                                {{ $lastCompletedMix->updated_at->format('M d, Y') }}
                            </span>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space:nowrap">
                        @if($isManufactured && $pendingMix)
                            <a href="{{ route('production-mixes.show', $pendingMix) }}" class="act-btn act-legacy" title="Old Production Mix record">
                                <i class="bi bi-hourglass-split"></i> Legacy
                            </a>
                        @endif
                        <button type="button" class="act-btn act-adjust" style="margin-left:.2rem"
                                onclick="openAdjustModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock_on_hand }})">
                            <i class="bi bi-sliders"></i> Adjust
                        </button>
                        <a href="{{ route('finished-products.show', $product) }}" class="act-btn act-view" style="margin-left:.2rem">
                            <i class="bi bi-eye-fill"></i> View
                        </a>
                        <a href="{{ route('finished-products.edit', $product) }}" class="act-btn act-edit" style="margin-left:.2rem">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        @if($product->barcode)
                        <button type="button" class="act-btn act-barcode act-barcode-icon" style="margin-left:.2rem"
                                title="Download barcode PNG"
                                aria-label="Download barcode PNG"
                                onclick="downloadBarcode('{{ $product->barcode }}', '{{ addslashes($product->name) }}')">
                            <i class="bi bi-upc-scan"></i>
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
                                       style="color:var(--accent)">Clear search</a>
                                @else
                                    No products found. <a href="{{ route('finished-products.create') }}" style="color:var(--accent)">Add your first product</a>.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->total() > 0)
    <div class="fp-footer">
        <span class="page-info">
            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
            of {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            @if(request('search'))<span style="color:var(--accent);margin-left:.3rem">· filtered</span>@endif
        </span>

        @if($products->lastPage() > 1)
        @php $qs = http_build_query(request()->except('page')); @endphp
        <div class="fp-pagination">
            @if($products->onFirstPage())
                <span class="pg-btn pg-disabled">&#8592; Prev</span>
            @else
                <a href="{{ $products->previousPageUrl() }}&{{ $qs }}" class="pg-btn">&#8592; Prev</a>
            @endif
            @php $current = $products->currentPage(); $last = $products->lastPage(); $start = max(1,$current-2); $end = min($last,$current+2); @endphp
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
        <div class="modal-content" style="border-radius:var(--radius);overflow:hidden;border:none;box-shadow:0 8px 32px rgba(0,0,0,.15)">
            <div class="modal-header" style="background:var(--s-danger-text);padding:.75rem 1rem">
                <h6 class="modal-title text-white mb-0" style="font-size:.85rem;font-weight:700">
                    <i class="bi bi-trash-fill me-2"></i>Confirm Delete
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf @method('DELETE')
                <div class="modal-body" style="padding:1.25rem 1.25rem .75rem">
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:.75rem">
                        You are about to delete: <strong id="deleteProductName" style="color:var(--text-primary)"></strong>
                    </p>
                    <div id="pendingMixWarning" style="display:none">
                        <div class="alert alert-warning" style="font-size:.78rem;padding:.6rem .85rem">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <strong>This product has an ongoing MIX!</strong><br>
                            Deleting will cancel production and return all materials.
                        </div>
                    </div>
                    <div class="alert alert-danger mb-0" style="font-size:.78rem;padding:.6rem .85rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        This action is <strong>permanent</strong>.
                    </div>
                </div>
                <div class="modal-footer" style="padding:.6rem 1rem;background:var(--bg-page);border-top:1px solid var(--border)">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash-fill me-1"></i>Yes, Delete</button>
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
    svg.style.cssText = 'position:absolute;left:-9999px;visibility:hidden';
    document.body.appendChild(svg);
    JsBarcode(svg, code, { format:'CODE128', lineColor:'#122e34', background:'#ffffff', width:2.5, height:70, displayValue:true, fontSize:13, font:'monospace', fontOptions:'700', textMargin:5, margin:12 });
    const svgData = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([svgData], { type:'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const img = new Image();
    img.onload = function() {
        const scale = 3, canvas = document.createElement('canvas');
        canvas.width = img.width * scale; canvas.height = img.height * scale;
        const ctx = canvas.getContext('2d'); ctx.scale(scale, scale); ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(url); document.body.removeChild(svg);
        const a = document.createElement('a'); a.href = canvas.toDataURL('image/png');
        a.download = `barcode-${code}.png`; a.click();
    };
    img.src = url;
}
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    let t;
    searchInput.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => document.getElementById('searchForm').submit(), 400); });
    searchInput.addEventListener('focus', () => { const v = searchInput.value; searchInput.value = ''; searchInput.value = v; });
}
</script>


{{-- Inventory Adjustment Modal --}}
<div class="dj-modal-overlay" id="adjustModal">
    <div class="dj-modal-box">
        <div class="dj-modal-title"><i class="bi bi-sliders me-1" style="color:var(--accent)"></i> Adjust Stock</div>
        <div class="dj-modal-sub">Set the exact physical count for <strong id="adjProductName"></strong>.</div>

        <div class="adj-current">
            <span>Current Stock</span>
            <strong id="adjCurrentDisplay"></strong>
        </div>

        <form method="POST" id="adjustForm">
            @csrf
            @method('PATCH')
            <label class="adj-field-label">New Physical Count</label>
            <input type="number" name="new_stock" id="adjInput"
                   class="adj-input" min="0" step="1"
                   oninput="updateAdjDiff()" placeholder="Enter actual count">
            <div class="adj-diff" id="adjDiff"></div>
            <div class="adj-actions">
                <button type="submit" class="btn-adj-save"><i class="bi bi-check-lg me-1"></i>Apply</button>
                <button type="button" class="btn-adj-cancel" onclick="closeAdjustModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var _adjCurrent = 0;

function openAdjustModal(productId, productName, currentStock) {
    _adjCurrent = currentStock;
    document.getElementById('adjProductName').textContent    = productName;
    document.getElementById('adjCurrentDisplay').textContent = currentStock + ' units';
    document.getElementById('adjInput').value                = currentStock;
    document.getElementById('adjustForm').action             = '/finished-products/' + productId + '/adjust';
    updateAdjDiff();
    document.getElementById('adjustModal').classList.add('show');
    setTimeout(function() {
        var inp = document.getElementById('adjInput');
        inp.focus(); inp.select();
    }, 80);
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.remove('show');
}

function updateAdjDiff() {
    var newVal = parseInt(document.getElementById('adjInput').value) || 0;
    var diff   = newVal - _adjCurrent;
    var el     = document.getElementById('adjDiff');
    if (diff === 0) {
        el.textContent = 'No change';
        el.style.color = 'var(--text-muted)';
    } else if (diff > 0) {
        el.textContent = '+' + diff + ' units will be added';
        el.style.color = 'var(--s-success-text)';
    } else {
        el.textContent = Math.abs(diff) + ' units will be removed';
        el.style.color = 'var(--s-danger-text)';
    }
}

document.getElementById('adjustModal').addEventListener('click', function(e) {
    if (e.target === this) closeAdjustModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAdjustModal();
});
</script>

@endsection