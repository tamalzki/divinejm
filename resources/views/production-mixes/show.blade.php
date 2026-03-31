@extends('layouts.sidebar')
@section('page-title', 'Production Batch #' . $mix->id)
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.73rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); transition:background .12s; }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    .stat-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:.65rem; margin-bottom:.9rem; }
    .stat-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .stat-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); display:block; margin-bottom:.18rem; }
    .stat-value { font-size:.92rem; font-weight:700; color:var(--text-primary); }
    .stat-value.accent { color:var(--accent); }
    .stat-value.green  { color:var(--s-success-text); }
    .stat-value.red    { color:var(--s-danger-text); }
    .stat-value.amber  { color:var(--s-warning-text); }

    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:.9rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.5rem .9rem; border-bottom:1px solid var(--border); background:var(--bg-page); }
    .dj-card-title { font-size:.76rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .data-table { width:100%; border-collapse:collapse; font-size:.79rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.48rem .85rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.45rem .85rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }
    .data-table tfoot td { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.48rem .85rem; font-size:.78rem; font-weight:700; }

    .cat-pill { display:inline-flex; align-items:center; gap:.25rem; padding:.08rem .38rem; border-radius:3px; font-size:.68rem; font-weight:600; }
    .cat-ingredient { background:var(--s-info-bg); color:var(--s-info-text); }
    .cat-packaging   { background:#f3e8ff; color:#7c3aed; }

    .rate-pill { display:inline-block; border-radius:4px; padding:.1rem .42rem; font-size:.69rem; font-weight:700; }
    .rate-ok   { background:var(--s-success-bg); color:var(--s-success-text); }
    .rate-mid  { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .rate-bad  { background:var(--s-danger-bg);  color:var(--s-danger-text); }

    .expiry-pill { display:inline-flex; align-items:center; gap:.25rem; border-radius:4px; padding:.1rem .45rem; font-size:.69rem; font-weight:700; }
    .expiry-ok      { background:var(--s-success-bg); color:var(--s-success-text); }
    .expiry-soon    { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .expiry-expired { background:var(--s-danger-bg);  color:var(--s-danger-text); }

    .note-bar { background:var(--accent-faint); border:1px solid #c7d2fe; border-radius:var(--radius); padding:.55rem .9rem; font-size:.78rem; color:var(--text-secondary); margin-bottom:.9rem; display:flex; align-items:center; gap:.4rem; }

    /* Reject notice */
    .reject-note { font-size:.67rem; color:var(--text-muted); margin-top:.1rem; }
</style>

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('production-mixes.index') }}" class="dj-back">
            <i class="bi bi-arrow-left"></i> Batches
        </a>
        <div>
            <h5 class="fw-bold mb-0" style="font-size:.9rem">
                <i class="bi bi-gear-wide-connected me-1" style="color:var(--accent)"></i>
                {{ $mix->finishedProduct->name }}
                <span style="color:var(--text-muted);font-weight:400;font-size:.78rem">· Batch #{{ $mix->id }}</span>
            </h5>
            <span style="font-size:.65rem;color:var(--text-muted)">
                {{ $mix->mix_date->format('F d, Y') }}
            </span>
        </div>
    </div>
</div>

@if($mix->notes)
<div class="note-bar">
    <i class="bi bi-chat-left-text" style="color:var(--accent)"></i>
    <strong>Notes:</strong> {{ $mix->notes }}
</div>
@endif

{{-- Stat tiles --}}
@php
    $rate = round($mix->rejection_rate ?? 0, 2);
    $expClass = 'expiry-ok'; $expLabel = '—';
    if ($mix->expiration_date) {
        if ($mix->expiration_date->isPast()) {
            $expClass = 'expiry-expired'; $expLabel = $mix->expiration_date->format('M d, Y');
        } elseif ($mix->expiration_date->diffInDays(now()) <= 7) {
            $expClass = 'expiry-soon'; $expLabel = $mix->expiration_date->format('M d, Y');
        } else {
            $expClass = 'expiry-ok'; $expLabel = $mix->expiration_date->format('M d, Y');
        }
    }
@endphp

<div class="stat-grid">
    <div class="stat-tile">
        <span class="stat-label">Standard Output</span>
        <span class="stat-value">{{ number_format($mix->expected_output, 0) }}</span>
        <div class="reject-note">Per recipe target</div>
    </div>
    <div class="stat-tile">
        <span class="stat-label">Actual Output</span>
        <span class="stat-value green">{{ number_format($mix->actual_output, 0) }}</span>
        <div class="reject-note">Added to warehouse stock</div>
    </div>
    <div class="stat-tile">
        <span class="stat-label">Rejects</span>
        <span class="stat-value {{ $mix->rejected_quantity > 0 ? 'red' : '' }}">
            {{ number_format($mix->rejected_quantity, 0) }}
        </span>
        <div class="reject-note">Documentation only</div>
    </div>
    <div class="stat-tile">
        <span class="stat-label">Reject Rate</span>
        <span class="stat-value">
            <span class="rate-pill {{ $rate == 0 ? 'rate-ok' : ($rate <= 5 ? 'rate-mid' : 'rate-bad') }}">
                {{ $rate }}%
            </span>
        </span>
    </div>
    <div class="stat-tile">
        <span class="stat-label">Cost / Unit</span>
        <span class="stat-value accent">&#8369;{{ number_format($mix->cost_per_unit, 4) }}</span>
        <div class="reject-note">Based on actual output</div>
    </div>
    <div class="stat-tile">
        <span class="stat-label">Expiry Date</span>
        <span class="stat-value" style="font-size:.78rem">
            @if($mix->expiration_date)
                <span class="expiry-pill {{ $expClass }}">
                    @if($expClass === 'expiry-expired')<i class="bi bi-x-circle"></i>
                    @elseif($expClass === 'expiry-soon')<i class="bi bi-exclamation-triangle"></i>
                    @else<i class="bi bi-check-circle"></i>@endif
                    {{ $expLabel }}
                </span>
            @else
                <span style="color:var(--text-muted)">—</span>
            @endif
        </span>
    </div>
</div>

{{-- Ingredients table --}}
<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-boxes" style="color:var(--accent)"></i>Raw Materials Used
        </span>
        <span style="font-size:.68rem;color:var(--text-muted)">
            Total Cost: <strong style="color:var(--text-primary)">&#8369;{{ number_format($mix->total_cost, 2) }}</strong>
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:35%">Material</th>
                    <th style="width:15%">Category</th>
                    <th style="width:10%">Unit</th>
                    <th class="text-end" style="width:13%">Qty Used</th>
                    <th class="text-end" style="width:13%">Cost/Unit</th>
                    <th class="text-end" style="width:14%">Line Cost</th>
                </tr>
            </thead>
            <tbody>
            @foreach($mix->ingredients as $ingredient)
            <tr>
                <td style="font-weight:600">{{ $ingredient->rawMaterial->name }}</td>
                <td>
                    @if($ingredient->rawMaterial->category === 'ingredient')
                        <span class="cat-pill cat-ingredient"><i class="bi bi-droplet-fill"></i>Ingredient</span>
                    @else
                        <span class="cat-pill cat-packaging"><i class="bi bi-box-seam"></i>Packaging</span>
                    @endif
                </td>
                <td style="color:var(--text-muted);font-size:.75rem">{{ $ingredient->rawMaterial->unit }}</td>
                <td class="text-end" style="font-weight:600">{{ number_format($ingredient->quantity_used, 4) }}</td>
                <td class="text-end" style="color:var(--text-muted);font-size:.75rem">&#8369;{{ number_format($ingredient->rawMaterial->unit_price, 4) }}</td>
                <td class="text-end" style="font-weight:700;color:var(--s-success-text)">
                    &#8369;{{ number_format($ingredient->quantity_used * $ingredient->rawMaterial->unit_price, 2) }}
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end">Total Raw Material Cost</td>
                    <td class="text-end">&#8369;{{ number_format($mix->total_cost, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection