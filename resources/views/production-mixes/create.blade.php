@extends('layouts.sidebar')

@section('page-title', 'Create Production MIX - ' . $product->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('finished-products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Products
    </a>
</div>

<form action="{{ route('production-mixes.store') }}" method="POST" id="mixForm">
    @csrf
    <input type="hidden" name="finished_product_id" value="{{ $product->id }}">

    <div class="row">
        <!-- Left: Product Info & MIX Details -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam-fill me-2"></i>{{ $product->name }}
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted">Current Stock:</td>
                            <td class="text-end"><strong class="text-success">{{ number_format($product->stock_on_hand, 0) }} units</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Selling Price:</td>
                            <td class="text-end"><strong>₱{{ number_format($product->selling_price, 2) }}</strong></td>
                        </tr>
                    </table>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected Output (per batch) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="0.01" name="expected_output" id="expectedOutput" class="form-control" required onchange="calculateTotal()">
                            <span class="input-group-text">units</span>
                        </div>
                    </div>

                    <!-- NEW: Multiplier Field -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-x-circle me-1"></i>Multiplier (How many batches?) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" step="1" min="1" max="100" name="multiplier" id="multiplier" class="form-control" value="1" required onchange="calculateTotal()">
                            <span class="input-group-text">batches</span>
                        </div>
                        <small class="text-muted">Create multiple batches at once (1-100)</small>
                    </div>

                    <!-- NEW: Total Expected Output Display -->
                    <div class="alert alert-info border-0 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><i class="bi bi-calculator me-2"></i>Total Expected Output:</strong>
                            <h4 class="mb-0 text-primary" id="totalExpected">0 units</h4>
                        </div>
                        <small class="text-muted" id="calculation">-</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Expiration Date <span class="text-danger">*</span></label>
                        <input type="date" name="expiration_date" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Batch Number</label>
                        <input type="text" name="batch_number" class="form-control">
                        <small class="text-muted">Auto-generated if empty</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">MIX Date <span class="text-danger">*</span></label>
                        <input type="date" name="mix_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Standard Recipe (READ-ONLY) -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2"></i>Ingredients (Auto-multiplied)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Quantities shown are multiplied by your batch count.</strong> To change the recipe, edit the product settings.
                    </div>
                    
                    @if($product->recipes && $product->recipes->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-center">Per Batch</th>
                                    <th class="text-center">Total Needed</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-end">Total Cost</th>
                                </tr>
                            </thead>
                            <tbody id="ingredientsTable">
                                @php $totalCost = 0; @endphp
                                @foreach($product->recipes as $index => $recipe)
                                @php 
                                    $costPerBatch = $recipe->quantity_needed * $recipe->rawMaterial->unit_price;
                                    $totalCost += $costPerBatch;
                                @endphp
                                <tr data-per-batch="{{ $recipe->quantity_needed }}" 
                                    data-available="{{ $recipe->rawMaterial->quantity }}"
                                    data-cost-per-batch="{{ $costPerBatch }}"
                                    data-unit="{{ $recipe->rawMaterial->unit }}">
                                    <td>
                                        <strong>{{ $recipe->rawMaterial->name }}</strong>
                                        <br><small class="text-muted">₱{{ number_format($recipe->rawMaterial->unit_price, 2) }}/{{ $recipe->rawMaterial->unit }}</small>
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ number_format($recipe->quantity_needed, 2) }}</strong> {{ $recipe->rawMaterial->unit }}
                                    </td>
                                    <td class="text-center total-needed">
                                        <strong class="text-primary">{{ number_format($recipe->quantity_needed, 2) }}</strong> {{ $recipe->rawMaterial->unit }}
                                    </td>
                                    <td class="text-center available-stock">
                                        <strong class="text-success">{{ number_format($recipe->rawMaterial->quantity, 2) }}</strong> {{ $recipe->rawMaterial->unit }}
                                    </td>
                                    <td class="text-end total-cost">
                                        <strong>₱{{ number_format($costPerBatch, 2) }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Material Cost:</strong></td>
                                    <td class="text-end"><strong class="text-primary fs-5" id="grandTotal">₱{{ number_format($totalCost, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-warning border-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>No recipe defined!</strong> Please edit the product and add a recipe first.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="bi bi-check-circle me-2"></i>Create MIX & Deduct Materials
                </button>
                <a href="{{ route('finished-products.index') }}" class="btn btn-secondary btn-lg px-4">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
function calculateTotal() {
    const expected = parseFloat(document.getElementById('expectedOutput').value) || 0;
    const multiplier = parseInt(document.getElementById('multiplier').value) || 1;
    const total = expected * multiplier;
    
    document.getElementById('totalExpected').textContent = total.toFixed(0) + ' units';
    document.getElementById('calculation').textContent = `${expected} units × ${multiplier} batches`;
    
    // Update ingredient quantities
    let grandTotal = 0;
    document.querySelectorAll('#ingredientsTable tr').forEach(row => {
        const perBatch = parseFloat(row.dataset.perBatch) || 0;
        const available = parseFloat(row.dataset.available) || 0;
        const costPerBatch = parseFloat(row.dataset.costPerBatch) || 0;
        const unit = row.dataset.unit || '';
        
        const totalNeeded = perBatch * multiplier;
        const totalCost = costPerBatch * multiplier;
        grandTotal += totalCost;
        
        const isAvailable = available >= totalNeeded;
        
        // Update total needed
        const totalNeededCell = row.querySelector('.total-needed strong');
        if (totalNeededCell) {
            totalNeededCell.textContent = totalNeeded.toFixed(2);
            totalNeededCell.className = isAvailable ? 'text-primary' : 'text-danger';
        }
        
        // Update available stock color
        const availableCell = row.querySelector('.available-stock strong');
        if (availableCell) {
            availableCell.className = isAvailable ? 'text-success' : 'text-danger';
        }
        
        // Highlight row if insufficient
        row.className = isAvailable ? '' : 'table-danger';
        
        // Update total cost
        const totalCostCell = row.querySelector('.total-cost strong');
        if (totalCostCell) {
            totalCostCell.textContent = '₱' + totalCost.toFixed(2);
        }
    });
    
    document.getElementById('grandTotal').textContent = '₱' + grandTotal.toFixed(2);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>
@endsection