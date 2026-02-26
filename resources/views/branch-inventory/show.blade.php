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

<div class="row">
    <!-- Left Column: Deploy Form -->
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

        <!-- Deliver to Area -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-success text-white">
                <i class="bi bi-truck me-2"></i>Deliver to Area
            </div>
            <div class="card-body">
                <form action="{{ route('branch-inventory.transfer', $branch) }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Batch <span class="text-danger">*</span></label>
                        <select name="production_mix_id" id="batchSelect" class="form-select" required onchange="updateBatchInfo()">
                            <option value="">-- Select Batch --</option>
                            @forelse($availableBatches as $batch)
                            <option value="{{ $batch->id }}" 
                                    data-stock="{{ $batch->actual_output }}"
                                    data-product="{{ $batch->product->name }}"
                                    data-batch="{{ $batch->batch_number }}"
                                    data-expiry="{{ $batch->expiration_date ? $batch->expiration_date->format('M d, Y') : 'N/A' }}">
                                {{ $batch->product->name }} - Batch: {{ $batch->batch_number }} ({{ $batch->actual_output }} units)
                            </option>
                            @empty
                            <option value="" disabled>No batches available</option>
                            @endforelse
                        </select>
                        <small class="text-muted" id="batchInfo">Select a batch to deploy</small>
                    </div>

                    <div id="batchDetails" style="display: none;" class="alert alert-info small mb-3">
                        <div><strong>Product:</strong> <span id="detailProduct">-</span></div>
                        <div><strong>Batch #:</strong> <span id="detailBatch">-</span></div>
                        <div><strong>Available:</strong> <span id="detailStock">-</span> units</div>
                        <div><strong>Expiry:</strong> <span id="detailExpiry">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity (For Sale) <span class="text-danger">*</span></label>
                        <input type="number" 
                               step="0.01" 
                               name="quantity" 
                               id="quantityInput" 
                               class="form-control" 
                               placeholder="0" 
                               required 
                               min="0.01" 
                               oninput="validateQuantity()">
                        <small class="text-muted">Will be added to area inventory</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-gift me-1"></i>Extra/Free Qty (Optional)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               name="extra_quantity" 
                               id="extraQuantityInput" 
                               class="form-control" 
                               placeholder="0" 
                               min="0" 
                               value="0"
                               oninput="validateQuantity()">
                        <small class="text-muted text-danger">⚠️ Deducted from inventory as expense (not for sale)</small>
                    </div>

                    <div class="alert alert-warning border-0 small" id="extraWarning" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Total to deduct:</strong> <span id="totalDeduct">0</span> units (Regular + Extra)
                    </div>

                    <div id="quantityWarning" class="text-danger small mb-3" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill"></i> <span id="warningText">Insufficient stock in batch!</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">DR Number (Delivery Receipt) <span class="text-danger">*</span></label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g., DR-2024-001" required>
                        <small class="text-muted">Required delivery receipt number</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Delivery notes..."></textarea>
                    </div>

                    <button type="submit" id="deployBtn" class="btn btn-success w-100" {{ $availableBatches->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-truck me-2"></i>Deliver to Area
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Inventory & History -->
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
                            @foreach($branch->inventory as $item)
                                @if($item->quantity > 0)
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

                                        <!-- Return BO Modal -->
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
                                                                <strong>Available at {{ $branch->name }}:</strong> {{ $item->quantity }} units
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Quantity to Return <span class="text-danger">*</span></label>
                                                                <input type="number" 
                                                                       step="0.01" 
                                                                       name="quantity" 
                                                                       class="form-control" 
                                                                       max="{{ $item->quantity }}" 
                                                                       placeholder="Enter quantity"
                                                                       required>
                                                                <small class="text-muted">Max: {{ $item->quantity }} units</small>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
                                                                <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Reason for Return</label>
                                                                <textarea name="notes" class="form-control" rows="3" placeholder="Why are you returning this stock? (e.g., Unsold, Damaged, Expired, etc.)"></textarea>
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
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox display-1 opacity-25 d-block mb-3"></i>
                    <h5>No Stock at This Area</h5>
                    <p class="mb-0">Deploy batches from the warehouse using the form on the left</p>
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
                                <th>Product</th>
                                <th>Batch #</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th>Notes</th>
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
                                <td>
                                    <div class="fw-semibold">{{ $movement->finishedProduct->name }}</div>
                                    @if($movement->expiration_date)
                                    <small class="text-muted">Exp: {{ $movement->expiration_date->format('M d, Y') }}</small>
                                    @endif
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
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-down-circle"></i> Delivered
                                        </span>
                                    @elseif($movement->movement_type === 'extra_free')
                                        <span class="badge bg-info">
                                            <i class="bi bi-gift"></i> Extra/Free
                                        </span>
                                    @elseif($movement->movement_type === 'return_bo')
                                        <span class="badge bg-warning">
                                            <i class="bi bi-arrow-counterclockwise"></i> Return BO
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ $movement->movement_type }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($movement->movement_type === 'transfer_out')
                                        <strong class="text-success">+{{ number_format($movement->quantity, 2) }}</strong>
                                    @elseif($movement->movement_type === 'extra_free')
                                        <strong class="text-info">-{{ number_format($movement->quantity, 2) }}</strong>
                                        <small class="d-block text-muted">(Expense)</small>
                                    @elseif($movement->movement_type === 'return_bo')
                                        <strong class="text-warning">-{{ number_format($movement->quantity, 2) }}</strong>
                                    @else
                                        <strong>{{ number_format($movement->quantity, 2) }}</strong>
                                    @endif
                                </td>
                                <td>
                                    @if($movement->notes)
                                        <small>{{ $movement->notes }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
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

<script>
function updateBatchInfo() {
    const select = document.getElementById('batchSelect');
    const selectedOption = select.options[select.selectedIndex];
    const stock = selectedOption.dataset.stock || 0;
    const product = selectedOption.dataset.product || '-';
    const batch = selectedOption.dataset.batch || '-';
    const expiry = selectedOption.dataset.expiry || '-';
    
    const batchInfo = document.getElementById('batchInfo');
    const batchDetails = document.getElementById('batchDetails');
    
    if (select.value) {
        batchInfo.textContent = `${stock} units available in this batch`;
        batchInfo.classList.add('text-success');
        
        document.getElementById('detailProduct').textContent = product;
        document.getElementById('detailBatch').textContent = batch;
        document.getElementById('detailStock').textContent = stock;
        document.getElementById('detailExpiry').textContent = expiry;
        
        batchDetails.style.display = 'block';
    } else {
        batchInfo.textContent = 'Select a batch to deploy';
        batchInfo.classList.remove('text-success');
        batchDetails.style.display = 'none';
    }
    
    validateQuantity();
}

function validateQuantity() {
    const select = document.getElementById('batchSelect');
    const selectedOption = select.options[select.selectedIndex];
    const availableStock = parseFloat(selectedOption.dataset.stock || 0);
    const quantity = parseFloat(document.getElementById('quantityInput').value || 0);
    const extra = parseFloat(document.getElementById('extraQuantityInput').value || 0);
    const total = quantity + extra;
    
    const warning = document.getElementById('quantityWarning');
    const warningText = document.getElementById('warningText');
    const deployBtn = document.getElementById('deployBtn');
    const extraWarning = document.getElementById('extraWarning');
    const totalDeduct = document.getElementById('totalDeduct');
    
    // Show extra warning if there's extra quantity
    if (extra > 0) {
        extraWarning.style.display = 'block';
        totalDeduct.textContent = total.toFixed(2);
    } else {
        extraWarning.style.display = 'none';
    }
    
    // Check total against available stock
    if (total > availableStock && total > 0) {
        warning.style.display = 'block';
        warningText.textContent = `Only ${availableStock} units available in this batch! (You're trying to send ${total} total)`;
        deployBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        deployBtn.disabled = false;
    }
}
</script>
@endsection