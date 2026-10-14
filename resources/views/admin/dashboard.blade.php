@extends('layouts.admin')

@section('title', 'Ringkasan Sistem')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div>
        <h1 class="text-2xl font-bold">Ringkasan Sistem Rekomendasi</h1>
        <p class="text-sm text-muted">Pantau data katalog film, genre, dan statistik pengguna terdaftar.</p>
    </div>

    <!-- Stat Grid -->
    <div class="grid-cols-4">
        <div class="stat-card">
            <div class="stat-label">Total Film</div>
            <div class="stat-value" id="statMovies">-</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Genre</div>
            <div class="stat-value" id="statGenres">-</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pengguna Terdaftar</div>
            <div class="stat-value" id="statUsers">-</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Aktivitas Tonton</div>
            <div class="stat-value" id="statWatched">-</div>
        </div>
    </div>

    <!-- Quick Shortcuts Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Akses Cepat Pengelolaan</h2>
            <p class="text-sm text-muted">Pintasan ke modul CRUD data film dan preferensi sistem.</p>
        </div>
        <div class="card-body" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="{{ route('admin.movies') }}" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line></svg>
                <span>Kelola Katalog Film</span>
            </a>
            <a href="{{ route('admin.genres') }}" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span>Kelola Kategori Genre</span>
            </a>
            <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Kelola Pengguna</span>
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $.get('/api/admin/stats', function(res) {
        if (res.data) {
            $('#statMovies').text(res.data.total_movies);
            $('#statGenres').text(res.data.total_genres);
            $('#statUsers').text(res.data.total_users);
            $('#statWatched').text(res.data.total_watched);
        }
    });
});
</script>
@endsection
