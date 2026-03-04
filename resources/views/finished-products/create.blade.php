@extends('layouts.sidebar')

@section('page-title', 'Add New Product')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.bootstrap5.min.css">

<div class="mb-3">
    <a href="{{ route('finished-products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

{{-- Server-side validation errors --}}
@if($errors->any())
<div class="alert alert-danger mb-3">
    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('finished-products.store') }}" method="POST" id="productForm" novalidate>
    @csrf
    <input type="hidden" name="sku" value="{{ old('sku', 'PROD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}">

    <div class="row">

        {{-- ① Product Type --}}
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-1-circle me-2"></i>Product Type</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex gap-2">
                        <div style="width:200px">
                            <input type="radio" class="btn-check" name="product_type" id="typeManufactured"
                                   value="manufactured" {{ old('product_type','manufactured') === 'manufactured' ? 'checked' : '' }} required>
                            <label class="btn btn-outline-success w-100" for="typeManufactured">
                                <i class="bi bi-tools d-block fs-4 mb-1"></i>
                                <strong class="d-block">Manufactured</strong>
                                <small>Define recipe</small>
                            </label>
                        </div>
                        <div style="width:200px">
                            <input type="radio" class="btn-check" name="product_type" id="typeConsigned"
                                   value="consigned" {{ old('product_type') === 'consigned' ? 'checked' : '' }}>
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

        {{-- ② Recipe --}}
        <div class="col-lg-12 mb-3" id="recipeCard" style="display:none">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-2-circle me-2"></i>Recipe</h6>
                    <button type="button" class="btn btn-light btn-sm" onclick="addIngredient()">
                        <i class="bi bi-plus-circle"></i> Add Ingredient
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" id="ingredientsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36%">Material <span class="text-danger">*</span></th>
                                    <th style="width:16%">Quantity <span class="text-danger">*</span></th>
                                    <th style="width:8%">Unit</th>
                                    <th style="width:18%">Available Stock</th>
                                    <th style="width:14%">Line Cost</th>
                                    <th style="width:8%" class="text-center">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="ingredientsContainer"></tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning py-2 mb-0 rounded-0 border-0 border-top"
                         id="noIngredientsAlert">
                        <small><i class="bi bi-exclamation-triangle me-1"></i>Add at least one ingredient</small>
                    </div>
                </div>

                {{-- Cost summary — total only, no units per batch --}}
                <div class="card-footer bg-light" id="costSummary" style="display:none">
                    <div class="d-flex align-items-center gap-4">
                        <div>
                            <small class="text-muted d-block">Total Material Cost</small>
                            <strong class="text-primary fs-5">₱<span id="totalCost">0.00</span></strong>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        {{-- ③ Product Info --}}
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0"><i id="productInfoIcon" class="bi bi-3-circle me-2"></i>Product Information</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label mb-1">
                                <strong>Product Name</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}">
                            <div class="invalid-feedback" id="err-name">
                                @error('name'){{ $message }}@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1"><strong>Description</strong></label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ④ Pricing & Stock --}}
        <div class="col-lg-12 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-warning py-2">
                    <h6 class="mb-0"><i id="pricingIcon" class="bi bi-4-circle me-2"></i>Pricing & Stock</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <strong>Cost Price (₱)</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" name="cost_price" id="costPriceInput"
                                   class="form-control @error('cost_price') is-invalid @enderror"
                                   value="{{ old('cost_price', 0) }}" min="0">
                            <small class="text-muted" id="costHint">Auto-calculated from recipe ✓</small>
                            <div class="invalid-feedback">@error('cost_price'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <strong>Selling Price (₱)</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" name="selling_price" id="sellingPrice"
                                   class="form-control @error('selling_price') is-invalid @enderror"
                                   value="{{ old('selling_price') }}" min="0">
                            <div id="profitMargin"></div>
                            <div class="invalid-feedback" id="err-selling">
                                @error('selling_price'){{ $message }}@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <strong>Initial Stock</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" name="quantity"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   value="{{ old('quantity', 0) }}" min="0">
                            <small class="text-muted">Starting inventory</small>
                            <div class="invalid-feedback">@error('quantity'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">
                                <strong>Minimum Stock</strong> <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" name="minimum_stock"
                                   class="form-control @error('minimum_stock') is-invalid @enderror"
                                   value="{{ old('minimum_stock') }}" min="0">
                            <small class="text-muted">Alert threshold</small>
                            <div class="invalid-feedback" id="err-min-stock">
                                @error('minimum_stock'){{ $message }}@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle me-1"></i>Create Product
        </button>
        <a href="{{ route('finished-products.index') }}" class="btn btn-secondary px-3">Cancel</a>
    </div>
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>
<script>
const rawMaterials  = @json($rawMaterials ?? []);
let ingredientCount = 0;
let selectedByIndex = {};
let tomInstances    = {};

function updateProductType() {
    const isMfg = document.getElementById('typeManufactured').checked;
    document.getElementById('recipeCard').style.display          = isMfg ? 'block' : 'none';
    document.getElementById('productInfoIcon').className         = `bi bi-${isMfg ? 3 : 2}-circle me-2`;
    document.getElementById('pricingIcon').className             = `bi bi-${isMfg ? 4 : 3}-circle me-2`;
    document.getElementById('costPriceInput').readOnly           = isMfg;
    document.getElementById('costPriceInput').classList.toggle('bg-light', isMfg);
    document.getElementById('costHint').textContent = isMfg
        ? 'Auto-calculated from recipe ✓'
        : 'Enter purchase cost';
    if (isMfg && ingredientCount === 0) addIngredient();
}

function addIngredient() {
    const idx = ingredientCount;

    const optionsHtml = rawMaterials.map(m =>
        `<option value="${m.id}" data-unit="${m.unit}" data-price="${m.unit_price}" data-available="${m.quantity}">
            ${m.name} — ₱${parseFloat(m.unit_price).toFixed(2)}/${m.unit} (${parseFloat(m.quantity).toFixed(2)} avail.)
         </option>`
    ).join('');

    const tr = document.createElement('tr');
    tr.id = `ingredient-${idx}`;
    tr.innerHTML = `
        <td>
            <select id="material-select-${idx}" name="ingredients[${idx}][id]" class="form-select form-select-sm">
                ${optionsHtml}
            </select>
            <div class="text-danger mt-1" style="font-size:.75rem" id="err-material-${idx}"></div>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01"
                   name="ingredients[${idx}][quantity]"
                   id="qty-${idx}"
                   class="form-control form-control-sm"
                   placeholder="0.00">
            <div class="text-danger mt-1" style="font-size:.75rem" id="err-qty-${idx}"></div>
        </td>
        <td><span class="badge bg-secondary" id="unit-${idx}">—</span></td>
        <td><span id="available-${idx}" class="fw-bold text-info" style="font-size:.85rem">—</span></td>
        <td><span id="cost-${idx}" class="fw-semibold text-success" style="font-size:.85rem"></span></td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(${idx})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    document.getElementById('ingredientsContainer').appendChild(tr);
    document.getElementById('noIngredientsAlert').style.display = 'none';

    const ts = new TomSelect(`#material-select-${idx}`, {
        placeholder:      'Type to search…',
        allowEmptyOption: false,
        maxOptions:       500,
        onChange(value)   { handleMaterialSelect(idx, value); },
        render: {
            option(data, escape) {
                return isTakenByOther(parseInt(data.value), idx)
                    ? `<div style="color:#94a3b8;text-decoration:line-through;cursor:not-allowed">${escape(data.text)}</div>`
                    : `<div>${escape(data.text)}</div>`;
            },
        },
    });
    ts.clear(true);

    tomInstances[idx] = ts;
    ingredientCount++;

    document.getElementById(`qty-${idx}`).addEventListener('input', () => {
        clearErr(`err-qty-${idx}`);
        document.getElementById(`qty-${idx}`).classList.remove('is-invalid');
        calculateCost(idx);
    });
}

function handleMaterialSelect(index, value) {
    if (value) {
        selectedByIndex[index] = parseInt(value);
        clearErr(`err-material-${index}`);
        const m = rawMaterials.find(x => x.id == value);
        if (m) {
            document.getElementById(`unit-${index}`).textContent      = m.unit.toUpperCase();
            document.getElementById(`available-${index}`).textContent = `${parseFloat(m.quantity).toFixed(2)} ${m.unit}`;
        }
    } else {
        delete selectedByIndex[index];
        document.getElementById(`unit-${index}`).textContent      = '—';
        document.getElementById(`available-${index}`).textContent = '—';
    }
    updateTomOptions();
    calculateCost(index);
}

function isTakenByOther(optVal, forIdx) {
    return Object.entries(selectedByIndex).some(([i, v]) => v === optVal && parseInt(i) !== forIdx);
}

function updateTomOptions() {
    Object.entries(tomInstances).forEach(([idx, ts]) => {
        const myIdx   = parseInt(idx);
        const current = selectedByIndex[myIdx];
        ts.clearOptions();
        rawMaterials.forEach(m => {
            ts.addOption({
                value: String(m.id),
                text:  `${m.name} — ₱${parseFloat(m.unit_price).toFixed(2)}/${m.unit} (${parseFloat(m.quantity).toFixed(2)} avail.)`,
            });
        });
        if (current) ts.setValue(String(current), true);
        ts.refreshOptions(false);
    });
}

function calculateCost(index) {
    const ts     = tomInstances[index];
    const input  = document.getElementById(`qty-${index}`);
    const costEl = document.getElementById(`cost-${index}`);
    if (ts && ts.getValue() && input && parseFloat(input.value) > 0) {
        const m = rawMaterials.find(x => x.id == ts.getValue());
        if (m) costEl.textContent = `₱${(parseFloat(m.unit_price) * parseFloat(input.value)).toFixed(2)}`;
    } else {
        if (costEl) costEl.textContent = '';
    }
    calculateTotalCost();
}

function calculateTotalCost() {
    let total = 0;
    for (let i = 0; i < ingredientCount; i++) {
        const ts    = tomInstances[i];
        const input = document.getElementById(`qty-${i}`);
        if (ts && ts.getValue() && input && parseFloat(input.value) > 0) {
            const m = rawMaterials.find(x => x.id == ts.getValue());
            if (m) total += parseFloat(m.unit_price) * parseFloat(input.value);
        }
    }
    document.getElementById('totalCost').textContent     = total.toFixed(2);
    document.getElementById('costSummary').style.display = total > 0 ? 'block' : 'none';
    // Set cost_price = total material cost (unit cost calculated during Create MIX)
    document.getElementById('costPriceInput').value = total.toFixed(2);
    calculateProfit();
}

function calculateProfit() {
    const cost    = parseFloat(document.getElementById('costPriceInput').value) || 0;
    const selling = parseFloat(document.getElementById('sellingPrice').value)   || 0;
    const div     = document.getElementById('profitMargin');
    if (selling > 0) {
        const profit = selling - cost;
        const margin = ((profit / selling) * 100).toFixed(1);
        div.innerHTML = profit >= 0
            ? `<small class="text-success"><i class="bi bi-graph-up"></i> Profit: ₱${profit.toFixed(2)} (${margin}%)</small>`
            : `<small class="text-danger"><i class="bi bi-graph-down"></i> Loss: ₱${Math.abs(profit).toFixed(2)}</small>`;
    } else {
        div.innerHTML = '';
    }
}

function removeIngredient(index) {
    if (tomInstances[index]) { tomInstances[index].destroy(); delete tomInstances[index]; }
    delete selectedByIndex[index];
    const row = document.getElementById(`ingredient-${index}`);
    if (row) row.remove();
    updateTomOptions();
    calculateTotalCost();
    if (!document.getElementById('ingredientsContainer').children.length) {
        document.getElementById('noIngredientsAlert').style.display = 'block';
    }
}

function showErr(id, msg) { const e = document.getElementById(id); if (e) e.textContent = msg; }
function clearErr(id)     { const e = document.getElementById(id); if (e) e.textContent = ''; }

document.getElementById('productForm').addEventListener('submit', function (e) {
    let valid   = true;
    const isMfg = document.getElementById('typeManufactured').checked;

    const nameEl = document.querySelector('[name="name"]');
    if (!nameEl.value.trim()) {
        nameEl.classList.add('is-invalid');
        showErr('err-name', 'Product name is required.');
        valid = false;
    } else {
        nameEl.classList.remove('is-invalid');
        clearErr('err-name');
    }

    const sellEl = document.getElementById('sellingPrice');
    if (!sellEl.value || parseFloat(sellEl.value) <= 0) {
        sellEl.classList.add('is-invalid');
        showErr('err-selling', 'Enter a selling price greater than 0.');
        valid = false;
    } else {
        sellEl.classList.remove('is-invalid');
        clearErr('err-selling');
    }

    const minEl = document.querySelector('[name="minimum_stock"]');
    if (minEl.value === '') {
        minEl.classList.add('is-invalid');
        showErr('err-min-stock', 'Minimum stock is required.');
        valid = false;
    } else {
        minEl.classList.remove('is-invalid');
        clearErr('err-min-stock');
    }

    if (isMfg) {
        if (!document.getElementById('ingredientsContainer').children.length) {
            e.preventDefault();
            alert('⚠️ Add at least one ingredient for manufactured products.');
            return;
        }
        for (let i = 0; i < ingredientCount; i++) {
            if (!document.getElementById(`ingredient-${i}`)) continue;
            const ts    = tomInstances[i];
            const qtyEl = document.getElementById(`qty-${i}`);

            if (!ts || !ts.getValue()) {
                showErr(`err-material-${i}`, 'Please select a material.');
                valid = false;
            } else {
                clearErr(`err-material-${i}`);
            }

            if (!qtyEl || !qtyEl.value || parseFloat(qtyEl.value) <= 0) {
                qtyEl.classList.add('is-invalid');
                showErr(`err-qty-${i}`, 'Enter a quantity greater than 0.');
                valid = false;
            } else {
                qtyEl.classList.remove('is-invalid');
                clearErr(`err-qty-${i}`);
            }
        }
    }

    if (!valid) e.preventDefault();
});

document.querySelector('[name="name"]').addEventListener('blur', function () {
    if (!this.value.trim()) {
        this.classList.add('is-invalid');
        showErr('err-name', 'Product name is required.');
    } else {
        this.classList.remove('is-invalid');
        clearErr('err-name');
    }
});

document.getElementById('sellingPrice').addEventListener('blur', function () {
    if (!this.value || parseFloat(this.value) <= 0) {
        this.classList.add('is-invalid');
        showErr('err-selling', 'Enter a selling price greater than 0.');
    } else {
        this.classList.remove('is-invalid');
        clearErr('err-selling');
    }
});

document.getElementById('typeManufactured').addEventListener('change', updateProductType);
document.getElementById('typeConsigned').addEventListener('change', updateProductType);
document.addEventListener('DOMContentLoaded', updateProductType);
</script>

<style>
.ts-wrapper .ts-control {
    min-height: 31px;
    padding: .25rem .5rem;
    font-size: .875rem;
}
.ts-dropdown {
    font-size: .875rem;
    z-index: 9999 !important;
    position: absolute !important;
}
.table-responsive { overflow: visible !important; }
#ingredientsTable  { overflow: visible !important; }
.card              { overflow: visible !important; }
.card-body         { overflow: visible !important; }
.ts-dropdown .option div[style*="line-through"] { pointer-events: none; }
#ingredientsTable thead th {
    font-size: .74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    padding: .55rem .75rem;
    white-space: nowrap;
    background: #f8fafc;
}
#ingredientsTable tbody td {
    padding: .5rem .75rem;
    vertical-align: middle;
}
#ingredientsContainer tr:last-child td { border-bottom: none; }
</style>

@endsection