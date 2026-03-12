@extends('layouts.sidebar')
@section('page-title', 'Raw Materials')
@section('content')

<style>
    .rm-toolbar { display:flex; align-items:center; gap:.5rem; margin-bottom:.6rem; flex-wrap:wrap; }
    .rm-search-wrap { position:relative; display:flex; align-items:center; }
    .rm-search-icon { position:absolute; left:.6rem; color:var(--text-muted); font-size:.78rem; pointer-events:none; }
    .rm-search-input { height:30px; padding:0 2rem 0 1.85rem; border:1px solid var(--border); border-radius:6px; font-size:.78rem; color:var(--text-primary); background:var(--bg-card); width:220px; outline:none; transition:border-color .15s,box-shadow .15s; }
    .rm-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(30,77,123,.1); }
    .rm-search-input::placeholder { color:var(--text-muted); }
    .rm-search-clear { position:absolute; right:.5rem; color:var(--text-muted); font-size:.7rem; text-decoration:none !important; }
    .rm-search-clear:hover { color:var(--s-danger-text); }
    .toolbar-label { font-size:.68rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; }
    .toolbar-div { color:var(--border); }
    .filter-pill { display:inline-flex; align-items:center; gap:.25rem; padding:.18rem .55rem; border-radius:999px; font-size:.7rem; font-weight:600; text-decoration:none !important; border:1.5px solid var(--border); color:var(--text-secondary); background:var(--bg-card); transition:all .15s; }
    .filter-pill:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-light); }
    .filter-pill.active { border-color:var(--accent); color:var(--accent); background:var(--accent-light); box-shadow:0 0 0 1.5px var(--accent) inset; }
    .filter-pill.fp-out.active  { border-color:var(--s-danger-text);  color:var(--s-danger-text);  background:var(--s-danger-bg);  box-shadow:0 0 0 1.5px var(--s-danger-text) inset; }
    .filter-pill.fp-low.active  { border-color:var(--s-warning-text); color:var(--s-warning-text); background:var(--s-warning-bg); box-shadow:0 0 0 1.5px var(--s-warning-text) inset; }
    .filter-pill.fp-good.active { border-color:var(--s-success-text); color:var(--s-success-text); background:var(--s-success-bg); box-shadow:0 0 0 1.5px var(--s-success-text) inset; }
    .rm-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .rm-table { width:100%; border-collapse:collapse; font-size:.79rem; }
    .rm-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .85rem; white-space:nowrap; border:none; }
    .rm-table tbody td { padding:.48rem .85rem; border-bottom:1px solid var(--border); vertical-align:middle; color:var(--text-primary); }
    .rm-table tbody tr:last-child td { border-bottom:none; }
    .rm-table tbody tr:hover td { background:var(--accent-faint); }
    .rm-table .row-danger  td { background:var(--s-danger-bg)  !important; }
    .rm-table .row-warning td { background:var(--s-warning-bg) !important; }
    .rm-table .row-danger:hover  td { background:#fce8e8 !important; }
    .rm-table .row-warning:hover td { background:#fef3dc !important; }
    .cell-name { font-weight:600; color:var(--text-primary); line-height:1.3; }
    .cell-desc { font-size:.7rem; color:var(--text-muted); margin-top:.1rem; }
    .cell-qty { font-weight:700; font-size:.86rem; font-variant-numeric:tabular-nums; }
    .qty-ok    { color:var(--s-success-text); }
    .qty-low   { color:var(--s-warning-text); }
    .qty-empty { color:var(--s-danger-text); }
    mark { background:#fef08a; color:var(--text-primary); border-radius:2px; padding:0 1px; }
    .unit-tag { display:inline-block; background:var(--bg-page); color:var(--text-secondary); border-radius:4px; padding:.08rem .38rem; font-size:.68rem; font-weight:700; letter-spacing:.3px; border:1px solid var(--border); }
    .cat-tag { display:inline-block; border-radius:4px; padding:.1rem .42rem; font-size:.69rem; font-weight:700; }
    .cat-ingredient { background:var(--s-success-bg); color:var(--s-success-text); }
    .cat-packaging  { background:var(--s-info-bg);    color:var(--s-info-text); }
    .status-dot { display:inline-flex; align-items:center; gap:.3rem; font-size:.72rem; font-weight:600; }
    .status-dot::before { content:''; width:7px; height:7px; border-radius:50%; flex-shrink:0; }
    .status-ok::before    { background:var(--s-success-text); }
    .status-ok            { color:var(--s-success-text); }
    .status-low::before   { background:var(--s-warning-text); }
    .status-low           { color:var(--s-warning-text); }
    .status-empty::before { background:var(--s-danger-text); }
    .status-empty         { color:var(--s-danger-text); }
    .prod-chip { display:inline-block; background:var(--accent-light); color:var(--accent); border-radius:4px; padding:.08rem .38rem; font-size:.68rem; font-weight:600; margin:.05rem; white-space:nowrap; }
    .prod-more { display:inline-block; background:var(--bg-page); color:var(--text-muted); border-radius:4px; padding:.08rem .38rem; font-size:.68rem; font-weight:600; border:1px solid var(--border); }
    .act-btn { display:inline-flex; align-items:center; gap:.22rem; padding:.2rem .52rem; border-radius:5px; font-size:.71rem; font-weight:600; border:none; cursor:pointer; text-decoration:none !important; white-space:nowrap; transition:filter .15s; }
    .act-btn:hover { filter:brightness(.88); }
    .act-manage { background:var(--accent);       color:#fff; }
    .act-edit   { background:var(--s-warning-bg); color:var(--s-warning-text); border:1px solid #e8d5a0; }
    .act-delete { background:var(--s-danger-bg);  color:var(--s-danger-text);  border:1px solid #f0c0c0; }
    .btn-add-rm { display:inline-flex; align-items:center; gap:.35rem; padding:.38rem .85rem; background:var(--accent); color:#fff; border-radius:6px; font-size:.78rem; font-weight:700; text-decoration:none !important; transition:background .15s; }
    .btn-add-rm:hover { background:var(--accent-hover); color:#fff; }
    .rm-footer { display:flex; align-items:center; justify-content:space-between; padding:.55rem .9rem; border-top:1px solid var(--border); background:var(--bg-page); flex-wrap:wrap; gap:.4rem; }
    .rm-footer .page-info { font-size:.72rem; color:var(--text-muted); }
    .rm-pagination { display:flex; align-items:center; gap:.2rem; }
    .pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:26px; padding:0 .5rem; font-size:.72rem; font-weight:600; border-radius:5px; border:1px solid var(--border); color:var(--text-secondary); background:var(--bg-card); text-decoration:none !important; transition:all .15s; }
    a.pg-btn:hover { background:var(--accent-light); border-color:var(--accent); color:var(--accent); }
    .pg-active   { background:var(--accent) !important; border-color:var(--accent) !important; color:#fff !important; cursor:default; }
    .pg-disabled { color:var(--text-muted) !important; border-color:var(--border) !important; background:var(--bg-page) !important; pointer-events:none; }
    .pg-ellipsis { color:var(--text-muted); font-size:.72rem; padding:0 .2rem; }
    .empty-state { text-align:center; padding:2.5rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2rem; display:block; margin-bottom:.5rem; opacity:.3; }
    .empty-state p { font-size:.8rem; margin:0; }
</style>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2" style="font-size:.81rem">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.7rem"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2" style="font-size:.81rem">
    <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:.7rem"></button>
</div>
@endif

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-box-seam me-2" style="color:var(--accent)"></i>Raw Materials
        </h5>
        <p class="mb-0" style="font-size:.72rem;color:var(--text-muted)">Manage ingredients and packaging materials</p>
    </div>
    <a href="{{ route('raw-materials.create') }}" class="btn-add-rm">
        <i class="bi bi-plus-lg"></i> Add Material
    </a>
</div>

<div class="rm-toolbar">
    <form method="GET" action="{{ route('raw-materials.index') }}" id="searchForm">
        <input type="hidden" name="filter" value="{{ request('filter') }}">
        <div class="rm-search-wrap">
            <i class="bi bi-search rm-search-icon"></i>
            <input type="text" name="search" id="searchInput" class="rm-search-input"
                   placeholder="Search materials..." value="{{ request('search') }}" autocomplete="off">
            @if(request('search'))
            <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}" class="rm-search-clear">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>
    <span class="toolbar-div">|</span>
    <span class="toolbar-label">Filter:</span>
    <a href="{{ route('raw-materials.index', array_filter(['search' => request('search')])) }}"
       class="filter-pill {{ !request()->filled('filter') ? 'active' : '' }}">All</a>
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'out', 'search' => request('search')])) }}"
       class="filter-pill fp-out {{ request('filter') === 'out' ? 'active' : '' }}">
        <i class="bi bi-x-circle" style="font-size:.6rem"></i> Out of Stock
    </a>
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'low', 'search' => request('search')])) }}"
       class="filter-pill fp-low {{ request('filter') === 'low' ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle" style="font-size:.6rem"></i> Low Stock
    </a>
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'good', 'search' => request('search')])) }}"
       class="filter-pill fp-good {{ request('filter') === 'good' ? 'active' : '' }}">
        <i class="bi bi-check-circle" style="font-size:.6rem"></i> In Stock
    </a>
</div>

@if(request('search'))
<div class="mb-2" style="font-size:.73rem;color:var(--text-muted)">
    <i class="bi bi-search me-1"></i>
    Results for <strong>"{{ request('search') }}"</strong> — {{ $rawMaterials->total() }} found
    <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}"
       style="color:var(--text-muted);margin-left:.4rem"><i class="bi bi-x"></i> Clear</a>
</div>
@endif

<div class="rm-card">
    <div style="overflow-x:auto">
        <table class="rm-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th class="text-end">Quantity</th>
                    <th>Unit</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Min Stock</th>
                    <th>Used In</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rawMaterials as $material)
                @php
                    $isEmpty  = $material->quantity == 0;
                    $isLow    = !$isEmpty && $material->isLowStock();
                    $rowClass = $isEmpty ? 'row-danger' : ($isLow ? 'row-warning' : '');
                    $search   = request('search');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        <div class="cell-name">
                            @if($search)
                                {!! preg_replace('/(' . preg_quote(e($search), '/') . ')/iu', '<mark>$1</mark>', e($material->name)) !!}
                            @else
                                {{ $material->name }}
                            @endif
                        </div>
                        @if($material->description)
                            <div class="cell-desc">{{ Str::limit($material->description, 45) }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="cat-tag {{ $material->category === 'ingredient' ? 'cat-ingredient' : 'cat-packaging' }}">
                            {{ ucfirst($material->category) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <span class="cell-qty {{ $isEmpty ? 'qty-empty' : ($isLow ? 'qty-low' : 'qty-ok') }}">
                            {{ number_format($material->quantity, 2) }}
                        </span>
                    </td>
                    <td><span class="unit-tag">{{ strtoupper($material->unit) }}</span></td>
                    <td class="text-end" style="font-variant-numeric:tabular-nums;font-size:.78rem">
                        {{ number_format($material->unit_price, 2) }}
                    </td>
                    <td class="text-end" style="color:var(--text-muted);font-size:.78rem">
                        {{ number_format($material->minimum_stock, 2) }}
                    </td>
                    <td>
                        @if($material->recipes->count() > 0)
                            @foreach($material->recipes->take(3) as $recipe)
                                <span class="prod-chip">{{ Str::limit($recipe->finishedProduct->name, 16) }}</span>
                            @endforeach
                            @if($material->recipes->count() > 3)
                                <span class="prod-more">+{{ $material->recipes->count() - 3 }}</span>
                            @endif
                        @else
                            <span style="color:var(--text-muted);font-size:.73rem">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($isEmpty)
                            <span class="status-dot status-empty">Empty</span>
                        @elseif($isLow)
                            <span class="status-dot status-low">Low</span>
                        @else
                            <span class="status-dot status-ok">In Stock</span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space:nowrap">
                        <a href="{{ route('raw-materials.show', $material) }}" class="act-btn act-manage">
                            <i class="bi bi-arrow-left-right"></i> Stock In
                        </a>
                        <a href="{{ route('raw-materials.edit', $material) }}" class="act-btn act-edit" style="margin-left:.2rem">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="act-btn act-delete" style="margin-left:.2rem"
                                data-id="{{ $material->id }}"
                                data-name="{{ $material->name }}"
                                data-usage="{{ $material->recipes->count() }}"
                                data-products="{{ e(json_encode($material->recipes->pluck('finishedProduct.name')->unique()->values())) }}"
                                onclick="confirmDeleteBtn(this)">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            @if(request('search'))
                                <i class="bi bi-search"></i>
                                <p>No materials match <strong>"{{ request('search') }}"</strong>.
                                <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}" style="color:var(--accent)">Clear</a></p>
                            @else
                                <i class="bi bi-inbox"></i>
                                <p>No raw materials yet. <a href="{{ route('raw-materials.create') }}" style="color:var(--accent)">Add one now</a></p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($rawMaterials->total() > 0)
    <div class="rm-footer">
        <span class="page-info">
            Showing {{ $rawMaterials->firstItem() }}–{{ $rawMaterials->lastItem() }}
            of {{ $rawMaterials->total() }} {{ Str::plural('material', $rawMaterials->total()) }}
            @if(request('search'))
                <span style="color:var(--accent);margin-left:.3rem">· filtered</span>
            @endif
        </span>
        @if($rawMaterials->lastPage() > 1)
            @php
                $qs   = http_build_query(request()->except('page'));
                $cur  = $rawMaterials->currentPage();
                $last = $rawMaterials->lastPage();
                $s    = max(1, $cur - 2);
                $e    = min($last, $cur + 2);
            @endphp
            <div class="rm-pagination">
                @if($rawMaterials->onFirstPage())
                    <span class="pg-btn pg-disabled">&larr; Prev</span>
                @else
                    <a href="{{ $rawMaterials->previousPageUrl() }}&{{ $qs }}" class="pg-btn">&larr; Prev</a>
                @endif

                @if($s > 1)
                    <a href="{{ $rawMaterials->url(1) }}&{{ $qs }}" class="pg-btn">1</a>
                    @if($s > 2)
                        <span class="pg-ellipsis">...</span>
                    @endif
                @endif

                @for($p = $s; $p <= $e; $p++)
                    @if($p === $cur)
                        <span class="pg-btn pg-active">{{ $p }}</span>
                    @else
                        <a href="{{ $rawMaterials->url($p) }}&{{ $qs }}" class="pg-btn">{{ $p }}</a>
                    @endif
                @endfor

                @if($e < $last)
                    @if($e < $last - 1)
                        <span class="pg-ellipsis">...</span>
                    @endif
                    <a href="{{ $rawMaterials->url($last) }}&{{ $qs }}" class="pg-btn">{{ $last }}</a>
                @endif

                @if($rawMaterials->hasMorePages())
                    <a href="{{ $rawMaterials->nextPageUrl() }}&{{ $qs }}" class="pg-btn">Next &rarr;</a>
                @else
                    <span class="pg-btn pg-disabled">Next &rarr;</span>
                @endif
            </div>
        @endif
    </div>
    @endif
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border);box-shadow:0 8px 32px rgba(0,0,0,.12)">
            <div class="modal-header py-2 px-3" style="background:var(--s-danger-bg);border-bottom:1px solid #f0c0c0">
                <h6 class="modal-title mb-0" style="font-size:.83rem;font-weight:700;color:var(--s-danger-text)">
                    <i class="bi bi-trash me-1"></i> Confirm Delete
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="font-size:.7rem"></button>
            </div>
            <div class="modal-body" style="padding:1rem 1.1rem .75rem;font-size:.81rem">
                <p style="margin-bottom:.75rem;color:var(--text-secondary)">
                    Delete <strong id="delName" style="color:var(--text-primary)"></strong>?
                </p>
                <div id="delUsageWarn" style="display:none;font-size:.78rem" class="alert alert-danger py-2 mb-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Used in recipes:</strong>
                    <div id="delProductList" class="mt-1 ps-2"></div>
                    <div class="mt-1" style="font-size:.73rem">Remove from these recipes first.</div>
                </div>
                <div class="alert alert-warning py-2 mb-0" style="font-size:.76rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This action is <strong>permanent</strong> and cannot be undone.
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="background:var(--bg-page);border-top:1px solid var(--border)">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delForm" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" id="delBtn" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteBtn(btn) {
    var id       = btn.getAttribute('data-id');
    var name     = btn.getAttribute('data-name');
    var usage    = parseInt(btn.getAttribute('data-usage'));
    var products = JSON.parse(btn.getAttribute('data-products'));
    confirmDelete(id, name, usage, products);
}
function confirmDelete(id, name, usage, products) {
    document.getElementById('delName').textContent = name;
    document.getElementById('delForm').action = '/raw-materials/' + id;
    var warn = document.getElementById('delUsageWarn');
    var btn  = document.getElementById('delBtn');
    if (usage > 0) {
        warn.style.display = 'block';
        var html = '';
        for (var i = 0; i < products.length; i++) {
            html += '<div><strong>' + products[i] + '</strong></div>';
        }
        document.getElementById('delProductList').innerHTML = html;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-lock me-1"></i>Cannot Delete';
    } else {
        warn.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
    }
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
var searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        document.getElementById('searchForm').submit();
    }, 400);
});
</script>

@endsection