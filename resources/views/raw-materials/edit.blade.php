@extends('layouts.sidebar')
@section('page-title', 'Edit Raw Material')
@section('content')

<style>
    .edit-wrap { max-width:680px; }
    .edit-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.05); padding:1.5rem 1.75rem; }
    .edit-title { font-size:.93rem; font-weight:700; color:var(--text-primary); margin-bottom:1.1rem; padding-bottom:.6rem; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:.5rem; }
    .edit-title i { color:var(--accent); }
    .section-label { font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin:1.1rem 0 .7rem; display:flex; align-items:center; gap:.5rem; }
    .section-label::after { content:''; flex:1; height:1px; background:var(--border); }
    .field-label { font-size:.78rem; font-weight:600; color:var(--text-secondary); margin-bottom:.25rem; display:block; }
    .field-hint  { font-size:.7rem; color:var(--text-muted); margin-top:.2rem; }
    .stock-strip { display:flex; align-items:center; gap:1.5rem; padding:.65rem 1rem; background:var(--accent-light); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:.25rem; }
    .stock-strip-item-label { font-size:.65rem; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); display:block; }
    .stock-strip-item-value { font-size:.92rem; font-weight:700; color:var(--accent); }
    .form-actions { display:flex; gap:.5rem; padding-top:1.1rem; border-top:1px solid var(--border); margin-top:1.1rem; }
    .btn-save { display:inline-flex; align-items:center; gap:.3rem; padding:.4rem 1rem; background:var(--accent); color:#fff; border:none; border-radius:6px; font-size:.81rem; font-weight:700; cursor:pointer; transition:background .15s; }
    .btn-save:hover { background:var(--accent-hover); }
    .btn-cancel { display:inline-flex; align-items:center; gap:.3rem; padding:.4rem .85rem; background:var(--bg-page); color:var(--text-secondary); border:1px solid var(--border); border-radius:6px; font-size:.81rem; font-weight:600; text-decoration:none !important; }
    .btn-cancel:hover { background:var(--border); }
    .btn-manage { display:inline-flex; align-items:center; gap:.3rem; padding:.32rem .75rem; background:var(--bg-card); color:var(--accent); border:1.5px solid var(--accent); border-radius:6px; font-size:.76rem; font-weight:700; text-decoration:none !important; }
    .btn-manage:hover { background:var(--accent); color:#fff; }
</style>

<div class="mb-3">
    <a href="{{ route('raw-materials.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to Materials
    </a>
</div>

<div class="edit-wrap">
<div class="edit-card">
    <div class="edit-title"><i class="bi bi-pencil-square"></i> Edit — {{ $rawMaterial->name }}</div>

    <form action="{{ route('raw-materials.update', $rawMaterial) }}" method="POST">
        @csrf @method('PUT')

        <div class="section-label">Material Info</div>
        <div class="row g-3">
            <div class="col-12">
                <label class="field-label">Material Name <span style="color:var(--s-danger-text)">*</span></label>
                <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                       value="{{ old('name', $rawMaterial->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="field-label">Category <span style="color:var(--s-danger-text)">*</span></label>
                <select name="category" class="form-select form-select-sm @error('category') is-invalid @enderror" required>
                    <option value="ingredient" {{ old('category',$rawMaterial->category)=='ingredient'?'selected':'' }}>🌾 Ingredient</option>
                    <option value="packaging"  {{ old('category',$rawMaterial->category)=='packaging' ?'selected':'' }}>📦 Packaging</option>
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="field-label">Unit <span style="color:var(--s-danger-text)">*</span></label>
                <input type="text" name="unit" class="form-control form-control-sm @error('unit') is-invalid @enderror"
                       value="{{ old('unit', $rawMaterial->unit) }}" required>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="field-label">Unit Price (₱) <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" step="0.01" name="unit_price" class="form-control form-control-sm @error('unit_price') is-invalid @enderror"
                       value="{{ old('unit_price', $rawMaterial->unit_price) }}" required>
                @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="field-label">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $rawMaterial->description) }}</textarea>
            </div>
        </div>

        <div class="section-label">Stock</div>
        <div class="stock-strip">
            @php
                $isEmpty = $rawMaterial->quantity == 0;
                $isLow   = !$isEmpty && $rawMaterial->isLowStock();
            @endphp
            <div>
                <span class="stock-strip-item-label">Current Stock</span>
                <span class="stock-strip-item-value" style="color:{{ $isEmpty ? 'var(--s-danger-text)' : ($isLow ? 'var(--s-warning-text)' : 'var(--s-success-text)') }}">
                    {{ number_format($rawMaterial->quantity, 2) }} {{ $rawMaterial->unit }}
                </span>
            </div>
            <div>
                <span class="stock-strip-item-label">Min Stock</span>
                <span class="stock-strip-item-value" style="font-size:.82rem;color:var(--text-secondary)">
                    {{ number_format($rawMaterial->minimum_stock, 2) }} {{ $rawMaterial->unit }}
                </span>
            </div>
            <div class="ms-auto">
                <a href="{{ route('raw-materials.show', $rawMaterial) }}" class="btn-manage">
                    <i class="bi bi-arrow-left-right"></i> Manage Stock
                </a>
            </div>
        </div>
        <div class="field-hint mb-2" style="margin-top:-.1rem">To adjust stock quantities, use the Manage Stock page.</div>

        <div class="section-label">Thresholds</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="field-label">Minimum Stock Level <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" step="0.01" name="minimum_stock" class="form-control form-control-sm @error('minimum_stock') is-invalid @enderror"
                       value="{{ old('minimum_stock', $rawMaterial->minimum_stock) }}" required>
                <div class="field-hint">Low stock alert threshold</div>
                @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Update Material</button>
            <a href="{{ route('raw-materials.index') }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</div>
@endsection