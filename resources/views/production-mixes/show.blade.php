@extends('layouts.sidebar')

@section('page-title', 'MIX Details - ' . $productionMix->batch_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('finished-products.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-2"></i>Back to Products
</a>
</div>

<div class="row">
    <!-- Left Column: MIX Details -->
    <div class="col-lg-5 mb-4">
        <!-- Status Card -->
        <div class="card shadow-sm mb-3">
            <div class="card-header {{ $productionMix->status === 'completed' ? 'bg-success' : ($productionMix->status === 'in_progress' ? 'bg-primary' : 'bg-warning') }} text-white">
                <h5 class="mb-0">
                    <i class="bi bi-layers-fill me-2"></i>MIX Status
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <span class="badge {{ $productionMix->status === 'completed' ? 'bg-success' : ($productionMix->status === 'in_progress' ? 'bg-primary' : 'bg-warning') }} fs-5 px-4 py-2">
                        {{ strtoupper($productionMix->status) }}
                    </span>
                </div>

                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted"><i class="bi bi-box-seam me-1"></i>Product:</td>
                        <td class="text-end"><strong>{{ $productionMix->finishedProduct->name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-upc-scan me-1"></i>Batch Number:</td>
                        <td class="text-end"><code class="fs-6">{{ $productionMix->batch_number }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-x-circle me-1"></i>Multiplier:</td>
                        <td class="text-end"><strong class="text-primary">×{{ $productionMix->multiplier }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-calendar-event me-1"></i>Mix Date:</td>
                        <td class="text-end"><strong>{{ $productionMix->mix_date->format('M d, Y') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="bi bi-calendar-x me-1"></i>Expiration:</td>
                        <td class="text-end">
                            <strong class="{{ $productionMix->expiration_date->isPast() ? 'text-danger' : 'text-success' }}">
                                {{ $productionMix->expiration_date->format('M d, Y') }}
                                @if($productionMix->expiration_date->isPast())
                                <i class="bi bi-exclamation-triangle-fill ms-1"></i>
                                @endif
                            </strong>
                        </td>
                    </tr>
                    @if($productionMix->barcode)
                    <tr>
                        <td class="text-muted"><i class="bi bi-barcode me-1"></i>Barcode:</td>
                        <td class="text-end"><code>{{ $productionMix->barcode }}</code></td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted"><i class="bi bi-person me-1"></i>Created By:</td>
                        <td class="text-end">{{ $productionMix->user ? $productionMix->user->name : 'System' }}</td>
                    </tr>
                </table>

                @if($productionMix->notes)
                <div class="alert alert-light border">
                    <small class="text-muted d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>Notes:</small>
                    {{ $productionMix->notes }}
                </div>
                @endif
            </div>
        </div>

        <!-- Output Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Production Output</h6>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">Expected (×{{ $productionMix->multiplier }})</small>
                            <h3 class="mb-0 text-primary">{{ number_format($productionMix->total_expected_output, 0) }}</h3>
                            <small class="text-muted">units</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">Actual</small>
                            <h3 class="mb-0 {{ $productionMix->actual_output ? 'text-success' : 'text-muted' }}">
                                {{ $productionMix->actual_output ? number_format($productionMix->actual_output, 0) : '---' }}
                            </h3>
                            <small class="text-muted">units</small>
                        </div>
                    </div>
                    @if($productionMix->actual_output)
                    <div class="col-6">
                        <div class="p-3 border border-danger border-2 rounded bg-white">
                            <small class="text-muted d-block mb-1">Rejected</small>
                            <h4 class="mb-0 text-danger fw-bold">{{ number_format($productionMix->rejected_quantity, 0) }}</h4>
                            <small class="text-danger fw-bold">{{ number_format($productionMix->rejection_rate, 1) }}%</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border border-success border-2 rounded bg-white">
                            <small class="text-muted d-block mb-1">Good Output</small>
                            <h4 class="mb-0 text-success fw-bold">{{ number_format($productionMix->good_output, 0) }}</h4>
                            <small class="text-success fw-bold">{{ number_format($productionMix->yield_rate, 1) }}% yield</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 border {{ $productionMix->variance >= 0 ? 'border-success' : 'border-danger' }} border-2 rounded bg-white">
                            <small class="text-muted d-block mb-1">Variance</small>
                            <h4 class="mb-0 {{ $productionMix->variance >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                {{ $productionMix->variance >= 0 ? '+' : '' }}{{ number_format($productionMix->variance, 0) }} units
                            </h4>
                            <small class="{{ $productionMix->variance >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                ({{ number_format($productionMix->variance_percentage, 1) }}%)
                            </small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Complete Production / Ingredients -->
    <div class="col-lg-7 mb-4">
        @if($productionMix->status !== 'completed')
        <!-- Complete Production Form -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i>Complete Production
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Ready to finalize?</strong> Enter the actual output and rejected quantity.
                </div>

                <form action="{{ route('production-mixes.complete', $productionMix) }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-box-seam-fill me-1"></i>Actual Output <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number" 
                                       step="0.01" 
                                       name="actual_output" 
                                       id="actual_output"
                                       class="form-control @error('actual_output') is-invalid @enderror" 
                                       placeholder="e.g., {{ $productionMix->total_expected_output }}" 
                                       required
                                       onchange="calculateGood()">
                                <span class="input-group-text">units</span>
                            </div>
                            <small class="text-muted">Expected: {{ number_format($productionMix->total_expected_output, 0) }} units ({{ $productionMix->expected_output }} × {{ $productionMix->multiplier }})</small>
                            @error('actual_output')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-x-circle me-1 text-danger"></i>Rejected/Defective <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number" 
                                       step="0.01" 
                                       name="rejected_quantity" 
                                       id="rejected_quantity"
                                       class="form-control @error('rejected_quantity') is-invalid @enderror" 
                                       value="0"
                                       required
                                       onchange="calculateGood()">
                                <span class="input-group-text">units</span>
                            </div>
                            <small class="text-muted">Defective/damaged products</small>
                            @error('rejected_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mb-3">
                            <div class="alert alert-success border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><i class="bi bi-check-circle me-2"></i>Good Output (will be added to inventory):</strong>
                                        <br><small class="text-muted">Actual - Rejected = Good Output</small>
                                    </div>
                                    <h3 class="mb-0" id="good_output">0 units</h3>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Rejection Rate: <strong id="rejection_rate">0%</strong></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-circle-fill me-2"></i>Complete Production
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Ingredients List -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Ingredients Used ({{ $productionMix->ingredients->count() }})
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Raw Material</th>
                                <th>Category</th>
                                <th class="text-center">Quantity Used</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalCost = 0; @endphp
                            @foreach($productionMix->ingredients as $ingredient)
                            @php 
                                $cost = $ingredient->quantity_used * $ingredient->rawMaterial->unit_price;
                                $totalCost += $cost;
                            @endphp
                            <tr>
                                <td>
                                    <i class="bi bi-{{ $ingredient->rawMaterial->category === 'ingredient' ? 'egg-fill text-warning' : 'box-seam-fill text-info' }} me-2"></i>
                                    <strong>{{ $ingredient->rawMaterial->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ingredient->rawMaterial->category === 'ingredient' ? 'success' : 'primary' }}">
                                        {{ ucfirst($ingredient->rawMaterial->category) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ number_format($ingredient->quantity_used, 2) }}</strong>
                                    <small class="text-muted">{{ $ingredient->rawMaterial->unit }}</small>
                                </td>
                                <td class="text-end">
                                    ₱{{ number_format($ingredient->rawMaterial->unit_price, 2) }}
                                </td>
                                <td class="text-end">
                                    <strong>₱{{ number_format($cost, 2) }}</strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Total MIX Cost:</th>
                                <th class="text-end text-success">
                                    <h5 class="mb-0">₱{{ number_format($totalCost, 2) }}</h5>
                                </th>
                            </tr>
                            @if($productionMix->actual_output && $productionMix->good_output > 0)
                            <tr>
                                <th colspan="4" class="text-end">Cost per Good Unit:</th>
                                <th class="text-end text-primary">
                                    ₱{{ number_format($totalCost / $productionMix->good_output, 2) }}
                                </th>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateGood() {
    const actual = parseFloat(document.getElementById('actual_output').value) || 0;
    const rejected = parseFloat(document.getElementById('rejected_quantity').value) || 0;
    const good = actual - rejected;
    
    document.getElementById('good_output').textContent = good.toFixed(0) + ' units';
    
    if (actual > 0) {
        const rejectionRate = (rejected / actual) * 100;
        document.getElementById('rejection_rate').textContent = rejectionRate.toFixed(2) + '%';
    } else {
        document.getElementById('rejection_rate').textContent = '0%';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateGood();
});
</script>
@endsection