<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Sistem Management</title>
    <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css"
    integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX"
    crossorigin="anonymous"
    >
    <link rel="stylesheet" href="{{ asset('theme.css') }}">
    <link rel="stylesheet" href="{{ asset('app-shell.css') }}">
    @stack('styles')
    <script src="{{ asset('app-shell.js') }}"></script>
</head>
<body>
    <div class="app-shell">
        @include('partials.header')

        <aside class="app-sidebar" id="app-sidebar">
            @include('partials.sidebar')
        </aside>

        <button class="sidebar-backdrop" type="button" aria-label="Tutup sidebar" tabindex="-1"></button>

        <main class="app-main" id="app-main">
            <div class="page-content">
                @unless (request()->routeIs('sim.*'))
                    @auth('web')
                        @if (session()->has(\App\Http\Controllers\UserImpersonationController::SESSION_KEY))
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>Anda sedang masuk sebagai <strong>{{ Auth::guard('web')->user()->name }}</strong>.</span>
                                <form method="post" action="{{ route('admin.impersonation.destroy') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-dark" type="submit">Kembali ke Super Admin</button>
                                </form>
                            </div>
                        @endif
                    @endauth
                @endunless
                @yield('breadcrumbs')
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
