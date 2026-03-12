@if ($paginator->hasPages())
<nav class="dj-pagination">
    <div class="dj-page-info">
        Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> results
    </div>

    <ul class="dj-page-list">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="dj-page-item dj-disabled">
                <span class="dj-page-link">&#8249;</span>
            </li>
        @else
            <li class="dj-page-item">
                <a class="dj-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&#8249;</a>
            </li>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="dj-page-item dj-disabled"><span class="dj-page-link">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="dj-page-item dj-active"><span class="dj-page-link">{{ $page }}</span></li>
                    @else
                        <li class="dj-page-item"><a class="dj-page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="dj-page-item">
                <a class="dj-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&#8250;</a>
            </li>
        @else
            <li class="dj-page-item dj-disabled">
                <span class="dj-page-link">&#8250;</span>
            </li>
        @endif

    </ul>
</nav>

<style>
.dj-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 0 .25rem;
    flex-wrap: wrap;
    gap: .5rem;
}

.dj-page-info {
    font-size: .72rem;
    color: var(--text-m, #8a8a82);
}

.dj-page-info strong {
    color: var(--text-s, #44443e);
    font-weight: 600;
}

.dj-page-list {
    display: flex;
    align-items: center;
    gap: .2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.dj-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 .4rem;
    font-size: .75rem;
    font-weight: 500;
    border-radius: 5px;
    border: 1px solid var(--border, #e4e4e0);
    background: var(--bg-card, #fff);
    color: var(--text-s, #44443e);
    text-decoration: none;
    line-height: 1;
    transition: background .12s, border-color .12s, color .12s;
    cursor: pointer;
}

a.dj-page-link:hover {
    background: var(--accent-l, #eef2ff);
    border-color: var(--accent, #3b5bdb);
    color: var(--accent, #3b5bdb);
}

.dj-page-item.dj-active .dj-page-link {
    background: var(--brand-deep, #2c2c38);
    border-color: var(--brand-deep, #2c2c38);
    color: #fff;
    font-weight: 700;
    cursor: default;
}

.dj-page-item.dj-disabled .dj-page-link {
    color: var(--text-m, #8a8a82);
    background: var(--bg-page, #f5f5f2);
    border-color: var(--border, #e4e4e0);
    cursor: not-allowed;
    opacity: .6;
}
</style>
@endif