<nav class="nav nav-pills flex-column bg-light border-bottom px-3 py-2" aria-label="Menu utama">
    <a @class(['nav-link', 'active' => request()->routeIs('dashboard')])
       href="{{ route('dashboard') }}"
       @if (request()->routeIs('dashboard')) aria-current="page" @endif>
        Dashboard
    </a>

    <a @class(['nav-link', 'active' => request()->routeIs('form_SOP')])
       href="{{ route('form_SOP') }}"
       @if (request()->routeIs('form_SOP')) aria-current="page" @endif>
        Form SOP
    </a>
</nav>