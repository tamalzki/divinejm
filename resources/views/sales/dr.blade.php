@extends('layouts.sidebar')
@section('page-title', 'Record Sales — DR# ' . $sale->dr_number)
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); }
    .dj-back:hover { background:var(--bg-page); }
    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); flex-wrap:wrap; gap:.5rem; }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }
    .dr-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .dr-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.85); font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.48rem .75rem; white-space:nowrap; border:none; }
    .dr-table tbody td { padding:.48rem .75rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dr-table tbody tr:last-child td { border-bottom:none; }
    .dr-table tfoot td { padding:.48rem .75rem; font-size:.78rem; vertical-align:middle; }
    .dr-table tfoot tr.dr-tfoot-sub td {
        background:#f1f5f9; color:#334155; font-weight:600;
        border-top:1px solid #e2e8f0;
    }
    .dr-table tfoot tr.dr-tfoot-sub td:first-child { color:#64748b; font-weight:500; font-size:.74rem; }
    .dr-table tfoot tr.dr-tfoot-less td {
        background:#fff; color:#334155; font-weight:500;
        border-top:1px solid #e2e8f0;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.6);
    }
    .dr-table tfoot tr.dr-tfoot-less td.dr-tfoot-less-label { color:#64748b; font-size:.72rem; }
    .dr-table tfoot tr.dr-tfoot-total td {
        background:#e8edf3; color:#334155; font-weight:700;
        border-top:1px solid #cbd5e1;
    }
    .dr-table tfoot tr.dr-tfoot-total td.dr-tfoot-total-amt { color:var(--accent); font-size:.9rem; }
    .qty-input { width:70px; padding:.22rem .4rem; font-size:.80rem; border:1px solid var(--border); border-radius:4px; text-align:center; background:var(--bg-card); color:var(--text-primary); }
    .qty-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 2px rgba(59,91,219,.1); }
    .btn-all-sold { font-size:.66rem; padding:.16rem .42rem; border-radius:4px; border:1px solid var(--s-success-text); color:var(--s-success-text); background:transparent; cursor:pointer; white-space:nowrap; }
    .btn-all-sold:hover { background:var(--s-success-bg); }
    .btn-all-sold.done { border-color:var(--border); color:var(--text-muted); pointer-events:none; }
    .status-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; margin-bottom:.85rem; }
    .status-option { display:none; }
    .status-label { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.3rem; padding:.65rem .5rem; border:2px solid var(--border); border-radius:6px; cursor:pointer; font-size:.74rem; font-weight:600; color:var(--text-muted); text-align:center; transition:all .12s; user-select:none; }
    .status-label:hover { border-color:var(--text-muted); background:var(--bg-page); }
    .status-option.s-paid:checked    + .status-label { border-color:var(--s-success-text); background:var(--s-success-bg); color:var(--s-success-text); }
    .status-option.s-partial:checked + .status-label { border-color:var(--s-warning-text); background:var(--s-warning-bg); color:var(--s-warning-text); }
    .status-option.s-collect:checked + .status-label { border-color:var(--s-danger-text);  background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .status-grid.is-error .status-label { border-color:var(--s-danger-text); }
    .status-required-msg { display:none; font-size:.70rem; color:var(--s-danger-text); margin-top:-.5rem; margin-bottom:.65rem; }
    .status-required-msg.show { display:block; }
    .pay-grid   { display:grid; grid-template-columns:repeat(3,1fr); gap:.85rem; margin-bottom:.85rem; }
    .pay-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
    .field-group { display:flex; flex-direction:column; gap:.2rem; }
    .field-label { font-size:.72rem; font-weight:600; color:var(--text-primary); }
    .field-label span { color:var(--s-danger-text); }
    .field-input { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .field-input.is-error { border-color:var(--s-danger-text) !important; background:var(--s-danger-bg); }
    .field-select { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-select:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .field-select.is-error { border-color:var(--s-danger-text) !important; background:var(--s-danger-bg); }
    .field-error { font-size:.68rem; color:var(--s-danger-text); margin-top:.15rem; display:none; }
    .field-error.show { display:block; }
    textarea.field-input { resize:vertical; min-height:68px; }
    .section-label { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); display:flex; align-items:center; gap:.4rem; margin-bottom:.65rem; }
    .section-label::after { content:''; flex:1; height:1px; background:var(--border); }
    .form-actions { display:flex; align-items:center; gap:.6rem; padding:.85rem 1.1rem; border-top:1px solid var(--border); background:var(--bg-page); }
    .btn-submit { padding:.38rem 1.4rem; font-size:.82rem; font-weight:600; border-radius:5px; background:var(--accent); color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.35rem; }
    .btn-submit:hover { background:var(--accent-hover); }
    .btn-cancel { padding:.38rem .9rem; font-size:.78rem; border-radius:5px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-cancel:hover { background:var(--bg-page); }
    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }
    .pill-info    { background:var(--s-info-bg);    color:var(--s-info-text); }
    .meta-strip { display:flex; align-items:center; gap:1rem; font-size:.72rem; color:var(--text-muted); flex-wrap:wrap; }
    .meta-strip strong { color:var(--text-primary); }
    .validation-banner { display:none; background:var(--s-danger-bg); border:1px solid var(--s-danger-text); color:var(--s-danger-text); border-radius:var(--radius); padding:.55rem .9rem; font-size:.78rem; font-weight:600; margin-bottom:.75rem; align-items:center; gap:.4rem; }
    .validation-banner.show { display:flex; }
    .col-subtext { font-size:.58rem; font-weight:400; opacity:.72; text-transform:none; letter-spacing:0; display:block; margin-top:.1rem; }

    .less-compact-cell { padding: .32rem .75rem !important; vertical-align: middle; }
    .less-compact-inner { display: flex; flex-direction: column; gap: .35rem; align-items: stretch; max-width: 100%; }
    .less-compact-top {
        display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .5rem;
    }
    .less-amt-inline { display: inline-flex; align-items: center; gap: .2rem; flex: 0 0 auto; }
    .less-currency { color: #64748b; font-size: .68rem; font-weight: 600; }
    .less-inline-input {
        width: 5.25rem; max-width: 100%; text-align: right; font-size: .76rem; font-weight: 700;
        padding: .2rem .4rem; border-radius: 4px; border: 1px solid var(--border);
        background: var(--bg-card); color: var(--s-danger-text);
    }
    .less-inline-input:focus { outline: none; border-color: var(--s-danger-text); box-shadow: 0 0 0 2px rgba(220,38,38,.12); }
    .less-inline-input::placeholder { color: var(--text-muted); }
    .less-inline-textarea {
        flex: 1 1 12rem; min-width: 0; min-height: 36px; max-height: 120px; font-size: .74rem; resize: vertical;
        line-height: 1.35; padding: .28rem .45rem; border-radius: 4px; border: 1px solid var(--border);
        background: var(--bg-card); color: var(--text-primary);
    }
    .less-inline-textarea::placeholder { color: var(--text-muted); }
    .less-inline-textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(59,91,219,.1); }
    /* Keep inputs readable on the light footer band even in dark UI themes */
    .dr-table tfoot tr.dr-tfoot-less .less-inline-input {
        background: #fafafa; border-color: #cbd5e1; color: #b91c1c;
    }
    .dr-table tfoot tr.dr-tfoot-less .less-inline-textarea {
        background: #fafafa; border-color: #cbd5e1; color: #1e293b;
    }
    .dr-table tfoot tr.dr-tfoot-less .less-inline-textarea::placeholder { color: #94a3b8; }
    .dr-table tfoot tr.dr-tfoot-less .less-inline-input::placeholder { color: #94a3b8; }

    .history-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); margin-bottom:1rem; overflow:hidden; }
    .history-card > summary { padding:.55rem 1rem; font-size:.74rem; font-weight:700; color:var(--text-primary); cursor:pointer; list-style:none; display:flex; align-items:center; justify-content:space-between; gap:.5rem; background:var(--bg-page); border-bottom:1px solid var(--border); }
    .history-card > summary::-webkit-details-marker { display:none; }
    .history-card > summary::after { content:'\25BC'; font-size:.55rem; color:var(--text-muted); transition:transform .15s; }
    .history-card[open] > summary::after { transform:rotate(-180deg); }
    .history-body { padding:.65rem 1rem; max-height:280px; overflow-y:auto; font-size:.72rem; }
    .history-block { border-bottom:1px solid var(--border); padding:.5rem 0; }
    .history-block:last-child { border-bottom:none; }
    .history-meta { font-size:.65rem; color:var(--text-muted); margin-bottom:.35rem; }
    .history-mini { width:100%; border-collapse:collapse; font-size:.68rem; }
    .history-mini th { text-align:left; font-weight:600; color:var(--text-secondary); padding:.2rem .35rem .2rem 0; border:none; }
    .history-mini td { padding:.2rem .35rem .2rem 0; border:none; vertical-align:top; }
    .btn-del-dr-h { font-size:.68rem; padding:.22rem .55rem; border-radius:4px; border:1px solid #dc2626; color:#dc2626; background:transparent; cursor:pointer; white-space:nowrap; }
    .btn-del-dr-h:hover { background:#fef2f2; }
</style>

<div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)]) }}" class="dj-back">
            <i class="bi bi-arrow-left"></i> {{ $sale->customer_name }}
        </a>
        <div>
            <h5 class="fw-bold mb-0" style="font-size:.93rem">
                <i class="bi bi-pencil-square me-1" style="color:var(--accent)"></i>
                Record Sales &mdash; DR# <span style="color:var(--accent)">{{ $sale->dr_number }}</span>
            </h5>
            <div class="meta-strip mt-1">
                <span>{{ $sale->branch->name }}</span>
                <span>&rarr; <strong>{{ $sale->customer_name }}</strong></span>
                <span><i class="bi bi-calendar3"></i> {{ $sale->sale_date->format('M d, Y') }}</span>
                @if($sale->payment_status === 'paid')
                    <span class="pill pill-success">Paid</span>
                @elseif($sale->payment_status === 'partial')
                    <span class="pill pill-warning">Partial</span>
                @else
                    <span class="pill pill-danger">To Collect</span>
                @endif
            </div>
        </div>
    </div>
    <form method="POST" action="{{ route('sales.destroy', $sale->id) }}" class="flex-shrink-0"
          onsubmit="return confirm('Delete this DR entirely?\n\n• Removes this DR and all sold / BO / payment / less data on it\n• Restores main warehouse, batches, and area stock from delivery movements\n\nContinue only if area still holds enough stock for this delivery, or deletion may error.\n\nContinue?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-del-dr-h"><i class="bi bi-trash"></i> Delete DR</button>
    </form>
</div>

<div class="validation-banner" id="validationBanner">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span id="validationMsg">Please fill in all required fields before saving.</span>
</div>

<form action="{{ route('sales.drUpdate', $sale->id) }}" method="POST" id="drForm" novalidate>
@csrf
@method('PATCH')

<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-box-seam" style="color:var(--accent)"></i> Products Sold
        </span>
        <button type="button" class="btn-all-sold" onclick="soldOutAll()">
            <i class="bi bi-check2-all"></i> Mark All Sold
        </button>
    </div>
    <div style="overflow-x:auto">
        <table class="dr-table">
            <thead>
                <tr>
                    <th style="min-width:160px">Product</th>
                    <th class="text-end">Deployed</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-center">
                        Unsold
                        <span class="col-subtext">returned</span>
                    </th>
                    <th class="text-center">
                        BO
                        <span class="col-subtext">damaged</span>
                    </th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Collectible</th>
                    <th class="text-center">Quick Fill</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sale->items as $i => $item)
            @php $isSoldOut = ($item->quantity_unsold == 0 && $item->quantity_sold > 0); @endphp
            <tr id="irow-{{ $item->id }}">
                <td>
                    <div style="font-weight:600;font-size:.82rem">{{ $item->finishedProduct->name ?? '---' }}</div>
                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                </td>
                <td class="text-end" style="font-weight:700">{{ number_format($item->quantity_deployed, 0) }}</td>
                {{-- Qty Sold --}}
                <td class="text-center">
                    <input type="number"
                           name="items[{{ $i }}][quantity_sold]"
                           class="qty-input js-sold"
                           id="sold-{{ $item->id }}"
                           value="{{ old('items.'.$i.'.quantity_sold', (int) $item->quantity_sold) }}"
                           min="0" max="{{ $item->quantity_deployed }}" step="1"
                           data-deployed="{{ $item->quantity_deployed }}"
                           data-price="{{ $item->unit_price }}"
                           data-item="{{ $item->id }}"
                           oninput="recalcRow({{ $item->id }}, {{ $item->unit_price }})">
                </td>
                {{-- Unsold --}}
                <td class="text-center">
                    <input type="number"
                           name="items[{{ $i }}][quantity_unsold]"
                           class="qty-input"
                           id="unsold-{{ $item->id }}"
                           value="{{ old('items.'.$i.'.quantity_unsold', (int) $item->quantity_unsold) }}"
                           min="0" max="{{ $item->quantity_deployed }}" step="1"
                           data-item="{{ $item->id }}"
                           style="border-color:var(--s-warning-text)"
                           oninput="onTrackingChange({{ $item->id }}, {{ $item->unit_price }})">
                    <div style="font-size:.60rem;color:var(--s-warning-text);margin-top:.1rem">tracking only</div>
                </td>
                {{-- BO --}}
                <td class="text-center">
                    <input type="number"
                           name="items[{{ $i }}][quantity_bo]"
                           class="qty-input"
                           id="bo-{{ $item->id }}"
                           value="{{ old('items.'.$i.'.quantity_bo', (int) $item->quantity_bo) }}"
                           min="0" max="{{ $item->quantity_deployed }}" step="1"
                           data-item="{{ $item->id }}"
                           style="border-color:var(--s-danger-text)"
                           oninput="onTrackingChange({{ $item->id }}, {{ $item->unit_price }})">
                    <div style="font-size:.60rem;color:var(--s-danger-text);margin-top:.1rem">tracking only</div>
                </td>
                <td class="text-end">&#8369;{{ number_format($item->unit_price, 2) }}</td>
                {{-- Collectible --}}
                <td class="text-end" id="subtotal-{{ $item->id }}">
                    <strong>&#8369;{{ number_format($item->subtotal, 2) }}</strong>
                </td>
                {{-- Quick fill --}}
                <td class="text-center">
                    <button type="button"
                            class="btn-all-sold {{ $isSoldOut ? 'done' : '' }}"
                            id="btn-row-{{ $item->id }}"
                            onclick="soldOutRow({{ $item->id }}, {{ $item->quantity_deployed }}, {{ $item->unit_price }})">
                        <i class="bi bi-check2"></i> All Sold
                    </button>
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="dr-tfoot-sub">
                    <td colspan="6" class="text-end">Subtotal (Sold × Price)</td>
                    <td class="text-end" id="subtotalRow">&#8369;{{ number_format($sale->total_amount, 2) }}</td>
                    <td></td>
                </tr>
                <tr class="dr-tfoot-less">
                    <td colspan="5" class="text-end less-compact-cell dr-tfoot-less-label">Less / Deduction</td>
                    <td colspan="3" class="less-compact-cell">
                        <div class="less-compact-inner">
                            <div class="less-compact-top">
                                <div class="less-amt-inline">
                                    <label class="visually-hidden" for="lessAmount">Less amount pesos</label>
                                    <span class="less-currency" aria-hidden="true">&#8369;</span>
                                    <input type="number"
                                           name="less_amount"
                                           id="lessAmount"
                                           class="less-inline-input"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           inputmode="decimal"
                                           value="{{ old('less_amount', ($sale->less_amount ?? 0) > 0 ? number_format((float) $sale->less_amount, 2, '.', '') : '') }}"
                                           oninput="recalcGrandTotal()">
                                </div>
                                <label class="visually-hidden" for="lessNotesInput">Less reason</label>
                                <textarea name="less_notes"
                                          id="lessNotesInput"
                                          class="less-inline-textarea"
                                          rows="2"
                                          title="Common reasons: Bad Order, Returned – Unsold, Damaged in Transit, Promo Deduction, Shortage, Expired"
                                          placeholder="e.g. Bad Order, Returned – Unsold, Damaged in Transit, Promo Deduction, Shortage, Expired">{{ old('less_notes', $sale->less_notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="dr-tfoot-total">
                    <td colspan="6" class="text-end">Total Collectible</td>
                    <td class="text-end dr-tfoot-total-amt" id="grandTotal">&#8369;{{ number_format(max(0, $sale->total_amount - ($sale->less_amount ?? 0)), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Payment section --}}
<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-wallet2" style="color:var(--accent)"></i> Payment
        </span>
    </div>
    <div style="padding:1rem 1.1rem">
        <div class="section-label"><i class="bi bi-toggles"></i> Payment Status <span style="color:var(--s-danger-text)">*</span></div>
        <div class="status-grid" id="statusGrid">
            <div>
                <input type="radio" name="payment_status_override" id="s-paid" class="status-option s-paid" value="paid" {{ old('payment_status_override', $sale->payment_status) === 'paid' ? 'checked' : '' }}>
                <label for="s-paid" class="status-label">
                    <i class="bi bi-check-circle-fill" style="font-size:1.1rem"></i> Money Received
                </label>
            </div>
            <div>
                <input type="radio" name="payment_status_override" id="s-partial" class="status-option s-partial" value="partial" {{ old('payment_status_override', $sale->payment_status) === 'partial' ? 'checked' : '' }}>
                <label for="s-partial" class="status-label">
                    <i class="bi bi-dash-circle-fill" style="font-size:1.1rem"></i> Partial Payment
                </label>
            </div>
            <div>
                <input type="radio" name="payment_status_override" id="s-collect" class="status-option s-collect" value="to_be_collected" {{ old('payment_status_override', $sale->payment_status) === 'to_be_collected' ? 'checked' : '' }}>
                <label for="s-collect" class="status-label">
                    <i class="bi bi-clock-fill" style="font-size:1.1rem"></i> For Collection
                </label>
            </div>
        </div>
        <div class="status-required-msg" id="statusRequiredMsg">
            <i class="bi bi-exclamation-circle"></i> Please select a payment status.
        </div>
        <div id="paymentInfoBlock">
            <div class="section-label"><i class="bi bi-cash-stack"></i> Payment Details</div>
            <div class="pay-grid">
                <div class="field-group">
                    <label class="field-label">Amount Received <span>*</span></label>
                    <input type="number" name="amount_paid" id="amountPaid" class="field-input"
                           value="{{ old('amount_paid', $sale->amount_paid > 0 ? number_format($sale->amount_paid, 2, '.', '') : '') }}" step="0.01" min="0" placeholder="0.00">
                    <span class="field-error" id="amountPaidError">Please enter the amount received.</span>
                </div>
                <div class="field-group">
                    <label class="field-label">Payment Method <span>*</span></label>
                    <select name="payment_mode" id="paymentMode" class="field-select">
                        <option value="">-- Select method --</option>
                        <option value="cash"          {{ old('payment_mode', $sale->payment_mode) === 'cash'          ? 'selected' : '' }}>Cash</option>
                        <option value="gcash"         {{ old('payment_mode', $sale->payment_mode) === 'gcash'         ? 'selected' : '' }}>GCash</option>
                        <option value="cheque"        {{ old('payment_mode', $sale->payment_mode) === 'cheque'        ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ old('payment_mode', $sale->payment_mode) === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="other"         {{ old('payment_mode', $sale->payment_mode) === 'other'         ? 'selected' : '' }}>Other</option>
                    </select>
                    <span class="field-error" id="paymentModeError">Please select a payment method.</span>
                </div>
                <div class="field-group">
                    <label class="field-label">Payment Date <span>*</span></label>
                    <input type="date" name="payment_date" id="paymentDate" class="field-input" value="{{ old('payment_date', $sale->payment_date ? $sale->payment_date->format('Y-m-d') : '') }}">
                    <span class="field-error" id="paymentDateError">Please select a payment date.</span>
                </div>
            </div>
            <div class="pay-grid-2">
                <div class="field-group">
                    <label class="field-label">Reference Number</label>
                    <input type="text" name="payment_reference" class="field-input"
                           value="{{ old('payment_reference', $sale->payment_reference) }}"
                           placeholder="GCash ref, cheque no., etc.">
                </div>
                <div class="field-group">
                    <label class="field-label">Remarks / Notes</label>
                    <textarea name="notes" class="field-input" placeholder="Optional notes...">{{ old('notes', $sale->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="button" class="btn-submit" onclick="submitForm()">
            <i class="bi bi-check-circle"></i> Record Sales
        </button>
        <a href="{{ route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)]) }}" class="btn-cancel">
            <i class="bi bi-x"></i> Cancel
        </a>
    </div>
</div>

</form>

@if(isset($recordHistories) && $recordHistories->isNotEmpty())
<details class="history-card">
    <summary>
        <span><i class="bi bi-clock-history me-1" style="color:var(--accent)"></i>Save history <span style="font-weight:500;color:var(--text-muted)">({{ $recordHistories->count() }})</span></span>
        <span style="font-size:.62rem;font-weight:500;color:var(--text-muted)">Each row is a snapshot after you saved</span>
    </summary>
    <div class="history-body">
        @foreach($recordHistories as $h)
        <div class="history-block">
            <div class="history-meta">
                {{ $h->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                @if($h->user)
                    &middot; {{ $h->user->name }}
                @endif
                @if($h->payment_status_snapshot)
                    &middot; <span class="text-capitalize">{{ str_replace('_', ' ', $h->payment_status_snapshot) }}</span>
                @endif
                @if($h->total_amount !== null)
                    &middot; Total &#8369;{{ number_format($h->total_amount, 2) }}
                @endif
            </div>
            <table class="history-mini">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Deployed</th>
                        <th class="text-end">Sold</th>
                        <th class="text-end">Unsold</th>
                        <th class="text-end">BO</th>
                        <th class="text-end">Collectible</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($h->lines as $line)
                    <tr>
                        <td>{{ $line['product'] ?? '—' }}</td>
                        <td class="text-end">{{ number_format($line['deployed'] ?? 0, 0) }}</td>
                        <td class="text-end">{{ number_format($line['sold'] ?? 0, 0) }}</td>
                        <td class="text-end">{{ number_format($line['unsold'] ?? 0, 0) }}</td>
                        <td class="text-end">{{ number_format($line['bo'] ?? 0, 0) }}</td>
                        <td class="text-end">&#8369;{{ number_format($line['collectible'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    </div>
</details>
@endif

<script>
function recalcRow(itemId, unitPrice) {
    var sold     = parseFloat(document.getElementById('sold-' + itemId).value) || 0;
    var deployed = parseFloat(document.querySelector('#sold-' + itemId).dataset.deployed) || 0;
    var subtotal = sold * unitPrice;
    document.getElementById('subtotal-' + itemId).innerHTML = '<strong>&#8369;' + fmtNum(subtotal) + '</strong>';
    var rowBtn = document.getElementById('btn-row-' + itemId);
    if (rowBtn) rowBtn.classList.toggle('done', sold >= deployed && sold > 0);
    recalcGrandTotal();
}

function recalcSubtotalRow() {
    var sum = 0;
    document.querySelectorAll('[id^="subtotal-"] strong').forEach(function(el) {
        sum += parseFloat(el.textContent.replace(/[^\d.]/g,'')) || 0;
    });
    var el = document.getElementById('subtotalRow');
    if (el) el.textContent = '\u20B1' + fmtNum(sum);
    return sum;
}

function recalcGrandTotal() {
    var subtotal = recalcSubtotalRow();
    var less     = parseFloat(document.getElementById('lessAmount').value) || 0;
    var total    = Math.max(0, subtotal - less);
    var gtEl     = document.getElementById('grandTotal');
    if (gtEl) gtEl.textContent = '\u20B1' + fmtNum(total);
}

function onTrackingChange(itemId, unitPrice) {
    recalcRow(itemId, unitPrice);
}

function soldOutRow(itemId, deployed, unitPrice) {
    document.getElementById('sold-' + itemId).value = deployed;
    recalcRow(itemId, unitPrice);
}

function soldOutAll() {
    document.querySelectorAll('.js-sold').forEach(function(input) {
        var itemId = input.dataset.item;
        var price  = parseFloat(input.dataset.price) || 0;
        input.value = parseFloat(input.dataset.deployed);
        recalcRow(itemId, price);
    });
}

function togglePaymentFields() {
    var selected = document.querySelector('input[name="payment_status_override"]:checked');
    var status   = selected ? selected.value : '';
    var payBlock = document.getElementById('paymentInfoBlock');
    if (payBlock) payBlock.style.display = (status === 'to_be_collected' || status === '') ? 'none' : '';
    clearErrors();
}

function clearErrors() {
    document.querySelectorAll('.is-error').forEach(function(el) { el.classList.remove('is-error'); });
    document.querySelectorAll('.field-error').forEach(function(el) { el.classList.remove('show'); });
    document.getElementById('statusRequiredMsg').classList.remove('show');
    document.getElementById('statusGrid').classList.remove('is-error');
    document.getElementById('validationBanner').classList.remove('show');
}

function submitForm() {
    clearErrors();
    var errors = [];
    var selected = document.querySelector('input[name="payment_status_override"]:checked');
    if (!selected) {
        document.getElementById('statusGrid').classList.add('is-error');
        document.getElementById('statusRequiredMsg').classList.add('show');
        errors.push('Payment status is required.');
    }
    var status = selected ? selected.value : '';
    if (status === 'paid' || status === 'partial') {
        var ap = document.getElementById('amountPaid');
        var pm = document.getElementById('paymentMode');
        var pd = document.getElementById('paymentDate');
        if (!ap.value || parseFloat(ap.value) < 0) {
            ap.classList.add('is-error');
            document.getElementById('amountPaidError').classList.add('show');
            errors.push('Amount received is required.');
        }
        if (!pm.value) {
            pm.classList.add('is-error');
            document.getElementById('paymentModeError').classList.add('show');
            errors.push('Payment method is required.');
        }
        if (!pd.value) {
            pd.classList.add('is-error');
            document.getElementById('paymentDateError').classList.add('show');
            errors.push('Payment date is required.');
        }
    }
    if (errors.length > 0) {
        var banner = document.getElementById('validationBanner');
        document.getElementById('validationMsg').textContent = errors[0];
        banner.classList.add('show');
        banner.scrollIntoView({ behavior:'smooth', block:'center' });
        return;
    }
    document.getElementById('drForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    togglePaymentFields();
    document.querySelectorAll('input[name="payment_status_override"]').forEach(function(r) {
        r.addEventListener('change', togglePaymentFields);
    });
    document.querySelectorAll('.field-input, .field-select').forEach(function(el) {
        el.addEventListener('input', function() { this.classList.remove('is-error'); });
        el.addEventListener('change', function() { this.classList.remove('is-error'); });
    });
    recalcGrandTotal();
});

function fmtNum(n) {
    return parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
</script>

@endsection