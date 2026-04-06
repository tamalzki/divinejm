@extends('layouts.sidebar')
@section('page-title', $rawMaterial->name . ' — Manage Stock')
@section('content')

<style>
    .show-grid { display:grid; grid-template-columns:1fr 1.4fr; gap:1rem; }
    @media(max-width:768px){ .show-grid{ grid-template-columns:1fr; } }

    .show-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .show-card-header { display:flex; align-items:center; gap:.45rem; padding:.55rem .9rem; background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.75rem; font-weight:700; }

    .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:.6rem; padding:.85rem; }
    .stat-item-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.15rem; }
    .stat-item-value { font-size:.95rem; font-weight:700; color:var(--text-primary); display:block; }

    .restock-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; }
    .restock-header { display:flex; align-items:center; gap:.45rem; padding:.55rem .9rem; background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.75rem; font-weight:700; }
    .restock-body { padding:.9rem; }
    .field-label { font-size:.72rem; font-weight:600; color:var(--text-secondary); display:block; margin-bottom:.25rem; }
    .field-hint  { font-size:.67rem; color:var(--text-muted); margin-top:.2rem; }
    .btn-restock { display:inline-flex; align-items:center; gap:.35rem; padding:.32rem .9rem; font-size:.78rem; font-weight:700; background:var(--accent); color:#fff; border:none; border-radius:var(--radius); cursor:pointer; }
    .btn-restock:hover { background:var(--accent-hover); }

    .hist-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .hist-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:.5rem .9rem; white-space:nowrap; }
    .hist-table tbody td { padding:.5rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .hist-table tbody tr:last-child td { border-bottom:none; }
    .hist-table tbody tr:hover td { background:var(--accent-faint); }

    .type-pill { display:inline-flex; align-items:center; gap:.25rem; font-size:.68rem; font-weight:700; padding:.12rem .45rem; border-radius:4px; }
    .type-restock { background:var(--s-success-bg); color:var(--s-success-text); }
    .type-usage   { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .type-adjust  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .purpose-tag  { display:inline-block; font-size:.68rem; padding:.1rem .4rem; border-radius:4px; background:var(--s-info-bg); color:var(--s-info-text); font-weight:600; }

    .filter-bar { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; padding:.6rem .9rem; background:var(--bg-page); border-bottom:1px solid var(--border); }
    .filter-select { font-size:.76rem; padding:.22rem .5rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .search-input  { font-size:.76rem; padding:.22rem .55rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); min-width:160px; }
    .btn-filter-sm { font-size:.73rem; font-weight:600; padding:.22rem .7rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-box-seam me-2" style="color:var(--accent)"></i>{{ $rawMaterial->name }}
        </h5>
        <p class="mb-0" style="font-size:.72rem;color:var(--text-muted)">Stock management & transaction history</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('raw-materials.edit', $rawMaterial) }}"
           style="font-size:.76rem;font-weight:600;color:var(--accent);text-decoration:none">
            <i class="bi bi-pencil me-1"></i>Edit Details
        </a>
        <a href="{{ route('raw-materials.index') }}"
           style="font-size:.76rem;color:var(--text-muted);text-decoration:none">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="show-grid mb-3">

    {{-- Info card --}}
    <div class="show-card">
        <div class="show-card-header">
            <i class="bi bi-info-circle"></i>
            <span>Material Details</span>
        </div>
        @php
            $isEmpty = $rawMaterial->quantity == 0;
            $isLow   = !$isEmpty && $rawMaterial->isLowStock();
        @endphp
        <div class="stat-grid">
            <div>
                <span class="stat-item-label">Available Stock</span>
                <span class="stat-item-value" style="color:{{ $isEmpty ? 'var(--s-danger-text)' : ($isLow ? 'var(--s-warning-text)' : 'var(--s-success-text)') }}">
                    {{ number_format($rawMaterial->quantity, 2) }}
                    <span style="font-size:.72rem;font-weight:500;color:var(--text-muted)">{{ $rawMaterial->unit }}</span>
                </span>
            </div>
            <div>
                <span class="stat-item-label">Min Stock</span>
                <span class="stat-item-value" style="font-size:.85rem;color:var(--text-secondary)">
                    {{ number_format($rawMaterial->minimum_stock, 2) }}
                    <span style="font-size:.72rem;font-weight:500;color:var(--text-muted)">{{ $rawMaterial->unit }}</span>
                </span>
            </div>
            <div>
                <span class="stat-item-label">Unit Price</span>
                <span class="stat-item-value" style="font-size:.85rem">&#8369;{{ number_format($rawMaterial->unit_price, 2) }}</span>
            </div>
            <div>
                <span class="stat-item-label">Category</span>
                <span class="stat-item-value" style="font-size:.78rem">{{ ucfirst($rawMaterial->category) }}</span>
            </div>
        </div>
        @if($isEmpty)
        <div style="margin:.25rem .85rem .85rem;padding:.45rem .7rem;background:var(--s-danger-bg);border-radius:5px;font-size:.75rem;color:var(--s-danger-text);font-weight:600">
            <i class="bi bi-x-circle me-1"></i>Out of Stock
        </div>
        @elseif($isLow)
        <div style="margin:.25rem .85rem .85rem;padding:.45rem .7rem;background:var(--s-warning-bg);border-radius:5px;font-size:.75rem;color:var(--s-warning-text);font-weight:600">
            <i class="bi bi-exclamation-triangle me-1"></i>Low Stock
        </div>
        @endif
    </div>

    {{-- Restock form --}}
    <div class="restock-card">
        <div class="restock-header">
            <i class="bi bi-plus-circle" style="color:#fff;font-size:.82rem"></i>
            <span>Restock / Add Stock</span>
        </div>
        <div class="restock-body">
            <form action="{{ route('raw-materials.restock', $rawMaterial) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="field-label">Qty to Add <span style="color:var(--s-danger-text)">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" name="quantity_added"
                                   class="form-control form-control-sm @error('quantity_added') is-invalid @enderror"
                                   value="{{ old('quantity_added') }}" placeholder="0.00" required>
                            <span class="input-group-text" style="font-size:.75rem;background:var(--bg-page);color:var(--text-muted)">{{ $rawMaterial->unit }}</span>
                        </div>
                        @error('quantity_added')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Date <span style="color:var(--s-danger-text)">*</span></label>
                        <input type="date" name="restock_date"
                               class="form-control form-control-sm @error('restock_date') is-invalid @enderror"
                               value="{{ old('restock_date', date('Y-m-d')) }}" required>
                        @error('restock_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Supplier</label>
                        <input type="text" name="supplier" class="form-control form-control-sm"
                               value="{{ old('supplier') }}" placeholder="Optional">
                    </div>
                    <div class="col-md-4">
                        <label class="field-label">Total Cost (&#8369;)</label>
                        <input type="number" step="0.01" name="cost" class="form-control form-control-sm"
                               value="{{ old('cost') }}" placeholder="0.00">
                        <div class="field-hint">Updates unit price if provided</div>
                    </div>
                    <div class="col-md-8">
                        <label class="field-label">Notes</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                               value="{{ old('notes') }}" placeholder="Invoice #, purchase details…">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-restock">
                            <i class="bi bi-plus-lg"></i> Add to Stock
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Transaction history --}}
<div class="show-card">
    <div class="show-card-header">
        <i class="bi bi-clock-history"></i>
        <span>Transaction History</span>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('raw-materials.show', $rawMaterial) }}" id="filterForm">
        <div class="filter-bar">
            <select name="type" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">All Types</option>
                <option value="restock" {{ request('type') === 'restock' ? 'selected' : '' }}>Restock</option>
                <option value="usage"   {{ request('type') === 'usage'   ? 'selected' : '' }}>Usage</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
            </select>
            <input type="text" name="search" id="searchInput" class="search-input"
                   value="{{ request('search') }}" placeholder="Search notes / purpose…">
            <button type="submit" class="btn-filter-sm"><i class="bi bi-search"></i></button>
            @if(request('type') || request('search'))
            <a href="{{ route('raw-materials.show', $rawMaterial) }}"
               style="font-size:.73rem;color:var(--text-muted);text-decoration:none">
                <i class="bi bi-x"></i> Clear
            </a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto">
        <table class="hist-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="text-end">Quantity</th>
                    <th>Purpose / Notes</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($usageHistory as $usage)
            <tr>
                <td style="font-size:.76rem;color:var(--text-muted)">{{ $usage->usage_date->format('M d, Y') }}</td>
                <td>
                    @if($usage->purpose === 'adjustment')
                        <span class="type-pill type-adjust"><i class="bi bi-sliders"></i> Adjustment</span>
                    @elseif($usage->quantity_used < 0)
                        <span class="type-pill type-restock"><i class="bi bi-arrow-up-circle"></i> Restock</span>
                    @else
                        <span class="type-pill type-usage"><i class="bi bi-arrow-down-circle"></i> Usage</span>
                    @endif
                </td>
                <td class="text-end" style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $usage->purpose === 'adjustment' ? 'var(--s-warning-text)' : ($usage->quantity_used < 0 ? 'var(--s-success-text)' : 'var(--s-danger-text)') }}">
                    {{ $usage->quantity_used < 0 ? '+' : '-' }}{{ number_format(abs($usage->quantity_used), 2) }}
                    <span style="font-size:.7rem;font-weight:400;color:var(--text-muted)">{{ $rawMaterial->unit }}</span>
                </td>
                <td>
                    <span class="purpose-tag">{{ ucfirst($usage->purpose) }}</span>
                    @if($usage->notes)
                    <span style="font-size:.72rem;color:var(--text-muted);margin-left:.35rem">{{ $usage->notes }}</span>
                    @endif
                </td>
                <td style="font-size:.75rem;color:var(--text-muted)">{{ $usage->user->name ?? 'System' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.8rem">
                    <i class="bi bi-inbox" style="display:block;font-size:1.5rem;opacity:.3;margin-bottom:.4rem"></i>
                    No transactions yet.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($usageHistory->hasPages())
    <div style="padding:.55rem .9rem;border-top:1px solid var(--border);background:var(--bg-page)">
        {{ $usageHistory->links() }}
    </div>
    @endif
</div>

<script>
var searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        document.getElementById('filterForm').submit();
    }, 400);
});
</script>

@endsection