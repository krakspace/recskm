@php
    $currentRoute = request()->route()?->getName();
    $sidebarUser = request()->routeIs('sim.*') ? null : Auth::guard('web')->user();
@endphp

<div class="sidebar-toolbar">
    <img class="sidebar-logo" src="{{ asset('modul-sop-pdf/assets/ihc.png') }}" alt="" width="198" height="83">
    <span class="sidebar-brand">
        <span>Rumah Sakit</span>
        <strong>Krakatau Medika</strong>
    </span>
</div>
<nav class="sidebar-nav" id="sidebar-nav" aria-label="Menu utama">
    @if (request()->routeIs('sim.*'))
        <a class="sidebar-link sidebar-home" href="{{ route('sim.index') }}">Daftar SOP</a>
    @else
    <a class="sidebar-link sidebar-home"
       href="{{ route('dashboard') }}"
       @if ($currentRoute === 'dashboard') aria-current="page" @endif>
        Beranda
    </a>

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
                {{ $sectionLabel }}
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
    @endif
</nav>
@auth(request()->routeIs('sim.*') ? 'simulation' : 'web')
    <form class="sidebar-footer" method="post" action="{{ route(request()->routeIs('sim.*') ? 'sim.logout' : 'account.logout') }}">
        @csrf
        <button class="sidebar-logout" type="submit">
            Logout
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 4H5v16h5m4-12 4 4-4 4m4-4H9"/>
            </svg>
        </button>
    </form>
@endauth
