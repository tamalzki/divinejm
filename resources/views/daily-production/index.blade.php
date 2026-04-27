@extends('layouts.sidebar')
@section('page-title', 'Daily Production')
@section('content')

<style>
    .dp-toolbar { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; flex-wrap: wrap; }
    .dp-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 4px rgba(0,0,0,.04); overflow: hidden; }
    .dp-table { width: 100%; border-collapse: collapse; font-size: .78rem; }
    .dp-table thead th {
        background: var(--brand-deep); color: rgba(255,255,255,.88);
        font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        padding: .5rem .65rem; white-space: nowrap; border: none;
    }
    .dp-table tbody td { padding: .45rem .65rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .dp-table tbody tr:hover td { background: var(--accent-light); }
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
    .pm-search-wide { width: 200px; min-width: 160px; }
    .pk-meta { font-size: .68rem; line-height: 1.35; color: var(--text-secondary); }
    .pk-meta .by { color: var(--text-muted); font-size: .65rem; }
    .dp-packed-col { color: var(--s-success-text); font-weight: 700; }
    .dp-remaining-col { color: var(--text-secondary); font-weight: 600; }
    .dj-modal-overlay { display: none; position: fixed; inset: 0; z-index: 9998; background: rgba(0,0,0,.45); align-items: center; justify-content: center; }
    .dj-modal-overlay.show { display: flex; }
    .dj-modal { background: var(--bg-card); border-radius: 10px; padding: 1.5rem; width: 400px; max-width: 95vw; }
    .dj-modal-title { font-size: .9rem; font-weight: 700; margin-bottom: .5rem; }
    .dj-modal-actions { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }
</style>

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="fw-bold mb-0" style="font-size:.95rem">
            <i class="bi bi-clipboard2-data me-2" style="color:var(--accent)"></i>Daily Production
        </h5>
        <p class="mb-0" style="font-size:.72rem;color:var(--text-muted)">Each row is one report. <strong>Update</strong> edits the grid; raw materials deduct from recipes × # of mix (same as before).</p>
    </div>
    <a href="{{ route('daily-production.create') }}" class="btn-new"><i class="bi bi-plus-lg"></i> Add daily production</a>
</div>

{{-- session success / error: partials.flash in layout --}}

<form method="GET" class="dp-toolbar mb-2">
    <input type="text" name="search" class="pm-search-input pm-search-wide" placeholder="Search ID, notes, product…" value="{{ request('search') }}" autocomplete="off">
    <input type="date" name="from" class="pm-search-input" value="{{ request('from') }}" title="From">
    <input type="date" name="to" class="pm-search-input" value="{{ request('to') }}" title="To">
    <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem">Apply</button>
    <a href="{{ route('daily-production.index') }}" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem">Reset</a>
</form>

<div class="dp-card">
    <div style="overflow-x:auto">
        <table class="dp-table">
            <thead>
                <tr>
                    <th>Production date</th>
                    <th class="text-end">Lines</th>
                    <th class="text-end">Σ Actual yield</th>
                    <th class="text-end">Packed</th>
                    <th class="text-end">Remaining</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                    @php
                        $ua = $r->updated_at;
                        $showUpdatedRel = $r->created_at && $ua && $ua->gt($r->created_at);
                        $lines = (int) ($r->lines_count ?? 0);
                        $totAct = (float) ($r->total_actual_yield ?? 0);
                        $totPacked = (float) ($r->total_packed_quantity ?? 0);
                        $totUnpacked = (float) ($r->total_unpacked_quantity ?? 0);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $r->production_date->format('M j, Y') }}</td>
                        <td class="text-end">{{ $lines }}</td>
                        <td class="text-end">{{ number_format($totAct, 0) }}</td>
                        <td class="text-end dp-packed-col">{{ number_format($totPacked, 0) }}</td>
                        <td class="text-end dp-remaining-col">{{ number_format($totUnpacked, 0) }}</td>
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
                            <a href="{{ route('daily-production.sheet', $r) }}" class="btn-open">Update</a>
                            <button type="button" class="btn-del ms-1" onclick="confirmDel({{ $r->id }})">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted" style="font-size:.82rem">
                            No reports yet.
                            <a href="{{ route('daily-production.create') }}" style="color:var(--accent)">Add daily production</a>
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
        <div class="dj-modal-title">Delete this daily production report?</div>
        <p class="mb-0" style="font-size:.78rem;color:var(--text-secondary)">Raw material deductions for this report will be restored.</p>
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
    document.getElementById('delForm').action = '{{ url('daily-production') }}/' + id;
    document.getElementById('delModal').classList.add('show');
}
document.getElementById('delModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
@endsection
