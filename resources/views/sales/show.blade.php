@extends('layouts.sidebar')

@section('page-title', 'DR#' . $sale->dr_number . ' - Sales History')

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
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- DR Summary Card -->
<div class="card shadow-sm mb-4 border-primary" style="border-width: 2px;">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>DR# {{ $sale->dr_number }}</h4>
            <span class="badge bg-light text-dark fs-6">{{ $drSales->count() }} Sale(s)</span>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Area/Branch:</small>
                <div><strong>{{ $sale->branch->name }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Customer:</small>
                <div><strong>{{ $sale->customer_name }}</strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">First Sale:</small>
                <div>{{ $drSales->first()->sale_date->format('M d, Y') }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Latest Sale:</small>
                <div>{{ $drSales->last()->sale_date->format('M d, Y') }}</div>
            </div>
        </div>

        <hr>

        <!-- DR-Level Payment Summary -->
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Total Sold:</small>
                <h4 class="mb-0">₱{{ number_format($drTotalSold, 2) }}</h4>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Total Paid:</small>
                <h4 class="mb-0 text-success">₱{{ number_format($drTotalPaid, 2) }}</h4>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Balance:</small>
                <h4 class="mb-0 {{ $drBalance > 0 ? 'text-danger' : 'text-success' }}">
                    ₱{{ number_format($drBalance, 2) }}
                </h4>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Overall Status:</small>
                <div>
                    @php
                        if ($drTotalPaid >= $drTotalSold) {
                            $drStatus = 'Paid';
                            $drBadge = 'success';
                        } elseif ($drTotalPaid > 0) {
                            $drStatus = 'Partial';
                            $drBadge = 'warning';
                        } else {
                            $drStatus = 'To Be Collected';
                            $drBadge = 'danger';
                        }
                    @endphp
                    <span class="badge bg-{{ $drBadge }} fs-6">{{ $drStatus }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales History -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Sales History</h5>
    </div>
    <div class="card-body">
        @foreach($drSales as $index => $saleRecord)
        <div class="card mb-3 {{ $saleRecord->id === $sale->id ? 'border-primary' : '' }}">
            <div class="card-header {{ $saleRecord->id === $sale->id ? 'bg-primary text-white' : 'bg-light' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        Sale #{{ $index + 1 }} - {{ $saleRecord->sale_date->format('F d, Y') }}
                        @if($saleRecord->id === $sale->id)
                            <span class="badge bg-light text-primary ms-2">Current</span>
                        @endif
                    </h6>
                    <span class="badge bg-{{ $saleRecord->payment_status_badge }}">
                        {{ $saleRecord->payment_status_label }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Products Table -->
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Batch#</th>
                                <th class="text-center">Sold</th>
                                <th class="text-center">Unsold</th>
                                <th class="text-center">BO</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($saleRecord->items as $item)
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
                                        <i class="bi bi-arrow-return-left text-danger" title="Returned to warehouse"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">
                                    @if($item->discount > 0)
                                        <span class="text-danger">-₱{{ number_format($item->discount, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end"><strong>₱{{ number_format($item->subtotal, 2) }}</strong></td>
                            </tr>
                            @if($item->notes)
                            <tr>
                                <td colspan="8" class="bg-light">
                                    <small><i class="bi bi-info-circle me-1"></i><strong>Note:</strong> {{ $item->notes }}</small>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end">TOTAL:</th>
                                <th class="text-end">₱{{ number_format($saleRecord->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment Info -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Amount Paid:</small>
                                    <strong class="text-success">₱{{ number_format($saleRecord->amount_paid, 2) }}</strong>
                                </div>
                                @if($saleRecord->payment_mode)
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Payment Mode:</small>
                                    <strong>{{ $saleRecord->payment_mode_label }}</strong>
                                </div>
                                @endif
                                @if($saleRecord->payment_reference)
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Reference:</small>
                                    <strong>{{ $saleRecord->payment_reference }}</strong>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Created by:</small>
                                    <strong>{{ $saleRecord->user->name ?? 'System' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small>Created:</small>
                                    <span>{{ $saleRecord->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                @if($saleRecord->notes)
                                <div class="mt-2">
                                    <small class="text-muted">Notes:</small>
                                    <div><small>{{ $saleRecord->notes }}</small></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-3 d-flex gap-2">
                    <a href="{{ route('sales.edit', $saleRecord) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil me-1"></i>Edit Payment
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $saleRecord->id }}">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal{{ $saleRecord->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">Delete Sale #{{ $index + 1 }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this sale?</p>
                                <div class="alert alert-warning">
                                    <strong>Date:</strong> {{ $saleRecord->sale_date->format('M d, Y') }}<br>
                                    <strong>Amount:</strong> ₱{{ number_format($saleRecord->total_amount, 2) }}<br>
                                    <strong>Products:</strong> {{ $saleRecord->items->count() }} item(s)
                                </div>
                                <p class="text-danger">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <strong>This will:</strong>
                                </p>
                                <ul class="text-danger">
                                    <li>Return sold quantities to branch inventory</li>
                                    <li>Reverse any BO returns to warehouse</li>
                                    <li>Remove this sale from the DR history</li>
                                </ul>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="{{ route('sales.destroy', $saleRecord) }}" method="POST" class="d-inline">
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
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection