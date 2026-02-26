@extends('layouts.sidebar')

@section('page-title', 'Manage Raw Material')

@section('content')
<div class="mb-4">
    <a href="{{ route('raw-materials.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
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
    <!-- Material Info Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-box-seam me-2"></i>{{ $rawMaterial->name }}
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Available Stock:</td>
                        <td class="text-end">
                            <strong class="{{ $rawMaterial->isLowStock() ? 'text-danger' : 'text-success' }}">
                                {{ $rawMaterial->quantity }} {{ $rawMaterial->unit }}
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Minimum Stock:</td>
                        <td class="text-end"><strong>{{ $rawMaterial->minimum_stock }} {{ $rawMaterial->unit }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Unit Price:</td>
                        <td class="text-end"><strong>₱{{ number_format($rawMaterial->unit_price, 2) }}</strong></td>
                    </tr>
                </table>
                
                @if($rawMaterial->quantity == 0)
                <div class="alert alert-danger mb-0 mt-3">
                    <i class="bi bi-x-circle me-2"></i>
                    <small><strong>Out of Stock!</strong></small>
                </div>
                @elseif($rawMaterial->isLowStock())
                <div class="alert alert-warning mb-0 mt-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <small>Low stock alert!</small>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs for Use/Restock -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="restock-tab" data-bs-toggle="tab" data-bs-target="#restock" type="button" role="tab">
                            <i class="bi bi-plus-circle me-2"></i>Restock / Add Stock
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- USE MATERIAL TAB -->
                    

                    <!-- RESTOCK TAB -->
                    <div class="tab-pane fade" id="restock" role="tabpanel">
                        <form action="{{ route('raw-materials.restock', $rawMaterial) }}" method="POST" id="restockForm">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Quantity to Add <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" 
                                               step="0.01" 
                                               name="quantity_added" 
                                               class="form-control @error('quantity_added') is-invalid @enderror" 
                                               value="{{ old('quantity_added') }}" 
                                               placeholder="0.00" 
                                               required>
                                        <span class="input-group-text">{{ $rawMaterial->unit }}</span>
                                    </div>
                                    @error('quantity_added')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Restock Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           name="restock_date" 
                                           class="form-control @error('restock_date') is-invalid @enderror" 
                                           value="{{ old('restock_date', date('Y-m-d')) }}" 
                                           required>
                                    @error('restock_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Supplier</label>
                                    <input type="text" 
                                           name="supplier" 
                                           class="form-control" 
                                           value="{{ old('supplier') }}" 
                                           placeholder="Supplier name">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total Cost (₱)</label>
                                    <input type="number" 
                                           step="0.01" 
                                           name="cost" 
                                           id="totalCost"
                                           class="form-control" 
                                           value="{{ old('cost') }}" 
                                           placeholder="0.00">
                                    <small class="text-muted">Optional - will update unit price</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Notes</label>
                                <textarea name="notes" 
                                          class="form-control" 
                                          rows="2" 
                                          placeholder="Purchase details, invoice number, etc...">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i>Add to Stock
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>Transaction History
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Purpose/Details</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usageHistory as $usage)
                    <tr>
                        <td>{{ $usage->usage_date->format('M d, Y') }}</td>
                        <td>
                            @if($usage->quantity_used < 0)
                                <span class="badge bg-success">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Restock
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Usage
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($usage->quantity_used < 0)
                                <span class="text-success fw-bold">
                                    + {{ abs($usage->quantity_used) }} {{ $rawMaterial->unit }}
                                </span>
                            @else
                                <span class="text-danger fw-bold">
                                    - {{ $usage->quantity_used }} {{ $rawMaterial->unit }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ ucfirst($usage->purpose) }}</span>
                            @if($usage->notes)
                                <br><small class="text-muted">{{ $usage->notes }}</small>
                            @endif
                        </td>
                        <td>{{ $usage->user ? $usage->user->name : 'System' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            No transaction history yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $usageHistory->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate usage form
    const quantityInput = document.getElementById('quantityUsed');
    const useStockAlert = document.getElementById('useStockAlert');
    const useStockAlertMessage = document.getElementById('useStockAlertMessage');
    const useSubmitBtn = document.getElementById('useSubmitBtn');
    const maxStock = {{ $rawMaterial->quantity }};

    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const quantity = parseFloat(this.value) || 0;
            
            if (quantity > maxStock) {
                useStockAlert.classList.remove('d-none');
                useStockAlertMessage.innerHTML = `You're trying to use <strong>${quantity} {{ $rawMaterial->unit }}</strong>, but only <strong>${maxStock} {{ $rawMaterial->unit }}</strong> available!`;
                useSubmitBtn.disabled = true;
            } else if (quantity <= 0 && this.value !== '') {
                useStockAlert.classList.remove('d-none');
                useStockAlertMessage.innerHTML = `Quantity must be greater than 0!`;
                useSubmitBtn.disabled = true;
            } else {
                useStockAlert.classList.add('d-none');
                useSubmitBtn.disabled = false;
            }
        });
    }
});
</script>
@endsection