@extends('layouts.sidebar')

@section('page-title', 'Raw Materials')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-box-seam me-2"></i>Raw Materials</h4>
        <p class="text-muted mb-0">Manage ingredients and packaging materials</p>
    </div>
    <a href="{{ route('raw-materials.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-2"></i>Add New Material
    </a>
</div>

<!-- Stock Level Filters -->
<div class="mb-4">
    <label class="me-2"><strong>Stock Levels:</strong></label>
    <div class="btn-group" role="group">
        <a href="{{ route('raw-materials.index') }}" class="btn btn-sm btn-outline-danger">
            Out of Stock
        </a>
        <a href="{{ route('raw-materials.index', ['filter' => 'low']) }}" class="btn btn-sm btn-outline-warning">
            Low Stock
        </a>
        <a href="{{ route('raw-materials.index', ['filter' => 'good']) }}" class="btn btn-sm btn-outline-success">
            In Stock
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Category</th>
                        <th class="text-center">Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th class="text-center">Min Stock</th>
                        <th>Used In Products</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rawMaterials as $material)
                    <tr class="{{ $material->quantity == 0 ? 'table-danger' : ($material->isLowStock() ? 'table-warning' : '') }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-{{ $material->category === 'ingredient' ? 'egg-fill text-warning' : 'box-seam-fill text-info' }} fs-4 me-2"></i>
                                <div>
                                    <strong>{{ $material->name }}</strong>
                                    @if($material->description)
                                    <br><small class="text-muted">{{ Str::limit($material->description, 40) }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $material->category === 'ingredient' ? 'success' : 'primary' }}">
                                {{ ucfirst($material->category) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <strong class="fs-5 {{ $material->quantity == 0 ? 'text-danger' : ($material->isLowStock() ? 'text-warning' : 'text-success') }}">
                                {{ number_format($material->quantity, 2) }}
                            </strong>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $material->unit }}</span>
                        </td>
                        <td>
                            <strong>₱{{ number_format($material->unit_price, 2) }}</strong>
                        </td>
                        <td class="text-center">
                            {{ number_format($material->minimum_stock, 2) }}
                        </td>
                        <td>
                            @if($material->recipes->count() > 0)
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($material->recipes->take(3) as $recipe)
                                <span class="badge bg-info" title="{{ $recipe->finishedProduct->name }}">
                                    {{ Str::limit($recipe->finishedProduct->name, 15) }}
                                </span>
                                @endforeach
                                @if($material->recipes->count() > 3)
                                <span class="badge bg-secondary" title="{{ $material->recipes->count() }} total products">
                                    +{{ $material->recipes->count() - 3 }} more
                                </span>
                                @endif
                            </div>
                            @else
                            <span class="text-muted"><i>Not used yet</i></span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($material->quantity == 0)
                            <span class="badge bg-danger">
                                <i class="bi bi-x-circle-fill"></i> Empty
                            </span>
                            @elseif($material->isLowStock())
                            <span class="badge bg-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> Low
                            </span>
                            @else
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle-fill"></i> In Stock
                            </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('raw-materials.show', $material) }}" 
                                   class="btn btn-info text-white" 
                                   title="Manage Stock">
                                    <i class="bi bi-arrow-left-right me-1"></i>Manage
                                </a>
                                <a href="{{ route('raw-materials.edit', $material) }}" 
                                   class="btn btn-warning" 
                                   title="Edit Material">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <button type="button" 
                                        class="btn btn-danger" 
                                        onclick='confirmDeleteMaterial(
                                            {{ $material->id }}, 
                                            "{{ $material->name }}", 
                                            {{ $material->recipes->count() }},
                                            @json($material->recipes->pluck("finishedProduct.name")->unique()->values())
                                        )'
                                        title="Delete Material">
                                    <i class="bi bi-trash-fill me-1"></i>Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <p class="mb-0">No raw materials found. Add your first material to get started!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rawMaterials->links() }}
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-trash-fill me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                </div>
                
                <h6 class="text-center mb-3">Are you sure you want to delete this material?</h6>
                
                <div class="alert alert-light border">
                    <strong>Material:</strong> <span id="deleteMaterialName"></span>
                </div>

                <div id="materialUsageWarning" class="alert alert-danger" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Warning: This material is used in the following products:</strong>
                    <div id="materialProductsList" class="mt-2"></div>
                    <hr>
                    <p class="mb-0"><strong>You must remove this material from these recipes before deleting.</strong></p>
                </div>

                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. All transaction history will also be deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form id="deleteMaterialForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" id="deleteMaterialBtn">
                        <i class="bi bi-trash-fill me-1"></i>Delete Material
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteMaterial(materialId, materialName, usageCount, productNames) {
    document.getElementById('deleteMaterialName').textContent = materialName;
    
    // Update form action
    const form = document.getElementById('deleteMaterialForm');
    form.action = `/raw-materials/${materialId}`;
    
    // Show warning if material is used in products
    const warningDiv = document.getElementById('materialUsageWarning');
    const productsList = document.getElementById('materialProductsList');
    const deleteBtn = document.getElementById('deleteMaterialBtn');
    
    if (usageCount > 0) {
        warningDiv.style.display = 'block';
        let listHtml = '<ul class="mb-0">';
        productNames.forEach(name => {
            listHtml += `<li><strong>${name}</strong></li>`;
        });
        listHtml += '</ul>';
        productsList.innerHTML = listHtml;
        
        // Disable delete button
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="bi bi-lock-fill me-1"></i>Cannot Delete';
    } else {
        warningDiv.style.display = 'none';
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="bi bi-trash-fill me-1"></i>Delete Material';
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('deleteMaterialModal'));
    modal.show();
}
</script>

<style>
.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    transform: scale(1.01);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-group-sm .btn {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection