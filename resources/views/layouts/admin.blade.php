<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Panel Admin RekomFilm</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#09090b">
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/jquery.min.js"></script>
    <script>
        const adminToken = localStorage.getItem('auth_token');
        const adminHeaders = {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        };
        if (adminToken) {
            adminHeaders['Authorization'] = 'Bearer ' + adminToken;
        }
        $.ajaxSetup({
            headers: adminHeaders
        });
    </script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Panel Admin</span>
                    </a>

                    <nav class="nav-links">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Ringkasan</a>
                        <a href="{{ route('admin.movies') }}" class="nav-link {{ request()->routeIs('admin.movies') ? 'active' : '' }}">Kelola Film</a>
                        <a href="{{ route('admin.genres') }}" class="nav-link {{ request()->routeIs('admin.genres') ? 'active' : '' }}">Kelola Genre</a>
                        <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">Kelola Pengguna</a>
                    </nav>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span>Ke Tampilan Pengguna</span>
                    </a>
                    <button id="logoutBtn" class="btn btn-ghost btn-sm btn-icon" title="Logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
        @yield('content')
    </main>

    <div id="toastContainer" class="toast-container"></div>
    <script src="/js/app.js"></script>
    @yield('scripts')
</body>
</html>
