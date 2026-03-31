@extends('layouts.sidebar')
@section('page-title', 'Add Raw Material')
@section('content')

<style>
    .create-wrap { }
    .create-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.05); padding:1.5rem 1.75rem; }
    .create-title { font-size:.93rem; font-weight:700; color:var(--text-primary); margin-bottom:1.1rem; padding-bottom:.6rem; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:.5rem; }
    .create-title i { color:var(--accent); }
    .section-label { font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin:1.1rem 0 .7rem; display:flex; align-items:center; gap:.5rem; }
    .section-label::after { content:''; flex:1; height:1px; background:var(--border); }
    .field-label { font-size:.78rem; font-weight:600; color:var(--text-secondary); margin-bottom:.25rem; display:block; }
    .field-hint  { font-size:.7rem; color:var(--text-muted); margin-top:.2rem; }
    .form-actions { display:flex; gap:.5rem; padding-top:1.1rem; border-top:1px solid var(--border); margin-top:1.1rem; }
    .btn-save { display:inline-flex; align-items:center; gap:.3rem; padding:.4rem 1rem; background:var(--accent); color:#fff; border:none; border-radius:6px; font-size:.81rem; font-weight:700; cursor:pointer; transition:background .15s; }
    .btn-save:hover { background:var(--accent-hover); }
    .btn-cancel { display:inline-flex; align-items:center; gap:.3rem; padding:.4rem .85rem; background:var(--bg-page); color:var(--text-secondary); border:1px solid var(--border); border-radius:6px; font-size:.81rem; font-weight:600; text-decoration:none !important; }
    .btn-cancel:hover { background:var(--border); }
</style>

<div class="mb-3">
    <a href="{{ route('raw-materials.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to Materials
    </a>
</div>

<div class="create-wrap">
<div class="create-card">
    <div class="create-title"><i class="bi bi-plus-circle"></i> Add New Raw Material</div>

    <form action="{{ route('raw-materials.store') }}" method="POST">
        @csrf

        <div class="section-label">Material Info</div>
        <div class="row g-3">
            <div class="col-12">
                <label class="field-label">Material Name <span style="color:var(--s-danger-text)">*</span></label>
                <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="e.g. Sugar, Flour, Carton Box" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="field-label">Category <span style="color:var(--s-danger-text)">*</span></label>
                <select name="category" class="form-select form-select-sm @error('category') is-invalid @enderror" required>
                    <option value="">— Select category —</option>
                    <option value="ingredient" {{ old('category') == 'ingredient' ? 'selected' : '' }}>🌾 Ingredient</option>
                    <option value="packaging"  {{ old('category') == 'packaging'  ? 'selected' : '' }}>📦 Packaging</option>
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="field-label">Unit <span style="color:var(--s-danger-text)">*</span></label>
                <input type="text" name="unit" class="form-control form-control-sm @error('unit') is-invalid @enderror"
                       value="{{ old('unit') }}" placeholder="kg, g, pcs…" required>
                @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="field-label">Unit Price (₱) <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" step="0.01" name="unit_price" class="form-control form-control-sm @error('unit_price') is-invalid @enderror"
                       value="{{ old('unit_price') }}" placeholder="0.00" required>
                @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="field-label">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2"
                          placeholder="Optional notes…">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="section-label">Stock Setup</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="field-label">Opening Quantity <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" step="0.01" name="quantity" class="form-control form-control-sm @error('quantity') is-invalid @enderror"
                       value="{{ old('quantity') }}" placeholder="0.00" required>
                <div class="field-hint">Current stock on hand</div>
                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="field-label">Minimum Stock Level <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" step="0.01" name="minimum_stock" class="form-control form-control-sm @error('minimum_stock') is-invalid @enderror"
                       value="{{ old('minimum_stock') }}" placeholder="0.00" required>
                <div class="field-hint">Low stock alert threshold</div>
                @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Save Material</button>
            <a href="{{ route('raw-materials.index') }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</div>
@endsection