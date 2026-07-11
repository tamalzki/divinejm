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

    .btn-record-sales-h { display:inline-flex; align-items:center; gap:.32rem; padding:.38rem .9rem; border-radius:6px; border:none; font-size:.80rem; font-weight:700; cursor:pointer; text-decoration:none !important; background:var(--accent); color:#fff !important; white-space:nowrap; box-shadow:0 1px 3px rgba(0,0,0,.06); transition:background .12s; }
    .btn-record-sales-h:hover { background:var(--accent-hover); color:#fff !important; }
    .btn-deliveries-h { display:inline-flex; align-items:center; gap:.28rem; padding:.36rem .75rem; border-radius:6px; border:1px solid var(--border); font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none !important; background:var(--bg-card); color:var(--text-secondary) !important; white-space:nowrap; }
    .btn-deliveries-h:hover { background:var(--bg-page); color:var(--text-primary) !important; }

    .dj-save-banner { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:.75rem 1rem; padding:.95rem 1.1rem; margin-bottom:1rem; border-radius:var(--radius); border:1px solid var(--s-success-text); background:var(--s-success-bg); color:var(--s-success-text); }
    .dj-save-banner strong { font-size:.84rem; }
    .dj-save-banner-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }

    .alert-flash { padding:.62rem .9rem; border-radius:var(--radius); margin-bottom:1rem; font-size:.8rem; font-weight:600; border:1px solid var(--s-success-text); background:var(--s-success-bg); color:var(--s-success-text); }

    .btn-print-h { display:inline-flex; align-items:center; gap:.28rem; padding:.36rem .75rem; border-radius:6px; border:1px solid var(--border); font-size:.78rem; font-weight:600; cursor:pointer; background:#16a34a; color:#fff !important; white-space:nowrap; }
    .btn-print-h:hover { background:#128a3e; }

    /* Printable DR — mirrors the paper Deliver Receipt pad; hidden on screen, shown only when printing */
    .dr-print { display:none; }
    @media print {
        .no-print { display:none !important; }
        .dr-print { display:block; }
        body * { visibility:hidden; }
        .dr-print, .dr-print * { visibility:visible; }
        .dr-print { position:absolute; top:0; left:0; width:100%; }
    }
    .dr-print { font-family:Arial, Helvetica, sans-serif; color:#000; max-width:800px; margin:0 auto; padding:.5in; }
    .dr-print .dr-p-company { font-size:1.35rem; font-weight:800; letter-spacing:.5px; }
    .dr-print .dr-p-address { font-size:.72rem; margin-top:.15rem; }
    .dr-print .dr-p-title-row { display:flex; align-items:flex-end; justify-content:space-between; border-bottom:2px solid #000; padding-bottom:.4rem; margin:.6rem 0 .8rem; }
    .dr-print .dr-p-title { font-size:1.15rem; font-weight:800; letter-spacing:1px; }
    .dr-print .dr-p-no { font-size:1rem; font-weight:800; }
    .dr-print .dr-p-meta { font-size:.82rem; display:flex; flex-direction:column; gap:.3rem; margin-bottom:.7rem; }
    .dr-print .dr-p-meta-row { display:flex; gap:.4rem; border-bottom:1px solid #000; padding-bottom:.15rem; }
    .dr-print .dr-p-meta-row span:first-child { font-weight:700; white-space:nowrap; }
    .dr-print .dr-p-table { width:100%; border-collapse:collapse; font-size:.8rem; margin-top:.3rem; }
    .dr-print .dr-p-table th, .dr-print .dr-p-table td { border:1px solid #000; padding:.3rem .45rem; }
    .dr-print .dr-p-table th { font-size:.68rem; text-transform:uppercase; text-align:left; }
    .dr-print .dr-p-table td.tr, .dr-print .dr-p-table th.tr { text-align:right; }
    .dr-print .dr-p-table td.tc, .dr-print .dr-p-table th.tc { text-align:center; }
    .dr-print .dr-p-total-row td { font-weight:800; border-top:2px solid #000; }
    .dr-print .dr-p-footer { display:flex; justify-content:space-between; margin-top:1.4rem; font-size:.78rem; }
    .dr-print .dr-p-sig { border-top:1px solid #000; margin-top:2.2rem; padding-top:.2rem; width:230px; text-align:center; }

    @media (max-width: 640px) {
        .meta-grid { grid-template-columns:repeat(2,1fr); }
        .dj-back { align-self:flex-start; }
        .dr-header-left { flex-direction:column; align-items:flex-start; }
        .dj-save-banner { flex-direction:column; }
        .dj-save-banner-actions { width:100%; }
        .dj-save-banner-actions a { flex:1; justify-content:center; text-align:center; }
    }
</style>

@php
    $userNotes = null;
    if ($first->notes && str_contains($first->notes, ' | ')) {
        $userNotes = trim(explode(' | ', $first->notes, 2)[1] ?? null);
    }
@endphp

@if(session('success') && ! session('delivery_just_saved'))
    <div class="alert-flash"><i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}</div>
@endif

@if(session('delivery_just_saved'))
    <div class="dj-save-banner">
        <div style="flex:1;min-width:12rem">
            <strong>Delivery saved — {{ $customerName }} · DR# {{ $drNumber }}</strong>
            @if(session('success'))
                <div style="font-size:.76rem;margin-top:.25rem;opacity:.9">{{ session('success') }}</div>
            @else
                <div style="font-size:.76rem;margin-top:.25rem;opacity:.9">
                    Continue to Record sales below for {{ $customerName }} on DR# {{ $drNumber }} (quantities sold and payment).
                </div>
            @endif
        </div>
        <div class="dj-save-banner-actions">
            @if(isset($saleRecord) && $saleRecord)
                <a href="{{ route('sales.dr', $saleRecord) }}" class="btn-record-sales-h"
                   title="Open Record sales — {{ $customerName }} · DR# {{ $drNumber }}">
                    <i class="bi bi-pencil-square"></i> Record sales · {{ Str::limit($customerName, 32) }} · DR# {{ $drNumber }}
                </a>
            @endif
            <a href="{{ route('branch-inventory.index') }}" class="btn-deliveries-h">
                <i class="bi bi-list-ul"></i> Deliveries list
            </a>
        </div>
    </div>
@endif

{{-- Header --}}
<div class="dr-header-row d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="dr-header-left d-flex align-items-center gap-2">
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
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" onclick="window.print()" class="btn-print-h no-print">
            <i class="bi bi-printer"></i> Print
        </button>
        @if(isset($saleRecord) && $saleRecord)
            <a href="{{ route('sales.dr', $saleRecord) }}" class="btn-record-sales-h"
               title="Open Record sales — {{ $customerName }} · DR# {{ $drNumber }}">
                <i class="bi bi-pencil-square"></i> Record sales · {{ Str::limit($customerName, 28) }} · DR# {{ $drNumber }}
            </a>
        @endif
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

@if($boReplacementMovements->isNotEmpty())
<div class="dj-card" style="border-color:#dc2626">
    <div class="dj-card-header" style="background:#fef2f2">
        <span class="dj-card-title" style="color:#991b1b">
            <i class="bi bi-arrow-repeat"></i>
            BO Replaced — free, not billed
        </span>
        <span style="font-size:.68rem;color:#991b1b">{{ $boReplacementMovements->groupBy('finished_product_id')->count() }} product(s)</span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Product</th>
                    <th style="width:18%">Original DR#</th>
                    <th class="text-center" style="width:12%">Qty Replaced</th>
                    <th class="text-end" style="width:14%">Ref. Price</th>
                    <th class="text-end" style="width:14%">Ref. Amount</th>
                </tr>
            </thead>
            <tbody>
            @php $boRowNum = 1; $boGrandAmount = 0; @endphp
            @foreach($boReplacementMovements as $boMv)
                @php
                    $boAmount = (float) $boMv->quantity * (float) ($boMv->unit_price ?? 0);
                    $boGrandAmount += $boAmount;
                @endphp
                <tr>
                    <td style="color:var(--text-muted);font-size:.68rem;text-align:center">{{ $boRowNum++ }}</td>
                    <td><span style="font-weight:600;color:var(--text-primary)">{{ $boMv->finishedProduct->name ?? '—' }}</span></td>
                    <td style="font-size:.75rem;color:var(--text-secondary)">DR# {{ $boMv->sourceSaleItem->sale->dr_number ?? '—' }}</td>
                    <td class="text-center" style="font-weight:700;font-size:.84rem">{{ number_format($boMv->quantity, 0) }}</td>
                    <td class="text-end">&#8369;{{ number_format($boMv->unit_price ?? 0, 2) }}</td>
                    <td class="text-end" style="font-weight:600">&#8369;{{ number_format($boAmount, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end">Reference total (not billed)</td>
                    <td class="text-end">&#8369;{{ number_format($boGrandAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Printable Deliver Receipt — mirrors the paper DR pad; shown only when printing --}}
@php
    $saleItemsByProduct = isset($saleRecord) && $saleRecord ? $saleRecord->items->keyBy('finished_product_id') : collect();
    $printGrandAmount = 0;
@endphp
<div class="dr-print">
    <div class="dr-p-company">DIVINE JM FOODS</div>
    <div class="dr-p-address">Km. 7.5, Cabantian Road, Cabantian, Davao City &bull; Cellphone Nos. 0933-8625893 / 0933-8625894</div>

    <div class="dr-p-title-row">
        <div class="dr-p-title">DELIVER RECEIPT</div>
        <div class="dr-p-no">No. {{ $drNumber }}</div>
    </div>

    <div class="dr-p-meta">
        <div class="dr-p-meta-row"><span>Date:</span><span>{{ $first->movement_date->format('n/j/y') }}</span></div>
        <div class="dr-p-meta-row"><span>To:</span><span>{{ $customerName }}</span></div>
        <div class="dr-p-meta-row"><span>Address:</span><span>{{ $branch->address ?? $branch->name ?? '—' }}</span></div>
        <div class="dr-p-meta-row"><span>Terms:</span><span>&nbsp;</span></div>
    </div>

    <table class="dr-p-table">
        <thead>
            <tr>
                <th class="tc" style="width:10%">Qty.</th>
                <th style="width:10%">Unit</th>
                <th>Description</th>
                <th class="tr" style="width:14%">U/P</th>
                <th class="tr" style="width:16%">Amount</th>
            </tr>
        </thead>
        <tbody>
        @foreach($grouped as $fpId => $mvs)
            @php
                $delivered = $mvs->where('movement_type', 'transfer_out')->sum('quantity');
                $extra     = $mvs->where('movement_type', 'extra_free')->sum('quantity');
                $product   = $mvs->first()->finishedProduct;
                $saleItem  = $saleItemsByProduct->get($fpId);
                $unitPrice = (float) ($saleItem->unit_price ?? $product->selling_price ?? 0);
                $amount    = $delivered * $unitPrice;
                $printGrandAmount += $amount;
            @endphp
            <tr>
                <td class="tc">{{ number_format($delivered, 0) }}</td>
                <td class="tc">&nbsp;</td>
                <td>
                    {{ $product->name }}
                    @if($extra > 0)
                        <span style="font-size:.7rem"> (+{{ number_format($extra, 0) }} free)</span>
                    @endif
                </td>
                <td class="tr">{{ number_format($unitPrice, 2) }}</td>
                <td class="tr">{{ number_format($amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="dr-p-total-row">
                <td colspan="4" class="tr">TOTAL &nbsp;&#8369;</td>
                <td class="tr">{{ number_format($printGrandAmount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($boReplacementMovements->isNotEmpty())
    <div style="font-size:.85rem; font-weight:800; margin-top:.6rem">BO REPLACED (FREE — NOT BILLED)</div>
    <table class="dr-p-table" style="margin-top:.2rem">
        <thead>
            <tr>
                <th class="tc" style="width:10%">Qty.</th>
                <th>Description</th>
                <th style="width:18%">Orig. DR#</th>
                <th class="tr" style="width:14%">Ref. U/P</th>
                <th class="tr" style="width:16%">Ref. Amount</th>
            </tr>
        </thead>
        <tbody>
        @php $boPrintTotal = 0; @endphp
        @foreach($boReplacementMovements as $boMv)
            @php
                $boPrintAmount = (float) $boMv->quantity * (float) ($boMv->unit_price ?? 0);
                $boPrintTotal += $boPrintAmount;
            @endphp
            <tr>
                <td class="tc">{{ number_format($boMv->quantity, 0) }}</td>
                <td>{{ $boMv->finishedProduct->name ?? '—' }}</td>
                <td>{{ $boMv->sourceSaleItem->sale->dr_number ?? '—' }}</td>
                <td class="tr">{{ number_format($boMv->unit_price ?? 0, 2) }}</td>
                <td class="tr">{{ number_format($boPrintAmount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="dr-p-total-row">
                <td colspan="4" class="tr">REF. TOTAL &nbsp;&#8369;</td>
                <td class="tr">{{ number_format($boPrintTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="dr-p-meta-row" style="border-bottom:1px solid #000; padding-bottom:.15rem; margin-top:.6rem; font-size:.82rem">
        <span style="font-weight:700">Note:</span><span>{{ $userNotes ?? '' }}</span>
    </div>

    <div class="dr-p-footer">
        <div>Received the above goods in good order &amp; condition.</div>
    </div>
    <div style="display:flex; justify-content:flex-end">
        <div class="dr-p-sig">Authorized Signature</div>
    </div>
</div>

@if(session('auto_print'))
<script>
    window.addEventListener('load', function() {
        window.print();
    });
</script>
@endif

@endsection