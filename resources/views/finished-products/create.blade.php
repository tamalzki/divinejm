@extends('layouts.sidebar')

@section('page-title', 'Add New Product')

@section('content')
<div class="mb-3">
    <a href="{{ route('finished-products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

<form action="{{ route('finished-products.store') }}" method="POST" id="productForm">
    @csrf
    <input type="hidden" name="sku" value="{{ old('sku', 'PROD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}">

    <div class="row">
        <!-- Product Type -->
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-1-circle me-2"></i>Product Type</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex gap-2">
                        <div style="width: 200px;">
                            <input type="radio" class="btn-check" name="product_type" id="typeManufactured" value="manufactured" {{ old('product_type', 'manufactured') === 'manufactured' ? 'checked' : '' }} required>
                            <label class="btn btn-outline-success w-100" for="typeManufactured">
                                <i class="bi bi-tools d-block fs-4 mb-1"></i>
                                <strong class="d-block">Manufactured</strong>
                                <small>Define recipe</small>
                            </label>
                        </div>
                        <div style="width: 200px;">
                            <input type="radio" class="btn-check" name="product_type" id="typeConsigned" value="consigned" {{ old('product_type') === 'consigned' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary w-100" for="typeConsigned">
                                <i class="bi bi-shop d-block fs-4 mb-1"></i>
                                <strong class="d-block">Consigned</strong>
                                <small>Ready-made</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recipe (Manufactured) -->
        <div class="col-lg-12 mb-3" id="recipeCard" style="display: none;">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-2-circle me-2"></i>Recipe</h6>
                    <button type="button" class="btn btn-light btn-sm" onclick="addIngredient()">
                        <i class="bi bi-plus-circle"></i> Add Ingredient
                    </button>
                </div>
                <div class="card-body p-3">
                    <!-- Column Headers -->
                    <div class="row g-2 mb-2 px-2">
                        <div class="col-md-4">
                            <small class="text-muted fw-bold">Material</small>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted fw-bold">Quantity</small>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted fw-bold">Available Stock</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted fw-bold">Action</small>
                        </div>
                    </div>
                    
                    <div id="ingredientsContainer"></div>
                    <div class="alert alert-warning py-2 mb-0" id="noIngredientsAlert">
                        <small><i class="bi bi-exclamation-triangle me-1"></i>Add at least one ingredient</small>
                    </div>
                </div>
                
                <!-- Cost Summary -->
                <div class="card-footer bg-light" id="costSummary" style="display: none;">
                    <div class="row g-2 text-center">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Total Material Cost</small>
                            <strong class="text-primary fs-5">₱<span id="totalCost">0</span></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Units per Batch</small>
                            <input type="number" id="unitsPerBatch" class="form-control form-control-sm text-center" value="100" min="1" onchange="calculateCostPerUnit()">
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Cost per Unit</small>
                            <strong class="text-success fs-5">₱<span id="costPerUnit">0</span></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-success d-block"><i class="bi bi-check-circle"></i> Auto-calculated</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-3-circle me-2" id="productInfoIcon"></i>Product Information</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label mb-1"><strong>Product Name</strong> <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1"><strong>Barcode</strong></label>
                            <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1"><strong>Description</strong></label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Stock -->
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-warning py-2">
                    <h6 class="mb-0"><i class="bi bi-4-circle me-2" id="pricingIcon"></i>Pricing & Stock</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label mb-1"><strong>Cost Price (₱)</strong> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="cost_price" id="costPriceInput" class="form-control" value="0" min="0" required>
                            <small class="text-muted" id="costHint">Auto-calculated from recipe ✓</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1"><strong>Selling Price (₱)</strong> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="selling_price" id="sellingPrice" class="form-control" value="{{ old('selling_price') }}" min="0" required>
                            <div id="profitMargin"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1"><strong>Initial Stock</strong> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" class="form-control" value="0" min="0" required>
                            <small class="text-muted">Starting inventory</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1"><strong>Minimum Stock</strong> <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="minimum_stock" class="form-control" value="{{ old('minimum_stock') }}" min="0" required>
                            <small class="text-muted">Alert threshold</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex gap-2 mb-3">
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle me-1"></i>Create Product
        </button>
        <a href="{{ route('finished-products.index') }}" class="btn btn-secondary px-3">Cancel</a>
    </div>
</form>

<script>
const rawMaterials = @json($rawMaterials ?? []);
let ingredientCount = 0;
let selectedMaterials = new Set();

function updateProductType() {
    const isManufactured = document.getElementById('typeManufactured').checked;
    document.getElementById('recipeCard').style.display = isManufactured ? 'block' : 'none';
    
    // Update step numbers in icons
    const productInfoIcon = document.getElementById('productInfoIcon');
    const pricingIcon = document.getElementById('pricingIcon');
    
    if (isManufactured) {
        productInfoIcon.className = 'bi bi-3-circle me-2';
        pricingIcon.className = 'bi bi-4-circle me-2';
    } else {
        productInfoIcon.className = 'bi bi-2-circle me-2';
        pricingIcon.className = 'bi bi-3-circle me-2';
    }
    
    document.getElementById('costPriceInput').readOnly = isManufactured;
    document.getElementById('costPriceInput').classList.toggle('bg-light', isManufactured);
    document.getElementById('costHint').textContent = isManufactured ? 'Auto-calculated from recipe ✓' : 'Enter purchase cost';
    
    if (isManufactured && ingredientCount === 0) addIngredient();
}

function addIngredient() {
    const html = `
        <div class="card mb-2 border-start border-success border-3" id="ingredient-${ingredientCount}">
            <div class="card-body p-2">
                <div class="row g-2">
                    <div class="col-md-4">
                        <select name="ingredients[${ingredientCount}][id]" class="form-select form-select-sm material-select" data-index="${ingredientCount}" onchange="handleMaterialSelect(${ingredientCount})" required>
                            <option value="">-- Select Material --</option>
                            ${rawMaterials.map(m => `<option value="${m.id}" data-unit="${m.unit}" data-price="${m.unit_price}" data-available="${m.quantity}" ${selectedMaterials.has(m.id) ? 'disabled' : ''}>${m.name} (₱${m.unit_price}/${m.unit})</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" name="ingredients[${ingredientCount}][quantity]" class="form-control" data-index="${ingredientCount}" onchange="calculateCost(${ingredientCount})" min="0.01" required>
                            <span class="input-group-text" id="unit-${ingredientCount}">qty</span>
                        </div>
                        <small class="text-success d-block mt-1" id="cost-${ingredientCount}"></small>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center h-100">
                            <strong class="text-info" id="available-${ingredientCount}">—</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeIngredient(${ingredientCount})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('ingredientsContainer').insertAdjacentHTML('beforeend', html);
    document.getElementById('noIngredientsAlert').style.display = 'none';
    ingredientCount++;
}

function handleMaterialSelect(index) {
    const select = document.querySelector(`select[data-index="${index}"]`);
    const option = select.options[select.selectedIndex];
    if (select.value) {
        selectedMaterials.add(parseInt(select.value));
        const unit = option.dataset.unit;
        const available = option.dataset.available;
        document.getElementById(`unit-${index}`).textContent = unit;
        document.getElementById(`available-${index}`).textContent = `${available} ${unit}`;
        updateAllSelects();
        calculateCost(index);
    }
}

function calculateCost(index) {
    const select = document.querySelector(`select[data-index="${index}"]`);
    const input = document.querySelector(`input[data-index="${index}"]`);
    if (select.value && input.value) {
        const option = select.options[select.selectedIndex];
        const cost = parseFloat(option.dataset.price) * parseFloat(input.value);
        document.getElementById(`cost-${index}`).textContent = `Cost: ₱${cost.toFixed(2)}`;
    }
    calculateTotalCost();
}

function calculateTotalCost() {
    let total = 0;
    for (let i = 0; i < ingredientCount; i++) {
        const select = document.querySelector(`select[data-index="${i}"]`);
        const input = document.querySelector(`input[data-index="${i}"]`);
        if (select && input && select.value && input.value) {
            const price = parseFloat(select.options[select.selectedIndex].dataset.price);
            total += price * parseFloat(input.value);
        }
    }
    document.getElementById('totalCost').textContent = total.toFixed(2);
    document.getElementById('costSummary').style.display = total > 0 ? 'block' : 'none';
    calculateCostPerUnit();
}

function calculateCostPerUnit() {
    const total = parseFloat(document.getElementById('totalCost').textContent);
    const units = parseFloat(document.getElementById('unitsPerBatch').value) || 1;
    const perUnit = total / units;
    document.getElementById('costPerUnit').textContent = perUnit.toFixed(2);
    document.getElementById('costPriceInput').value = perUnit.toFixed(2);
    calculateProfit();
}

function calculateProfit() {
    const cost = parseFloat(document.getElementById('costPriceInput').value) || 0;
    const selling = parseFloat(document.getElementById('sellingPrice').value) || 0;
    const div = document.getElementById('profitMargin');
    if (cost && selling) {
        const profit = selling - cost;
        const margin = ((profit / selling) * 100).toFixed(1);
        div.innerHTML = profit > 0 ? `<small class="text-success"><i class="bi bi-graph-up"></i> Profit: ₱${profit.toFixed(2)} (${margin}%)</small>` : `<small class="text-danger"><i class="bi bi-graph-down"></i> Loss: ₱${Math.abs(profit).toFixed(2)}</small>`;
    } else {
        div.innerHTML = '';
    }
}

function removeIngredient(index) {
    const row = document.getElementById(`ingredient-${index}`);
    const select = row.querySelector('.material-select');
    if (select.value) selectedMaterials.delete(parseInt(select.value));
    row.remove();
    updateAllSelects();
    calculateTotalCost();
    if (!document.getElementById('ingredientsContainer').children.length) {
        document.getElementById('noIngredientsAlert').style.display = 'block';
    }
}

function updateAllSelects() {
    document.querySelectorAll('.material-select').forEach(select => {
        const current = select.value;
        Array.from(select.options).forEach(opt => {
            if (opt.value) opt.disabled = selectedMaterials.has(parseInt(opt.value)) && opt.value != current;
        });
    });
}

document.getElementById('typeManufactured').addEventListener('change', updateProductType);
document.getElementById('typeConsigned').addEventListener('change', updateProductType);
document.getElementById('sellingPrice').addEventListener('input', calculateProfit);
document.getElementById('productForm').addEventListener('submit', function(e) {
    if (document.getElementById('typeManufactured').checked && !document.getElementById('ingredientsContainer').children.length) {
        e.preventDefault();
        alert('⚠️ Add at least one ingredient for manufactured products');
        return false;
    }
});

document.addEventListener('DOMContentLoaded', updateProductType);
</script>
@endsection