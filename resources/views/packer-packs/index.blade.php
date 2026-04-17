@extends('layouts.sidebar')
@section('page-title', 'Packers Report')
@section('content')

<style>
    .pk-toolbar { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; flex-wrap: wrap; }
    .pk-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.04); overflow: hidden; }
    .pk-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .pk-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .5rem .65rem; white-space: nowrap; border: none;
    }
    .pk-table tbody td { padding: .45rem .65rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .pk-table tbody tr:hover td { background: var(--accent-light); }
    .btn-new {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .38rem .85rem; background: var(--accent); color: #fff;
        border-radius: 6px; font-size: .78rem; font-weight: 700; text-decoration: none !important;
    }
    .btn-new:hover { background: var(--accent-hover); color: #fff; }
    .btn-open {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .22rem .55rem; background: var(--accent); color: #fff;
        border-radius: 5px; font-size: .72rem; font-weight: 600; text-decoration: none !important;
    }
    .btn-del {
        padding: .22rem .45rem; font-size: .7rem; border-radius: 5px;
        background: var(--s-danger-bg); color: var(--s-danger-text);
        border: 1px solid #fca5a5; cursor: pointer; font-weight: 600;
    }
    .pm-search-input { height: 30px; padding: 0 .75rem; border: 1px solid var(--border); border-radius: 6px; font-size: .78rem; width: 140px; }
    .exp-pill { display: inline-block; padding: .12rem .4rem; border-radius: 4px; font-size: .68rem; font-weight: 700; }
    .exp-ok { background: var(--s-success-bg); color: var(--s-success-text); }
    .exp-soon { background: var(--s-warning-bg); color: var(--s-warning-text); }
    .exp-past { background: var(--s-danger-bg); color: var(--s-danger-text); }
    .exp-none { background: var(--bg-page); color: var(--text-muted); border: 1px solid var(--border); }
    .pk-meta { font-size: .68rem; line-height: 1.35; color: var(--text-secondary); }
    .pk-meta .by { color: var(--text-muted); font-size: .65rem; }
    .pm-search-wide { width: 200px; min-width: 160px; }
    .dj-modal-overlay { display: none; position: fixed; inset: 0; z-index: 9998; background: rgba(0,0,0,.45); align-items: center; justify-content: center; }
    .dj-modal-overlay.show { display: flex; }
    .dj-modal { background: var(--bg-card); border-radius: 10px; padding: 1.5rem; width: 400px; max-width: 95vw; }
    .dj-modal-title { font-size: .9rem; font-weight: 700; margin-bottom: .5rem; }
    .dj-modal-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-box-seam me-2" style="color:var(--accent)"></i>Packers Report
        </h5>
        <p class="mb-0" style="font-size:.72rem;color:var(--text-muted)">Each line is one saved report. Use <strong>Update</strong> to edit the grid; new reports return here after you save.</p>
    </div>
    <a href="{{ route('packer-packs.create') }}" class="btn-new"><i class="bi bi-plus-lg"></i> Add packers report</a>
</div>

{{-- session success / error: partials.flash in layout --}}

<form method="GET" class="pk-toolbar mb-2">
    <input type="text" name="search" class="pm-search-input pm-search-wide" placeholder="Search ID, notes, product…" value="{{ request('search') }}" autocomplete="off">
    <input type="date" name="from" class="pm-search-input" value="{{ request('from') }}" title="From">
    <input type="date" name="to" class="pm-search-input" value="{{ request('to') }}" title="To">
    <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem">Apply</button>
    <a href="{{ route('packer-packs.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem">Reset</a>
</form>

<div class="pk-card">
    <div style="overflow-x:auto">
        <table class="pk-table">
            <thead>
                <tr>
                    <th>Pack date</th>
                    <th class="text-end">Products</th>
                    <th class="text-end">Total packs</th>
                    <th>Expires</th>
                    <th>Expiry status</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                    @php
                        $exp = $r->expiration_date;
                        $datePillClass = 'exp-ok';
                        $expDateLabel = $exp ? $exp->format('M j, Y') : '—';
                        $statusLabel = 'No expiry';
                        $statusClass = 'exp-none';
                        if ($exp) {
                            $expired = now()->startOfDay()->gt($exp->copy()->startOfDay());
                            if ($expired) {
                                $datePillClass = 'exp-past';
                                $statusLabel = 'Expired';
                                $statusClass = 'exp-past';
                            } elseif ($exp->lte(now()->addDays(14))) {
                                $datePillClass = 'exp-soon';
                                $statusLabel = 'Expiring soon';
                                $statusClass = 'exp-soon';
                            } else {
                                $datePillClass = 'exp-ok';
                                $statusLabel = 'OK';
                                $statusClass = 'exp-ok';
                            }
                        }
                        $totalPacks = (float) ($r->total_packs_sum ?? 0);
                        $prodCount = (int) ($r->products_count ?? 0);
                        $ua = $r->updated_at;
                        $showUpdatedRel = $r->created_at && $ua && $ua->gt($r->created_at);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $r->pack_date->format('M j, Y') }}</td>
                        <td class="text-end">{{ $prodCount }}</td>
                        <td class="text-end">{{ number_format($totalPacks, 0) }}</td>
                        <td>
                            @if($exp)
                                <span class="exp-pill {{ $datePillClass }}">{{ $expDateLabel }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="exp-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="pk-meta">
                            @if($ua)
                                <div>{{ $ua->format('M j, Y') }} <span class="text-muted">{{ $ua->format('g:i A') }}</span></div>
                                @if($showUpdatedRel)
                                    <div class="by">{{ $ua->diffForHumans() }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('packer-packs.sheet', $r) }}" class="btn-open">Update</a>
                            <button type="button" class="btn-del ms-1" onclick="confirmDel({{ $r->id }})">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted" style="font-size:.82rem">
                            No packers reports yet.
                            <a href="{{ route('packer-packs.create') }}" style="color:var(--accent)">Add packers report</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
        <div class="p-2 border-top" style="font-size:.72rem">{{ $reports->withQueryString()->links() }}</div>
    @endif
</div>

<div id="delModal" class="dj-modal-overlay">
    <div class="dj-modal">
        <div class="dj-modal-title">Delete this packers report?</div>
        <p class="mb-0" style="font-size:.78rem;color:var(--text-secondary)">All quantities in this report will be removed from finished product stock.</p>
        <form id="delForm" method="POST" class="dj-modal-actions">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('delModal').classList.remove('show')">Cancel</button>
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </div>
</div>

<script>
function confirmDel(id) {
    document.getElementById('delForm').action = '{{ url('packer-packs') }}/' + id;
    document.getElementById('delModal').classList.add('show');
}
document.getElementById('delModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
@endsection
