@extends('layouts.sidebar')

@section('page-title', 'Finished Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-basket-fill me-2"></i>Finished Products</h4>
        <p class="text-muted mb-0">Manage products and production batches</p>
    </div>
    <a href="{{ route('finished-products.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-2"></i>Add New Product
    </a>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Total Products</small>
                        <h3 class="mb-0">{{ $products->total() }}</h3>
                    </div>
                    <i class="bi bi-box-seam fs-1 text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Ongoing MIX</small>
                        <h3 class="mb-0 text-warning">{{ $pendingMixes ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Low Stock</small>
                        <h3 class="mb-0 text-danger">{{ $lowStockCount ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 text-danger opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Completed Today</small>
                        <h3 class="mb-0 text-success">{{ $completedToday ?? 0 }}</h3>
                    </div>
                    <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product Name</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Last Production</th>
                        <th class="text-center" style="min-width: 280px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    @php
                        $pendingMix = $product->pendingMixes->first();
                        $lastCompletedMix = $product->productionMixes()
                            ->where('status', 'completed')
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        $isManufactured = $product->product_type === 'manufactured';
                    @endphp
                    <tr>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $isManufactured ? 'success' : 'primary' }}">
                                {{ $isManufactured ? 'Manufactured' : 'Consigned' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <strong class="fs-5 {{ $product->stock_on_hand <= $product->minimum_stock ? 'text-danger' : 'text-success' }}">
                                {{ number_format($product->stock_on_hand, 0) }}
                            </strong>
                        </td>
                        <td class="text-center">
                            @if($isManufactured)
                                @if($pendingMix)
                                <span class="badge bg-warning"><i class="bi bi-clock-history"></i> Ongoing MIX</span>
                                @else
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Ready</span>
                                @endif
                            @else
                            <span class="badge bg-secondary"><i class="bi bi-shop"></i> Consigned</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($lastCompletedMix)
                            <strong class="text-success">{{ $lastCompletedMix->updated_at->format('M d, Y') }}</strong>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm w-100" role="group">
                                @if($isManufactured)
                                    @if($pendingMix)
                                    <a href="{{ route('production-mixes.show', $pendingMix) }}" class="btn btn-warning text-white">
                                        <i class="bi bi-check-circle"></i> Complete MIX
                                    </a>
                                    @else
                                    <a href="{{ route('production-mixes.create', $product->id) }}" class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> Create New MIX
                                    </a>
                                    @endif
                                @endif
                                <a href="{{ route('finished-products.show', $product) }}" class="btn btn-primary">
                                    <i class="bi bi-eye-fill"></i> View
                                </a>
                                <a href="{{ route('finished-products.edit', $product) }}" class="btn btn-info text-white">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                                <button type="button" class="btn btn-danger" onclick="confirmDelete({{ $product->id }}, '{{ $product->name }}', {{ $pendingMix ? 'true' : 'false' }})">
                                    <i class="bi bi-trash-fill"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <p class="mb-3">No products yet. Let's create your first product!</p>
                            <a href="{{ route('finished-products.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="alert alert-warning border-0"><strong>⚠️ Warning:</strong> You are about to delete this product.</div>
                    <p><strong>Product:</strong> <span id="deleteProductName"></span></p>
                    <div id="pendingMixWarning" style="display: none;">
                        <div class="alert alert-danger border-0">
                            <i class="bi bi-exclamation-circle me-2"></i><strong>This product has an ongoing MIX!</strong><br>
                            Deleting will cancel production and return all materials.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill me-2"></i>Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(productId, productName, hasPendingMix) {
    document.getElementById('deleteProductName').textContent = productName;
    document.getElementById('deleteForm').action = `/finished-products/${productId}`;
    document.getElementById('pendingMixWarning').style.display = hasPendingMix ? 'block' : 'none';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<style>
.btn-group-sm .btn {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection