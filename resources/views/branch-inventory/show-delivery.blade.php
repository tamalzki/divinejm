@extends('layouts.sidebar')
@section('page-title', 'Delivery DR# ' . $drNumber)
@section('content')

<style>
    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .meta-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1rem; }
    .meta-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.7rem 1rem; }
    .meta-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.2rem; }
    .meta-value { font-size:.88rem; font-weight:700; color:var(--text-primary); }
    .meta-value.accent { color:var(--accent); }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tfoot td { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.52rem .9rem; font-size:.78rem; font-weight:700; }

    .pill { display:inline-block; padding:.1rem .4rem; border-radius:4px; font-size:.68rem; font-weight:600; }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-info    { background:var(--s-info-bg);    color:var(--s-info-text); }

    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }
</style>

{{-- Parse notes first so $customerName is available everywhere --}}
@php
    $customerName = '—';
    if ($first->notes && str_contains($first->notes, 'Customer:')) {
        preg_match('/Customer:\s*([^|]+)/', $first->notes, $m);
        $customerName = trim($m[1] ?? '—');
    }
    $userNotes = null;
    if ($first->notes && str_contains($first->notes, ' | ')) {
        $userNotes = trim(explode(' | ', $first->notes, 2)[1] ?? null);
    }
@endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('branch-inventory.index') }}" class="dj-back">
            <i class="bi bi-arrow-left"></i> Deliveries
        </a>
        <div>
            <h5 class="fw-bold mb-0" style="font-size:.93rem">
                <i class="bi bi-file-earmark-text me-1" style="color:var(--accent)"></i>
                DR# <span style="color:var(--accent)">{{ $drNumber }}</span>
            </h5>
            <span style="font-size:.68rem;color:var(--text-muted)">
                {{ $first->movement_date->format('F d, Y') }}
                &middot;
                <span>Warehouse</span>
                &rarr; <span style="color:var(--text-secondary)">{{ $branch->name ?? '—' }}</span>
                &rarr; <strong style="color:var(--text-primary)">{{ $customerName }}</strong>
            </span>
        </div>
    </div>
</div>

{{-- Meta tiles --}}
<div class="meta-grid">
    <div class="meta-tile">
        <span class="meta-label">From</span>
        <span class="meta-value" style="font-size:.78rem">
            Warehouse &rarr; {{ $branch->name ?? '—' }}
        </span>
    </div>
    <div class="meta-tile">
        <span class="meta-label">Customer</span>
        <span class="meta-value accent">{{ $customerName }}</span>
    </div>
    <div class="meta-tile">
        <span class="meta-label">Delivery Date</span>
        <span class="meta-value">{{ $first->movement_date->format('M d, Y') }}</span>
    </div>
    <div class="meta-tile">
        <span class="meta-label">Recorded By</span>
        <span class="meta-value">{{ $first->user->name ?? '—' }}</span>
    </div>
</div>

@if($userNotes)
<div class="dj-card" style="margin-bottom:1rem">
    <div class="dj-card-header">
        <span class="dj-card-title"><i class="bi bi-chat-left-text" style="color:var(--accent)"></i>Notes</span>
    </div>
    <div style="padding:.7rem 1rem;font-size:.8rem;color:var(--text-secondary)">{{ $userNotes }}</div>
</div>
@endif

{{-- Products delivered — grouped by product, one row each --}}
@php
    // Group movements by product id
    $grouped = $movements->groupBy('finished_product_id');
@endphp

<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-box-seam" style="color:var(--accent)"></i>
            Products Delivered
        </span>
        <span style="font-size:.68rem;color:var(--text-muted)">{{ $grouped->count() }} product(s)</span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Product</th>
                    <th class="text-center" style="width:15%">Qty Deployed</th>
                    <th class="text-center" style="width:15%">Extra / Free</th>
                    <th class="text-end" style="width:15%">Total Qty</th>
                </tr>
            </thead>
            <tbody>
            @php $rowNum = 1; $grandTotal = 0; @endphp
            @foreach($grouped as $fpId => $mvs)
                @php
                    $delivered = $mvs->where('movement_type', 'transfer_out')->sum('quantity');
                    $extra     = $mvs->where('movement_type', 'extra_free')->sum('quantity');
                    $rowTotal  = $delivered + $extra;
                    $grandTotal += $rowTotal;
                    $product   = $mvs->first()->finishedProduct;
                @endphp
                <tr>
                    <td style="color:var(--text-muted);font-size:.68rem;text-align:center">{{ $rowNum++ }}</td>
                    <td>
                        <span style="font-weight:600;color:var(--text-primary)">{{ $product->name }}</span>
                        @if($product->sku)
                            <div style="font-size:.67rem;color:var(--text-muted)">{{ $product->sku }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-weight:700;font-size:.84rem">
                        {{ number_format($delivered, 0) }}
                    </td>
                    <td class="text-center">
                        @if($extra > 0)
                            <span class="pill pill-info">+{{ number_format($extra, 0) }}</span>
                        @else
                            <span style="color:var(--text-muted);font-size:.75rem">—</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-weight:700;font-size:.84rem;color:var(--accent)">
                        {{ number_format($rowTotal, 0) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Total Qty Delivered</td>
                    <td class="text-end">{{ number_format($grandTotal, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection