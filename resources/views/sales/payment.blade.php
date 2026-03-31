@extends('layouts.sidebar')
@section('page-title', 'Payment — DR# ' . $sale->dr_number)
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); }
    .dj-back:hover { background:var(--bg-page); color:var(--text-primary); }

    .pay-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; margin-bottom:1rem; }
    .pay-card-header { padding:.6rem 1rem; background:var(--bg-page); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    .pay-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-danger  { background:var(--s-danger-bg);  color:var(--s-danger-text); }
    .pill-warning { background:var(--s-warning-bg); color:var(--s-warning-text); }
    .pill-success { background:var(--s-success-bg); color:var(--s-success-text); }

    /* Summary table */
    .sum-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .sum-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.85); font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.48rem .9rem; border:none; }
    .sum-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .sum-table tbody tr:last-child td { border-bottom:none; }
    .sum-table tfoot td { padding:.52rem .9rem; background:var(--bg-page); font-weight:700; font-size:.80rem; border-top:2px solid var(--border); }

    /* Payment form */
    .form-section { padding:1rem 1.1rem; }
    .section-label { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--text-muted); display:flex; align-items:center; gap:.4rem; margin-bottom:.75rem; }
    .section-label::after { content:''; flex:1; height:1px; background:var(--border); }

    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.85rem; }
    .field-group { display:flex; flex-direction:column; gap:.2rem; }
    .field-label { font-size:.72rem; font-weight:600; color:var(--text-primary); }
    .field-label span { color:var(--s-danger-text); }
    .field-input { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .field-select { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-select:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    textarea.field-input { resize:vertical; min-height:72px; }

    .status-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; margin-bottom:.85rem; }
    .status-option { display:none; }
    .status-label { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.3rem; padding:.65rem .5rem; border:2px solid var(--border); border-radius:6px; cursor:pointer; font-size:.74rem; font-weight:600; color:var(--text-muted); text-align:center; transition:all .12s; }
    .status-option:checked + .status-label { border-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
    .status-option.s-paid:checked    + .status-label { border-color:var(--s-success-text); background:var(--s-success-bg); color:var(--s-success-text); }
    .status-option.s-partial:checked + .status-label { border-color:var(--s-warning-text); background:var(--s-warning-bg); color:var(--s-warning-text); }
    .status-option.s-collect:checked + .status-label { border-color:var(--s-danger-text);  background:var(--s-danger-bg);  color:var(--s-danger-text); }

    .form-actions { display:flex; align-items:center; gap:.6rem; padding:.85rem 1.1rem; border-top:1px solid var(--border); background:var(--bg-page); }
    .btn-submit { padding:.38rem 1.2rem; font-size:.80rem; font-weight:600; border-radius:5px; background:var(--accent); color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-submit:hover { background:var(--accent-hover); }
    .btn-cancel { padding:.38rem .9rem; font-size:.78rem; border-radius:5px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-cancel:hover { background:var(--bg-page); }

    .total-summary { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
    .total-chip { font-size:.74rem; padding:.25rem .7rem; border-radius:5px; border:1px solid var(--border); background:var(--bg-card); }
    .total-chip strong { color:var(--text-primary); }
    .total-chip.balance { border-color:var(--s-danger-bg); background:var(--s-danger-bg); color:var(--s-danger-text); font-weight:700; }
    .total-chip.paid    { border-color:var(--s-success-bg); background:var(--s-success-bg); color:var(--s-success-text); font-weight:700; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)]) }}" class="dj-back">
        <i class="bi bi-arrow-left"></i> Back to DRs
    </a>
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-cash-coin me-1" style="color:var(--accent)"></i>
            Payment — DR# <span style="color:var(--accent)">{{ $sale->dr_number }}</span>
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">
            {{ $sale->branch->name }} &middot; {{ $sale->customer_name }} &middot; {{ $sale->sale_date->format('M d, Y') }}
        </span>
    </div>
</div>

{{-- Products summary --}}
<div class="pay-card">
    <div class="pay-card-header">
        <span class="pay-card-title">
            <i class="bi bi-box-seam" style="color:var(--accent)"></i> Products Delivered
        </span>
        <div class="total-summary">
            <span class="total-chip">
                Total: <strong>&#8369;{{ number_format($sale->total_amount, 2) }}</strong>
            </span>
            <span class="total-chip {{ $sale->balance > 0 ? 'balance' : 'paid' }}">
                Balance: &#8369;{{ number_format($sale->balance, 2) }}
            </span>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table class="sum-table">
            <thead>
                <tr>
                    <th style="width:30%">Product</th>
                    <th class="text-end">Delivered Qty</th>
                    <th class="text-end">Sold Qty</th>
                    <th class="text-end">BO</th>
                    <th class="text-end">Unsold</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>
                    <div style="font-weight:600">{{ $item->finishedProduct->name ?? '—' }}</div>
                    @if($item->batch_number)
                        <span style="font-size:.65rem;color:var(--text-muted)">{{ $item->batch_number }}</span>
                    @endif
                </td>
                <td class="text-end" style="font-weight:700">{{ number_format($item->quantity_deployed, 0) }}</td>
                <td class="text-end" style="color:var(--s-success-text);font-weight:600">{{ number_format($item->quantity_sold, 0) }}</td>
                <td class="text-end" style="color:{{ $item->quantity_bo > 0 ? 'var(--s-danger-text)' : 'var(--text-muted)' }}">
                    {{ number_format($item->quantity_bo, 0) }}
                </td>
                <td class="text-end" style="color:{{ $item->quantity_unsold > 0 ? 'var(--s-warning-text)' : 'var(--text-muted)' }}">
                    {{ number_format($item->quantity_unsold, 0) }}
                </td>
                <td class="text-end">&#8369;{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end" style="font-weight:700">&#8369;{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end">Total</td>
                    <td class="text-end">&#8369;{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Payment form --}}
<div class="pay-card">
    <div class="pay-card-header">
        <span class="pay-card-title">
            <i class="bi bi-wallet2" style="color:var(--accent)"></i> Payment Details
        </span>
        @if($sale->payment_status === 'paid')
            <span class="pill pill-success">Paid</span>
        @elseif($sale->payment_status === 'partial')
            <span class="pill pill-warning">Partial</span>
        @else
            <span class="pill pill-danger">To Collect</span>
        @endif
    </div>

    <form action="{{ route('sales.updatePayment', $sale->id) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="form-section">

            {{-- Payment status selector --}}
            <div class="section-label"><i class="bi bi-toggles"></i> Payment Status</div>
            <div class="status-grid">
                <div>
                    <input type="radio" name="payment_status_override" id="s-paid"
                           class="status-option s-paid"
                           value="paid" {{ $sale->payment_status === 'paid' ? 'checked' : '' }}>
                    <label for="s-paid" class="status-label">
                        <i class="bi bi-check-circle-fill" style="font-size:1.1rem"></i>
                        Paid
                    </label>
                </div>
                <div>
                    <input type="radio" name="payment_status_override" id="s-partial"
                           class="status-option s-partial"
                           value="partial" {{ $sale->payment_status === 'partial' ? 'checked' : '' }}>
                    <label for="s-partial" class="status-label">
                        <i class="bi bi-dash-circle-fill" style="font-size:1.1rem"></i>
                        Partial
                    </label>
                </div>
                <div>
                    <input type="radio" name="payment_status_override" id="s-collect"
                           class="status-option s-collect"
                           value="to_be_collected" {{ $sale->payment_status === 'to_be_collected' ? 'checked' : '' }}>
                    <label for="s-collect" class="status-label">
                        <i class="bi bi-clock-fill" style="font-size:1.1rem"></i>
                        To Collect
                    </label>
                </div>
            </div>

            {{-- Amount + Mode --}}
            <div class="section-label"><i class="bi bi-cash-stack"></i> Payment Info</div>
            <div class="form-grid-3" style="margin-bottom:.85rem">
                <div class="field-group">
                    <label class="field-label">Amount Paid <span>*</span></label>
                    <input type="number" name="amount_paid" class="field-input"
                           value="{{ number_format($sale->amount_paid, 2, '.', '') }}"
                           step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="field-group">
                    <label class="field-label">Payment Mode <span>*</span></label>
                    <select name="payment_mode" class="field-select">
                        <option value="cash"          {{ $sale->payment_mode === 'cash'          ? 'selected' : '' }}>Cash</option>
                        <option value="gcash"         {{ $sale->payment_mode === 'gcash'         ? 'selected' : '' }}>GCash</option>
                        <option value="cheque"        {{ $sale->payment_mode === 'cheque'        ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ $sale->payment_mode === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="other"         {{ $sale->payment_mode === 'other'         ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Payment Date <span>*</span></label>
                    <input type="date" name="payment_date" class="field-input"
                           value="{{ $sale->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                </div>
            </div>

            {{-- Reference + Remarks --}}
            <div class="form-grid" style="margin-bottom:.85rem">
                <div class="field-group">
                    <label class="field-label">Reference Number</label>
                    <input type="text" name="payment_reference" class="field-input"
                           value="{{ $sale->payment_reference }}"
                           placeholder="GCash ref, cheque no., etc.">
                </div>
                <div class="field-group">
                    <label class="field-label">Remarks / Notes</label>
                    <textarea name="notes" class="field-input"
                              placeholder="Optional notes about this payment...">{{ $sale->notes }}</textarea>
                </div>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="bi bi-save"></i> Save Payment
            </button>
            <a href="{{ route('sales.show', [$sale->branch_id, rawurlencode($sale->customer_name)]) }}"
               class="btn-cancel">
                <i class="bi bi-x"></i> Cancel
            </a>
        </div>

    </form>
</div>

@endsection