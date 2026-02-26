@extends('layouts.sidebar')

@section('page-title', 'Sale Details - DR#' . $sale->dr_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Sales
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
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>{{ session('error') }}</strong>
    <div class="mt-2">
        <small>This sale is currently marked as: <span class="badge bg-{{ $sale->payment_status_badge }}">{{ $sale->payment_status_label }}</span></small>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <!-- Sale Information -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Sale Information</h5>
                <span class="badge bg-{{ $sale->payment_status_badge }}">{{ $sale->payment_status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">DR Number:</small>
                        <div><strong class="fs-4">{{ $sale->dr_number }}</strong></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Sale Date:</small>
                        <div><strong>{{ $sale->sale_date->format('F d, Y') }}</strong></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Area/Branch:</small>
                        <div><strong>{{ $sale->branch->name }}</strong></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Customer:</small>
                        <div><strong>{{ $sale->customer_name }}</strong></div>
                    </div>
                </div>

                @if($sale->notes)
                <div class="row">
                    <div class="col-12">
                        <small class="text-muted">Notes:</small>
                        <div>{{ $sale->notes }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sale Items -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Products Sold</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Batch #</th>
                                <th class="text-center">Sold</th>
                                <th class="text-center">Unsold</th>
                                <th class="text-center">BO</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->finishedProduct->name }}</strong><br>
                                    <small class="text-muted">SKU: {{ $item->finishedProduct->sku }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $item->batch_number ?? 'N/A' }}</span>
                                </td>
                                <td class="text-center">
                                    <strong class="text-success">{{ $item->quantity_sold }}</strong>
                                </td>
                                <td class="text-center">
                                    @if($item->quantity_unsold > 0)
                                        <span class="text-warning">{{ $item->quantity_unsold }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->quantity_bo > 0)
                                        <span class="text-danger">{{ $item->quantity_bo }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end"><strong>₱{{ number_format($item->subtotal, 2) }}</strong></td>
                            </tr>
                            @if($item->notes)
                            <tr>
                                <td colspan="7" class="bg-light">
                                    <small><i class="bi bi-info-circle me-1"></i><strong>Note:</strong> {{ $item->notes }}</small>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">TOTAL AMOUNT:</th>
                                <th class="text-end">₱{{ number_format($sale->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment & Actions Sidebar -->
    <div class="col-md-4">
        <!-- Payment Summary -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Payment Summary</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Amount:</span>
                    <strong>₱{{ number_format($sale->total_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Amount Paid:</span>
                    <strong class="text-success">₱{{ number_format($sale->amount_paid, 2) }}</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span><strong>Balance:</strong></span>
                    <strong class="{{ $sale->balance > 0 ? 'text-danger' : 'text-success' }}">
                        ₱{{ number_format($sale->balance, 2) }}
                    </strong>
                </div>

                <hr>

                <div class="mb-2">
                    <small class="text-muted">Payment Status:</small>
                    <div>
                        <span class="badge bg-{{ $sale->payment_status_badge }}">
                            {{ $sale->payment_status_label }}
                        </span>
                    </div>
                </div>

                @if($sale->payment_mode)
                <div class="mb-2">
                    <small class="text-muted">Payment Mode:</small>
                    <div><strong>{{ $sale->payment_mode_label }}</strong></div>
                </div>
                @endif

                @if($sale->payment_reference)
                <div class="mb-2">
                    <small class="text-muted">Reference:</small>
                    <div><strong>{{ $sale->payment_reference }}</strong></div>
                </div>
                @endif

                @if($sale->payment_date)
                <div class="mb-2">
                    <small class="text-muted">Payment Date:</small>
                    <div>{{ $sale->payment_date->format('M d, Y') }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('sales.edit', $sale) }}" class="btn btn-warning">
                        <i class="bi bi-pencil me-2"></i>Edit Payment
                    </a>
                    
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash me-2"></i>Delete Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- Sale Info -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-3">Sale Information</h6>
                <div class="small">
                    <div class="mb-2">
                        <i class="bi bi-person-circle me-2"></i>
                        Created by: {{ $sale->user->name ?? 'System' }}
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-calendar me-2"></i>
                        Created: {{ $sale->created_at->format('M d, Y g:i A') }}
                    </div>
                    @if($sale->updated_at != $sale->created_at)
                    <div>
                        <i class="bi bi-clock-history me-2"></i>
                        Updated: {{ $sale->updated_at->format('M d, Y g:i A') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this sale?</p>
                <div class="alert alert-warning">
                    <strong>DR#:</strong> {{ $sale->dr_number }}<br>
                    <strong>Customer:</strong> {{ $sale->customer_name }}<br>
                    <strong>Amount:</strong> ₱{{ number_format($sale->total_amount, 2) }}
                </div>
                <p class="text-danger"><strong>Note:</strong> This will return {{ $sale->items->count() }} product(s) to the branch inventory.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>Delete Sale
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection