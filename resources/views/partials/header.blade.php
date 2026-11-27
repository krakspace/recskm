<header class="site-header">
    <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-controls="app-sidebar" aria-expanded="true" aria-label="Tutup sidebar" title="Tutup sidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <rect x="2.5" y="4" width="19" height="16" rx="2"/>
            <path d="M9 4v16"/>
            <path class="toggle-chevron" d="m16 9-3 3 3 3"/>
        </svg>
    </button>
    <a class="site-brand" href="{{ route(request()->routeIs('sim.*') ? 'sim.index' : 'dashboard') }}">Sistem Management</a>
    @php($account = Auth::guard(request()->routeIs('sim.*') ? 'simulation' : 'web')->user())
    @if ($account)
        <span class="header-account" title="{{ $account->name }}">{{ $account->name }}</span>
    @else
        <a class="header-account header-sign-in" href="{{ route('account.login') }}">Masuk</a>
    @endif
</header>
