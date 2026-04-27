@extends('layouts.sidebar')
@section('page-title', 'New Bank Deposit')
@section('content')

<style>
    .dj-back { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; color:var(--text-secondary); text-decoration:none; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--border); background:var(--bg-card); }
    .dj-back:hover { background:var(--bg-page); }

    .dep-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; margin-bottom:1rem; }
    .dep-card-header { padding:.6rem 1rem; background:var(--bg-page); border-bottom:1px solid var(--border); }
    .dep-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .type-grid { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; padding:1rem 1.1rem; }
    .type-radio { display:none; }
    .type-label { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.4rem; padding:1.1rem .8rem; border:2px solid var(--border); border-radius:8px; cursor:pointer; font-size:.82rem; font-weight:600; color:var(--text-muted); text-align:center; transition:all .12s; }
    .type-label:hover { border-color:var(--accent); background:var(--accent-faint); color:var(--accent); }
    .type-label i { font-size:1.4rem; }
    .type-radio.t-cash:checked + .type-label { border-color:#16a34a; background:#dcfce7; color:#15622e; }
    .type-radio.t-check:checked + .type-label { border-color:#7c3aed; background:#ede9fe; color:#5b21b6; }

    .form-body { padding:1rem 1.1rem; }
    .field-group { display:flex; flex-direction:column; gap:.2rem; margin-bottom:.85rem; }
    .field-label { font-size:.72rem; font-weight:600; color:var(--text-primary); }
    .field-label span { color:var(--s-danger-text); }
    .field-input { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    .field-select { padding:.32rem .6rem; font-size:.80rem; border:1px solid var(--border); border-radius:5px; background:var(--bg-card); color:var(--text-primary); width:100%; }
    .field-select:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,91,219,.1); }
    textarea.field-input { resize:vertical; min-height:64px; }

    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; }

    .info-chip { display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:600; padding:.3rem .7rem; border-radius:5px; border:1px solid var(--border); background:var(--bg-card); }
    .info-chip.green { border-color:#bbf7d0; background:#dcfce7; color:#15622e; }
    .info-chip.purple { border-color:#ddd6fe; background:#ede9fe; color:#5b21b6; }

    .form-actions { display:flex; align-items:center; gap:.6rem; padding:.85rem 1.1rem; border-top:1px solid var(--border); background:var(--bg-page); }
    .btn-submit { padding:.38rem 1.2rem; font-size:.80rem; font-weight:600; border-radius:5px; background:var(--accent); color:#fff; border:none; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-submit:hover { background:var(--accent-hover); }
    .btn-cancel { padding:.38rem .9rem; font-size:.78rem; border-radius:5px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:.3rem; }
    .btn-cancel:hover { background:var(--bg-page); }
</style>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('bank-deposits.index') }}" class="dj-back">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-bank me-1" style="color:var(--accent)"></i>New Bank Deposit
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">Record a cash deposit or check deposit to the bank</span>
    </div>
</div>

<form method="POST" action="{{ route('bank-deposits.store') }}" id="depositForm">
@csrf

<div class="dep-card">
    <div class="dep-card-header">
        <span class="dep-card-title"><i class="bi bi-toggles" style="color:var(--accent)"></i> Deposit Type</span>
    </div>
    <div class="type-grid">
        <div>
            <input type="radio" name="deposit_type" id="t-cash" class="type-radio t-cash" value="cash_deposit"
               {{ request('type') !== 'check_deposit' ? 'checked' : '' }}>
            <label for="t-cash" class="type-label">
                <i class="bi bi-cash-stack"></i>
                Cash on Hand → Bank
                <span style="font-size:.68rem;font-weight:400">Cash collected from sales</span>
            </label>
        </div>
        <div>
            <input type="radio" name="deposit_type" id="t-check" class="type-radio t-check" value="check_deposit"
                   {{ request('type') === 'check_deposit' ? 'checked' : '' }}>
            <label for="t-check" class="type-label">
                <i class="bi bi-file-earmark-text"></i>
                Check Deposit → Bank
                <span style="font-size:.68rem;font-weight:400">Check from an expense or payment</span>
            </label>
        </div>
    </div>
</div>

<div class="dep-card">
    <div class="dep-card-header">
        <span class="dep-card-title"><i class="bi bi-wallet2" style="color:var(--accent)"></i> Deposit Details</span>
    </div>
    <div class="form-body">

        {{-- Cash on hand info (shown for cash_deposit) --}}
        <div id="cashInfo" class="mb-3">
            <span class="info-chip green">
                <i class="bi bi-cash"></i>
                Cash on Hand: <strong>&#8369;{{ number_format($cashOnHand, 2) }}</strong>
            </span>
            <span style="font-size:.70rem;color:var(--text-muted);margin-left:.5rem">
                Cash collected from sales minus previous deposits
            </span>
        </div>

        {{-- Check expense selector (shown for check_deposit) --}}
        <div id="checkInfo" class="mb-3" style="display:none">
            <div class="field-group">
                <label class="field-label">Link to Expense (Optional)</label>
                <select name="expense_id" id="expenseSelect" class="field-select" onchange="prefillFromExpense()">
                    <option value="">— Select a check expense —</option>
                    {{-- Pre-select if coming from expense list --}}
                    @foreach($checkExpenses as $expense)
                    <option value="{{ $expense->id }}"
                            data-amount="{{ $expense->amount }}"
                            data-desc="{{ $expense->description }}"
                            {{ request('expense_id') == $expense->id ? 'selected' : '' }}>
                        {{ $expense->expense_date->format('M d, Y') }} — {{ $expense->description }}
                        (₱{{ number_format($expense->amount, 2) }})
                    </option>
                    @endforeach
                </select>
                @if($checkExpenses->isEmpty())
                    <span style="font-size:.70rem;color:var(--text-muted)">No pending check expenses found.</span>
                @endif
            </div>
        </div>

        <div class="form-grid-2">
            <div class="field-group">
                <label class="field-label">Bank Name <span>*</span></label>
                <input type="text" name="bank_name" class="field-input" value="{{ old('bank_name') }}"
                       placeholder="e.g. BDO, Metrobank, Landbank" required>
            </div>
            <div class="field-group">
                <label class="field-label">Amount <span>*</span></label>
                <input type="number" step="0.01" name="amount" id="amountField" class="field-input"
                       value="{{ old('amount') }}" placeholder="0.00" required>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="field-group">
                <label class="field-label">Deposit Date <span>*</span></label>
                <input type="date" name="deposit_date" class="field-input"
                       value="{{ old('deposit_date', date('Y-m-d')) }}" required>
            </div>
            <div class="field-group">
                <label class="field-label">Notes</label>
                <textarea name="notes" class="field-input" placeholder="Optional notes...">{{ old('notes') }}</textarea>
            </div>
        </div>

    </div>

    <div class="form-actions">
        <button type="submit" class="btn-submit">
            <i class="bi bi-check-circle"></i> Confirm Deposit
        </button>
        <a href="{{ route('bank-deposits.index') }}" class="btn-cancel">
            <i class="bi bi-x"></i> Cancel
        </a>
    </div>
</div>

</form>

<script>
function toggleType() {
    var type = document.querySelector('input[name="deposit_type"]:checked').value;
    document.getElementById('cashInfo').style.display  = type === 'cash_deposit'  ? '' : 'none';
    document.getElementById('checkInfo').style.display = type === 'check_deposit' ? '' : 'none';
}

function prefillFromExpense() {
    var sel = document.getElementById('expenseSelect');
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('amountField').value = parseFloat(opt.dataset.amount).toFixed(2);
    }
}

document.querySelectorAll('input[name="deposit_type"]').forEach(function(r) {
    r.addEventListener('change', toggleType);
});
document.addEventListener('DOMContentLoaded', function() {
    toggleType();
    prefillFromExpense();
});
</script>

@endsection
