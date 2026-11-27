@php
    $currentRoute = request()->route()?->getName();
    $isSimulation = request()->routeIs('sim.*');
    $homeRoute = $isSimulation ? 'sim.index' : 'dashboard';
    $sidebarUser = $isSimulation ? null : Auth::guard('web')->user();
    $sectionIcons = [
        'Pengelolaan SOP' => 'M4 7V4h6l2 2h8v14H4V7Zm0 2h16',
        'Pemeriksaan SOP' => 'M8 5H5v16h14V5h-3m-8 0V3h8v4H8V5Zm0 9 2.5 2.5L16 11',
        'Pustaka SOP' => 'M4 4h5v16H4zm5 0h5v16H9zm5 2 4.5-1.5L22 19l-4.5 1.5L14 6Z',
        'Verifikasi Dokumen' => 'M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3Zm-3 9 2 2 4-4',
        'Administrasi' => 'M4 7h16M4 12h16M4 17h16M8 5v4m8 1v4m-6 1v4',
        'Audit' => 'M6 3h12v18H6zM9 8h6m-6 4h6m-6 4h4',
    ];
@endphp

<div class="sidebar-toolbar">
    <img class="sidebar-logo" src="{{ asset('modul-sop-pdf/assets/ihc.png') }}" alt="" width="198" height="83">
    <span class="sidebar-brand">
        <span>Rumah Sakit</span>
        <strong>Krakatau Medika</strong>
    </span>
</div>
<nav class="sidebar-nav" id="sidebar-nav" aria-label="Menu utama">
    <a class="sidebar-link sidebar-home"
       href="{{ route($homeRoute) }}"
       @if ($currentRoute === $homeRoute) aria-current="page" @endif>
        <svg class="sidebar-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 10.5 12 3l9 7.5M5 9v12h5v-6h4v6h5V9"/>
        </svg>
        {{ $isSimulation ? 'Daftar SOP' : 'Beranda' }}
    </a>

    @unless ($isSimulation)
    @foreach (config('sidebar') as $sectionLabel => $items)
        @php
            if ($sidebarUser) {
                $items = array_filter($items, fn ($page) => $page === 'admin.users.index'
                    ? \App\Http\Middleware\EnsureSuperAdmin::allows(request())
                    : \App\Http\Middleware\EnsurePageAccess::allows(request(), $page));
            }
            $sectionIsActive = in_array($currentRoute, $items, true)
                || (in_array('admin.users.index', $items, true) && request()->routeIs('admin.users.*', 'admin.roles.*'));
        @endphp
        @continue($items === [])

        <details @if ($sectionIsActive) open @endif>
            <summary @class(['sidebar-link', 'sidebar-section', 'sidebar-section-current' => $sectionIsActive])>
                <svg class="sidebar-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="{{ $sectionIcons[$sectionLabel] ?? 'M5 6h14M5 12h14M5 18h14' }}"/>
                </svg>
                <span>{{ $sectionLabel }}</span>
            </summary>

            <ul class="sidebar-submenu">
                @foreach ($items as $label => $routeName)
                    <li>
                        <a class="sidebar-sublink"
                           href="{{ route($routeName) }}"
                           @if ($currentRoute === $routeName) aria-current="page" @endif>
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </details>
    @endforeach
    @endunless
</nav>
@auth($isSimulation ? 'simulation' : 'web')
    <form class="sidebar-footer" method="post" action="{{ route($isSimulation ? 'sim.logout' : 'account.logout') }}">
        @csrf
        <button class="sidebar-logout" type="submit">
            <svg class="sidebar-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M10 4H5v16h5m4-12 4 4-4 4m4-4H9"/>
            </svg>
            Keluar
        </button>
    </form>
@endauth
