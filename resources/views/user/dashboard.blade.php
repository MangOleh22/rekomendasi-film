@extends('layouts.app')

@section('title', 'Dashboard Rekomendasi')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Banner Profil Singkat -->
    <div class="card" style="background: linear-gradient(135deg, #18181b 0%, #09090b 100%); border-color: #27272a; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="text-2xl font-bold" style="margin-bottom: 0.25rem;">Halo, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-muted">
                    Sistem mendeteksi usia Anda <strong style="color: var(--foreground);">{{ auth()->user()->age }} tahun</strong>. 
                    Hanya menampilkan film dengan klasifikasi usia sesuai izin Sensor (LSF: {{ auth()->user()->age < 13 ? 'SU' : (auth()->user()->age < 17 ? 'SU, 13+' : (auth()->user()->age < 21 ? 'SU, 13+, 17+' : 'Semua Termasuk 21+')) }}).
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <span class="badge badge-rating" style="padding: 0.4rem 0.8rem; background: #1c1c22; border-radius: var(--radius); border: 1px solid var(--border);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>Min Rating: {{ auth()->user()->minimum_rating }}</span>
                </span>
                <a href="{{ route('preferences') }}" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    <span>Ubah Preferensi</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Navigasi Tab -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div class="tabs">
            <button class="tab-btn active" data-tab="recommendations">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Rekomendasi Untuk Anda
            </button>
            <button class="tab-btn" data-tab="all-movies">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line></svg>
                Katalog Film
            </button>
        </div>

        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="movieSearchInput" class="form-input search-input" placeholder="Cari judul atau sinopsis film...">
        </div>
    </div>

    <!-- Filter Genre Pills (Untuk Katalog Film) -->
    <div id="genreFilterContainer" style="display: none;">
        <div class="genre-pills" id="genrePillsList">
            <button class="genre-pill active" data-genre="">Semua Genre</button>
            <!-- Diisi lewat jQuery -->
        </div>
    </div>

    <!-- Kontainer Daftar Film -->
    <div>
        <div id="moviesLoading" style="display: none; text-align: center; padding: 4rem 0;">
            <p class="text-sm text-muted">Memuat daftar film...</p>
        </div>

        <div id="moviesGrid" class="grid-movies">
            <!-- Kartu film dirender lewat jQuery -->
        </div>

        <div id="moviesEmpty" style="display: none; text-align: center; padding: 4rem 0;" class="card">
            <div style="max-width: 320px; margin: 0 auto;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted-foreground); margin: 0 auto 1rem;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <h3 class="text-lg font-semibold" style="margin-bottom: 0.5rem;">Tidak Ada Film</h3>
                <p class="text-sm text-muted">Tidak ditemukan film yang sesuai dengan filter usia atau preferensi pencarian Anda saat ini.</p>
            </div>
        </div>

        <!-- Tombol Muat Lebih Banyak (Paginasi Katalog) -->
        <div id="loadMoreContainer" style="display: none; text-align: center; margin-top: 2rem;">
            <button id="loadMoreBtn" class="btn btn-outline">Muat Lebih Banyak</button>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentTab = 'recommendations';
    let currentGenre = '';
    let searchQuery = '';
    let currentPage = 1;
    let hasMorePages = false;
    let searchDebounceTimer = null;

    // Load Genre Pills
    $.get('/api/genres', function(res) {
        if (res.data) {
            let html = '<button class="genre-pill active" data-genre="">Semua Genre</button>';
            res.data.forEach(function(g) {
                html += `<button class="genre-pill" data-genre="${g.slug}">${escapeHtml(g.name)}</button>`;
            });
            $('#genrePillsList').html(html);
        }
    });

    // Helper render badge usia LSF
    function getAgeBadge(ageLimit) {
        switch(parseInt(ageLimit)) {
            case 0: return '<span class="badge badge-su">SU</span>';
            case 13: return '<span class="badge badge-13">13+</span>';
            case 17: return '<span class="badge badge-17">17+</span>';
            case 21: return '<span class="badge badge-21">21+</span>';
            default: return `<span class="badge badge-su">${ageLimit}+</span>`;
        }
    }

    // Render single movie card
    function renderMovieCard(movie, isRecommendation = false) {
        const genresHtml = (movie.genres || []).map(g => `<span class="badge badge-genre">${escapeHtml(g.name)}</span>`).join(' ');
        
        let scoreBadge = '';
        if (isRecommendation && typeof movie.score !== 'undefined') {
            scoreBadge = `
                <div class="movie-score-badge" title="Skor rekomendasi: dihitung dari afinitas genre, Jaccard, dan rating">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <span>${movie.score}% Cocok</span>
                </div>
            `;
        }

        const posterSrc = movie.poster_path || '';
        const posterEl = posterSrc 
            ? `<img src="${posterSrc}" alt="${escapeHtml(movie.title)}" class="movie-poster-img">`
            : `<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--muted-foreground); gap: 0.5rem;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line></svg>
                <span class="text-xs">${escapeHtml(movie.title)}</span>
               </div>`;

        return `
            <div class="movie-card" data-id="${movie.id}">
                <div class="movie-poster-box">
                    ${posterEl}
                    ${scoreBadge}
                </div>
                <div class="movie-content">
                    <div class="movie-meta">
                        ${getAgeBadge(movie.age_limit)}
                        <span class="badge-rating">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            ${parseFloat(movie.rating).toFixed(1)}
                        </span>
                        <span class="text-xs text-muted">${movie.year}</span>
                    </div>
                    <h3 class="movie-title">${escapeHtml(movie.title)}</h3>
                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                        ${genresHtml}
                    </div>
                    <p class="movie-synopsis">${escapeHtml(movie.synopsis || 'Belum ada sinopsis.')}</p>
                    <div style="display: flex; gap: 0.5rem; margin-top: auto; padding-top: 0.5rem; border-top: 1px solid var(--border);">
                        <button class="btn btn-secondary btn-sm view-detail-btn" data-id="${movie.id}" style="flex: 1;">
                            Detail
                        </button>
                        <button class="btn btn-outline btn-sm watch-movie-btn" data-id="${movie.id}" title="Tandai Sudah Ditonton">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Tonton</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Load recommendations
    function loadRecommendations() {
        $('#moviesLoading').show();
        $('#moviesGrid').empty();
        $('#moviesEmpty').hide();
        $('#loadMoreContainer').hide();
        $('#genreFilterContainer').hide();

        $.get('/api/recommendations', function(res) {
            $('#moviesLoading').hide();
            const list = res.data || [];
            if (list.length === 0) {
                $('#moviesEmpty').show();
                return;
            }
            let html = '';
            list.forEach(m => {
                html += renderMovieCard(m, true);
            });
            $('#moviesGrid').html(html);
        }).fail(function() {
            $('#moviesLoading').hide();
            showToast('Gagal memuat rekomendasi.', 'error');
        });
    }

    // Load all movies catalog
    function loadAllMovies(page = 1, append = false) {
        if (!append) {
            $('#moviesLoading').show();
            $('#moviesGrid').empty();
            $('#moviesEmpty').hide();
        }

        let url = `/api/movies?page=${page}&per_page=12`;
        if (currentGenre) {
            url += `&genre=${encodeURIComponent(currentGenre)}`;
        }

        $.get(url, function(res) {
            $('#moviesLoading').hide();
            const list = res.data || [];
            currentPage = res.current_page || 1;
            hasMorePages = currentPage < (res.last_page || 1);

            if (list.length === 0 && !append) {
                $('#moviesEmpty').show();
                $('#loadMoreContainer').hide();
                return;
            }

            let html = '';
            list.forEach(m => {
                html += renderMovieCard(m, false);
            });

            if (append) {
                $('#moviesGrid').append(html);
            } else {
                $('#moviesGrid').html(html);
            }

            if (hasMorePages) {
                $('#loadMoreContainer').show();
            } else {
                $('#loadMoreContainer').hide();
            }
        }).fail(function() {
            $('#moviesLoading').hide();
            showToast('Gagal memuat katalog film.', 'error');
        });
    }

    // Perform Search with Debounce
    function doSearch(term) {
        if (term.trim() === '') {
            if (currentTab === 'recommendations') {
                loadRecommendations();
            } else {
                loadAllMovies(1);
            }
            return;
        }

        $('#moviesLoading').show();
        $('#moviesGrid').empty();
        $('#moviesEmpty').hide();
        $('#loadMoreContainer').hide();

        $.get(`/api/movies/search?q=${encodeURIComponent(term)}`, function(res) {
            $('#moviesLoading').hide();
            const list = res.data || [];
            if (list.length === 0) {
                $('#moviesEmpty').show();
                return;
            }
            let html = '';
            list.forEach(m => {
                html += renderMovieCard(m, false);
            });
            $('#moviesGrid').html(html);
        }).fail(function() {
            $('#moviesLoading').hide();
            showToast('Pencarian gagal.', 'error');
        });
    }

    // Debounced search input handler
    $('#movieSearchInput').on('input', function() {
        const query = $(this).val();
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function() {
            doSearch(query);
        }, 300);
    });

    // Tab buttons
    $('.tab-btn').on('click', function() {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        currentTab = $(this).data('tab');
        $('#movieSearchInput').val('');

        if (currentTab === 'recommendations') {
            loadRecommendations();
        } else {
            $('#genreFilterContainer').show();
            loadAllMovies(1);
        }
    });

    // Genre pill click
    $(document).on('click', '.genre-pill', function() {
        $('.genre-pill').removeClass('active');
        $(this).addClass('active');
        currentGenre = $(this).data('genre');
        loadAllMovies(1);
    });

    // Load more button
    $('#loadMoreBtn').on('click', function() {
        if (hasMorePages) {
            loadAllMovies(currentPage + 1, true);
        }
    });

    // Mark as watched
    $(document).on('click', '.watch-movie-btn', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.post(`/api/movies/${id}/watch`, function(res) {
            showToast('Ditambahkan ke riwayat tonton!');
            // Jika di tab rekomendasi, reload rekomendasi karena film yang sudah ditonton disaring keluar
            if (currentTab === 'recommendations') {
                setTimeout(loadRecommendations, 300);
            } else {
                $btn.html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Ditonton</span>');
            }
        }).fail(function(xhr) {
            $btn.prop('disabled', false);
            showToast(xhr.responseJSON?.message || 'Gagal mencatat tontonan.', 'error');
        });
    });

    // View Movie Detail in modal
    $(document).on('click', '.view-detail-btn', function() {
        const id = $(this).data('id');
        $.get(`/api/movies/${id}`, function(res) {
            const m = res.data;
            $('#modalMovieTitle').text(m.title);

            const genresHtml = (m.genres || []).map(g => `<span class="badge badge-genre">${escapeHtml(g.name)}</span>`).join(' ');
            
            const detailHtml = `
                <div style="display: flex; gap: 1.25rem; flex-direction: column;">
                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                        ${getAgeBadge(m.age_limit)}
                        <span class="badge-rating" style="font-size: 0.875rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            ${m.rating} / 10
                        </span>
                        <span class="text-sm text-muted">Tahun Rilis: ${m.year}</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-muted" style="text-transform: uppercase;">Genre</span>
                        <div style="display: flex; gap: 0.375rem; flex-wrap: wrap; margin-top: 0.25rem;">
                            ${genresHtml}
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-muted" style="text-transform: uppercase;">Sinopsis</span>
                        <p class="text-sm" style="margin-top: 0.25rem; line-height: 1.6; color: #d4d4d8;">
                            ${escapeHtml(m.synopsis || 'Tidak ada deskripsi sinopsis untuk film ini.')}
                        </p>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                        <button class="btn btn-primary watch-movie-btn" data-id="${m.id}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Tandai Sudah Ditonton</span>
                        </button>
                    </div>
                </div>
            `;
            $('#modalMovieBody').html(detailHtml);
            $('#movieDetailDialog').addClass('active');
        }).fail(function(xhr) {
            showToast(xhr.responseJSON?.message || 'Gagal memuat detail film.', 'error');
        });
    });

    // Close modal
    $('#closeModalBtn, #movieDetailDialog').on('click', function(e) {
        if (e.target === this) {
            $('#movieDetailDialog').removeClass('active');
        }
    });

    // Initial load
    loadRecommendations();
});
</script>
@endsection
