@extends('layouts.sidebar')

@section('page-title', 'Edit Finished Product')

@section('content')

<style>
    .edit-wrap { max-width: 680px; margin: 0 auto; }

    .edit-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 1.75rem 2rem;
    }
    .edit-title {
        font-size: 1rem; font-weight: 700; color: var(--text-primary);
        margin-bottom: 1.5rem; padding-bottom: .75rem;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: .5rem;
    }
    .edit-title i { color: var(--brand-accent); }

    .field-label { font-size: .8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: .3rem; display: block; }
    .field-hint  { font-size: .72rem; color: var(--text-muted); margin-top: .25rem; }
    .field-group { margin-bottom: 1.1rem; }

    .section-label {
        font-size: .65rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 1px; color: var(--text-muted);
        margin: 1.25rem 0 .85rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    /* Type badge (read-only) */
    .type-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .5rem .85rem;
        border: 1px solid var(--border); border-radius: var(--radius);
        background: var(--bg-page); font-size: .82rem; font-weight: 600;
        color: var(--text-secondary);
    }
    .type-badge i { color: var(--brand-accent); }

    /* Stock info strip */
    .stock-info {
        display: flex; align-items: center; gap: .5rem;
        padding: .55rem .85rem;
        background: var(--accent-light); border: 1px solid var(--border);
        border-radius: var(--radius); font-size: .78rem;
        color: var(--text-secondary); margin-bottom: 1rem;
    }
    .stock-info i { color: var(--brand-accent); flex-shrink: 0; }
    .stock-info strong { color: var(--accent); }

    /* Profit strip */
    .profit-strip {
        display: flex; gap: 1.5rem; flex-wrap: wrap;
        padding: .65rem .85rem;
        background: var(--bg-page); border: 1px solid var(--border);
        border-radius: var(--radius); margin-top: .5rem;
    }
    .profit-item-label { font-size: .65rem; color: var(--text-muted); display: block; text-transform: uppercase; letter-spacing: .4px; }
    .profit-item-value { font-size: .95rem; font-weight: 700; color: var(--text-primary); }

    /* Recipe */
    .recipe-wrap { border: 1px solid var(--border); border-radius: var(--radius); overflow: visible; margin-bottom: 1rem; }
    .recipe-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: .5rem .85rem; background: var(--bg-page);
        border-bottom: 1px solid var(--border);
        border-radius: var(--radius) var(--radius) 0 0;
    }
    .recipe-header-left { display: flex; align-items: center; gap: .5rem; font-size: .75rem; font-weight: 600; color: var(--text-secondary); }
    .recipe-cost-badge {
        font-size: .72rem; font-weight: 700;
        background: var(--accent-light); color: var(--accent);
        padding: .12rem .5rem; border-radius: 4px; border: 1px solid var(--border);
    }
    .btn-add-ing {
        display: inline-flex; align-items: center; gap: .25rem;
        padding: .2rem .55rem; font-size: .72rem; font-weight: 600;
        background: var(--accent); color: #fff;
        border: none; border-radius: 5px; cursor: pointer; transition: background .15s;
    }
    .btn-add-ing:hover { background: var(--accent-hover); }

    .recipe-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .recipe-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.85);
        font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .4rem .75rem; white-space: nowrap;
    }
    .recipe-table tbody td { padding: .45rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .recipe-table tbody tr:last-child td { border-bottom: none; }

    .cost-summary {
        display: flex; align-items: center; gap: 1.5rem;
        padding: .6rem .85rem; background: var(--accent-light);
        border-top: 1px solid var(--border);
        border-radius: 0 0 var(--radius) var(--radius);
    }
    .cost-label { font-size: .68rem; color: var(--text-muted); display: block; }
    .cost-value { font-size: 1rem; font-weight: 700; color: var(--accent); }

    /* Actions */
    .form-actions {
        display: flex; gap: .5rem;
        padding-top: 1.25rem; border-top: 1px solid var(--border); margin-top: 1.5rem;
    }
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
</style>

<div class="mb-3">
    <a href="{{ route('finished-products.index') }}" style="font-size:.78rem;color:var(--text-muted);text-decoration:none">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

<div class="edit-wrap">
    <div class="edit-card">
        <div class="edit-title">
            <i class="bi bi-pencil-square"></i>
            Edit — {{ $finishedProduct->name }}
        </div>

        <form action="{{ route('finished-products.update', $finishedProduct) }}" method="POST" id="productForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="product_type" value="{{ $finishedProduct->product_type }}">

            {{-- Product Type (read-only) --}}
            <div class="field-group">
                <label class="field-label">Product Type</label>
                <div class="type-badge">
                    <i class="bi bi-{{ $finishedProduct->product_type === 'manufactured' ? 'gear-wide-connected' : 'shop' }}"></i>
                    {{ ucfirst($finishedProduct->product_type) }}
                    <span style="font-size:.7rem;color:var(--text-muted);font-weight:400">
                        — {{ $finishedProduct->product_type === 'manufactured' ? 'with recipe' : 'ready-made' }}
                    </span>
                </div>
            </div>

            {{-- Stock info --}}
            <div class="stock-info">
                <i class="bi bi-box-seam"></i>
                Current Stock: <strong>{{ number_format($finishedProduct->quantity, 0) }} units</strong>
                <span style="color:var(--text-muted);font-size:.72rem">— managed through production & sales</span>
            </div>

            {{-- Product Info --}}
            <div class="section-label">Product Information</div>

            <div class="field-group">
                <label class="field-label" for="productName">
                    Product Name <span style="color:var(--s-danger-text)">*</span>
                </label>
                <input type="text" name="name" id="productName"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $finishedProduct->name) }}" required>
                <div class="invalid-feedback">@error('name'){{ $message }}@enderror</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="field-label">SKU</label>
                    <input type="text" name="sku" class="form-control"
                           style="background:var(--bg-page)"
                           value="{{ old('sku', $finishedProduct->sku) }}" readonly>
                    <div class="field-hint">Auto-generated, read-only</div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">
                        Low Stock Alert <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="minimum_stock"
                           class="form-control @error('minimum_stock') is-invalid @enderror"
                           value="{{ old('minimum_stock', $finishedProduct->minimum_stock) }}" required min="0">
                    <div class="field-hint">Alert when stock drops to or below this</div>
                    <div class="invalid-feedback">@error('minimum_stock'){{ $message }}@enderror</div>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Description</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Optional product description">{{ old('description', $finishedProduct->description) }}</textarea>
            </div>

            {{-- Recipe (manufactured only) --}}
            @if($finishedProduct->product_type === 'manufactured')
            <div class="section-label">Recipe / Ingredients</div>
            <div class="recipe-wrap">
                <div class="recipe-header">
                    <div class="recipe-header-left">
                        <i class="bi bi-list-ul"></i> Ingredients
                        <span class="recipe-cost-badge">Total: <span id="totalCostBadge">₱{{ number_format($finishedProduct->total_cost, 2) }}</span></span>
                    </div>
                    <button type="button" class="btn-add-ing" onclick="addRecipeItem()">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
                <div style="overflow-x:auto">
                    <table class="recipe-table" id="recipeTable">
                        <thead>
                            <tr>
                                <th style="width:42%">Material</th>
                                <th style="width:16%">Qty</th>
                                <th style="width:16%">Unit Price</th>
                                <th style="width:16%">Line Cost</th>
                                <th style="width:10%" class="text-center">—</th>
                            </tr>
                        </thead>
                        <tbody id="recipeItems">
                            @foreach($finishedProduct->recipes as $index => $recipe)
                            <tr id="recipeItem{{ $index + 1 }}">
                                <td>
                                    <select name="raw_materials[{{ $index + 1 }}][id]"
                                            class="form-select form-select-sm"
                                            onchange="updateCost({{ $index + 1 }})" required>
                                        <option value="">— Select —</option>
                                        @foreach($ingredients as $item)
                                        <option value="{{ $item->id }}"
                                                data-price="{{ $item->unit_price }}"
                                                data-unit="{{ $item->unit }}"
                                                data-stock="{{ $item->quantity }}"
                                                {{ $recipe->raw_material_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->quantity }} avail)
                                        </option>
                                        @endforeach
                                        @foreach($packaging as $item)
                                        <option value="{{ $item->id }}"
                                                data-price="{{ $item->unit_price }}"
                                                data-unit="{{ $item->unit }}"
                                                data-stock="{{ $item->quantity }}"
                                                {{ $recipe->raw_material_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->quantity }} avail)
                                        </option>
                                        @endforeach
                                    </select>
                                    <div id="stockWarning{{ $index + 1 }}" style="display:none;font-size:.7rem;color:var(--s-danger-text);margin-top:.2rem">
                                        <i class="bi bi-exclamation-circle"></i> <span id="stockWarningText{{ $index + 1 }}"></span>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" step="0.01"
                                           name="raw_materials[{{ $index + 1 }}][quantity]"
                                           class="form-control form-control-sm"
                                           id="quantity{{ $index + 1 }}"
                                           value="{{ $recipe->quantity_needed }}"
                                           onchange="updateCost({{ $index + 1 }})" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                           style="background:var(--bg-page)"
                                           id="unitPrice{{ $index + 1 }}"
                                           value="₱{{ number_format($recipe->rawMaterial->unit_price, 2) }}" readonly>
                                </td>
                                <td>
                                    <span id="itemCost{{ $index + 1 }}" style="font-weight:700;color:var(--s-success-text);font-size:.82rem">
                                        ₱{{ number_format($recipe->quantity_needed * $recipe->rawMaterial->unit_price, 2) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button type="button" onclick="removeRecipeItem({{ $index + 1 }})"
                                            style="background:none;border:none;color:var(--s-danger-text);cursor:pointer;font-size:.9rem;padding:.1rem .3rem">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="cost-summary" id="costSummary">
                    <div>
                        <span class="cost-label">Total Material Cost</span>
                        <span class="cost-value">₱<span id="totalCostDisplay">{{ number_format($finishedProduct->total_cost, 2) }}</span></span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Pricing --}}
            <div class="section-label">Pricing</div>

            <div class="row g-3 mb-3">
                @if($finishedProduct->product_type === 'consigned')
                <div class="col-md-6">
                    <label class="field-label">
                        Purchase Cost (₱) <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="cost_price_consigned" id="costPriceConsigned"
                           class="form-control @error('cost_price') is-invalid @enderror"
                           value="{{ old('cost_price', $finishedProduct->cost_price) }}" required min="0">
                    <div class="invalid-feedback">@error('cost_price'){{ $message }}@enderror</div>
                </div>
                @endif
                <div class="{{ $finishedProduct->product_type === 'consigned' ? 'col-md-6' : 'col-md-6' }}">
                    <label class="field-label">Cost Price (₱)</label>
                    <input type="number" step="0.01" name="cost_price" id="costPrice"
                           class="form-control"
                           style="background:var(--bg-page)"
                           value="{{ $finishedProduct->total_cost }}" readonly>
                    <div class="field-hint" id="costPriceHelp">
                        {{ $finishedProduct->product_type === 'manufactured' ? 'Auto-calculated from recipe' : 'Your purchase cost' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="field-label">
                        Selling Price (₱) <span style="color:var(--s-danger-text)">*</span>
                    </label>
                    <input type="number" step="0.01" name="selling_price" id="sellingPrice"
                           class="form-control @error('selling_price') is-invalid @enderror"
                           value="{{ old('selling_price', $finishedProduct->selling_price) }}"
                           required min="0" oninput="calculateProfit()">
                    <div class="invalid-feedback">@error('selling_price'){{ $message }}@enderror</div>
                </div>
            </div>

            {{-- Profit strip --}}
            <div class="profit-strip">
                <div>
                    <span class="profit-item-label">Profit / Unit</span>
                    <span class="profit-item-value" id="profitDisplay">₱0.00</span>
                </div>
                <div>
                    <span class="profit-item-label">Margin</span>
                    <span class="profit-item-value" id="profitMargin">0%</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="submitBtn">
                    <i class="bi bi-check-lg"></i> Update Product
                </button>
                <a href="{{ route('finished-products.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const ingredients  = @json($ingredients);
const packaging    = @json($packaging);
let recipeItemCount = {{ $finishedProduct->recipes->count() }};
const productType  = '{{ $finishedProduct->product_type }}';

function getSelectedMaterials() {
    return Array.from(document.querySelectorAll('#recipeItems select')).map(s => s.value).filter(Boolean);
}

function addRecipeItem() {
    recipeItemCount++;
    const tbody = document.getElementById('recipeItems');
    const selected = getSelectedMaterials();
    const allMaterials = [...ingredients, ...packaging];

    const optionsHtml = allMaterials.map(item => {
        const dis = selected.includes(String(item.id)) ? 'disabled' : '';
        return `<option value="${item.id}" data-price="${item.unit_price}" data-unit="${item.unit}" data-stock="${item.quantity}" ${dis}>
            ${item.name} (${item.quantity} avail)
        </option>`;
    }).join('');

    const tr = document.createElement('tr');
    tr.id = `recipeItem${recipeItemCount}`;
    tr.innerHTML = `
        <td>
            <select name="raw_materials[${recipeItemCount}][id]" class="form-select form-select-sm" onchange="updateCost(${recipeItemCount})" required>
                <option value="">— Select —</option>${optionsHtml}
            </select>
            <div id="stockWarning${recipeItemCount}" style="display:none;font-size:.7rem;color:var(--s-danger-text);margin-top:.2rem">
                <i class="bi bi-exclamation-circle"></i> <span id="stockWarningText${recipeItemCount}"></span>
            </div>
        </td>
        <td><input type="number" step="0.01" name="raw_materials[${recipeItemCount}][quantity]" class="form-control form-control-sm" id="quantity${recipeItemCount}" onchange="updateCost(${recipeItemCount})" required></td>
        <td><input type="text" class="form-control form-control-sm" style="background:var(--bg-page)" id="unitPrice${recipeItemCount}" value="₱0.00" readonly></td>
        <td><span id="itemCost${recipeItemCount}" style="font-weight:700;color:var(--s-success-text);font-size:.82rem">₱0.00</span></td>
        <td class="text-center">
            <button type="button" onclick="removeRecipeItem(${recipeItemCount})"
                    style="background:none;border:none;color:var(--s-danger-text);cursor:pointer;font-size:.9rem;padding:.1rem .3rem">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
}

function removeRecipeItem(id) {
    document.getElementById(`recipeItem${id}`)?.remove();
    refreshAllDropdowns();
    calculateTotalCost();
}

function refreshAllDropdowns() {
    const selected = getSelectedMaterials();
    document.querySelectorAll('#recipeItems select').forEach(select => {
        const cur = select.value;
        select.querySelectorAll('option').forEach(opt => {
            if (opt.value && opt.value !== cur)
                opt.disabled = selected.includes(opt.value);
        });
    });
}

function updateCost(id) {
    const row    = document.getElementById(`recipeItem${id}`);
    const sel    = row.querySelector('select');
    const opt    = sel.options[sel.selectedIndex];
    const qty    = parseFloat(document.getElementById(`quantity${id}`)?.value || 0);
    const price  = parseFloat(opt.dataset.price || 0);
    const stock  = parseFloat(opt.dataset.stock || 0);
    const unit   = opt.dataset.unit || '';

    document.getElementById(`unitPrice${id}`).value = '₱' + price.toFixed(2);
    document.getElementById(`itemCost${id}`).textContent = '₱' + (price * qty).toFixed(2);

    const warn = document.getElementById(`stockWarning${id}`);
    const wt   = document.getElementById(`stockWarningText${id}`);
    if (qty > stock && qty > 0) {
        warn.style.display = 'block';
        wt.textContent = `Need ${qty} ${unit}, only ${stock} avail`;
        document.getElementById(`quantity${id}`).classList.add('is-invalid');
    } else {
        warn.style.display = 'none';
        document.getElementById(`quantity${id}`).classList.remove('is-invalid');
    }
    refreshAllDropdowns();
    calculateTotalCost();
}

function calculateTotalCost() {
    let total = 0, hasErr = false;
    document.querySelectorAll('[id^="recipeItem"]').forEach(item => {
        const sel = item.querySelector('select');
        const qty = parseFloat(item.querySelector('input[type="number"]')?.value || 0);
        const opt = sel?.options[sel.selectedIndex];
        const price = parseFloat(opt?.dataset.price || 0);
        const stock = parseFloat(opt?.dataset.stock || 0);
        if (qty > stock && qty > 0) hasErr = true;
        total += price * qty;
    });
    document.getElementById('totalCostBadge').textContent   = '₱' + total.toFixed(2);
    document.getElementById('totalCostDisplay').textContent = total.toFixed(2);
    document.getElementById('costPrice').value = total.toFixed(2);
    const btn = document.getElementById('submitBtn');
    btn.disabled = hasErr && productType === 'manufactured';
    calculateProfit();
}

function calculateProfit() {
    const cost    = productType === 'manufactured'
        ? parseFloat(document.getElementById('costPrice').value || 0)
        : parseFloat(document.getElementById('costPriceConsigned')?.value || 0);
    const selling = parseFloat(document.getElementById('sellingPrice').value || 0);
    const profit  = selling - cost;
    const margin  = selling > 0 ? ((profit / selling) * 100).toFixed(1) : 0;
    document.getElementById('profitDisplay').textContent = (profit >= 0 ? '₱' : '-₱') + Math.abs(profit).toFixed(2);
    document.getElementById('profitDisplay').style.color = profit >= 0 ? 'var(--s-success-text)' : 'var(--s-danger-text)';
    document.getElementById('profitMargin').textContent  = margin + '%';
    document.getElementById('profitMargin').style.color  = profit >= 0 ? 'var(--s-success-text)' : 'var(--s-danger-text)';
    if (productType === 'consigned')
        document.getElementById('costPrice').value = cost.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    calculateTotalCost();
    document.getElementById('costPriceConsigned')?.addEventListener('input', calculateProfit);
});
</script>

@endsection