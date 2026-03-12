@extends('layouts.sidebar')

@section('page-title', 'Add New Product')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.bootstrap5.min.css">

<style>
    .create-wrap {
        max-width: 680px;
        margin: 0 auto;
    }
    .create-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 1.75rem 2rem;
    }
    .create-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .create-title i { color: var(--brand-accent); }

    .field-label {
        font-size: .8rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: .3rem;
        display: block;
    }
    .field-hint {
        font-size: .72rem;
        color: var(--text-muted);
        margin-top: .25rem;
    }
    .field-group { margin-bottom: 1.1rem; }

    /* Type toggle */
    .type-toggle {
        display: flex;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }
    .type-option { flex: 1; }
    .type-option input[type=radio] { display: none; }
    .type-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .25rem;
        padding: .75rem;
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        cursor: pointer;
        transition: all .15s;
        background: var(--bg-card);
        text-align: center;
        width: 100%;
    }
    .type-btn i { font-size: 1.2rem; color: var(--text-muted); }
    .type-btn strong { font-size: .82rem; color: var(--text-primary); display: block; }
    .type-btn small { font-size: .7rem; color: var(--text-muted); }
    .type-option input:checked + .type-btn {
        border-color: var(--brand-accent);
        background: var(--accent-light);
    }
    .type-option input:checked + .type-btn i,
    .type-option input:checked + .type-btn strong { color: var(--accent); }

    /* Section divider */
    .section-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin: 1.25rem 0 .85rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    /* Recipe table */
    .recipe-wrap {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: visible;
        margin-bottom: 1rem;
    }
    .recipe-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .5rem .85rem;
        background: var(--bg-page);
        border-bottom: 1px solid var(--border);
        border-radius: var(--radius) var(--radius) 0 0;
    }
    .recipe-header span {
        font-size: .75rem;
        font-weight: 600;
        color: var(--text-secondary);
    }
    .btn-add-ing {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .2rem .55rem;
        font-size: .72rem;
        font-weight: 600;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-add-ing:hover { background: var(--accent-hover); }

    .recipe-table { width: 100%; border-collapse: collapse; font-size: .78rem; overflow: visible; }
    .recipe-table thead th {
        background: var(--brand-deep);
        color: rgba(255,255,255,.85);
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: .4rem .75rem;
        white-space: nowrap;
    }
    .recipe-table tbody td {
        padding: .45rem .75rem;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .recipe-table tbody tr:last-child td { border-bottom: none; }

    .cost-summary {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: .6rem .85rem;
        background: var(--accent-light);
        border-top: 1px solid var(--border);
        border-radius: 0 0 var(--radius) var(--radius);
    }
    .cost-label { font-size: .68rem; color: var(--text-muted); display: block; }
    .cost-value { font-size: 1rem; font-weight: 700; color: var(--accent); }

    .no-ing-notice {
        padding: .6rem .85rem;
        font-size: .75rem;
        color: var(--s-warning-text);
        background: var(--s-warning-bg);
        border-top: 1px solid var(--border);
        border-radius: 0 0 var(--radius) var(--radius);
    }

    /* Profit badge */
    #profitMargin small { font-size: .72rem; }

    /* Action buttons */
    .form-actions {
        display: flex;
        gap: .5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--border);
        margin-top: 1.5rem;
    }
    .btn-save {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .42rem 1.1rem;
        background: var(--accent); color: #fff;
        border: none; border-radius: 6px;
        font-size: .82rem; font-weight: 700; cursor: pointer;
        transition: background .15s;
    }
    .btn-save:hover { background: var(--accent-hover); }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .42rem .9rem;
        background: #e8ecee; color: var(--text-secondary);
        border: none; border-radius: 6px;
        font-size: .82rem; font-weight: 600;
        text-decoration: none !important;
        transition: background .15s;
    }
    .btn-cancel:hover { background: #dde3e5; }

    /* TomSelect sizing */
    .ts-wrapper .ts-control { min-height: 31px; padding: .25rem .5rem; font-size: .82rem; }
    .ts-dropdown { font-size: .82rem; z-index: 9999 !important; }
</style>

<div class="mb-3">
    <a href="{{ route('finished-products.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

@if($errors->any())
<div class="create-wrap mb-3">
    <div class="alert alert-danger py-2" style="font-size:.8rem">
        <i class="bi bi-exclamation-triangle-fill me-1"></i><strong>Please fix the following:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="create-wrap">
    <div class="create-card">
        <div class="create-title">
            <i class="bi bi-plus-circle-fill"></i> Add Finished Product
        </div>

        <form action="{{ route('finished-products.store') }}" method="POST" id="productForm" novalidate>
            @csrf
            <input type="hidden" name="sku" value="{{ old('sku', 'PROD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}">

            {{-- Product Type --}}
            <div class="field-label">Product Type <span style="color:var(--s-danger-text)">*</span></div>
            <div class="type-toggle">
                <div class="type-option">
                    <input type="radio" name="product_type" id="typeManufactured" value="manufactured"
                           {{ old('product_type','manufactured') === 'manufactured' ? 'checked' : '' }} required>
                    <label class="type-btn" for="typeManufactured">
                        <i class="bi bi-gear-wide-connected"></i>
                        <strong>Manufactured</strong>
                        <small>Define recipe</small>
                    </label>
                </div>
                <div class="type-option">
                    <input type="radio" name="product_type" id="typeConsigned" value="consigned"
                           {{ old('product_type') === 'consigned' ? 'checked' : '' }}>
                    <label class="type-btn" for="typeConsigned">
                        <i class="bi bi-shop"></i>
                        <strong>Consigned</strong>
                        <small>Ready-made</small>
                    </label>
                </div>
            </div>

            {{-- Recipe (manufactured only) --}}
            <div id="recipeSection" style="display:none">
                <div class="section-label">Recipe / Ingredients</div>
                <div class="recipe-wrap" id="recipeWrap">
                    <div class="recipe-header">
                        <span><i class="bi bi-list-ul me-1"></i>Ingredients</span>
                        <button type="button" class="btn-add-ing" onclick="addIngredient()">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                    <div style="overflow-x:auto;overflow-y:visible">
                        <table class="recipe-table" id="ingredientsTable">
                            <thead>
                                <tr>
                                    <th style="width:38%">Material</th>
                                    <th style="width:15%">Qty</th>
                                    <th style="width:9%">Unit</th>
                                    <th style="width:18%">Available</th>
                                    <th style="width:14%">Line Cost</th>
                                    <th style="width:6%" class="text-center">—</th>
                                </tr>
                            </thead>
                            <tbody id="ingredientsContainer"></tbody>
                        </table>
                    </div>
                    <div id="noIngredientsAlert" class="no-ing-notice" style="display:none">
                        <i class="bi bi-exclamation-circle me-1"></i>Add at least one ingredient.
                    </div>
                    <div class="cost-summary" id="costSummary" style="display:none">
                        <div>
                            <span class="cost-label">Total Material Cost</span>
                            <span class="cost-value">₱<span id="totalCost">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Info --}}
            <div class="section-label">Product Information</div>

            <div class="field-group">
                <label class="field-label" for="productName">
                    Product Name <span style="color:var(--s-danger-text)">*</span>
                </label>
                <input type="text" name="name" id="productName"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="e.g. Chicharon 50g Pack"
                       value="{{ old('name') }}">
                <div class="invalid-feedback" id="err-name">@error('name'){{ $message }}@enderror</div>
            </div>

            <div class="field-group">
                <label class="field-label">Description</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Optional product description">{{ old('description') }}</textarea>
            </div>

            {{-- Pricing & Stock --}}
            <div class="section-label">Pricing & Stock</div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="field-label">
                        Cost Price (₱) <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="cost_price" id="costPriceInput"
                           class="form-control @error('cost_price') is-invalid @enderror"
                           value="{{ old('cost_price', 0) }}" min="0">
                    <div class="field-hint" id="costHint">Enter purchase cost</div>
                    <div class="invalid-feedback">@error('cost_price'){{ $message }}@enderror</div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">
                        Selling Price (₱) <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="selling_price" id="sellingPrice"
                           class="form-control @error('selling_price') is-invalid @enderror"
                           value="{{ old('selling_price') }}" min="0"
                           placeholder="0.00">
                    <div id="profitMargin"></div>
                    <div class="invalid-feedback" id="err-selling">
                        @error('selling_price'){{ $message }}@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">
                        Initial Stock <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="quantity"
                           class="form-control @error('quantity') is-invalid @enderror"
                           value="{{ old('quantity', 0) }}" min="0">
                    <div class="field-hint">Starting inventory</div>
                    <div class="invalid-feedback">@error('quantity'){{ $message }}@enderror</div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">
                        Low Stock Alert Threshold <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="minimum_stock"
                           class="form-control @error('minimum_stock') is-invalid @enderror"
                           value="{{ old('minimum_stock', 0) }}" min="0">
                    <div class="field-hint">Alert when stock drops to or below this number</div>
                    <div class="invalid-feedback" id="err-min-stock">
                        @error('minimum_stock'){{ $message }}@enderror
                    </div>
                </div>
            </div>

            <div class="alert py-2" style="font-size:.76rem;background:var(--accent-light);border:1px solid var(--border);color:var(--text-secondary)">
                <i class="bi bi-info-circle me-1" style="color:var(--brand-accent)"></i>
                <strong style="color:var(--accent)">Stock</strong> and
                <strong style="color:var(--accent)">Average Cost</strong>
                are automatically calculated from Production Batches. You cannot set them manually.
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="bi bi-check-lg"></i> Save Product
                </button>
                <a href="{{ route('finished-products.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>
<script>
const rawMaterials  = @json($rawMaterials ?? []);
let ingredientCount = 0;
let selectedByIndex = {};
let tomInstances    = {};

function updateProductType() {
    const isMfg = document.getElementById('typeManufactured').checked;
    document.getElementById('recipeSection').style.display = isMfg ? 'block' : 'none';
    document.getElementById('costPriceInput').readOnly = isMfg;
    document.getElementById('costPriceInput').style.background = isMfg ? 'var(--bg-page)' : '';
    document.getElementById('costHint').textContent = isMfg ? 'Auto-calculated from recipe ✓' : 'Enter purchase cost';
    if (isMfg && !document.getElementById('ingredientsContainer').children.length) addIngredient();
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
            <div style="font-size:.7rem;color:var(--s-danger-text);margin-top:.2rem" id="err-material-${idx}"></div>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01"
                   name="ingredients[${idx}][quantity]"
                   id="qty-${idx}"
                   class="form-control form-control-sm"
                   placeholder="0.00">
            <div style="font-size:.7rem;color:var(--s-danger-text);margin-top:.2rem" id="err-qty-${idx}"></div>
        </td>
        <td><span class="badge" id="unit-${idx}" style="background:var(--s-info-bg);color:var(--s-info-text);font-size:.68rem">—</span></td>
        <td><span id="available-${idx}" style="font-size:.8rem;color:var(--brand-accent);font-weight:600">—</span></td>
        <td><span id="cost-${idx}" style="font-size:.8rem;color:var(--s-success-text);font-weight:600"></span></td>
        <td class="text-center">
            <button type="button" onclick="removeIngredient(${idx})"
                    style="background:none;border:none;color:var(--s-danger-text);cursor:pointer;font-size:.9rem;padding:.1rem .3rem">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>
    `;
    document.getElementById('ingredientsContainer').appendChild(tr);
    document.getElementById('noIngredientsAlert').style.display = 'none';

    const ts = new TomSelect(`#material-select-${idx}`, {
        placeholder: 'Type to search…',
        allowEmptyOption: false,
        maxOptions: 500,
        dropdownParent: 'body',
        onChange(value) { handleMaterialSelect(idx, value); },
        render: {
            option(data, escape) {
                return isTakenByOther(parseInt(data.value), idx)
                    ? `<div style="color:var(--text-muted);text-decoration:line-through;cursor:not-allowed">${escape(data.text)}</div>`
                    : `<div>${escape(data.text)}</div>`;
            },
        },
    });
    ts.clear(true);
    tomInstances[idx] = ts;
    ingredientCount++;

    document.getElementById(`qty-${idx}`).addEventListener('input', () => {
        document.getElementById(`qty-${idx}`).classList.remove('is-invalid');
        clearErr(`err-qty-${idx}`);
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
            ts.addOption({ value: String(m.id), text: `${m.name} — ₱${parseFloat(m.unit_price).toFixed(2)}/${m.unit} (${parseFloat(m.quantity).toFixed(2)} avail.)` });
        });
        if (current) ts.setValue(String(current), true);
        ts.refreshOptions(false);
    });
}

function calculateCost(index) {
    const ts = tomInstances[index];
    const input = document.getElementById(`qty-${index}`);
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
        const ts = tomInstances[i];
        const input = document.getElementById(`qty-${i}`);
        if (ts && ts.getValue() && input && parseFloat(input.value) > 0) {
            const m = rawMaterials.find(x => x.id == ts.getValue());
            if (m) total += parseFloat(m.unit_price) * parseFloat(input.value);
        }
    }
    document.getElementById('totalCost').textContent = total.toFixed(2);
    document.getElementById('costSummary').style.display = total > 0 ? 'flex' : 'none';
    document.getElementById('costPriceInput').value = total.toFixed(2);
    calculateProfit();
}

function calculateProfit() {
    const cost    = parseFloat(document.getElementById('costPriceInput').value) || 0;
    const selling = parseFloat(document.getElementById('sellingPrice').value) || 0;
    const div     = document.getElementById('profitMargin');
    if (selling > 0) {
        const profit = selling - cost;
        const margin = ((profit / selling) * 100).toFixed(1);
        div.innerHTML = profit >= 0
            ? `<small style="color:var(--s-success-text)"><i class="bi bi-graph-up"></i> Profit: ₱${profit.toFixed(2)} (${margin}%)</small>`
            : `<small style="color:var(--s-danger-text)"><i class="bi bi-graph-down"></i> Loss: ₱${Math.abs(profit).toFixed(2)}</small>`;
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

document.getElementById('productForm').addEventListener('submit', function(e) {
    let valid = true;
    const isMfg = document.getElementById('typeManufactured').checked;

    const nameEl = document.querySelector('[name="name"]');
    if (!nameEl.value.trim()) {
        nameEl.classList.add('is-invalid'); showErr('err-name', 'Product name is required.'); valid = false;
    } else { nameEl.classList.remove('is-invalid'); clearErr('err-name'); }

    const sellEl = document.getElementById('sellingPrice');
    if (!sellEl.value || parseFloat(sellEl.value) <= 0) {
        sellEl.classList.add('is-invalid'); showErr('err-selling', 'Enter a selling price > 0.'); valid = false;
    } else { sellEl.classList.remove('is-invalid'); clearErr('err-selling'); }

    const minEl = document.querySelector('[name="minimum_stock"]');
    if (minEl.value === '') {
        minEl.classList.add('is-invalid'); showErr('err-min-stock', 'Minimum stock is required.'); valid = false;
    } else { minEl.classList.remove('is-invalid'); clearErr('err-min-stock'); }

    if (isMfg) {
        if (!document.getElementById('ingredientsContainer').children.length) {
            e.preventDefault(); alert('Add at least one ingredient for manufactured products.'); return;
        }
        for (let i = 0; i < ingredientCount; i++) {
            if (!document.getElementById(`ingredient-${i}`)) continue;
            const ts = tomInstances[i];
            const qtyEl = document.getElementById(`qty-${i}`);
            if (!ts || !ts.getValue()) { showErr(`err-material-${i}`, 'Select a material.'); valid = false; }
            else clearErr(`err-material-${i}`);
            if (!qtyEl || !qtyEl.value || parseFloat(qtyEl.value) <= 0) {
                qtyEl.classList.add('is-invalid'); showErr(`err-qty-${i}`, 'Enter qty > 0.'); valid = false;
            } else { qtyEl.classList.remove('is-invalid'); clearErr(`err-qty-${i}`); }
        }
    }
    if (!valid) e.preventDefault();
});

document.querySelector('[name="name"]').addEventListener('blur', function() {
    if (!this.value.trim()) { this.classList.add('is-invalid'); showErr('err-name', 'Required.'); }
    else { this.classList.remove('is-invalid'); clearErr('err-name'); }
});
document.getElementById('sellingPrice').addEventListener('input', calculateProfit);
document.getElementById('costPriceInput').addEventListener('input', calculateProfit);
document.getElementById('typeManufactured').addEventListener('change', updateProductType);
document.getElementById('typeConsigned').addEventListener('change', updateProductType);
document.addEventListener('DOMContentLoaded', updateProductType);
</script>

@endsection