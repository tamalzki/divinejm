@extends('layouts.sidebar')

@section('page-title', 'Raw Materials')

@section('content')

<style>
    /* ── Page Shell ──────────────────────────────────────────── */
    .rm-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 1.25rem;
    }
    .rm-header h4 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 .15rem;
        letter-spacing: -.3px;
    }
    .rm-header p {
        font-size: .78rem;
        color: #64748b;
        margin: 0;
    }

    /* ── Toolbar ─────────────────────────────────────────────── */
    .rm-toolbar {
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
    .toolbar-divider {
        color: #e2e8f0;
        font-size: .9rem;
        line-height: 1;
    }

    /* Search */
    .rm-search-form { display: flex; }
    .rm-search-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .rm-search-icon {
        position: absolute;
        left: .6rem;
        color: #94a3b8;
        font-size: .8rem;
        pointer-events: none;
    }
    .rm-search-input {
        height: 30px;
        padding: 0 2rem 0 1.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: .78rem;
        color: #1e293b;
        background: #fff;
        width: 220px;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .rm-search-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13,148,136,.1);
    }
    .rm-search-input::placeholder { color: #cbd5e1; }
    .rm-search-clear {
        position: absolute;
        right: .5rem;
        color: #94a3b8;
        font-size: .72rem;
        text-decoration: none !important;
        line-height: 1;
        transition: color .15s;
    }
    .rm-search-clear:hover { color: #ef4444; }

    /* Search notice */
    .rm-search-notice {
        font-size: .76rem;
        color: #475569;
        margin-bottom: .75rem;
        padding: .4rem .75rem;
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: .25rem;
    }

    /* Filter pills */
    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .65rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        text-decoration: none !important;
        border: 1.5px solid transparent;
        transition: all .15s;
        cursor: pointer;
    }
    .filter-pill.all  { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
    .filter-pill.out  { color: #dc2626; border-color: #fca5a5; background: #fff1f1; }
    .filter-pill.low  { color: #d97706; border-color: #fcd34d; background: #fffbeb; }
    .filter-pill.good { color: #16a34a; border-color: #86efac; background: #f0fdf4; }
    .filter-pill:hover { filter: brightness(.93); }
    .filter-pill.active { box-shadow: 0 0 0 2px currentColor inset; }

    /* ── Card ────────────────────────────────────────────────── */
    .rm-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        overflow: hidden;
    }

    /* ── Table ───────────────────────────────────────────────── */
    .rm-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8rem;
    }
    .rm-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: .6rem .85rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .rm-table tbody td {
        padding: .52rem .85rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }
    .rm-table tbody tr:last-child td { border-bottom: none; }
    .rm-table tbody tr:hover { background: #f8fafc; }

    /* Row tints */
    .row-danger  { background: #fff5f5 !important; }
    .row-warning { background: #fffdf0 !important; }
    .row-danger:hover  { background: #fee2e2 !important; }
    .row-warning:hover { background: #fef9c3 !important; }

    /* ── Cell helpers ────────────────────────────────────────── */
    .cell-name { font-weight: 600; color: #0f172a; line-height: 1.3; }
    .cell-desc { font-size: .7rem; color: #94a3b8; margin-top: .1rem; }
    .cell-qty  { font-weight: 700; font-size: .88rem; font-variant-numeric: tabular-nums; }
    .qty-ok    { color: #16a34a; }
    .qty-low   { color: #d97706; }
    .qty-empty { color: #dc2626; }

    /* Highlight search match */
    mark { background: #fef08a; color: #1e293b; border-radius: 2px; padding: 0 1px; font-style: normal; }

    .unit-tag {
        display: inline-block;
        background: #f1f5f9;
        color: #475569;
        border-radius: 4px;
        padding: .1rem .4rem;
        font-size: .69rem;
        font-weight: 600;
        letter-spacing: .3px;
    }
    .cat-tag {
        display: inline-block;
        border-radius: 4px;
        padding: .1rem .45rem;
        font-size: .69rem;
        font-weight: 700;
        letter-spacing: .3px;
    }
    .cat-ingredient { background: #dcfce7; color: #15803d; }
    .cat-packaging  { background: #dbeafe; color: #1d4ed8; }

    /* Status dots */
    .status-dot {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        font-weight: 600;
    }
    .status-dot::before {
        content: '';
        width: 7px; height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-ok::before    { background: #16a34a; }
    .status-ok            { color: #16a34a; }
    .status-low::before   { background: #d97706; }
    .status-low           { color: #d97706; }
    .status-empty::before { background: #dc2626; }
    .status-empty         { color: #dc2626; }

    /* Products list */
    .prod-link {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        border-radius: 4px;
        padding: .1rem .4rem;
        font-size: .68rem;
        font-weight: 600;
        margin: .05rem;
        white-space: nowrap;
    }
    .prod-more {
        display: inline-block;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 4px;
        padding: .1rem .4rem;
        font-size: .68rem;
        font-weight: 600;
    }
    .not-used { color: #cbd5e1; font-size: .73rem; }

    /* ── Action Buttons ──────────────────────────────────────── */
    .act-btn {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .22rem .55rem;
        border-radius: 5px;
        font-size: .72rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none !important;
        white-space: nowrap;
        transition: filter .15s, transform .1s;
    }
    .act-btn:hover  { filter: brightness(.9); transform: translateY(-1px); }
    .act-btn:active { transform: translateY(0); }
    .act-manage { background: #0891b2; color: #fff; }
    .act-edit   { background: #f59e0b; color: #fff; }
    .act-delete { background: #ef4444; color: #fff; }

    /* ── Pagination ──────────────────────────────────────────── */
    .rm-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .65rem 1rem;
        border-top: 1px solid #f1f5f9;
        background: #fafafa;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .rm-footer .page-info { font-size: .73rem; color: #94a3b8; }
    .rm-pagination { display: flex; align-items: center; gap: .2rem; }
    .pg-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 28px;
        padding: 0 .55rem;
        font-size: .73rem;
        font-weight: 600;
        border-radius: 5px;
        border: 1px solid #e2e8f0;
        color: #475569;
        background: #fff;
        text-decoration: none !important;
        transition: all .15s;
        white-space: nowrap;
    }
    a.pg-btn:hover { background: #f0fdfa; border-color: #0d9488; color: #0d9488; }
    .pg-active   { background: #0d9488 !important; border-color: #0d9488 !important; color: #fff !important; cursor: default; }
    .pg-disabled { color: #cbd5e1 !important; border-color: #f1f5f9 !important; background: #fafafa !important; cursor: default; pointer-events: none; }
    .pg-ellipsis { color: #94a3b8; font-size: .73rem; padding: 0 .2rem; line-height: 28px; }

    /* ── Add Button ──────────────────────────────────────────── */
    .btn-add-material {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .9rem;
        background: #0d9488;
        color: #fff;
        border-radius: 7px;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none !important;
        border: none;
        transition: background .15s, transform .1s;
    }
    .btn-add-material:hover { background: #0f766e; color: #fff; transform: translateY(-1px); }

    /* ── Empty State ─────────────────────────────────────────── */
    .empty-state { text-align: center; padding: 3rem 1rem; color: #94a3b8; }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }
    .empty-state p { font-size: .82rem; margin: 0; }

    /* price */
    .price-val { font-variant-numeric: tabular-nums; color: #374151; }
</style>

{{-- ── Flash: success / error from controller ─────────────── --}}
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

{{-- ── Page Header ──────────────────────────────────────────── --}}
<div class="rm-header">
    <div>
        <h4><i class="bi bi-box-seam me-2" style="color:#0d9488"></i>Raw Materials</h4>
        <p>Manage ingredients and packaging materials</p>
    </div>
    <a href="{{ route('raw-materials.create') }}" class="btn-add-material">
        <i class="bi bi-plus-lg"></i>Add New Material
    </a>
</div>

{{-- ── Toolbar ──────────────────────────────────────────────── --}}
{{-- NOTE: controller default (no ?filter) = ALL materials, not "out of stock" --}}
<div class="rm-toolbar">
    {{-- Search --}}
    <form method="GET" action="{{ route('raw-materials.index') }}" class="rm-search-form" id="searchForm">
        <input type="hidden" name="filter" value="{{ request('filter') }}">
        <div class="rm-search-wrap">
            <i class="bi bi-search rm-search-icon"></i>
            <input type="text"
                   name="search"
                   id="searchInput"
                   class="rm-search-input"
                   placeholder="Search materials…"
                   value="{{ request('search') }}"
                   autocomplete="off">
            @if(request('search'))
            <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}"
               class="rm-search-clear" title="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>

    <span class="toolbar-divider">|</span>
    <span class="toolbar-label">Filter:</span>

    {{-- All (no filter param) --}}
    <a href="{{ route('raw-materials.index', array_filter(['search' => request('search')])) }}"
       class="filter-pill all {{ !request()->filled('filter') ? 'active' : '' }}">
        <i class="bi bi-list" style="font-size:.65rem"></i> All
    </a>

    {{-- Out of Stock --}}
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'out', 'search' => request('search')])) }}"
       class="filter-pill out {{ request('filter') === 'out' ? 'active' : '' }}">
        <i class="bi bi-x-circle-fill" style="font-size:.65rem"></i> Out of Stock
    </a>

    {{-- Low Stock --}}
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'low', 'search' => request('search')])) }}"
       class="filter-pill low {{ request('filter') === 'low' ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:.65rem"></i> Low Stock
    </a>

    {{-- In Stock --}}
    <a href="{{ route('raw-materials.index', array_filter(['filter' => 'good', 'search' => request('search')])) }}"
       class="filter-pill good {{ request('filter') === 'good' ? 'active' : '' }}">
        <i class="bi bi-check-circle-fill" style="font-size:.65rem"></i> In Stock
    </a>
</div>

{{-- Search result notice --}}
@if(request('search'))
<div class="rm-search-notice mb-3">
    <i class="bi bi-search"></i>
    Results for <strong style="margin:0 .25rem">"{{ request('search') }}"</strong> —
    {{ $rawMaterials->total() }} {{ Str::plural('material', $rawMaterials->total()) }} found
    <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}"
       style="margin-left:auto;color:#64748b;text-decoration:none;font-size:.72rem">
        <i class="bi bi-x"></i> Clear search
    </a>
</div>
@endif

{{-- ── Table Card ───────────────────────────────────────────── --}}
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
                    <th>Used In Products</th>
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

                    {{-- Name (highlight search match) --}}
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

                    {{-- Category --}}
                    <td>
                        <span class="cat-tag {{ $material->category === 'ingredient' ? 'cat-ingredient' : 'cat-packaging' }}">
                            {{ ucfirst($material->category) }}
                        </span>
                    </td>

                    {{-- Quantity --}}
                    <td class="text-end">
                        <span class="cell-qty {{ $isEmpty ? 'qty-empty' : ($isLow ? 'qty-low' : 'qty-ok') }}">
                            {{ number_format($material->quantity, 2) }}
                        </span>
                    </td>

                    {{-- Unit --}}
                    <td><span class="unit-tag">{{ strtoupper($material->unit) }}</span></td>

                    {{-- Unit Price --}}
                    <td class="text-end">
                        <span class="price-val">₱{{ number_format($material->unit_price, 2) }}</span>
                    </td>

                    {{-- Min Stock --}}
                    <td class="text-end" style="color:#64748b">
                        {{ number_format($material->minimum_stock, 2) }}
                    </td>

                    {{-- Used In --}}
                    <td>
                        @if($material->recipes->count() > 0)
                            @foreach($material->recipes->take(3) as $recipe)
                                <span class="prod-link" title="{{ $recipe->finishedProduct->name }}">
                                    {{ Str::limit($recipe->finishedProduct->name, 18) }}
                                </span>
                            @endforeach
                            @if($material->recipes->count() > 3)
                                <span class="prod-more">+{{ $material->recipes->count() - 3 }}</span>
                            @endif
                        @else
                            <span class="not-used">—</span>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td class="text-center">
                        @if($isEmpty)
                            <span class="status-dot status-empty">Empty</span>
                        @elseif($isLow)
                            <span class="status-dot status-low">Low</span>
                        @else
                            <span class="status-dot status-ok">In Stock</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="text-center" style="white-space:nowrap">
                        <a href="{{ route('raw-materials.show', $material) }}" class="act-btn act-manage">
                            <i class="bi bi-arrow-left-right"></i>Manage
                        </a>
                        <a href="{{ route('raw-materials.edit', $material) }}" class="act-btn act-edit" style="margin-left:.2rem">
                            <i class="bi bi-pencil-square"></i>Edit
                        </a>
                        <button type="button"
                                class="act-btn act-delete"
                                style="margin-left:.2rem"
                                onclick='confirmDeleteMaterial(
                                    {{ $material->id }},
                                    "{{ $material->name }}",
                                    {{ $material->recipes->count() }},
                                    @json($material->recipes->pluck("finishedProduct.name")->unique()->values())
                                )'>
                            <i class="bi bi-trash-fill"></i>Delete
                        </button>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-{{ request('search') ? 'search' : 'inbox' }}"></i>
                            <p>
                                @if(request('search'))
                                    No materials match <strong>"{{ request('search') }}"</strong>.
                                    <a href="{{ route('raw-materials.index', array_filter(['filter' => request('filter')])) }}"
                                       style="color:#0d9488">Clear search</a>
                                @else
                                    No raw materials found. Add your first material to get started.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer / Pagination (fully custom — no Bootstrap SVG arrows) --}}
    @if($rawMaterials->total() > 0)
    <div class="rm-footer">
        <span class="page-info">
            Showing {{ $rawMaterials->firstItem() }}–{{ $rawMaterials->lastItem() }}
            of {{ $rawMaterials->total() }} {{ Str::plural('material', $rawMaterials->total()) }}
            @if(request('search'))<span style="color:#0d9488;margin-left:.3rem">· filtered</span>@endif
        </span>

        @if($rawMaterials->lastPage() > 1)
        @php $qs = http_build_query(request()->except('page')); @endphp
        <div class="rm-pagination">
            @if($rawMaterials->onFirstPage())
                <span class="pg-btn pg-disabled">&#8592; Prev</span>
            @else
                <a href="{{ $rawMaterials->previousPageUrl() }}&{{ $qs }}" class="pg-btn">&#8592; Prev</a>
            @endif

            @php
                $current = $rawMaterials->currentPage();
                $last    = $rawMaterials->lastPage();
                $start   = max(1, $current - 2);
                $end     = min($last, $current + 2);
            @endphp

            @if($start > 1)
                <a href="{{ $rawMaterials->url(1) }}&{{ $qs }}" class="pg-btn">1</a>
                @if($start > 2)<span class="pg-ellipsis">…</span>@endif
            @endif

            @for($p = $start; $p <= $end; $p++)
                @if($p === $current)
                    <span class="pg-btn pg-active">{{ $p }}</span>
                @else
                    <a href="{{ $rawMaterials->url($p) }}&{{ $qs }}" class="pg-btn">{{ $p }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)<span class="pg-ellipsis">…</span>@endif
                <a href="{{ $rawMaterials->url($last) }}&{{ $qs }}" class="pg-btn">{{ $last }}</a>
            @endif

            @if($rawMaterials->hasMorePages())
                <a href="{{ $rawMaterials->nextPageUrl() }}&{{ $qs }}" class="pg-btn">Next &#8594;</a>
            @else
                <span class="pg-btn pg-disabled">Next &#8594;</span>
            @endif
        </div>
        @endif
    </div>
    @endif
</div>


{{-- ── Delete Confirmation Modal ───────────────────────────── --}}
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:10px;overflow:hidden;border:none;box-shadow:0 8px 32px rgba(0,0,0,.18)">
            <div class="modal-header" style="background:#ef4444;padding:.75rem 1rem">
                <h6 class="modal-title text-white mb-0" style="font-size:.85rem;font-weight:700">
                    <i class="bi bi-trash-fill me-2"></i>Confirm Delete
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.25rem .75rem">
                <p style="font-size:.82rem;color:#374151;margin-bottom:.75rem">
                    You are about to delete:
                    <strong id="deleteMaterialName" style="color:#0f172a"></strong>
                </p>
                <div id="materialUsageWarning" class="alert alert-danger" style="display:none;font-size:.78rem;padding:.6rem .85rem">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>This material is used in:</strong>
                    <div id="materialProductsList" class="mt-1"></div>
                    <hr style="margin:.5rem 0">
                    <span>Remove it from these recipes before deleting.</span>
                </div>
                <div class="alert alert-warning mb-0" style="font-size:.78rem;padding:.6rem .85rem">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This action is <strong>permanent</strong>. All transaction history will be lost.
                </div>
            </div>
            <div class="modal-footer" style="padding:.6rem 1rem;background:#f8fafc;border-top:1px solid #e2e8f0">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteMaterialForm" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" id="deleteMaterialBtn">
                        <i class="bi bi-trash-fill me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Delete modal ──────────────────────────────────────────── */
function confirmDeleteMaterial(materialId, materialName, usageCount, productNames) {
    document.getElementById('deleteMaterialName').textContent = materialName;
    const form      = document.getElementById('deleteMaterialForm');
    const warning   = document.getElementById('materialUsageWarning');
    const prodList  = document.getElementById('materialProductsList');
    const deleteBtn = document.getElementById('deleteMaterialBtn');
    form.action = `/raw-materials/${materialId}`;
    if (usageCount > 0) {
        warning.style.display = 'block';
        prodList.innerHTML = '<ul style="margin:.25rem 0 0;padding-left:1.1rem">' +
            productNames.map(n => `<li><strong>${n}</strong></li>`).join('') + '</ul>';
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Cannot Delete';
    } else {
        warning.style.display = 'none';
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="bi bi-trash-fill me-1"></i>Delete';
    }
    new bootstrap.Modal(document.getElementById('deleteMaterialModal')).show();
}

/* ── Search: auto-submit on typing (debounced 400ms) ─────── */
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