@php
    $currentRoute = request()->route()?->getName();
@endphp

<div class="sidebar-toolbar">
    <h2 id="sidebar-heading">Menu utama</h2>
    <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-controls="sidebar-nav" aria-expanded="true" aria-label="Tutup sidebar" title="Tutup sidebar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <rect x="2.5" y="4" width="19" height="16" rx="2"/>
            <path d="M9 4v16"/>
            <path class="toggle-chevron" d="m16 9-3 3 3 3"/>
        </svg>
    </button>
</div>
<nav class="sidebar-nav" id="sidebar-nav" aria-labelledby="sidebar-heading">
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
            $sectionIsActive = in_array($currentRoute, $items, true)
                || (in_array('admin.users.index', $items, true) && request()->routeIs('admin.users.*'));
        @endphp

        <details @if ($sectionIsActive) open @endif>
            <summary @class(['sidebar-link', 'sidebar-section', 'sidebar-section-current' => $sectionIsActive])>
                {{ $sectionLabel }}
            </summary>

            <ul class="sidebar-submenu">
                @foreach ($items as $label => $routeName)
                    @continue($routeName === 'admin.users.index' && Auth::guard('web')->check() && ! \App\Http\Middleware\EnsureSuperAdmin::allows(request()))
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
