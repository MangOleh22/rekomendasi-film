<?php

use Illuminate\Support\Facades\Route;

// Setiap URL menampilkan view `app` dengan nama halaman; JS (resources/js/pages/{page}.js) yang mengisi konten.
// Autentikasi memakai token Sanctum (lihat routes/api.php); akses dijaga di resources/js/main.js dan di API.
$pages = [
    '/'                  => ['home', 'Beranda'],
    '/login'             => ['login', 'Masuk'],
    '/register'          => ['register', 'Daftar'],
    '/history'           => ['history', 'Riwayat Tonton'],
    '/transactions'      => ['transactions', 'Transaksi'],
    '/preferences'       => ['preferences', 'Preferensi'],
    '/cart'              => ['cart', 'Keranjang'],
    '/admin'             => ['admin-dashboard', 'Panel Admin'],
    '/admin/movies'      => ['admin-movies', 'Kelola Film'],
    '/admin/genres'      => ['admin-genres', 'Kelola Genre'],
    '/admin/users'       => ['admin-users', 'Kelola Pengguna'],
    '/admin/transactions' => ['admin-transactions', 'Riwayat Pembelian'],
];

foreach ($pages as $uri => [$page, $title]) {
    Route::view($uri, 'app', compact('page', 'title'));
}
