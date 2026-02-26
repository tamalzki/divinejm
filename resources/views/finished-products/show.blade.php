@extends('layouts.sidebar')

@section('page-title', $finishedProduct->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('finished-products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Products
    </a>
</div>

<div class="row">
    <!-- Product Details -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-box-seam-fill me-2"></i>Product Details</h5>
            </div>
            <div class="card-body">
                <h3 class="mb-3">{{ $finishedProduct->name }}</h3>
                
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="p-3 bg-success bg-opacity-10 rounded text-center">
                            <small class="text-muted d-block">Stock on Hand</small>
                           <h3 class="mb-0 fw-bold text-dark">
    {{ number_format($finishedProduct->stock_on_hand, 0) }}
</h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-warning bg-opacity-10 rounded text-center">
                            <small class="text-muted d-block">Min Stock</small>
                            <h3 class="mb-0 text-warning">{{ number_format($finishedProduct->minimum_stock, 0) }}</h3>
                        </div>
                    </div>
                </div>

                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">Product Type:</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $finishedProduct->product_type === 'manufactured' ? 'success' : 'primary' }}">
                                {{ ucfirst($finishedProduct->product_type) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cost Price:</td>
                        <td class="text-end"><strong>₱{{ number_format($finishedProduct->cost_price, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Selling Price:</td>
                        <td class="text-end"><strong class="text-success">₱{{ number_format($finishedProduct->selling_price, 2) }}</strong></td>
                    </tr>
                </table>

                @if($finishedProduct->description)
                <div class="alert alert-light border mt-3">
                    <small class="text-muted d-block mb-1">Description:</small>
                    {{ $finishedProduct->description }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Production Actions -->
    <div class="col-lg-6 mb-4">
        @if($finishedProduct->product_type === 'manufactured')
        @php
            $pendingMix = $finishedProduct->pendingMixes->first();
        @endphp
        
        @if(!$pendingMix)
        <!-- Create MIX Card (only if NO pending MIX) -->
        <div class="card shadow-sm border-success" style="border-width: 2px;">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-layers-fill me-2"></i>Create Production MIX</h5>
            </div>
            <div class="card-body text-center py-4">
                <p class="mb-3">Start a new production batch for this product.</p>
                <a href="{{ route('production-mixes.create', $finishedProduct->id) }}" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Create New MIX
                </a>
            </div>
        </div>
        @else
        <!-- Pending MIX Alert -->
        <div class="alert alert-warning border-0 shadow-sm">
            <h6 class="alert-heading"><i class="bi bi-clock-history me-2"></i>Ongoing Production MIX</h6>
            <p class="mb-2">This product already has an ongoing MIX that needs to be completed first.</p>
            <hr>
            <p class="mb-2"><strong>Batch:</strong> {{ $pendingMix->batch_number }}</p>
            <p class="mb-3"><strong>Expected Output:</strong> {{ number_format($pendingMix->expected_output, 0) }} units</p>
            <a href="{{ route('production-mixes.show', $pendingMix) }}" class="btn btn-warning">
                <i class="bi bi-arrow-right-circle me-2"></i>Go to MIX to Complete
            </a>
        </div>
        @endif
        @else
        <!-- Consigned Product Info -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-shop text-primary fs-1 d-block mb-3"></i>
                <h5>Consigned Product</h5>
                <p class="text-muted mb-0">No production needed. This is a ready-made product.</p>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Production MIX History -->
@if($finishedProduct->product_type === 'manufactured' && $finishedProduct->productionMixes && $finishedProduct->productionMixes->count() > 0)
<div class="card shadow-sm mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Production MIX History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Batch Number</th>
                        <th>Mix Date</th>
                        <th>Expiration</th>
                        <th class="text-center">Expected Output</th>
                        <th class="text-center">Actual Output</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($finishedProduct->productionMixes->sortByDesc('created_at') as $mix)
                    <tr>
                        <td><strong>{{ $mix->batch_number }}</strong></td>
                        <td>{{ $mix->mix_date->format('M d, Y') }}</td>
                        <td class="{{ $mix->expiration_date->isPast() ? 'text-danger fw-bold' : '' }}">
                            {{ $mix->expiration_date->format('M d, Y') }}
                        </td>
                        <td class="text-center">
                            <strong class="text-primary">{{ number_format($mix->expected_output, 0) }}</strong>
                        </td>
                        <td class="text-center">
                            @if($mix->actual_output)
                            <strong class="text-success">{{ number_format($mix->actual_output, 0) }}</strong>
                            @else
                            <span class="text-muted">Pending</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $mix->status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($mix->status) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('production-mixes.show', $mix) }}" class="btn btn-sm btn-info text-white">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection