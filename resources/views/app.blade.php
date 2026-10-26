<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    {{-- Pasang tema tersimpan sebelum render agar tidak berkedip (default: gelap) --}}
    <script>
        try { document.documentElement.classList.toggle('dark', (localStorage.getItem('theme') || 'dark') === 'dark'); } catch (e) { document.documentElement.classList.add('dark'); }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} - RekomFilm</title>
    <meta name="description" content="Rekomendasi film sesuai usia (LSF), genre favorit, dan riwayat tontonan.">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#09090b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    @vite('resources/js/main.js')
</head>
{{-- Isi halaman digambar oleh resources/js/pages/{page}.js --}}
<body data-page="{{ $page }}"></body>
</html>
