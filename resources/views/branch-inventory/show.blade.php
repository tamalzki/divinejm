@extends('layouts.sidebar')

@section('page-title', 'Area Inventory - ' . $branch->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('branch-inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to All Areas
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Deploy Multiple Products Modal -->
<div class="modal fade" id="deployModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Deploy to {{ $branch->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('branch-inventory.transfer', $branch) }}" method="POST" id="deployForm">
                @csrf
                <div class="modal-body">
                    <!-- DR Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                            <select name="customer_name" id="customerSelect" class="form-select" required>
                                <option value="">-- Select Customer --</option>
                                @foreach($branch->customers_list ?? [] as $customer)
                                    <option value="{{ $customer }}">{{ $customer }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">DR Number <span class="text-danger">*</span></label>
                            <input type="text" name="dr_number" class="form-control" placeholder="e.g., 2740" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">
                                <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip" title="Optional: Total value of this delivery for record keeping"></i>
                                Total Value
                            </label>
                            <input type="number" name="total_amount" step="0.01" class="form-control" placeholder="Optional">
                            <small class="text-muted">For records only</small>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Products to Deploy</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()">
                                    <i class="bi bi-plus-circle me-1"></i>Add Product
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Product / Batch</th>
                                            <th style="width: 12%;" class="text-center">Available</th>
                                            <th style="width: 12%;" class="text-center">Quantity</th>
                                            <th style="width: 12%;" class="text-center">Extra/Free</th>
                                            <th style="width: 12%;">Unit Price</th>
                                            <th style="width: 12%;" class="text-end">Amount</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody">
                                        <!-- Products will be added here -->
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>CALCULATED TOTAL:</strong></td>
                                            <td class="text-end"><strong id="grandTotal">₱0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold">Delivery Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes about this delivery..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-truck me-2"></i>Deploy to {{ $branch->name }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Info & Quick Actions -->
    <div class="col-md-4 mb-4">
        <!-- Area Info Card -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-geo-alt me-2"></i>{{ $branch->name }}
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Total Batches:</small>
                    <h4 class="text-success mb-0">{{ $branch->inventory->where('quantity', '>', 0)->count() }}</h4>
                </div>
                @if($branch->address)
                <hr>
                <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>{{ $branch->address }}</small>
                @endif
            </div>
        </div>

        <!-- Quick Deploy Button -->
        <div class="card shadow-sm border-success">
            <div class="card-header bg-success text-white text-center">
                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Quick Deploy</h5>
            </div>
            <div class="card-body text-center p-4">
                <i class="bi bi-truck display-4 text-success mb-3 d-block"></i>
                <button type="button" class="btn btn-success btn-lg w-100 mb-3" 
                        style="font-size: 1.25rem; padding: 15px;"
                        data-bs-toggle="modal" 
                        data-bs-target="#deployModal" 
                        {{ $availableBatches->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-truck me-2"></i>Deploy Products to Area
                </button>
                <p class="text-muted mb-0">
                    <small>Deploy multiple products at once using a single DR</small>
                </p>
                @if($availableBatches->isEmpty())
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle"></i> No batches available to deploy
                    </div>
                @else
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>{{ $availableBatches->count() }}</strong> batches ready to deploy
                    </div>
                @endif
            </div>
        </div>

        <!-- Available Batches Info -->
        @if($availableBatches->count() > 0)
        <div class="card mt-3 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Available for Deployment</h6>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @foreach($availableBatches->groupBy('product_id') as $productId => $batches)
                    <div class="mb-3">
                        <div class="fw-bold">{{ $batches->first()->product->name }}</div>
                        @foreach($batches as $batch)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                                <small>
                                    <span class="badge bg-secondary">{{ $batch->batch_number }}</span>
                                </small>
                                <small class="text-success"><strong>{{ $batch->actual_output }} units</strong></small>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Right Column: Current Inventory & History -->
    <div class="col-md-8">
        <!-- Current Area Inventory -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Current Stock at Area</h5>
                <span class="badge bg-primary">{{ $branch->inventory->where('quantity', '>', 0)->count() }} Batches</span>
            </div>
            <div class="card-body">
                @if($branch->inventory->where('quantity', '>', 0)->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Batch #</th>
                                <th>Expiry</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branch->inventory->where('quantity', '>', 0) as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->finishedProduct->name }}</div>
                                    <small class="text-muted">SKU: {{ $item->finishedProduct->sku }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $item->batch_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($item->expiration_date)
                                        <small>{{ $item->expiration_date->format('M d, Y') }}</small>
                                    @else
                                        <small class="text-muted">N/A</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">{{ $item->quantity }} units</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#returnModal{{ $item->id }}">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Return BO
                                    </button>

                                    <!-- Return BO Modal (keep existing modal code) -->
                                    <div class="modal fade" id="returnModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">
                                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Return BO (Bad Orders)
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('branch-inventory.return', $branch) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="branch_inventory_id" value="{{ $item->id }}">
                                                    
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <strong>Product:</strong> {{ $item->finishedProduct->name }}<br>
                                                            <strong>Batch #:</strong> {{ $item->batch_number ?? 'N/A' }}<br>
                                                            @if($item->expiration_date)
                                                            <strong>Expiry:</strong> {{ $item->expiration_date->format('M d, Y') }}<br>
                                                            @endif
                                                            <strong>Available:</strong> {{ $item->quantity }} units
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Quantity to Return <span class="text-danger">*</span></label>
                                                            <input type="number" 
                                                                   step="0.01" 
                                                                   name="quantity" 
                                                                   class="form-control" 
                                                                   max="{{ $item->quantity }}" 
                                                                   required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
                                                            <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Reason for Return</label>
                                                            <textarea name="notes" class="form-control" rows="3" placeholder="Why are you returning this stock?"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="bi bi-arrow-counterclockwise me-2"></i>Return to Warehouse
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-1 opacity-25 d-block mb-3"></i>
                    <h5>No Stock at This Area</h5>
                    <p class="mb-0">Deploy products using the button on the left</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Movement History -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Movement History</h5>
            </div>
            <div class="card-body">
                @if($stockMovements->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>DR#</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Batch #</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockMovements as $movement)
                            <tr>
                                <td>
                                    @if($movement->reference_number)
                                        <strong class="text-primary">{{ $movement->reference_number }}</strong>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td><small>{{ $movement->movement_date->format('M d, Y') }}</small></td>
                                <td><small>{{ $movement->customer_name ?? '-' }}</small></td>
                                <td>
                                    <div class="fw-semibold">{{ $movement->finishedProduct->name }}</div>
                                </td>
                                <td>
                                    @if($movement->batch_number)
                                        <span class="badge bg-secondary">{{ $movement->batch_number }}</span>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    @if($movement->movement_type === 'transfer_out')
                                        <span class="badge bg-success">Delivered</span>
                                    @elseif($movement->movement_type === 'extra_free')
                                        <span class="badge bg-info">Extra/Free</span>
                                    @elseif($movement->movement_type === 'return_bo')
                                        <span class="badge bg-warning">Return BO</span>
                                    @elseif($movement->movement_type === 'sale')
                                        <span class="badge bg-primary">Sale</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $movement->movement_type }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong class="{{ $movement->movement_type === 'transfer_out' ? 'text-success' : ($movement->movement_type === 'sale' || $movement->movement_type === 'return_bo' ? 'text-warning' : 'text-info') }}">
                                        {{ $movement->movement_type === 'transfer_out' ? '+' : '-' }}{{ number_format($movement->quantity, 2) }}
                                    </strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $stockMovements->links() }}
                </div>
                @else
                <div class="text-center text-muted py-3">
                    <p class="mb-0">No movement history yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Fix Select2 dropdown styling */
.select2-container--bootstrap-5 .select2-selection,
.select2-container .select2-selection--single {
    height: 38px !important;
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
    padding: 0.375rem 0.75rem !important;
    background-color: #fff !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important;
    padding-left: 0 !important;
    color: #212529 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 5px !important;
}

.select2-container--bootstrap-5 .select2-selection--single:focus,
.select2-container--bootstrap-5 .select2-selection--single:focus-within {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}

/* Dropdown menu */
.select2-container--bootstrap-5 .select2-dropdown {
    border: 1px solid #dee2e6 !important;
    border-radius: 0.375rem !important;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #0d6efd !important;
    color: white !important;
}
</style>

<script>
let productIndex = 0;
const availableBatches = @json($availableBatches);
let selectedBatches = []; // Track selected batches

$(document).ready(function() {
    // Initialize Select2 for customer when modal opens
    $('#deployModal').on('shown.bs.modal', function() {
        if (!$('#customerSelect').hasClass('select2-hidden-accessible')) {
            $('#customerSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#deployModal'),
                placeholder: '-- Select Customer --',
                width: '100%'
            });
        }
    });
    
    // Reset form when modal closes
    $('#deployModal').on('hidden.bs.modal', function() {
        $('#deployForm')[0].reset();
        $('#productsTableBody').empty();
        selectedBatches = [];
        productIndex = 0;
        addProductRow();
    });
    
    // Add first row automatically
    addProductRow();
});

function addProductRow() {
    const tbody = document.getElementById('productsTableBody');
    const row = document.createElement('tr');
    row.id = `productRow${productIndex}`;
    
    // Generate options excluding already selected batches
    const options = availableBatches
        .filter(batch => !selectedBatches.includes(batch.id))
        .map(batch => `
            <option value="${batch.id}" 
                    data-stock="${batch.actual_output}"
                    data-product="${batch.product.name}"
                    data-batch="${batch.batch_number}"
                    data-price="${batch.product.selling_price || 0}">
                ${batch.product.name} - Batch: ${batch.batch_number} (${batch.actual_output} avail)
            </option>
        `).join('');
    
    row.innerHTML = `
        <td>
            <select name="items[${productIndex}][production_mix_id]" class="form-select form-select-sm batch-select" data-index="${productIndex}" required onchange="updateBatchInfo(${productIndex})">
                <option value="">-- Select Product/Batch --</option>
                ${options}
            </select>
            <small class="text-muted available-stock" id="availStock${productIndex}"></small>
        </td>
        <td class="text-center">
            <span class="badge bg-info" id="availBadge${productIndex}">-</span>
        </td>
        <td>
            <input type="number" name="items[${productIndex}][quantity]" class="form-control form-control-sm qty-forsale" data-index="${productIndex}" step="0.01" min="0" placeholder="0" required oninput="calculateRow(${productIndex})">
        </td>
        <td>
            <input type="number" name="items[${productIndex}][extra_quantity]" class="form-control form-control-sm qty-extra" data-index="${productIndex}" step="0.01" min="0" value="0" placeholder="0" oninput="calculateRow(${productIndex})">
        </td>
        <td>
            <input type="number" name="items[${productIndex}][unit_price]" class="form-control form-control-sm unit-price" data-index="${productIndex}" step="0.01" min="0" placeholder="0.00" oninput="calculateRow(${productIndex})">
        </td>
        <td class="text-end">
            <strong class="row-total" id="rowTotal${productIndex}">₱0.00</strong>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeProductRow(${productIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    productIndex++;
}

function updateBatchInfo(index) {
    const select = document.querySelector(`select[data-index="${index}"]`);
    const option = select.options[select.selectedIndex];
    const previousValue = select.dataset.previousValue;
    
    // Remove previously selected batch from tracking
    if (previousValue) {
        const prevIndex = selectedBatches.indexOf(parseInt(previousValue));
        if (prevIndex > -1) {
            selectedBatches.splice(prevIndex, 1);
        }
    }
    
    if (option.value) {
        const batchId = parseInt(option.value);
        const stock = option.dataset.stock;
        const price = option.dataset.price;
        
        // Add to selected batches
        selectedBatches.push(batchId);
        select.dataset.previousValue = option.value;
        
        document.getElementById(`availStock${index}`).textContent = `${stock} units available`;
        document.getElementById(`availBadge${index}`).textContent = stock;
        
        // Auto-fill price
        document.querySelector(`.unit-price[data-index="${index}"]`).value = price;
        
        // Update all other dropdowns to exclude this batch
        updateAllDropdowns();
        
        calculateRow(index);
    } else {
        document.getElementById(`availStock${index}`).textContent = '';
        document.getElementById(`availBadge${index}`).textContent = '-';
        select.dataset.previousValue = '';
    }
}

function updateAllDropdowns() {
    // Update each dropdown to hide selected batches
    document.querySelectorAll('.batch-select').forEach(selectElement => {
        const currentValue = selectElement.value;
        const index = selectElement.dataset.index;
        
        // Clear and rebuild options
        const currentOptions = Array.from(selectElement.options).map(opt => ({
            value: opt.value,
            text: opt.text,
            selected: opt.selected,
            dataset: {
                stock: opt.dataset.stock,
                product: opt.dataset.product,
                batch: opt.dataset.batch,
                price: opt.dataset.price
            }
        }));
        
        selectElement.innerHTML = '<option value="">-- Select Product/Batch --</option>';
        
        availableBatches.forEach(batch => {
            const batchId = batch.id;
            const isCurrentSelection = parseInt(currentValue) === batchId;
            const isAlreadySelected = selectedBatches.includes(batchId) && !isCurrentSelection;
            
            if (!isAlreadySelected) {
                const option = document.createElement('option');
                option.value = batchId;
                option.dataset.stock = batch.actual_output;
                option.dataset.product = batch.product.name;
                option.dataset.batch = batch.batch_number;
                option.dataset.price = batch.product.selling_price || 0;
                option.textContent = `${batch.product.name} - Batch: ${batch.batch_number} (${batch.actual_output} avail)`;
                
                if (isCurrentSelection) {
                    option.selected = true;
                }
                
                selectElement.appendChild(option);
            }
        });
    });
}

function calculateRow(index) {
    const select = document.querySelector(`select[data-index="${index}"]`);
    const option = select.options[select.selectedIndex];
    const availableStock = parseFloat(option.dataset.stock || 0);
    
    const qtySale = parseFloat(document.querySelector(`.qty-forsale[data-index="${index}"]`).value || 0);
    const qtyExtra = parseFloat(document.querySelector(`.qty-extra[data-index="${index}"]`).value || 0);
    const unitPrice = parseFloat(document.querySelector(`.unit-price[data-index="${index}"]`).value || 0);
    
    const total = qtySale + qtyExtra;
    const amount = qtySale * unitPrice;
    
    // Check if exceeds available
    if (total > availableStock) {
        document.getElementById(`availStock${index}`).innerHTML = `<span class="text-danger">⚠️ Exceeds available! (${availableStock} max)</span>`;
    } else {
        document.getElementById(`availStock${index}`).innerHTML = `<span class="text-success">${availableStock} units available</span>`;
    }
    
    document.getElementById(`rowTotal${index}`).textContent = '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2});
    
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.unit-price').forEach((input, i) => {
        const qty = parseFloat(document.querySelectorAll('.qty-forsale')[i]?.value || 0);
        const price = parseFloat(input.value || 0);
        grand += qty * price;
    });
    
    document.getElementById('grandTotal').textContent = '₱' + grand.toLocaleString('en-PH', {minimumFractionDigits: 2});
}

function removeProductRow(index) {
    const row = document.getElementById(`productRow${index}`);
    if (row) {
        // Get the selected batch ID before removing
        const select = row.querySelector('.batch-select');
        const selectedBatchId = parseInt(select.value);
        
        // Remove from selectedBatches array
        if (selectedBatchId) {
            const batchIndex = selectedBatches.indexOf(selectedBatchId);
            if (batchIndex > -1) {
                selectedBatches.splice(batchIndex, 1);
            }
        }
        
        // Remove the row
        row.remove();
        
        // Update all remaining dropdowns
        updateAllDropdowns();
        
        // Recalculate grand total
        calculateGrandTotal();
    }
}
</script>
@endsection