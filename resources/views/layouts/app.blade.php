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
    <link rel="stylesheet" href="{{ asset('app-shell.css') }}">
    @stack('styles')
    <script src="{{ asset('app-shell.js') }}"></script>
</head>
<body>
    @include('partials.header')

    <div class="app-shell">
        <aside class="app-sidebar" id="app-sidebar">
            @include('partials.sidebar')
        </aside>

        <button class="sidebar-backdrop" type="button" aria-label="Tutup sidebar" tabindex="-1"></button>

        <main class="app-main" id="app-main">
            <div class="page-content">
                @yield('breadcrumbs')
                @yield('content')
            </div>
        </main>
    </div>

    @include('partials.footer')
    @stack('scripts')
</body>
</html>
