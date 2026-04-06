@extends('layouts.sidebar')
@section('page-title', 'Reports')
@section('content')

<style>
    .rpt-hub-title { font-size:.93rem; font-weight:700; margin-bottom:.15rem; }
    .rpt-hub-sub  { font-size:.68rem; color:var(--text-muted); margin-bottom:1rem; }
    .rpt-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:.85rem; }
    .rpt-card {
        display:flex; flex-direction:column; background:var(--bg-card); border:1px solid var(--border);
        border-radius:var(--radius); padding:1rem 1.1rem; text-decoration:none; color:inherit;
        transition:border-color .15s, box-shadow .15s, transform .12s; min-height:118px;
        position:relative; overflow:hidden;
    }
    .rpt-card::before {
        content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:var(--radius) var(--radius) 0 0;
    }
    .rpt-card.t-a::before { background:var(--accent); }
    .rpt-card.t-g::before { background:#16a34a; }
    .rpt-card.t-c::before { background:#0891b2; }
    .rpt-card.t-e::before { background:#dc2626; }
    .rpt-card.t-p::before { background:#7c3aed; }
    .rpt-card:hover {
        border-color:var(--accent); box-shadow:0 4px 14px rgba(59,91,219,.12); transform:translateY(-1px);
    }
    .rpt-card-icon { font-size:1.35rem; margin-bottom:.45rem; opacity:.9; }
    .rpt-card h6 { font-size:.82rem; font-weight:700; margin:0 0 .28rem 0; color:var(--text-primary); }
    .rpt-card p { font-size:.70rem; color:var(--text-muted); margin:0; line-height:1.45; flex:1; }
    .rpt-card-arrow { font-size:.72rem; color:var(--accent); font-weight:600; margin-top:.65rem; display:flex; align-items:center; gap:.25rem; }
</style>

<div class="mb-3">
    <h5 class="rpt-hub-title"><i class="bi bi-graph-up-arrow me-1" style="color:var(--accent)"></i>Reports</h5>
    <p class="rpt-hub-sub mb-0">Choose a report to open filters and details.</p>
</div>

<div class="rpt-grid">
    <a href="{{ route('financial-reports.index') }}" class="rpt-card t-a">
        <span class="rpt-card-icon"><i class="bi bi-bar-chart-line"></i></span>
        <h6>Financial Report</h6>
        <p>Revenue, COGS, expenses, cash flow, receivables, and product profitability.</p>
        <span class="rpt-card-arrow">Open <i class="bi bi-chevron-right"></i></span>
    </a>
    <a href="{{ route('reports.sales') }}" class="rpt-card t-g">
        <span class="rpt-card-icon"><i class="bi bi-graph-up"></i></span>
        <h6>Sales Report</h6>
        <p>DR-level sales grid with products, payment status, balances, and CSV export.</p>
        <span class="rpt-card-arrow">Open <i class="bi bi-chevron-right"></i></span>
    </a>
    <a href="{{ route('reports.inventory') }}" class="rpt-card t-c">
        <span class="rpt-card-icon"><i class="bi bi-clipboard-data"></i></span>
        <h6>Inventory Report</h6>
        <p>Warehouse and branch stock, raw materials, production, and movements.</p>
        <span class="rpt-card-arrow">Open <i class="bi bi-chevron-right"></i></span>
    </a>
    <a href="{{ route('reports.expenses') }}" class="rpt-card t-e">
        <span class="rpt-card-icon"><i class="bi bi-wallet2"></i></span>
        <h6>Expense Report</h6>
        <p>Operating expenses by date with category and payment method breakdown.</p>
        <span class="rpt-card-arrow">Open <i class="bi bi-chevron-right"></i></span>
    </a>
    <a href="{{ route('reports.production') }}" class="rpt-card t-p">
        <span class="rpt-card-icon"><i class="bi bi-gear-wide-connected"></i></span>
        <h6>Production Report</h6>
        <p>Production batches, output, rejects, and summary by finished product.</p>
        <span class="rpt-card-arrow">Open <i class="bi bi-chevron-right"></i></span>
    </a>
</div>

@endsection
