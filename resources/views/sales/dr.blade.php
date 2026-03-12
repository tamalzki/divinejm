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
    .dr-table tfoot td { padding:.48rem .75rem; background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.78rem; font-weight:700; }
    .qty-input { width:70px; padding:.22rem .4rem; font-size:.80rem; border:1px solid var(--border); border-radius:4px; text-align:center; background:var(--bg-card); color:var(--text-primary); }
    .qty-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 2px rgba(59,91,219,.1); }
    .less-input { width:84px; padding:.22rem .4rem; font-size:.80rem; border:1.5px solid var(--border); border-radius:4px; text-align:right; background:var(--bg-card); color:var(--s-danger-text); font-weight:600; }
    .less-input:focus { outline:none; border-color:var(--s-danger-text); box-shadow:0 0 0 2px rgba(220,38,38,.1); }
    .less-input.has-value { border-color:var(--s-danger-text); background:var(--s-danger-bg); }
    .remarks-badge { display:inline-flex; align-items:center; gap:.2rem; font-size:.63rem; padding:.1rem .38rem; border-radius:3px; background:var(--bg-page); color:var(--text-muted); font-weight:600; cursor:pointer; border:1px solid var(--border); white-space:nowrap; }
    .remarks-badge:hover { background:var(--accent-light); color:var(--accent); border-color:#c7d2fe; }
    .remarks-badge.has-notes { background:var(--accent-light); color:var(--accent); border-color:#c7d2fe; }
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
    /* Remarks Modal */
    .dj-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; }
    .dj-modal-overlay.show { display:flex; }
    .dj-modal { background:var(--bg-card); border-radius:10px; padding:1.4rem 1.5rem; width:100%; max-width:420px; box-shadow:0 8px 32px rgba(0,0,0,.18); animation:modalIn .15s ease; }
    @keyframes modalIn { from { transform:scale(.96); opacity:0; } to { transform:scale(1); opacity:1; } }
    .dj-modal-title { font-size:.88rem; font-weight:700; color:var(--text-primary); margin-bottom:.2rem; }
    .dj-modal-sub { font-size:.72rem; color:var(--text-muted); margin-bottom:.75rem; }
    .dj-modal-actions { display:flex; gap:.5rem; justify-content:flex-end; margin-top:.9rem; }
    .btn-modal-cancel { padding:.32rem .8rem; background:var(--bg-page); border:1px solid var(--border); border-radius:5px; font-size:.78rem; font-weight:600; cursor:pointer; color:var(--text-secondary); }
    .btn-modal-cancel:hover { background:var(--border); }
    .btn-modal-ok { padding:.32rem .9rem; background:var(--accent); color:#fff; border:none; border-radius:5px; font-size:.78rem; font-weight:700; cursor:pointer; }
    .btn-modal-ok:hover { background:var(--accent-hover); }
    .reason-chips { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.65rem; }
    .reason-chip { padding:.18rem .52rem; border-radius:999px; font-size:.70rem; font-weight:600; border:1.5px solid var(--border); color:var(--text-secondary); background:var(--bg-card); cursor:pointer; transition:all .1s; }
    .reason-chip:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-light); }
    .reason-chip.active { border-color:var(--accent); color:var(--accent); background:var(--accent-light); }
    .col-subtext { font-size:.58rem; font-weight:400; opacity:.72; text-transform:none; letter-spacing:0; display:block; margin-top:.1rem; }
</style>

@if(session('success'))
<div class="alert-bar success"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert-bar danger"><i class="bi bi-exclamation-triangle-fill"></i>{{ $errors->first() }}</div>
@endif

<div class="d-flex align-items-center gap-2 mb-3">
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
                <tr style="background:var(--brand-deep);opacity:.7">
                    <td colspan="6" class="text-end" style="color:rgba(255,255,255,.7);font-size:.74rem;font-weight:500">Subtotal (Sold × Price)</td>
                    <td class="text-end" id="subtotalRow" style="color:rgba(255,255,255,.85);font-size:.78rem;font-weight:600">&#8369;{{ number_format($sale->total_amount, 2) }}</td>
                    <td></td>
                </tr>
                <tr style="background:#1e293b">
                    <td colspan="5" class="text-end" style="color:rgba(255,255,255,.75);font-size:.74rem;font-weight:500">Less / Deduction</td>
                    <td colspan="2" class="text-end" style="padding:.4rem .75rem">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.4rem">
                            <span style="color:rgba(255,255,255,.5);font-size:.72rem">&#8369;</span>
                            <input type="hidden" name="less_amount" id="lessAmount"
                                   value="{{ old('less_amount', $sale->less_amount ?? 0) ?: '' }}">
                            <input type="hidden" name="less_notes" id="lessNotesInput"
                                   value="{{ old('less_notes', $sale->less_notes ?? '') }}">
                            <button type="button" id="lessDisplayBtn"
                                onclick="openRemarksModal()"
                                style="min-width:110px;padding:.25rem .55rem;font-size:.82rem;font-weight:700;text-align:right;border:1.5px solid rgba(255,255,255,.25);border-radius:4px;background:rgba(255,255,255,.08);color:#fca5a5;cursor:pointer;display:inline-flex;align-items:center;gap:.3rem">
                                <span id="lessDisplayVal">{{ old('less_amount', $sale->less_amount ?? 0) > 0 ? '&#8369;'.number_format(old('less_amount', $sale->less_amount), 2) : 'Click to enter' }}</span>
                                <i class="bi bi-pencil-square" style="font-size:.65rem;opacity:.6"></i>
                            </button>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <tr style="background:var(--brand-deep)">
                    <td colspan="6" class="text-end" style="color:rgba(255,255,255,.88)">Total Collectible</td>
                    <td class="text-end" id="grandTotal" style="color:#fff;font-size:.9rem">&#8369;{{ number_format(max(0, $sale->total_amount - ($sale->less_amount ?? 0)), 2) }}</td>
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

{{-- Remarks Modal --}}
<div class="dj-modal-overlay" id="remarksModal">
    <div class="dj-modal">
        <div class="dj-modal-title"><i class="bi bi-dash-circle me-1" style="color:var(--s-danger-text)"></i> Less / Deduction</div>
        <div class="dj-modal-sub">Enter the deduction amount and reason for this DR.</div>
        <div style="margin-bottom:.75rem">
            <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.2rem">
                Less Amount (&#8369;) <span style="color:var(--s-danger-text)">*</span>
            </label>
            <input type="number" id="modalLessAmount" min="0" step="0.01" placeholder="0.00"
                style="width:100%;font-size:.88rem;font-weight:700;border:1.5px solid var(--border);border-radius:5px;padding:.35rem .6rem;color:var(--s-danger-text);background:var(--bg-card)">
        </div>
        <div style="margin-bottom:.5rem">
            <label style="font-size:.70rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:.35rem">Reason</label>
        </div>
        <div class="reason-chips">
            <span class="reason-chip" onclick="pickChip(this, 'Bad Order')">Bad Order</span>
            <span class="reason-chip" onclick="pickChip(this, 'Returned - Unsold')">Returned - Unsold</span>
            <span class="reason-chip" onclick="pickChip(this, 'Damaged in Transit')">Damaged in Transit</span>
            <span class="reason-chip" onclick="pickChip(this, 'Promo Deduction')">Promo Deduction</span>
            <span class="reason-chip" onclick="pickChip(this, 'Shortage')">Shortage</span>
            <span class="reason-chip" onclick="pickChip(this, 'Expired')">Expired</span>
        </div>
        <textarea id="remarksTextarea"
            style="width:100%;font-size:.80rem;border:1px solid var(--border);border-radius:5px;padding:.45rem .65rem;resize:vertical;min-height:80px;background:var(--bg-card);color:var(--text-primary);outline:none"
            placeholder="Type reason here, or pick one above..."></textarea>
        <div class="dj-modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeRemarksModal()">Cancel</button>
            <button type="button" class="btn-modal-ok" onclick="saveRemarks()">
                <i class="bi bi-check-lg"></i> Apply Deduction
            </button>
        </div>
    </div>
</div>

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
    var unsold = parseFloat(document.getElementById('unsold-' + itemId).value) || 0;
    var bo     = parseFloat(document.getElementById('bo-' + itemId).value) || 0;
    var notes  = document.getElementById('lessNotesInput').value.trim();
    if ((unsold > 0 || bo > 0) && !notes) openRemarksModal();
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

function openRemarksModal() {
    // Pre-fill modal with existing values
    var existingAmount = document.getElementById('lessAmount').value;
    var existingNotes  = document.getElementById('lessNotesInput').value;
    document.getElementById('modalLessAmount').value    = existingAmount || '';
    document.getElementById('remarksTextarea').value    = existingNotes;
    document.querySelectorAll('.reason-chip').forEach(function(c) { c.classList.remove('active'); });
    document.getElementById('remarksModal').classList.add('show');
    setTimeout(function() { document.getElementById('modalLessAmount').focus(); }, 80);
}

function closeRemarksModal() {
    document.getElementById('remarksModal').classList.remove('show');
}

function saveRemarks() {
    var amount = parseFloat(document.getElementById('modalLessAmount').value) || 0;
    var notes  = document.getElementById('remarksTextarea').value.trim();

    // Save to hidden inputs
    document.getElementById('lessAmount').value    = amount > 0 ? amount : '';
    document.getElementById('lessNotesInput').value = notes;

    // Update display button
    var displayVal = document.getElementById('lessDisplayVal');
    if (displayVal) {
        displayVal.innerHTML = amount > 0
            ? '₱' + fmtNum(amount) + (notes ? ' <span style="font-size:.62rem;opacity:.65">(' + notes.substring(0,20) + (notes.length>20?'...':'') + ')</span>' : '')
            : 'Click to enter';
    }

    recalcGrandTotal();
    closeRemarksModal();
}

function pickChip(el, text) {
    document.querySelectorAll('.reason-chip').forEach(function(c) { c.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('remarksTextarea').value = text;
    document.getElementById('remarksTextarea').focus();
}

document.getElementById('remarksModal').addEventListener('click', function(e) {
    if (e.target === this) closeRemarksModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeRemarksModal();
});



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
    // Init display button if there's a pre-existing less amount (e.g. old() on validation fail)
    var initAmount = parseFloat(document.getElementById('lessAmount').value) || 0;
    var initNotes  = document.getElementById('lessNotesInput').value;
    var displayVal = document.getElementById('lessDisplayVal');
    if (displayVal && initAmount > 0) {
        displayVal.innerHTML = '₱' + fmtNum(initAmount) + (initNotes ? ' <span style="font-size:.62rem;opacity:.65">(' + initNotes.substring(0,20) + (initNotes.length>20?'...':'') + ')</span>' : '');
    }
});

function fmtNum(n) {
    return parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
</script>

@endsection