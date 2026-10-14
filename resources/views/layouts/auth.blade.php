<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - RekomFilm</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#09090b">
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/jquery.min.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });
    </script>
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="width: 100%; max-width: 440px;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="/" style="display: inline-flex; align-items: center; gap: 0.625rem; font-weight: 700; font-size: 1.25rem; text-decoration: none; color: var(--foreground);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                    <line x1="7" y1="2" x2="7" y2="22"></line>
                    <line x1="17" y1="2" x2="17" y2="22"></line>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <line x1="2" y1="7" x2="7" y2="7"></line>
                    <line x1="2" y1="17" x2="7" y2="17"></line>
                    <line x1="17" y1="17" x2="22" y2="17"></line>
                    <line x1="17" y1="7" x2="22" y2="7"></line>
                </svg>
                <span>RekomFilm</span>
            </a>
            <p class="text-sm text-muted" style="margin-top: 0.5rem;">Rekomendasi film cerdas sesuai usia & preferensi Anda</p>
        </div>

        @yield('content')
    </div>
    <div id="toastContainer" class="toast-container"></div>
    <script src="/js/app.js"></script>
    @yield('scripts')
</body>
</html>
