@extends('layouts.sidebar')
@section('page-title', 'New Production Batch')
@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.bootstrap5.min.css">

<style>
    .create-wrap { }

    .create-card {
        background: var(--bg-card); border: 1px solid var(--border);
        border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 1.5rem 1.75rem;
    }
    .create-title {
        font-size: .95rem; font-weight: 700; color: var(--text-primary);
        margin-bottom: 1.25rem; padding-bottom: .65rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: .5rem;
    }
    .create-title i { color: var(--accent); }

    .section-label {
        font-size: .65rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; color: var(--text-muted);
        margin: 1.25rem 0 .75rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .section-label::after { content:''; flex:1; height:1px; background:var(--border); }

    .field-label { font-size: .78rem; font-weight: 600; color: var(--text-secondary); margin-bottom: .25rem; display: block; }
    .field-hint  { font-size: .7rem; color: var(--text-muted); margin-top: .2rem; }

    /* Locked product badge */
    .locked-product {
        display: flex; align-items: center; gap: .5rem;
        padding: .45rem .75rem; background: var(--bg-page);
        border: 1px solid var(--border); border-radius: 6px;
        font-size: .82rem; font-weight: 600; color: var(--text-primary);
    }
    .locked-product i { color: var(--accent); }

    /* Materials table */
    .mat-wrap { border: 1px solid var(--border); border-radius: var(--radius); overflow: visible; }
    .mat-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: .5rem .85rem; background: var(--bg-page);
        border-bottom: 1px solid var(--border);
        border-radius: var(--radius) var(--radius) 0 0;
    }
    .mat-header span { font-size: .75rem; font-weight: 600; color: var(--text-secondary); }
    .btn-add-mat {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .2rem .55rem; font-size: .72rem; font-weight: 600;
        background: var(--accent); color: #fff;
        border: none; border-radius: 5px; cursor: pointer; transition: background .15s;
    }
    .btn-add-mat:hover { background: var(--accent-hover); }

    .mat-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .mat-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .42rem .75rem; white-space: nowrap;
    }
    .mat-table tbody td { padding: .42rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .mat-table tbody tr:last-child td { border-bottom: none; }

    /* Cost summary strip */
    .cost-strip {
        display: flex; gap: 2rem; flex-wrap: wrap;
        padding: .6rem .85rem; background: var(--accent-light);
        border-top: 1px solid var(--border);
        border-radius: 0 0 var(--radius) var(--radius);
    }
    .cost-strip-item-label { font-size: .65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .4px; display: block; }
    .cost-strip-item-value { font-size: .92rem; font-weight: 700; color: var(--accent); }

    /* Stock warning (blocking) */
    .stock-warning {
        padding: .55rem .85rem; font-size: .78rem;
        background: var(--s-danger-bg); border: 1px solid #f5c0c0;
        color: var(--s-danger-text); border-radius: var(--radius); margin-bottom: .75rem;
    }
    /* Short stock advisory (non-blocking — inventory may go negative) */
    .stock-advisory {
        padding: .55rem .85rem; font-size: .78rem;
        background: var(--s-warning-bg); border: 1px solid #e8d5a0;
        color: var(--s-warning-text); border-radius: var(--radius); margin-bottom: .75rem;
    }

    /* Actions */
    .form-actions { display: flex; gap: .5rem; padding-top: 1.25rem; border-top: 1px solid var(--border); margin-top: 1.25rem; }
    .btn-save {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .42rem 1.1rem; background: var(--accent); color: #fff;
        border: none; border-radius: 6px; font-size: .82rem; font-weight: 700;
        cursor: pointer; transition: background .15s;
    }
    .btn-save:hover { background: var(--accent-hover); }
    .btn-save:disabled { background: var(--text-muted); cursor: not-allowed; }
    .btn-cancel {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .42rem .9rem; background: #e8ecee; color: var(--text-secondary);
        border: none; border-radius: 6px; font-size: .82rem; font-weight: 600;
        text-decoration: none !important; transition: background .15s;
    }
    .btn-cancel:hover { background: #dde3e5; }

    /* TomSelect */
    .ts-wrapper .ts-control { min-height: 31px; padding: .22rem .5rem; font-size: .8rem; }
    .ts-dropdown { font-size: .8rem; z-index: 9999 !important; }
</style>

<div class="mb-3">
    <a href="{{ route('production-mixes.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to Batches
    </a>
</div>

<div class="create-wrap">
<div class="create-card">
    <div class="create-title">
        <i class="bi bi-gear-wide-connected"></i> New Production Batch
    </div>

    <form action="{{ route('production-mixes.store') }}" method="POST" id="mixForm">
        @csrf

        {{-- Batch Details --}}
        <div class="section-label">Batch Details</div>

        <div class="row g-3 mb-1">
            {{-- Product --}}
            <div class="col-md-5">
                <label class="field-label">Finished Product <span style="color:var(--s-danger-text)">*</span></label>
                @if($preselectedProduct)
                    <input type="hidden" name="finished_product_id" value="{{ $preselectedProduct->id }}">
                    <div class="locked-product">
                        <i class="bi bi-lock-fill"></i>
                        {{ $preselectedProduct->name }}
                        <span style="font-size:.72rem;color:var(--text-muted);font-weight:400">
                            — Stock: {{ number_format($preselectedProduct->stock_on_hand, 0) }}
                        </span>
                    </div>
                    <div class="field-hint">
                        <i class="bi bi-lock me-1"></i>Locked.
                        <a href="{{ route('production-mixes.create') }}" style="color:var(--accent)">Switch to free form</a>
                    </div>
                @else
                    <select name="finished_product_id" id="productSelect"
                            class="form-select form-select-sm @error('finished_product_id') is-invalid @enderror" required>
                        <option value="">— Select product —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('finished_product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} (Stock: {{ number_format($p->stock_on_hand, 0) }})
                            </option>
                        @endforeach
                    </select>
                    @error('finished_product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>

            {{-- Mix Date --}}
            <div class="col-md-3">
                <label class="field-label">Mix Date <span style="color:var(--s-danger-text)">*</span></label>
                <input type="date" name="mix_date"
                       class="form-control form-control-sm @error('mix_date') is-invalid @enderror"
                       value="{{ old('mix_date', date('Y-m-d')) }}" required>
                @error('mix_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Expiry --}}
            <div class="col-md-4">
                <label class="field-label">Expiry Date <span style="font-size:.68rem;color:var(--text-muted);font-weight:400">optional</span></label>
                <input type="date" name="expiration_date"
                       class="form-control form-control-sm @error('expiration_date') is-invalid @enderror"
                       value="{{ old('expiration_date') }}">
                @error('expiration_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Multiplier --}}
            <div class="col-md-2">
                <label class="field-label">
                    # of Mixes
                    <span style="font-size:.66rem;color:var(--text-muted);font-weight:400">multiplier</span>
                </label>
                <div style="display:flex;align-items:center;gap:.3rem">
                    <button type="button" id="multMinus" style="width:28px;height:31px;border:1px solid var(--border);border-radius:5px;background:var(--bg-page);color:var(--text-secondary);font-size:.9rem;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center">−</button>
                    <input type="number" name="multiplier" id="mixMultiplier"
                           class="form-control form-control-sm text-center"
                           style="font-weight:700;font-size:.9rem;color:var(--accent)"
                           min="1" max="99" step="1" value="{{ old('multiplier', 1) }}">
                    <button type="button" id="multPlus" style="width:28px;height:31px;border:1px solid var(--border);border-radius:5px;background:var(--bg-page);color:var(--text-secondary);font-size:.9rem;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center">+</button>
                </div>
                <div class="field-hint" style="color:var(--accent)">1 = single batch</div>
                <div class="field-hint" style="font-size:.68rem;color:var(--text-muted);max-width:28rem">
                    Ingredient <strong>Qty</strong> below = (recipe per batch × this number). Changing it updates all lines.
                </div>
            </div>
            <div class="col-12 mb-0">
                <p class="mb-0" style="font-size:.72rem;color:var(--text-muted);line-height:1.45">
                    <i class="bi bi-info-circle me-1" style="color:var(--accent)"></i>
                    <strong>Qty unit:</strong> enter amounts in any compatible unit (e.g. <strong>300 G</strong> of flour when inventory is tracked in <strong>KG</strong>) — stock deducts in the material&rsquo;s inventory unit.
                </p>
            </div>

            {{-- Expected --}}
            <div class="col-md-3">
                <label class="field-label">Standard Output <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" name="expected_output" id="expectedQty"
                       class="form-control form-control-sm @error('expected_output') is-invalid @enderror"
                       step="0.01" min="0.01" value="{{ old('expected_output') }}" placeholder="Auto from multiplier" required>
                <div class="field-hint">Standard output per recipe</div>
                @error('expected_output')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Actual --}}
            <div class="col-md-3">
                <label class="field-label">Actual Output <span style="color:var(--s-danger-text)">*</span></label>
                <input type="number" name="actual_output" id="actualQty"
                       class="form-control form-control-sm @error('actual_output') is-invalid @enderror"
                       step="0.01" min="0.01" value="{{ old('actual_output') }}" placeholder="0" required>
                <div class="field-hint" style="color:var(--accent)">↑ Full output added to stock</div>
                @error('actual_output')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Rejects --}}
            <div class="col-md-2">
                <label class="field-label">Rejects</label>
                <input type="number" name="rejected_quantity"
                       class="form-control form-control-sm"
                       step="0.01" min="0" value="{{ old('rejected_quantity', 0) }}" placeholder="0">
                <div class="field-hint" style="color:var(--s-danger-text)">For records only</div>
            </div>

            {{-- Notes --}}
            <div class="col-md-4">
                <label class="field-label">Notes</label>
                <input type="text" name="notes" class="form-control form-control-sm"
                       value="{{ old('notes') }}" placeholder="Optional…">
            </div>
        </div>

        {{-- Raw Materials --}}
        <div class="section-label">Raw Materials Used</div>

        {{-- Blocking: invalid qty unit. Advisory: short stock (save still allowed). --}}
        <div id="stockWarning" class="stock-warning d-none mb-2">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Fix before saving:</strong>
            <span id="stockWarningMsg"></span>
        </div>
        <div id="stockAdvisory" class="stock-advisory d-none mb-2">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Below-zero inventory:</strong> you can still save — raw material stock will go negative for:
            <span id="stockAdvisoryMsg"></span>
        </div>

        {{-- No product notice --}}
        <div id="noProductNotice" class="mb-2" style="font-size:.75rem;color:var(--text-muted);{{ $preselectedProduct ? 'display:none' : '' }}">
            <i class="bi bi-info-circle me-1" style="color:var(--accent)"></i>
            Select a product above to auto-populate raw materials from its recipe.
        </div>

        <div class="mat-wrap">
            <div class="mat-header">
                <span><i class="bi bi-boxes me-1"></i>Ingredients</span>
                <button type="button" class="btn-add-mat" id="addRow">
                    <i class="bi bi-plus-lg"></i> Add Material
                </button>
            </div>
            <div style="overflow-x:auto;overflow-y:visible">
                <table class="mat-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width:24%">Material</th>
                            <th style="width:8%">Category</th>
                            <th style="width:10%">Available</th>
                            <th style="width:7%">Inv. unit</th>
                            <th style="width:9%">Qty</th>
                            <th style="width:11%">Qty unit</th>
                            <th style="width:10%">Remaining</th>
                            <th style="width:9%">Cost/Unit</th>
                            <th style="width:9%">Line Cost</th>
                            <th style="width:4%" class="text-center">—</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
            <div class="cost-strip" id="costStrip">
                <div>
                    <span class="cost-strip-item-label">Multiplier</span>
                    <span class="cost-strip-item-value" style="color:var(--text-primary)">×<span id="stripMultiplier">1</span></span>
                </div>
                <div>
                    <span class="cost-strip-item-label">Total Material Cost</span>
                    <span class="cost-strip-item-value">₱<span id="totalCost">0.00</span></span>
                </div>
                <div>
                    <span class="cost-strip-item-label">Est. Cost / Unit</span>
                    <span class="cost-strip-item-value">₱<span id="costPerUnit">0.0000</span></span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save" id="submitBtn">
                <i class="bi bi-check-lg"></i> Save Production Batch
            </button>
            <a href="{{ route('production-mixes.index') }}" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>
<script>
let allMaterials   = @json($allMaterials);
let productRecipes = @json($productRecipes);
const MIX_UNIT_OPTIONS = @json($mixUnitOptions);
let rowIndex       = 0;
let tomInstances   = {}; // { rowIndex: TomSelectInstance }

function buildUnitSelectHtml(selected) {
    let html = '';
    const sel = selected || 'KG';
    for (const [k, label] of Object.entries(MIX_UNIT_OPTIONS)) {
        html += '<option value="' + k + '"' + (k === sel ? ' selected' : '') + '>' + label + '</option>';
    }
    return html;
}

/** Convert qty from input unit to raw material storage unit (matches server). */
function toStorageQty(qty, fromUnit, storageUnit) {
    if (!qty || !fromUnit || !storageUnit) return 0;
    if (fromUnit === storageUnit) return qty;
    const mass = { KG: 1000, G: 1 };
    if (mass[fromUnit] && mass[storageUnit]) {
        return qty * mass[fromUnit] / mass[storageUnit];
    }
    const vol = { L: 1000, ML: 1 };
    if (vol[fromUnit] && vol[storageUnit]) {
        return qty * vol[fromUnit] / vol[storageUnit];
    }
    return NaN;
}

/** Convert storage-unit qty to display unit (inverse of toStorageQty). */
function fromStorageQty(storageQty, storageUnit, inputUnit) {
    if (!storageQty || !storageUnit || !inputUnit) return 0;
    if (storageUnit === inputUnit) return storageQty;
    const mass = { KG: 1000, G: 1 };
    if (mass[storageUnit] && mass[inputUnit]) {
        return storageQty * mass[storageUnit] / mass[inputUnit];
    }
    const vol = { L: 1000, ML: 1 };
    if (vol[storageUnit] && vol[inputUnit]) {
        return storageQty * vol[storageUnit] / vol[inputUnit];
    }
    return NaN;
}

/** dataset.baseQty = per-batch quantity in inventory (storage) units. */
function syncBaseQtyFromRow(row) {
    var mult = getMultiplier();
    var qtyIn = parseFloat(row.querySelector('.qty-input').value) || 0;
    var unitIn = row.querySelector('.unit-select') ? row.querySelector('.unit-select').value : '';
    var storageU = row.dataset.storageUnit || '';
    if (!storageU || mult < 1) return;
    if (qtyIn <= 0) {
        row.dataset.baseQty = '';
        return;
    }
    var totalStorage = toStorageQty(qtyIn, unitIn, storageU);
    if (isNaN(totalStorage) || totalStorage <= 0) return;
    row.dataset.baseQty = String(totalStorage / mult);
}

/** Set qty input from baseQty × multiplier, respecting qty unit dropdown. */
function syncRowQtyFromBase(row) {
    var mult = getMultiplier();
    var base = parseFloat(row.dataset.baseQty);
    var storageU = row.dataset.storageUnit || '';
    var input = row.querySelector('.qty-input');
    var unitSel = row.querySelector('.unit-select');
    if (!input || !unitSel || !storageU) return;
    if (isNaN(base) || base <= 0) return;
    var totalStorage = base * mult;
    var unitIn = unitSel.value;
    var displayVal = fromStorageQty(totalStorage, storageU, unitIn);
    if (isNaN(displayVal)) displayVal = totalStorage;
    input.value = displayVal % 1 === 0 ? displayVal : parseFloat(displayVal.toFixed(4));
}

// ── Product select (free mode) ──────────────────────────────────────────────
const productSelectEl = document.getElementById('productSelect');
if (productSelectEl && productSelectEl.tagName === 'SELECT') {
    const tsProduct = new TomSelect('#productSelect', {
        placeholder: 'Type to search product…',
        allowEmptyOption: true,
        maxOptions: 200,
        dropdownParent: 'body',
        onChange(value) {
            if (!value) {
                document.getElementById('itemsBody').innerHTML = '';
                destroyAllTom();
                calcTotals(); checkAllStock();
                return;
            }
            populateRecipe(value);
        }
    });
}

// ── Multiplier helpers ──────────────────────────────────────────────────────
function getMultiplier() {
    return Math.max(1, parseInt(document.getElementById('mixMultiplier').value) || 1);
}

function applyMultiplier() {
    var mult = getMultiplier();
    document.getElementById('stripMultiplier').textContent = mult;

    document.querySelectorAll('.item-row').forEach(function(row) {
        syncBaseQtyFromRow(row);
        syncRowQtyFromBase(row);
        calcRow(row);
    });

    // Scale expected output if it has a base value stored
    var baseExp = parseFloat(document.getElementById('expectedQty').dataset.baseExpected || 0);
    if (baseExp > 0) {
        document.getElementById('expectedQty').value = parseFloat((baseExp * mult).toFixed(2));
    }

    calcTotals();
    checkAllStock();
}

// ── Populate recipe ─────────────────────────────────────────────────────────
function populateRecipe(productId) {
    var recipe = productRecipes[productId] || [];
    destroyAllTom();
    document.getElementById('itemsBody').innerHTML = '';
    document.getElementById('noProductNotice').style.display = 'none';
    rowIndex = 0;

    if (recipe.length > 0) {
        var mult = getMultiplier();
        recipe.forEach(function(item) {
            var storageU = item.unit_canonical || item.unit || 'KG';
            var defU = storageU;
            var totalStorage = parseFloat(item.quantity_needed) * mult;
            var displayQty = fromStorageQty(totalStorage, storageU, defU);
            if (isNaN(displayQty)) displayQty = totalStorage;
            addRow(item, displayQty, item.quantity_needed, defU);
        });
    } else {
        addRow(null, '');
    }
    calcTotals(); checkAllStock();
}

// ── Destroy all TomSelect instances ────────────────────────────────────────
function destroyAllTom() {
    Object.values(tomInstances).forEach(ts => { try { ts.destroy(); } catch(e){} });
    tomInstances = {};
}

// ── Get selected material IDs ───────────────────────────────────────────────
function getSelectedIds() {
    return Object.entries(tomInstances)
        .map(([idx, ts]) => ts.getValue())
        .filter(v => v !== '');
}

// ── Add a row ───────────────────────────────────────────────────────────────
function addRow(material = null, qty = '', baseQty = null, defaultUnit = null) {
    const idx     = rowIndex;
    const matId   = material ? (material.raw_material_id ?? material.id ?? '') : '';
    const stockVal = material ? parseFloat(material.stock_quantity ?? material.quantity ?? 0) : 0;
    const costVal  = material ? parseFloat(material.cost_per_unit  ?? material.unit_price  ?? 0) : 0;
    const unit     = material ? (material.unit ?? '—') : '—';
    const storageU = material ? (material.unit_canonical || material.unit || '') : '';
    const cat      = material ? (material.category ?? '') : '';
    const stockClass = stockVal <= 0 ? 'color:var(--s-danger-text)' : 'color:var(--accent)';
    const unitSelDefault = defaultUnit || storageU || 'KG';

    // Build option list
    const selected = getSelectedIds();
    const optionsHtml = allMaterials.map(m => {
        const isCur = String(m.id) === String(matId);
        const isDis = selected.includes(String(m.id)) && !isCur;
        return `<option value="${m.id}"
            data-unit="${m.unit}" data-category="${m.category}"
            data-stock="${m.quantity}" data-cost="${m.unit_price}"
            ${isCur ? 'selected' : ''} ${isDis ? 'disabled' : ''}>
            ${m.name} — ${parseFloat(m.quantity).toFixed(2)} ${m.unit}
        </option>`;
    }).join('');

    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.index = idx;
    if (baseQty !== null && baseQty !== '' && !isNaN(parseFloat(baseQty))) {
        tr.dataset.baseQty = String(parseFloat(baseQty));
    } else if (material && qty !== '' && qty !== null && !isNaN(parseFloat(qty))) {
        var mult0 = getMultiplier();
        var totalSt = toStorageQty(parseFloat(qty), unitSelDefault, storageU);
        if (!isNaN(totalSt) && totalSt > 0 && mult0 >= 1 && storageU) {
            tr.dataset.baseQty = String(totalSt / mult0);
        } else {
            tr.dataset.baseQty = '';
        }
    } else {
        tr.dataset.baseQty = '';
    }
    tr.dataset.storageUnit = storageU;
    tr.innerHTML = `
        <td style="overflow:visible;min-width:180px">
            <select id="mat-select-${idx}" name="items[${idx}][raw_material_id]" required>
                <option value="">— Select material —</option>
                ${optionsHtml}
            </select>
        </td>
        <td><span class="cat-label" style="font-size:.72rem;color:var(--text-muted)">${catLabel(cat)}</span></td>
        <td><span class="stock-label" data-stock="${stockVal}" style="font-size:.78rem;font-weight:600;${stockClass}">${material ? stockVal.toFixed(2)+' '+unit : '—'}</span></td>
        <td><span class="unit-label" style="font-size:.72rem;color:var(--text-muted)">${unit}</span></td>
        <td><input type="number" name="items[${idx}][quantity_used]" class="form-control form-control-sm qty-input" step="0.0001" min="0.0001" value="${qty}" placeholder="0" required></td>
        <td><select name="items[${idx}][unit]" class="form-select form-select-sm unit-select" required>${buildUnitSelectHtml(unitSelDefault)}</select></td>
        <td><span class="remaining-label" style="font-size:.74rem;color:var(--text-muted)">—</span></td>
        <td><span class="cost-label" data-cost="${costVal}" style="font-size:.74rem;color:var(--text-muted)">${material ? '₱'+costVal.toFixed(4) : '—'}</span></td>
        <td><span class="line-cost" style="font-size:.78rem;font-weight:700;color:var(--s-success-text)">₱0.00</span></td>
        <td class="text-center">
            <button type="button" class="remove-row" style="background:none;border:none;color:var(--s-danger-text);cursor:pointer;font-size:.9rem;padding:.1rem .3rem">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>`;

    document.getElementById('itemsBody').appendChild(tr);

    var unitSelEl = tr.querySelector('.unit-select');
    if (unitSelEl) unitSelEl.dataset.prevUnitSnapshot = unitSelEl.value;

    // Init TomSelect on this row's select
    const ts = new TomSelect(`#mat-select-${idx}`, {
        placeholder: 'Type to search…',
        allowEmptyOption: true,
        maxOptions: 300,
        dropdownParent: 'body',
        onChange(value) { handleMaterialChange(idx, value); }
    });
    if (matId) ts.setValue(String(matId), true);
    tomInstances[idx] = ts;

    rowIndex++;
    if (material && qty) {
        calcRow(tr);
    }
}

function catLabel(cat) {
    if (cat === 'ingredients') return '🧂 Ingredient';
    if (cat === 'packaging')   return '📦 Packaging';
    return cat || '—';
}

// ── Handle material change ──────────────────────────────────────────────────
function handleMaterialChange(idx, value) {
    const row = document.querySelector(`.item-row[data-index="${idx}"]`);
    if (!row) return;

    if (!value) {
        row.querySelector('.cat-label').textContent        = '—';
        row.querySelector('.stock-label').textContent      = '—';
        row.querySelector('.stock-label').dataset.stock    = '0';
        row.querySelector('.unit-label').textContent       = '—';
        row.querySelector('.cost-label').textContent       = '—';
        row.querySelector('.cost-label').dataset.cost      = '0';
        row.querySelector('.remaining-label').textContent  = '—';
        row.querySelector('.line-cost').textContent        = '₱0.00';
        row.querySelector('.qty-input').value              = '';
        refreshAllTom();
        calcTotals(); checkAllStock();
        return;
    }

    const m = allMaterials.find(x => String(x.id) === String(value));
    if (!m) return;

    row.querySelector('.cat-label').textContent     = catLabel(m.category);
    row.querySelector('.unit-label').textContent    = m.unit;
    row.dataset.storageUnit = m.unit_canonical || m.unit || '';
    row.querySelector('.stock-label').textContent   = parseFloat(m.quantity).toFixed(2) + ' ' + m.unit;
    row.querySelector('.stock-label').dataset.stock = m.quantity;
    row.querySelector('.stock-label').style.color   = parseFloat(m.quantity) <= 0 ? 'var(--s-danger-text)' : 'var(--accent)';
    row.querySelector('.cost-label').textContent    = '₱' + parseFloat(m.unit_price).toFixed(4);
    row.querySelector('.cost-label').dataset.cost   = m.unit_price;
    row.querySelector('.qty-input').value           = '';
    var uSel = row.querySelector('.unit-select');
    if (uSel) {
        uSel.value = m.unit_canonical || m.unit || 'KG';
        uSel.dataset.prevUnitSnapshot = uSel.value;
    }
    row.querySelector('.remaining-label').textContent = '—';
    row.querySelector('.line-cost').textContent     = '₱0.00';

    refreshAllTom();
    calcTotals(); checkAllStock();
}

// ── Refresh all TomSelect (disable already-selected options) ────────────────
function refreshAllTom() {
    const selected = getSelectedIds();
    Object.entries(tomInstances).forEach(([idx, ts]) => {
        const cur = ts.getValue();
        allMaterials.forEach(m => {
            const isDis = selected.includes(String(m.id)) && String(m.id) !== String(cur);
            if (isDis) ts.getOption(String(m.id))?.classList.add('disabled');
            else       ts.getOption(String(m.id))?.classList.remove('disabled');
        });
    });
}

// ── Calc row ────────────────────────────────────────────────────────────────
function calcRow(row) {
    const qtyIn    = parseFloat(row.querySelector('.qty-input').value) || 0;
    const unitIn   = row.querySelector('.unit-select') ? row.querySelector('.unit-select').value : '';
    const storageU = row.dataset.storageUnit || '';
    const qty      = toStorageQty(qtyIn, unitIn, storageU);
    const cost     = parseFloat(row.querySelector('.cost-label').dataset.cost) || 0;
    const stock    = parseFloat(row.querySelector('.stock-label').dataset.stock) || 0;
    const unit     = row.querySelector('.unit-label').textContent.trim();
    const remaining = stock - qty;

    if (qtyIn > 0 && isNaN(qty)) {
        row.querySelector('.line-cost').textContent = '—';
        row.querySelector('.remaining-label').textContent = 'Incompatible unit';
        row.querySelector('.remaining-label').style.color = 'var(--s-danger-text)';
        calcTotals();
        checkAllStock();
        return;
    }

    row.querySelector('.line-cost').textContent = '₱' + (qty * cost).toFixed(2);

    const rem = row.querySelector('.remaining-label');
    if (qtyIn > 0 && !isNaN(qty)) {
        rem.textContent = remaining.toFixed(4) + ' ' + unit;
        rem.style.color = remaining < 0 ? 'var(--s-danger-text)' : remaining === 0 ? 'var(--s-warning-text)' : 'var(--s-success-text)';
        rem.style.fontWeight = '600';
    } else {
        rem.textContent = '—';
        rem.style.color = 'var(--text-muted)';
        rem.style.fontWeight = 'normal';
    }
    calcTotals(); checkAllStock();
}

// ── Calc totals ─────────────────────────────────────────────────────────────
function calcTotals() {
    let total = 0;
    document.querySelectorAll('.line-cost').forEach(el => {
        const raw = el.textContent.replace('₱', '').trim();
        if (raw === '—' || raw === '-') return;
        total += parseFloat(raw) || 0;
    });
    document.getElementById('totalCost').textContent = total.toFixed(2);
    const actual = parseFloat(document.getElementById('actualQty').value) || 0;
    document.getElementById('costPerUnit').textContent = actual > 0 ? (total/actual).toFixed(4) : '0.0000';
}

// ── Stock check: only invalid units block save; short stock shows advisory only. ──
function checkAllStock() {
    var errors = [];
    var shortfalls = [];
    var mult = getMultiplier();
    document.querySelectorAll('.item-row').forEach(function(row) {
        var qtyIn = parseFloat(row.querySelector('.qty-input').value) || 0;
        var unitIn = row.querySelector('.unit-select') ? row.querySelector('.unit-select').value : '';
        var storageU = row.dataset.storageUnit || '';
        var qty = toStorageQty(qtyIn, unitIn, storageU);
        var stock = parseFloat(row.querySelector('.stock-label').dataset.stock) || 0;
        var idx   = row.dataset.index;
        var ts    = tomInstances[idx];
        var invU  = row.querySelector('.unit-label').textContent.trim();
        var rawName = ts ? (ts.getOption(ts.getValue()) ? ts.getOption(ts.getValue()).textContent.split('—')[0].trim() : 'Item') : 'Item';
        if (ts && ts.getValue() && qtyIn > 0 && isNaN(qty)) {
            errors.push('<strong>' + rawName + '</strong>: qty unit does not match inventory unit (' + invU + ').');
        } else if (ts && ts.getValue() && qtyIn > 0 && !isNaN(qty) && qty > stock) {
            var shortage = (qty - stock).toFixed(4);
            var multNote = mult > 1 ? ' (×' + mult + ' mixes)' : '';
            shortfalls.push('<strong>' + rawName + '</strong>' + multNote + ': after mix, <strong>' + (qty - stock).toFixed(4) + ' ' + invU + '</strong> short (remaining will be negative)');
        }
    });

    var banner = document.getElementById('stockWarning');
    var advis  = document.getElementById('stockAdvisory');
    var btn    = document.getElementById('submitBtn');
    if (errors.length > 0) {
        banner.classList.remove('d-none');
        document.getElementById('stockWarningMsg').innerHTML = '<ul class="mb-0 mt-1 ps-3">'+errors.map(function(e){ return '<li>'+e+'</li>'; }).join('')+'</ul>';
        btn.disabled = true;
        advis.classList.add('d-none');
    } else {
        banner.classList.add('d-none');
        btn.disabled = false;
        if (shortfalls.length > 0) {
            advis.classList.remove('d-none');
            document.getElementById('stockAdvisoryMsg').innerHTML = '<ul class="mb-0 mt-1 ps-3">'+shortfalls.map(function(e){ return '<li>'+e+'</li>'; }).join('')+'</ul>';
        } else {
            advis.classList.add('d-none');
        }
    }
}

// ── Events ──────────────────────────────────────────────────────────────────
document.getElementById('itemsBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) {
        var row = e.target.closest('.item-row');
        syncBaseQtyFromRow(row);
        calcRow(row);
    }
});
document.getElementById('itemsBody').addEventListener('focusin', function(e) {
    if (e.target.classList.contains('unit-select')) {
        e.target.dataset.prevUnitSnapshot = e.target.value;
    }
});
document.getElementById('itemsBody').addEventListener('change', function(e) {
    if (e.target.classList.contains('unit-select')) {
        var row = e.target.closest('.item-row');
        var prev = e.target.dataset.prevUnitSnapshot || e.target.value;
        var storageU = row.dataset.storageUnit || '';
        var qtyIn = parseFloat(row.querySelector('.qty-input').value) || 0;
        var mult = getMultiplier();
        if (qtyIn > 0 && storageU) {
            var totalStorage = toStorageQty(qtyIn, prev, storageU);
            if (!isNaN(totalStorage) && totalStorage > 0) {
                row.dataset.baseQty = String(totalStorage / mult);
            }
        }
        e.target.dataset.prevUnitSnapshot = e.target.value;
        syncRowQtyFromBase(row);
        calcRow(row);
    }
});

document.getElementById('actualQty').addEventListener('input', calcTotals);

// Multiplier +/- buttons
document.getElementById('multMinus').addEventListener('click', function() {
    var el = document.getElementById('mixMultiplier');
    var v = Math.max(1, parseInt(el.value) || 1);
    if (v > 1) { el.value = v - 1; applyMultiplier(); }
});
document.getElementById('multPlus').addEventListener('click', function() {
    var el = document.getElementById('mixMultiplier');
    var v = parseInt(el.value) || 1;
    el.value = Math.min(99, v + 1);
    applyMultiplier();
});
document.getElementById('mixMultiplier').addEventListener('input', function() {
    applyMultiplier();
});

document.getElementById('itemsBody').addEventListener('click', e => {
    if (e.target.closest('.remove-row')) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) { alert('At least one raw material is required.'); return; }
        const row = e.target.closest('.item-row');
        const idx = row.dataset.index;
        if (tomInstances[idx]) { try { tomInstances[idx].destroy(); } catch(e){} delete tomInstances[idx]; }
        row.remove();
        refreshAllTom(); calcTotals(); checkAllStock();
    }
});

document.getElementById('addRow').addEventListener('click', () => {
    document.getElementById('noProductNotice').style.display = 'none';
    addRow(null, '');
    checkAllStock();
});

// Prevent double submit
document.getElementById('mixForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (!btn.disabled) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
    }
});

// ── On load ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    @if(old('finished_product_id') && old('items'))
        document.getElementById('noProductNotice').style.display = 'none';
        const oldItems = @json(old('items', []));
        Object.values(oldItems).forEach(item => {
            const mat = allMaterials.find(m => String(m.id) === String(item.raw_material_id));
            addRow(mat, item.quantity_used, null, item.unit);
        });
        document.querySelectorAll('.item-row').forEach(function(row) {
            syncBaseQtyFromRow(row);
        });
        calcTotals(); checkAllStock();
    @elseif($preselectedProduct)
        const pid = {{ $preselectedProduct->id }};
        if (productRecipes[pid] !== undefined) {
            populateRecipe(pid);
        } else {
            document.getElementById('noProductNotice').style.display = 'none';
            addRow(null, '');
        }
    @endif
});
</script>

@endsection