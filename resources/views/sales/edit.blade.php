@extends('layouts.sidebar')

@section('page-title', 'Edit Payment - DR#' . $sale->dr_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Sale Details
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Payment Information</h5>
            </div>
            <div class="card-body">
                <!-- Sale Info -->
                <div class="alert alert-info">
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">DR Number:</small>
                            <div><strong>{{ $sale->dr_number }}</strong></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Customer:</small>
                            <div><strong>{{ $sale->customer_name }}</strong></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Total Amount:</small>
                            <div><strong>₱{{ number_format($sale->total_amount, 2) }}</strong></div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('sales.update', $sale) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sale Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   name="sale_date" 
                                   class="form-control" 
                                   value="{{ $sale->sale_date->format('Y-m-d') }}" 
                                   required>
                            @error('sale_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode</label>
                            <select name="payment_mode" id="paymentMode" class="form-select">
                                <option value="">-- Not Paid Yet --</option>
                                <option value="cash" {{ $sale->payment_mode == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="gcash" {{ $sale->payment_mode == 'gcash' ? 'selected' : '' }}>GCash</option>
                                <option value="cheque" {{ $sale->payment_mode == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="bank_transfer" {{ $sale->payment_mode == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="other" {{ $sale->payment_mode == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('payment_mode')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount Paid</label>
                            <input type="number" 
                                   name="amount_paid" 
                                   id="amountPaid"
                                   class="form-control" 
                                   value="{{ $sale->amount_paid }}"
                                   step="0.01" 
                                   min="0"
                                   max="{{ $sale->total_amount }}"
                                   placeholder="0.00">
                            <small class="text-muted">Max: ₱{{ number_format($sale->total_amount, 2) }}</small>
                            @error('amount_paid')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Date</label>
                            <input type="date" 
                                   name="payment_date" 
                                   class="form-control" 
                                   value="{{ $sale->payment_date ? $sale->payment_date->format('Y-m-d') : '' }}">
                            @error('payment_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Reference</label>
                        <input type="text" 
                               name="payment_reference" 
                               class="form-control" 
                               value="{{ $sale->payment_reference }}"
                               placeholder="Cheque #, GCash reference, etc.">
                        <small class="text-muted">Enter cheque number, GCash reference, or other payment reference</small>
                        @error('payment_reference')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Additional notes about payment...">{{ $sale->notes }}</textarea>
                        @error('notes')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Payment Summary -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Payment Summary</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Total Amount:</small>
                                    <div><strong>₱{{ number_format($sale->total_amount, 2) }}</strong></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Amount Paid:</small>
                                    <div class="text-success"><strong id="displayPaid">₱{{ number_format($sale->amount_paid, 2) }}</strong></div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Balance:</small>
                                    <div class="text-danger"><strong id="displayBalance">₱{{ number_format($sale->balance, 2) }}</strong></div>
                                </div>
                            </div>
                            <hr>
                            <div>
                                <small class="text-muted">Payment Status:</small>
                                <div id="displayStatus">
                                    <span class="badge bg-{{ $sale->payment_status_badge }}">
                                        {{ $sale->payment_status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Update Payment
                        </button>
                        <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const totalAmount = {{ $sale->total_amount }};

document.getElementById('amountPaid').addEventListener('input', function() {
    const amountPaid = parseFloat(this.value) || 0;
    const balance = totalAmount - amountPaid;

    // Update display
    document.getElementById('displayPaid').textContent = 
        '₱' + amountPaid.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    document.getElementById('displayBalance').textContent = 
        '₱' + balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Update status
    let status, badgeClass;
    if (amountPaid >= totalAmount) {
        status = 'Paid';
        badgeClass = 'success';
    } else if (amountPaid > 0) {
        status = 'Partially Paid';
        badgeClass = 'warning';
    } else {
        status = 'To Be Collected';
        badgeClass = 'danger';
    }

    document.getElementById('displayStatus').innerHTML = 
        `<span class="badge bg-${badgeClass}">${status}</span>`;
});
</script>
@endsection