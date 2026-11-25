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
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            font-family: system-ui, sans-serif;
        }

        .site-header, .site-footer {
            padding: 1rem;
            background: #172554;
            color: white;
        }


        .sidebar, main {
            padding: 1rem;
        }

        .sidebar {
            background: #eff6ff;
        }


    </style>
</head>
<body>
    @include('partials.header')

    <div class="d-flex flex-row">
    <aside class="flex-shrink-0 bg-light border-end" style="width: 12rem;">
        @include('partials.sidebar')
    </aside>

    <main class="flex-grow-1 p-3">
        @yield('content')
    </main>
    </div>

    @include('partials.footer')
</body>
</html>