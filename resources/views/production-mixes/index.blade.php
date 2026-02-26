@extends('layouts.sidebar')

@section('page-title', 'Production MIX Batches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-layers-fill me-2"></i>Production MIX Batches</h4>
        <p class="text-muted mb-0">Manage ingredient mixes and track production</p>
    </div>
    <a href="{{ route('production-mixes.create') }}" class="btn btn-success btn-lg">
        <i class="bi bi-plus-circle me-2"></i>Create New MIX
    </a>
</div>

<!-- Status Filter -->
<div class="mb-4">
    <label class="me-2"><strong>Filter by Status:</strong></label>
    <div class="btn-group" role="group">
        <a href="{{ route('production-mixes.index') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-clock-history me-1"></i>Pending
        </a>
        <a href="{{ route('production-mixes.index', ['status' => 'in_progress']) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-repeat me-1"></i>In Progress
        </a>
        <a href="{{ route('production-mixes.index', ['status' => 'completed']) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-check-circle me-1"></i>Completed
        </a>
    </div>
</div>

<div class="row">
    @forelse($mixes as $mix)
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm {{ $mix->status === 'completed' ? 'border-success' : ($mix->status === 'in_progress' ? 'border-primary' : 'border-warning') }}" style="border-width: 2px;">
            <div class="card-header {{ $mix->status === 'completed' ? 'bg-success' : ($mix->status === 'in_progress' ? 'bg-primary' : 'bg-warning') }} text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-box-seam-fill me-2"></i>{{ $mix->finishedProduct->name }}
                    </h6>
                    <span class="badge bg-white text-dark">
                        {{ ucfirst($mix->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Batch Info -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted"><i class="bi bi-upc-scan me-1"></i>Batch:</span>
                        <strong class="badge bg-dark">{{ $mix->batch_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted"><i class="bi bi-calendar-event me-1"></i>Mix Date:</span>
                        <strong>{{ $mix->mix_date->format('M d, Y') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-calendar-x me-1"></i>Expires:</span>
                        <strong class="{{ $mix->expiration_date->isPast() ? 'text-danger' : 'text-success' }}">
                            {{ $mix->expiration_date->format('M d, Y') }}
                            @if($mix->expiration_date->isPast())
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            @endif
                        </strong>
                    </div>
                </div>

                <hr>

                <!-- Output Info -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Expected Output:</span>
                        <strong class="text-primary">{{ number_format($mix->expected_output, 0) }} units</strong>
                    </div>
                    @if($mix->actual_output)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Actual Output:</span>
                        <strong class="text-success">{{ number_format($mix->actual_output, 0) }} units</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Variance:</span>
                        <strong class="{{ $mix->variance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $mix->variance >= 0 ? '+' : '' }}{{ number_format($mix->variance, 0) }} units
                            ({{ number_format($mix->variance_percentage, 1) }}%)
                        </strong>
                    </div>
                    @else
                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-clock-history me-1"></i>Awaiting production completion
                    </div>
                    @endif
                </div>

                <hr>

                <!-- Ingredients Preview -->
                <div class="mb-3">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-list-ul me-1"></i>Ingredients ({{ $mix->ingredients->count() }}):
                    </small>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($mix->ingredients->take(3) as $ingredient)
                        <span class="badge bg-secondary" title="{{ $ingredient->rawMaterial->name }}: {{ $ingredient->quantity_used }} {{ $ingredient->rawMaterial->unit }}">
                            {{ Str::limit($ingredient->rawMaterial->name, 10) }}
                        </span>
                        @endforeach
                        @if($mix->ingredients->count() > 3)
                        <span class="badge bg-info">+{{ $mix->ingredients->count() - 3 }} more</span>
                        @endif
                    </div>
                </div>

                @if($mix->barcode)
                <div class="mb-3">
                    <small class="text-muted"><i class="bi bi-barcode me-1"></i>Barcode:</small>
                    <div class="font-monospace small">{{ $mix->barcode }}</div>
                </div>
                @endif
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex gap-2">
                    <a href="{{ route('production-mixes.show', $mix) }}" class="btn btn-sm btn-primary flex-fill">
                        <i class="bi bi-eye-fill me-1"></i>View Details
                    </a>
                    @if($mix->status !== 'completed')
                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete({{ $mix->id }}, '{{ $mix->batch_number }}')">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted mb-3">No MIX batches yet</h5>
                <p class="text-muted mb-4">Create your first production MIX to get started!</p>
                <a href="{{ route('production-mixes.create') }}" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Create First MIX
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
<div class="mt-4">
    {{ $mixes->links() }}
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-trash-fill me-2"></i>Cancel MIX & Return Materials
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                </div>
                <h6 class="text-center mb-3">Are you sure you want to cancel this MIX?</h6>
                <div class="alert alert-info">
                    <strong>Batch:</strong> <span id="deleteBatchNumber"></span>
                </div>
                <div class="alert alert-success">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                    <strong>Good news:</strong> All raw materials will be returned to inventory!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash-fill me-1"></i>Delete MIX
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(mixId, batchNumber) {
    document.getElementById('deleteBatchNumber').textContent = batchNumber;
    document.getElementById('deleteForm').action = `/production-mixes/${mixId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<style>
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}
</style>
@endsection