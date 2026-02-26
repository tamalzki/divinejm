@extends('layouts.sidebar')

@section('page-title', 'Edit Raw Material')

@section('content')
<div class="mb-4">
    <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-pencil me-2"></i>Edit Raw Material
    </div>
    <div class="card-body">
        <form action="{{ route('raw-materials.update', $rawMaterial) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label class="form-label fw-bold">Material Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $rawMaterial->name) }}" required>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="ingredient" {{ old('category', $rawMaterial->category) == 'ingredient' ? 'selected' : '' }}>
                            🌾 Ingredient
                        </option>
                        <option value="packaging" {{ old('category', $rawMaterial->category) == 'packaging' ? 'selected' : '' }}>
                            📦 Packaging
                        </option>
                    </select>
                    <small class="text-muted">Is this an ingredient or packaging material?</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Unit (kg, g, pcs, etc) <span class="text-danger">*</span></label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $rawMaterial->unit) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Unit Price (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" value="{{ old('unit_price', $rawMaterial->unit_price) }}" required>
                </div>
            </div>

            <!-- Current Stock - READ ONLY -->
            <div class="alert alert-info">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Current Stock:</strong> {{ $rawMaterial->quantity }} {{ $rawMaterial->unit }}
                        <br>
                        <small>To adjust inventory, use the <strong>"Manage"</strong> button from the materials list.</small>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('raw-materials.show', $rawMaterial) }}" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-arrow-left-right me-1"></i>Manage Stock
                        </a>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Minimum Stock Level <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="minimum_stock" class="form-control" value="{{ old('minimum_stock', $rawMaterial->minimum_stock) }}" required>
                <small class="text-muted">You'll receive alerts when stock falls below this level</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $rawMaterial->description) }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Material
                </button>
                <a href="{{ route('raw-materials.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection