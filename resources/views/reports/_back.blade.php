@once
<style>
    .rpt-back-link {
        display:inline-flex; align-items:center; gap:.35rem; font-size:.74rem; font-weight:600;
        padding:.32rem .75rem; border-radius:var(--radius); text-decoration:none;
        color:var(--text-secondary); background:var(--bg-card); border:1px solid var(--border);
        transition:background .12s, border-color .12s, color .12s;
    }
    .rpt-back-link:hover { background:var(--accent-faint); border-color:var(--accent); color:var(--accent); }
</style>
@endonce
<div class="no-print mb-2">
    <a href="{{ route('reports.index') }}" class="rpt-back-link">
        <i class="bi bi-arrow-left"></i> Back to Reports
    </a>
</div>
