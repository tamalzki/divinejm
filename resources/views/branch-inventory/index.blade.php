@extends('layouts.sidebar')
@section('page-title', 'Deliver Products')
@section('content')

<style>
    .bi-toolbar { display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; flex-wrap:wrap; }
    .bi-search-wrap { position:relative; display:flex; align-items:center; }
    .bi-search-icon { position:absolute; left:.65rem; color:var(--text-muted); font-size:.8rem; pointer-events:none; }
    .bi-search-input { height:32px; padding:0 1rem 0 2rem; border:1px solid var(--border); border-radius:6px; font-size:.79rem; color:var(--text-primary); background:var(--bg-card); width:260px; outline:none; }
    .bi-search-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .bi-search-input::placeholder { color:var(--text-muted); }

    .btn-new { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .85rem; background:var(--accent); color:#fff !important; border-radius:6px; font-size:.78rem; font-weight:600; text-decoration:none !important; border:none; cursor:pointer; transition:background .14s; white-space:nowrap; }
    .btn-new:hover { background:var(--accent-hover); }

    .btn-secondary-link { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .85rem; background:var(--bg-card); color:var(--text-secondary) !important; border:1px solid var(--border); border-radius:6px; font-size:.78rem; font-weight:600; text-decoration:none !important; transition:background .14s; white-space:nowrap; }
    .btn-secondary-link:hover { background:var(--bg-page); color:var(--text-primary) !important; }

    .area-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap:.85rem; }
    .area-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:1rem 1.1rem; text-decoration:none !important; color:inherit; box-shadow:0 1px 4px rgba(0,0,0,.04); transition:border-color .14s, transform .1s; display:block; }
    .area-card:hover { border-color:var(--accent); transform:translateY(-1px); }
    .area-card-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem; }
    .area-name { font-size:.92rem; font-weight:700; color:var(--text-primary); }
    .area-code { font-size:.68rem; color:var(--text-muted); font-weight:600; }
    .area-badge { display:inline-block; background:#fef3c7; color:#92400e; border-radius:4px; padding:.08rem .4rem; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; margin-left:.4rem; }
    .area-stats { display:flex; gap:1rem; margin-top:.6rem; }
    .area-stat { font-size:.72rem; color:var(--text-secondary); display:flex; align-items:center; gap:.3rem; }
    .area-stat strong { color:var(--text-primary); font-size:.85rem; }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); }
    .empty-state i { font-size:2.2rem; display:block; margin-bottom:.5rem; opacity:.25; }
    .empty-state p { font-size:.8rem; margin:.25rem 0 0; }
</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-truck me-2" style="color:var(--accent)"></i>Deliver Products
        </h5>
        <p class="mb-0" style="font-size:.71rem;color:var(--text-muted)">Pick an area to browse its customers and delivery receipts</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('branch-inventory.all') }}" class="btn-secondary-link">
            <i class="bi bi-list-ul"></i> All Deliveries
        </a>
        <a href="{{ route('branch-inventory.create-delivery') }}" class="btn-new">
            <i class="bi bi-plus-lg"></i> Deliver to Customer
        </a>
    </div>
</div>

{{-- Toolbar --}}
<div class="bi-toolbar">
    <div class="bi-search-wrap">
        <i class="bi bi-search bi-search-icon"></i>
        <input type="text" id="areaSearchInput" class="bi-search-input" placeholder="Search area name or code..." autocomplete="off">
    </div>
</div>

@if($branches->isEmpty())
    <div class="empty-state">
        <i class="bi bi-signpost-split"></i>
        <p>No active areas yet.</p>
        <p><a href="{{ route('branches.create') }}" style="color:var(--accent)">Add an area</a></p>
    </div>
@else
<div class="area-grid" id="areaGrid">
    @foreach($branches as $branch)
    <a href="{{ route('branch-inventory.show', $branch) }}" class="area-card area-row" data-search="{{ strtolower($branch->name.' '.$branch->code) }}">
        <div class="area-card-top">
            <span class="area-name">
                <i class="bi bi-geo-alt me-1" style="color:var(--accent)"></i>{{ $branch->name }}
                @if($branch->is_distributor)<span class="area-badge">Distributor</span>@endif
            </span>
        </div>
        <div class="area-code">Code: {{ $branch->code }}</div>
        <div class="area-stats">
            <div class="area-stat"><strong>{{ $branch->branch_customers_count }}</strong> {{ Str::plural('customer', $branch->branch_customers_count) }}</div>
            <div class="area-stat"><strong>{{ $branch->delivery_count }}</strong> {{ Str::plural('delivery', $branch->delivery_count) }}</div>
        </div>
    </a>
    @endforeach
</div>
@endif

<script>
document.getElementById('areaSearchInput').addEventListener('input', function() {
    var val = this.value.toLowerCase().trim();
    document.querySelectorAll('.area-row').forEach(function(row) {
        row.style.display = (!val || row.dataset.search.includes(val)) ? '' : 'none';
    });
});
</script>

@endsection
