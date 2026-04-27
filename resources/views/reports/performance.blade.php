@extends('layouts.sidebar')
@section('page-title', 'Performance Report')
@section('content')

<style>
    .rpt-bar { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.6rem .9rem; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .lbl { font-size:.70rem; color:var(--text-muted); white-space:nowrap; }
    .date-input { padding:.22rem .48rem; font-size:.76rem; border:1px solid var(--border); border-radius:4px; background:var(--bg-card); color:var(--text-primary); }
    .btn-apply { font-size:.74rem; font-weight:600; padding:.24rem .75rem; border-radius:4px; background:var(--accent); color:#fff; border:none; cursor:pointer; }
    .btn-apply:hover { background:var(--accent-hover); }
    .tile-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; margin-bottom:.9rem; }
    @media (max-width:900px) { .tile-row { grid-template-columns:repeat(2,1fr); } }
    .sum-tile { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:.65rem .9rem; }
    .sum-tile-label { font-size:.60rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted); }
    .sum-tile-value { font-size:.95rem; font-weight:700; }
    .sec-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:.75rem; }
    .sec-head { background:var(--brand-deep); color:rgba(255,255,255,.9); padding:.46rem .85rem; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
    .dt { width:100%; border-collapse:collapse; font-size:.78rem; }
    .dt thead th { background:var(--brand-deep); color:rgba(255,255,255,.88); padding:.38rem .7rem; font-size:.62rem; font-weight:700; text-transform:uppercase; white-space:nowrap; }
    .dt tbody td { padding:.38rem .7rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    .dt tbody tr:last-child td { border-bottom:none; }
    .dt tbody tr:hover td { background:var(--accent-faint); }
    .dt tfoot td { font-weight:700; background:var(--bg-page); border-top:2px solid var(--border); padding:.38rem .7rem; }
    .dt .tr { text-align:right; }
    .dt .tc { text-align:center; }
    .rank { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; font-size:.62rem; font-weight:700; }
    .rank-1 { background:#fef08a; color:#713f12; }
    .rank-2 { background:#e2e8f0; color:#334155; }
    .rank-3 { background:#fed7aa; color:#7c2d12; }
    .rank-n { background:var(--bg-page); color:var(--text-muted); }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:.75rem; }
    @media (max-width:900px) { .grid-2 { grid-template-columns:1fr; } }
    .packer-bar-wrap { display:flex; align-items:center; gap:.5rem; }
    .packer-bar { height:8px; border-radius:4px; background:var(--accent); opacity:.75; transition:width .3s; }
    @media print { .no-print { display:none!important; } body * { font-size:11px; } }
</style>

@include('reports._back')

<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.93rem">
            <i class="bi bi-trophy me-1" style="color:#f59e0b"></i>Performance Report
        </h5>
        <span style="font-size:.67rem;color:var(--text-muted)">
            Packer performance from {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
        </span>
    </div>
    <button type="button" onclick="window.print()" class="btn-apply no-print" style="background:#16a34a">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

<form method="GET" action="{{ route('reports.performance') }}" class="no-print">
<div class="rpt-bar">
    <span class="lbl">From</span>
    <input type="date" name="start_date" class="date-input" value="{{ $startDate }}">
    <span class="lbl">To</span>
    <input type="date" name="end_date" class="date-input" value="{{ $endDate }}">
    <button type="submit" class="btn-apply"><i class="bi bi-search"></i> Apply</button>
</div>
</form>

{{-- Summary tiles --}}
<div class="tile-row">
    <div class="sum-tile">
        <span class="sum-tile-label">Total Packed</span>
        <span class="sum-tile-value" style="color:var(--accent)">{{ number_format($totals['total_packed'], 0) }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Packers</span>
        <span class="sum-tile-value" style="color:#16a34a">{{ $totals['total_packers'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Pack Sessions</span>
        <span class="sum-tile-value" style="color:#0891b2">{{ $totals['total_sessions'] }}</span>
    </div>
    <div class="sum-tile">
        <span class="sum-tile-label">Products Packed</span>
        <span class="sum-tile-value" style="color:#7c3aed">{{ $totals['total_products'] }}</span>
    </div>
</div>

<div class="grid-2">
    {{-- Packer Leaderboard --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-person-badge me-1"></i> Packer Leaderboard</div>
        <div style="overflow-x:auto">
            <table class="dt">
                <thead>
                    <tr>
                        <th class="tc">#</th>
                        <th>Packer</th>
                        <th class="tr">Total Packed</th>
                        <th class="tc">Sessions</th>
                        <th class="tc">Products</th>
                    </tr>
                </thead>
                <tbody>
                @php $maxPacked = $byPacker->max('total_packs') ?: 1; @endphp
                @forelse($byPacker as $i => $p)
                    @php $rank = $i + 1; @endphp
                    <tr>
                        <td class="tc">
                            <span class="rank {{ $rank <= 3 ? 'rank-'.$rank : 'rank-n' }}">{{ $rank }}</span>
                        </td>
                        <td>
                            <strong>{{ $p['packer_name'] }}</strong>
                            <div class="packer-bar-wrap mt-1">
                                <div class="packer-bar" style="width:{{ round(($p['total_packs']/$maxPacked)*120) }}px"></div>
                            </div>
                        </td>
                        <td class="tr" style="font-weight:700;color:var(--accent)">{{ number_format($p['total_packs'], 0) }}</td>
                        <td class="tc" style="color:var(--text-muted)">{{ $p['session_count'] }}</td>
                        <td class="tc" style="color:var(--text-muted)">{{ $p['product_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">No packing records in this period.</td></tr>
                @endforelse
                </tbody>
                @if($byPacker->count())
                <tfoot>
                    <tr>
                        <td colspan="2" class="tr">Total</td>
                        <td class="tr">{{ number_format($totals['total_packed'], 0) }}</td>
                        <td class="tc">{{ $totals['total_sessions'] }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- By Product --}}
    <div class="sec-card">
        <div class="sec-head"><i class="bi bi-box-seam me-1"></i> By Product</div>
        <div style="overflow-x:auto">
            <table class="dt">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="tr">Total Packed</th>
                        <th class="tc">Packers</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($byProduct as $row)
                    <tr>
                        <td style="font-weight:600">{{ $row['product_name'] }}</td>
                        <td class="tr" style="font-weight:700;color:#16a34a">{{ number_format($row['total_packed'], 0) }}</td>
                        <td class="tc" style="color:var(--text-muted)">{{ $row['packer_count'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem">No data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Session Detail --}}
<div class="sec-card">
    <div class="sec-head"><i class="bi bi-calendar3 me-1"></i> Session Detail</div>
    <div style="overflow-x:auto">
        <table class="dt">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Expiry</th>
                    <th>Packer</th>
                    <th>Product</th>
                    <th class="tr">Qty Packed</th>
                    <th>Notes</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
                @foreach($report->packs as $pack)
                <tr>
                    <td style="white-space:nowrap">{{ $report->pack_date->format('M d, Y') }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted);white-space:nowrap">
                        {{ $report->expiration_date ? $report->expiration_date->format('M d, Y') : '—' }}
                    </td>
                    <td style="font-weight:700;color:var(--accent)">{{ $pack->packer_name ?: '—' }}</td>
                    <td>{{ optional($pack->finishedProduct)->name ?? '—' }}</td>
                    <td class="tr" style="font-weight:700">{{ number_format($pack->quantity, 0) }}</td>
                    <td style="font-size:.72rem;color:var(--text-muted)">{{ $pack->notes ?: '—' }}</td>
                    <td style="font-size:.70rem;color:var(--text-muted)">{{ optional($report->user)->name ?? '—' }}</td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">No sessions in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
