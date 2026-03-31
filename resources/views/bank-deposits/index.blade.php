@extends('layouts.sidebar')
@section('page-title', 'Bank Deposits')
@section('content')

<style>
    .dj-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 1px 4px rgba(0,0,0,.04); overflow:hidden; margin-bottom:1rem; }
    .dj-card-header { display:flex; align-items:center; justify-content:space-between; padding:.55rem 1rem; border-bottom:1px solid var(--border); background:var(--bg-page); flex-wrap:wrap; gap:.5rem; }
    .dj-card-title { font-size:.78rem; font-weight:700; color:var(--text-primary); display:flex; align-items:center; gap:.4rem; }

    .cash-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:.75rem; margin-bottom:1rem; }
    .cash-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1rem; display:flex; flex-direction:column; gap:.15rem; }
    .cash-tile-icon { font-size:1.1rem; margin-bottom:.1rem; }
    .cash-tile-label { font-size:.62rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .cash-tile-value { font-size:.95rem; font-weight:700; }
    .cash-tile-sub   { font-size:.65rem; color:var(--text-muted); }
    .tile-cash     { border-top:3px solid #16a34a; }
    .tile-gcash    { border-top:3px solid #2563eb; }
    .tile-cheque   { border-top:3px solid #7c3aed; }
    .tile-bank     { border-top:3px solid #0891b2; }
    .tile-onhand   { border-top:3px solid #dc2626; }
    .tile-inbank   { border-top:3px solid #b45309; }
    .tile-cash .cash-tile-value   { color:#16a34a; }
    .tile-gcash .cash-tile-value  { color:#2563eb; }
    .tile-cheque .cash-tile-value { color:#7c3aed; }
    .tile-bank .cash-tile-value   { color:#0891b2; }
    .tile-onhand .cash-tile-value { color:#dc2626; }
    .tile-inbank .cash-tile-value { color:#b45309; }

    .data-table { width:100%; border-collapse:collapse; font-size:.80rem; }
    .data-table thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:.52rem .9rem; white-space:nowrap; border:none; }
    .data-table tbody td { padding:.52rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover td { background:var(--accent-faint); }
    .data-table tfoot td { padding:.52rem .9rem; background:var(--brand-deep); color:rgba(255,255,255,.88); font-size:.78rem; font-weight:700; }

    .pill { display:inline-block; padding:.12rem .5rem; border-radius:4px; font-size:.68rem; font-weight:700; }
    .pill-cash    { background:#dcfce7; color:#15622e; }
    .pill-gcash   { background:#dbeafe; color:#1e40af; }
    .pill-cheque  { background:#ede9fe; color:#5b21b6; }
    .pill-bank    { background:#cffafe; color:#155e75; }
    .pill-other   { background:var(--s-warning-bg); color:var(--s-warning-text); }

    .btn-new { display:inline-flex; align-items:center; gap:.3rem; font-size:.78rem; font-weight:600; padding:.32rem .9rem; border-radius:5px; background:var(--accent); color:#fff; border:none; text-decoration:none; }
    .btn-new:hover { background:var(--accent-hover); color:#fff; }
    .btn-edit { display:inline-flex; align-items:center; gap:.2rem; font-size:.70rem; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--accent); color:var(--accent); background:transparent; text-decoration:none; }
    .btn-edit:hover { background:var(--accent-faint); }
    .btn-del { display:inline-flex; align-items:center; gap:.2rem; font-size:.70rem; padding:.18rem .5rem; border-radius:4px; border:1px solid var(--s-danger-text); color:var(--s-danger-text); background:transparent; cursor:pointer; }
    .btn-del:hover { background:var(--s-danger-bg); }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-muted); font-size:.82rem; }
</style>

{{-- Page header --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-bank me-1" style="color:var(--accent)"></i>Bank Deposits
        </h5>
        <span style="font-size:.68rem;color:var(--text-muted)">Cash position from collected sales</span>
    </div>
    <a href="{{ route('bank-deposits.create') }}" class="btn-new">
        <i class="bi bi-plus-circle"></i> New Deposit
    </a>
</div>

{{-- Cash position tiles --}}
<div class="cash-grid">
    <div class="cash-tile tile-cash">
        <span class="cash-tile-icon">💵</span>
        <span class="cash-tile-label">Cash Collected</span>
        <span class="cash-tile-value">&#8369;{{ number_format($cashCollected, 2) }}</span>
        <span class="cash-tile-sub">From paid cash sales</span>
    </div>
    <div class="cash-tile tile-gcash">
        <span class="cash-tile-icon">📱</span>
        <span class="cash-tile-label">GCash</span>
        <span class="cash-tile-value">&#8369;{{ number_format($gcashCollected, 2) }}</span>
        <span class="cash-tile-sub">From paid GCash sales</span>
    </div>
    <div class="cash-tile tile-cheque">
        <span class="cash-tile-icon">📝</span>
        <span class="cash-tile-label">Cheque</span>
        <span class="cash-tile-value">&#8369;{{ number_format($chequeCollected, 2) }}</span>
        <span class="cash-tile-sub">From paid cheque sales</span>
    </div>
    <div class="cash-tile tile-bank">
        <span class="cash-tile-icon">🏦</span>
        <span class="cash-tile-label">Bank Transfer</span>
        <span class="cash-tile-value">&#8369;{{ number_format($bankCollected, 2) }}</span>
        <span class="cash-tile-sub">From paid bank transfers</span>
    </div>
    <div class="cash-tile tile-onhand">
        <span class="cash-tile-icon">🏧</span>
        <span class="cash-tile-label">Cash on Hand</span>
        <span class="cash-tile-value">&#8369;{{ number_format($cashOnHand, 2) }}</span>
        <span class="cash-tile-sub">Cash less deposits</span>
    </div>
    <div class="cash-tile tile-inbank">
        <span class="cash-tile-icon">🏦</span>
        <span class="cash-tile-label">Cash in Bank</span>
        <span class="cash-tile-value">&#8369;{{ number_format($totalDeposited, 2) }}</span>
        <span class="cash-tile-sub">Total deposited</span>
    </div>
</div>

{{-- Deposits table --}}
<div class="dj-card">
    <div class="dj-card-header">
        <span class="dj-card-title">
            <i class="bi bi-list-ul" style="color:var(--accent)"></i> Deposit Records
        </span>
        <span style="font-size:.72rem;color:var(--text-muted)">
            Total deposited: <strong style="color:var(--text-primary)">&#8369;{{ number_format($totalDeposited, 2) }}</strong>
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Bank</th>
                    <th>Account #</th>
                    <th>Notes</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($deposits as $deposit)
            <tr>
                <td style="white-space:nowrap;font-size:.77rem;color:var(--text-secondary)">
                    {{ $deposit->deposit_date->format('M d, Y') }}
                </td>
                <td style="font-weight:600">{{ $deposit->bank_name }}</td>
                <td style="font-size:.77rem;color:var(--text-muted)">{{ $deposit->account_number }}</td>
                <td style="font-size:.76rem;color:var(--text-muted)">{{ $deposit->notes ?? '—' }}</td>
                <td class="text-end" style="font-weight:700;color:var(--s-success-text)">
                    &#8369;{{ number_format($deposit->amount, 2) }}
                </td>
                <td class="text-center">
                    <div style="display:flex;align-items:center;justify-content:center;gap:.35rem">
                        <a href="{{ route('bank-deposits.edit', $deposit->id) }}" class="btn-edit">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('bank-deposits.destroy', $deposit->id) }}" method="POST"
                              onsubmit="return confirm('Delete this deposit?')" style="margin:0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-del">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="empty-state">No deposits recorded yet.</td></tr>
            @endforelse
            </tbody>
            @if($deposits->count())
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Total Deposited</td>
                    <td class="text-end">&#8369;{{ number_format($totalDeposited, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection