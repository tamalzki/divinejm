@extends('layouts.sidebar')

@section('page-title', 'Edit Finished Product')

@section('content')
<div class="mb-4">
    <a href="{{ route('finished-products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ route('finished-products.update', $finishedProduct) }}" method="POST" id="productForm">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- Left Column: Product Details -->
        <div class="col-lg-5">
            <!-- Product Type (Read-only) -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Product Type</h6>
                    <div class="alert {{ $finishedProduct->product_type === 'manufactured' ? 'alert-success' : 'alert-primary' }} mb-0">
                        <i class="bi bi-{{ $finishedProduct->product_type === 'manufactured' ? 'gear-fill' : 'box-seam' }} me-2"></i>
                        <strong>{{ ucfirst($finishedProduct->product_type) }}</strong>
                        @if($finishedProduct->product_type === 'manufactured')
                            (With Recipe)
                        @else
                            (Ready-Made)
                        @endif
                    </div>
                    <input type="hidden" name="product_type" value="{{ $finishedProduct->product_type }}">
                </div>
            </div>

            <!-- Basic Information -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Product Details</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $finishedProduct->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control bg-light" value="{{ old('sku', $finishedProduct->sku) }}" readonly>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Current Stock:</strong> {{ $finishedProduct->quantity }} units
                        <br>
                        <small>Quantities are managed through production/sales. Use "Restock" to add inventory.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Min Stock Alert Level <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="minimum_stock" class="form-control" value="{{ old('minimum_stock', $finishedProduct->minimum_stock) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $finishedProduct->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing Summary -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Pricing</h6>
                    
                    @if($finishedProduct->product_type === 'consigned')
                    <div class="mb-3">
                        <label class="form-label">Purchase Cost <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" name="cost_price_consigned" id="costPriceConsigned" class="form-control" value="{{ old('cost_price', $finishedProduct->cost_price) }}" required>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Cost Price</label>
                        <div class="input-group">
                            <span class="input-group-text bg-secondary text-white">₱</span>
                            <input type="number" step="0.01" name="cost_price" id="costPrice" class="form-control bg-light" value="{{ $finishedProduct->total_cost }}" readonly>
                        </div>
                        <small class="text-muted" id="costPriceHelp">
                            @if($finishedProduct->product_type === 'manufactured')
                                From recipe calculation
                            @else
                                Your purchase cost
                            @endif
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">₱</span>
                            <input type="number" step="0.01" name="selling_price" id="sellingPrice" class="form-control" value="{{ old('selling_price', $finishedProduct->selling_price) }}" required onchange="calculateProfit()">
                        </div>
                    </div>

                    <div class="alert alert-success mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="d-block text-muted">Profit per Unit</small>
                                <h4 class="mb-0" id="profitDisplay">₱0.00</h4>
                            </div>
                            <div class="text-end">
                                <small class="d-block text-muted">Margin</small>
                                <h4 class="mb-0" id="profitMargin">0%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Recipe Builder (Only for Manufactured) -->
        <div class="col-lg-7">
            @if($finishedProduct->product_type === 'manufactured')
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check me-2"></i>Product Recipe</span>
                    <span class="badge bg-white text-success">Total: <span id="totalCostBadge">₱{{ number_format($finishedProduct->total_cost, 2) }}</span></span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="recipeTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">Material</th>
                                    <th width="20%">Qty</th>
                                    <th width="20%">Price</th>
                                    <th width="15%" class="text-end">Cost</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="recipeItems">
                                @foreach($finishedProduct->recipes as $index => $recipe)
                                <tr id="recipeItem{{ $index + 1 }}">
                                    <td>
                                        <select name="raw_materials[{{ $index + 1 }}][id]" class="form-select form-select-sm" onchange="updateCost({{ $index + 1 }})" required>
                                            <option value="">-- Select Material --</option>
                                            <optgroup label="🌾 Ingredients">
                                                @foreach($ingredients as $item)
                                                <option value="{{ $item->id }}" 
                                                        data-price="{{ $item->unit_price }}" 
                                                        data-unit="{{ $item->unit }}"
                                                        data-stock="{{ $item->quantity }}"
                                                        data-name="{{ $item->name }}"
                                                        {{ $recipe->raw_material_id == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }} - ₱{{ number_format($item->unit_price, 2) }}/{{ $item->unit }} ({{ $item->quantity }} {{ $item->unit }} avail)
                                                </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="📦 Packaging">
                                                @foreach($packaging as $item)
                                                <option value="{{ $item->id }}" 
                                                        data-price="{{ $item->unit_price }}" 
                                                        data-unit="{{ $item->unit }}"
                                                        data-stock="{{ $item->quantity }}"
                                                        data-name="{{ $item->name }}"
                                                        {{ $recipe->raw_material_id == $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }} - ₱{{ number_format($item->unit_price, 2) }}/{{ $item->unit }} ({{ $item->quantity }} {{ $item->unit }} avail)
                                                </option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <div id="stockWarning{{ $index + 1 }}" class="text-danger small mt-1" style="display: none;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> <span id="stockWarningText{{ $index + 1 }}"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               step="0.01" 
                                               name="raw_materials[{{ $index + 1 }}][quantity]" 
                                               class="form-control form-control-sm" 
                                               placeholder="0" 
                                               value="{{ $recipe->quantity_needed }}"
                                               onchange="updateCost({{ $index + 1 }})"
                                               id="quantity{{ $index + 1 }}"
                                               required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm bg-light" id="unitPrice{{ $index + 1 }}" value="₱{{ number_format($recipe->rawMaterial->unit_price, 2) }}" readonly>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success" id="itemCost{{ $index + 1 }}">₱{{ number_format($recipe->quantity_needed * $recipe->rawMaterial->unit_price, 2) }}</strong>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeRecipeItem({{ $index + 1 }})" title="Remove">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-sm btn-success" onclick="addRecipeItem()">
                        <i class="bi bi-plus-circle me-1"></i>Add Material
                    </button>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <small><i class="bi bi-info-circle me-2"></i>Modify recipe to recalculate costs. Stock will be validated.</small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-save me-2"></i>Update Product
        </button>
        <a href="{{ route('finished-products.index') }}" class="btn btn-outline-secondary btn-lg px-5">Cancel</a>
    </div>
</form>

<script>
const ingredients = @json($ingredients);
const packaging = @json($packaging);
let recipeItemCount = {{ $finishedProduct->recipes->count() }};
const productType = '{{ $finishedProduct->product_type }}';

function getSelectedMaterials() {
    const selected = [];
    const selects = document.querySelectorAll('#recipeItems select');
    selects.forEach(select => {
        if (select.value) {
            selected.push(select.value);
        }
    });
    return selected;
}

function addRecipeItem() {
    recipeItemCount++;
    const tbody = document.getElementById('recipeItems');
    const selectedMaterials = getSelectedMaterials();
    
    const row = document.createElement('tr');
    row.id = `recipeItem${recipeItemCount}`;
    row.innerHTML = `
        <td>
            <select name="raw_materials[${recipeItemCount}][id]" class="form-select form-select-sm" onchange="updateCost(${recipeItemCount})" required>
                <option value="">-- Select Material --</option>
                <optgroup label="🌾 Ingredients">
                    ${ingredients.map(item => {
                        const disabled = selectedMaterials.includes(item.id.toString()) ? 'disabled' : '';
                        return `
                            <option value="${item.id}" 
                                    data-price="${item.unit_price}" 
                                    data-unit="${item.unit}"
                                    data-stock="${item.quantity}"
                                    data-name="${item.name}"
                                    ${disabled}>
                                ${item.name} - ₱${parseFloat(item.unit_price).toFixed(2)}/${item.unit} (${item.quantity} ${item.unit} avail)
                            </option>
                        `;
                    }).join('')}
                </optgroup>
                <optgroup label="📦 Packaging">
                    ${packaging.map(item => {
                        const disabled = selectedMaterials.includes(item.id.toString()) ? 'disabled' : '';
                        return `
                            <option value="${item.id}" 
                                    data-price="${item.unit_price}" 
                                    data-unit="${item.unit}"
                                    data-stock="${item.quantity}"
                                    data-name="${item.name}"
                                    ${disabled}>
                                ${item.name} - ₱${parseFloat(item.unit_price).toFixed(2)}/${item.unit} (${item.quantity} ${item.unit} avail)
                            </option>
                        `;
                    }).join('')}
                </optgroup>
            </select>
            <div id="stockWarning${recipeItemCount}" class="text-danger small mt-1" style="display: none;">
                <i class="bi bi-exclamation-triangle-fill"></i> <span id="stockWarningText${recipeItemCount}"></span>
            </div>
        </td>
        <td>
            <input type="number" 
                   step="0.01" 
                   name="raw_materials[${recipeItemCount}][quantity]" 
                   class="form-control form-control-sm" 
                   placeholder="0" 
                   onchange="updateCost(${recipeItemCount})"
                   id="quantity${recipeItemCount}"
                   required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm bg-light" id="unitPrice${recipeItemCount}" value="₱0.00" readonly>
        </td>
        <td class="text-end">
            <strong class="text-success" id="itemCost${recipeItemCount}">₱0.00</strong>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRecipeItem(${recipeItemCount})" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

function removeRecipeItem(id) {
    document.getElementById(`recipeItem${id}`).remove();
    refreshAllDropdowns();
    calculateTotalCost();
}

function refreshAllDropdowns() {
    const selectedMaterials = getSelectedMaterials();
    const selects = document.querySelectorAll('#recipeItems select');
    
    selects.forEach(select => {
        const currentValue = select.value;
        const options = select.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value && option.value !== currentValue) {
                if (selectedMaterials.includes(option.value)) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
        });
    });
}

function updateCost(id) {
    const row = document.getElementById(`recipeItem${id}`);
    const select = row.querySelector('select');
    const quantityInput = document.getElementById(`quantity${id}`);
    const unitPriceDisplay = document.getElementById(`unitPrice${id}`);
    const costDisplay = document.getElementById(`itemCost${id}`);
    const stockWarning = document.getElementById(`stockWarning${id}`);
    const stockWarningText = document.getElementById(`stockWarningText${id}`);
    
    const selectedOption = select.options[select.selectedIndex];
    const price = parseFloat(selectedOption.dataset.price || 0);
    const quantity = parseFloat(quantityInput.value || 0);
    const unit = selectedOption.dataset.unit || '';
    const availableStock = parseFloat(selectedOption.dataset.stock || 0);
    
    unitPriceDisplay.value = '₱' + price.toFixed(2);
    
    const cost = price * quantity;
    costDisplay.textContent = '₱' + cost.toFixed(2);
    
    if (quantity > availableStock && quantity > 0) {
        stockWarning.style.display = 'block';
        stockWarningText.textContent = `Need ${quantity} ${unit}, only ${availableStock} ${unit} available`;
        quantityInput.classList.add('is-invalid');
    } else {
        stockWarning.style.display = 'none';
        quantityInput.classList.remove('is-invalid');
    }
    
    refreshAllDropdowns();
    calculateTotalCost();
}

function calculateTotalCost() {
    let total = 0;
    let hasError = false;
    const items = document.querySelectorAll('[id^="recipeItem"]');
    
    items.forEach(item => {
        const select = item.querySelector('select');
        const quantityInput = item.querySelector('input[type="number"]');
        
        if (select && quantityInput) {
            const selectedOption = select.options[select.selectedIndex];
            const price = parseFloat(selectedOption.dataset.price || 0);
            const quantity = parseFloat(quantityInput.value || 0);
            const availableStock = parseFloat(selectedOption.dataset.stock || 0);
            
            if (quantity > availableStock && quantity > 0) {
                hasError = true;
            }
            
            total += price * quantity;
        }
    });
    
    document.getElementById('totalCostBadge').textContent = '₱' + total.toFixed(2);
    document.getElementById('costPrice').value = total.toFixed(2);
    
    const submitBtn = document.querySelector('button[type="submit"]');
    if (hasError && productType === 'manufactured') {
        submitBtn.disabled = true;
        submitBtn.classList.add('btn-secondary');
        submitBtn.classList.remove('btn-primary');
    } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn-secondary');
        submitBtn.classList.add('btn-primary');
    }
    
    calculateProfit();
}

function calculateProfit() {
    let costPrice = 0;
    
    if (productType === 'manufactured') {
        costPrice = parseFloat(document.getElementById('costPrice').value || 0);
    } else {
        costPrice = parseFloat(document.getElementById('costPriceConsigned').value || 0);
        document.getElementById('costPrice').value = costPrice.toFixed(2);
    }
    
    const sellingPrice = parseFloat(document.getElementById('sellingPrice').value || 0);
    const profit = sellingPrice - costPrice;
    const margin = costPrice > 0 ? ((profit / costPrice) * 100) : 0;
    
    document.getElementById('profitDisplay').textContent = '₱' + profit.toFixed(2);
    document.getElementById('profitMargin').textContent = margin.toFixed(1) + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    calculateTotalCost();
    
    @if($finishedProduct->product_type === 'consigned')
    document.getElementById('costPriceConsigned')?.addEventListener('input', calculateProfit);
    @endif
});
</script>

<style>
#recipeTable tbody tr {
    vertical-align: middle;
}

#recipeTable tbody td {
    vertical-align: middle;
}
</style>
@endsection