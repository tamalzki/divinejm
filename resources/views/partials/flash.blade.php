@once
<style>
    /* Global session / validation messages (main app layout) */
    .dj-flash-wrap {
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .dj-flash {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.7rem 0.85rem;
        border-radius: 8px;
        font-size: 0.82rem;
        line-height: 1.45;
        font-weight: 500;
        border: 1px solid transparent;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .dj-flash--success {
        background: #ecfdf5;
        color: #065f46;
        border-color: #6ee7b7;
        border-left: 4px solid #059669;
    }
    .dj-flash--error {
        background: #fef2f2;
        color: #7f1d1d;
        border-color: #fca5a5;
        border-left: 4px solid #dc2626;
    }
    .dj-flash--warning {
        background: #fffbeb;
        color: #78350f;
        border-color: #fcd34d;
        border-left: 4px solid #d97706;
    }
    .dj-flash-icon {
        flex-shrink: 0;
        font-size: 1.15rem;
        margin-top: 0.05rem;
        line-height: 1;
    }
    .dj-flash--success .dj-flash-icon { color: #059669; }
    .dj-flash--error .dj-flash-icon { color: #dc2626; }
    .dj-flash--warning .dj-flash-icon { color: #d97706; }
    .dj-flash-body { flex: 1; min-width: 0; }
    .dj-flash-title {
        font-weight: 700;
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
        letter-spacing: 0.02em;
    }
    .dj-flash ul.dj-flash-list {
        margin: 0.3rem 0 0;
        padding-left: 1.2rem;
        font-size: 0.78rem;
        font-weight: 400;
        color: inherit;
    }
    .dj-flash-dismiss {
        flex-shrink: 0;
        background: transparent;
        border: none;
        padding: 0.2rem;
        margin: -0.1rem -0.05rem 0 0;
        cursor: pointer;
        opacity: 0.45;
        color: inherit;
        line-height: 1;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    .dj-flash-dismiss:hover { opacity: 0.85; background: rgba(0, 0, 0, 0.06); }
</style>
@endonce

@php
    $__flashAny = session()->has('success') || session()->has('error') || session()->has('warning')
        || session()->has('status') || session()->has('resent') || (isset($errors) && $errors->any());
@endphp
@if ($__flashAny)
<div class="dj-flash-wrap">
    @if (session('success'))
        <div class="dj-flash dj-flash--success" role="alert">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
            <div class="dj-flash-body">{{ session('success') }}</div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
    @if (session('resent'))
        <div class="dj-flash dj-flash--success" role="alert">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-envelope-check-fill"></i></span>
            <div class="dj-flash-body">{{ __('A fresh verification link has been sent to your email address.') }}</div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
    @if (session('status') && ! session('success'))
        <div class="dj-flash dj-flash--success" role="status">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
            <div class="dj-flash-body">{{ session('status') }}</div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
    @if (session('error'))
        <div class="dj-flash dj-flash--error" role="alert">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div class="dj-flash-body">{{ session('error') }}</div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="dj-flash dj-flash--warning" role="alert">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
            <div class="dj-flash-body">{{ session('warning') }}</div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="dj-flash dj-flash--error" role="alert">
            <span class="dj-flash-icon" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
            <div class="dj-flash-body">
                <div class="dj-flash-title">Please fix the following</div>
                @if ($errors->count() === 1)
                    <div>{{ $errors->first() }}</div>
                @else
                    <ul class="dj-flash-list">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button type="button" class="dj-flash-dismiss" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif
</div>
@endif
