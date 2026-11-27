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
        <form method="post" action="{{ route('sim.logout') }}">@csrf
            <button class="btn btn-outline-secondary m-2" type="submit">Keluar / ganti akun</button>
        </form>
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
    @auth('web')
        <form method="post" action="{{ route('account.logout') }}">
            @csrf
            <button class="btn btn-outline-secondary m-2" type="submit">Keluar</button>
        </form>
    @endauth
    @endif
</nav>
