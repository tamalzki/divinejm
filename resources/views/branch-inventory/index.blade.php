@extends('layouts.sidebar')

@section('page-title', 'Area Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-geo-alt me-2"></i>Area Inventory Overview</h2>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="bi bi-geo-alt text-primary fs-1"></i>
                <h3 class="mt-2 mb-0">{{ $branches->count() }}</h3>
                <small class="text-muted">Total Areas</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-success fs-1"></i>
                <h3 class="mt-2 mb-0">{{ $branches->sum(fn($b) => $b->inventory->where('quantity', '>', 0)->count()) }}</h3>
                <small class="text-muted">Products in Stock</small>
            </div>
        </div>
    </div>
</div>

<!-- Table View -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Areas</h5>
            <input type="text" id="searchArea" class="form-control form-control-sm" style="max-width: 250px;" placeholder="Search area...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Area Name</th>
                        <th style="width: 25%;">Location</th>
                        <th class="text-center" style="width: 15%;">Products</th>
                        <th style="width: 30%;">Top Products</th>
                        <th class="text-center" style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="areaTableBody">
                    @forelse($branches as $branch)
                    @php
                        $stockItems = $branch->inventory->where('quantity', '>', 0);
                        $topProducts = $stockItems->sortByDesc('quantity')->take(3);
                    @endphp
                    <tr class="area-row">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 35px; height: 35px; font-size: 14px;">
                                    <i class="bi bi-geo-alt"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $branch->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <small class="text-muted">
                                @if($branch->address)
                                    <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($branch->address, 30) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </small>
                        </td>
                        <td class="text-center">
                            @if($stockItems->count() > 0)
                                <span class="badge bg-success fs-6">{{ $stockItems->count() }}</span>
                            @else
                                <span class="badge bg-secondary">0</span>
                            @endif
                        </td>
                        <td>
                            @if($topProducts->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($topProducts as $item)
                                        <span class="badge bg-light text-dark border" style="font-weight: normal;">
                                            {{ $item->finishedProduct->name }}: <strong>{{ $item->quantity }}</strong>
                                        </span>
                                    @endforeach
                                    @if($stockItems->count() > 3)
                                        <span class="badge bg-light text-muted border" style="font-weight: normal;">
                                            +{{ $stockItems->count() - 3 }} more
                                        </span>
                                    @endif
                                </div>
                            @else
                                <small class="text-muted">No stock</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('branch-inventory.show', $branch) }}" 
                               class="btn btn-sm btn-primary">
                                <i class="bi bi-box-seam me-1"></i>Manage
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 opacity-25 d-block mb-2"></i>
                            No areas found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Search Filter Script -->
<script>
document.getElementById('searchArea').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('.area-row');
    
    rows.forEach(row => {
        const areaName = row.querySelector('td:first-child').textContent.toLowerCase();
        const location = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        
        if (areaName.includes(searchValue) || location.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

@endsection