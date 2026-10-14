@extends('layouts.app')

@section('title', 'Preferensi Rekomendasi')

@section('content')
<div style="max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem;">
    <div>
        <h1 class="text-2xl font-bold">Pengaturan Preferensi</h1>
        <p class="text-sm text-muted">Sesuaikan preferensi genre dan rating minimum Anda agar algoritma memberikan rekomendasi paling relevan.</p>
    </div>

    <!-- Preferensi Genre Favorit -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Pilih Genre Favorit</h2>
            <p class="text-sm text-muted">Pilih satu atau lebih genre yang paling Anda sukai.</p>
        </div>
        <div class="card-body">
            <div id="genreChipsLoading" class="text-sm text-muted">Memuat daftar genre...</div>
            <div id="genreChipsContainer" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Diisi via jQuery -->
            </div>
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="button" id="saveGenresBtn" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    <span>Simpan Genre Favorit</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Rating Minimum & Profil -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold">Rating Minimum & Profil Akun</h2>
            <p class="text-sm text-muted">Saring film berdasarkan batas rating minimum yang Anda inginkan.</p>
        </div>
        <div class="card-body">
            <form id="profileForm">
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" for="prefMinRating" style="margin-bottom: 0;">Ambang Batas Rating Minimum</label>
                        <span id="currentRatingVal" class="badge badge-rating" style="font-size: 0.875rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            <span id="ratingDisplay">{{ auth()->user()->minimum_rating }}</span>
                        </span>
                    </div>
                    <input type="range" id="prefMinRating" name="minimum_rating" min="0" max="10" step="0.5" value="{{ auth()->user()->minimum_rating }}" style="width: 100%; accent-color: var(--foreground);">
                    <span class="text-xs text-muted" style="display: block; margin-top: 0.25rem;">
                        Film dengan rating di bawah nilai ini tidak akan ditampilkan pada rekomendasi maupun pencarian umum.
                    </span>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label class="form-label" for="profileName">Nama Lengkap</label>
                    <input type="text" id="profileName" name="name" class="form-input" value="{{ auth()->user()->name }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tanggal Lahir & Usia</label>
                    <input type="date" id="profileBirthDate" name="birth_date" class="form-input" value="{{ auth()->user()->birth_date?->format('Y-m-d') }}" max="{{ date('Y-m-d', strtotime('-1 day')) }}">
                    <span class="text-xs text-muted" style="display: block; margin-top: 0.25rem;">
                        Usia saat ini: <strong style="color: var(--foreground);">{{ auth()->user()->age }} tahun</strong>. Batas usia film LSF diatur otomatis menurut tanggal lahir.
                    </span>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="submit" id="saveProfileBtn" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <span>Simpan Perubahan Akun</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let allGenres = [];
    let userFavorites = [];

    $('#prefMinRating').on('input', function() {
        $('#ratingDisplay').text(parseFloat($(this).val()).toFixed(1));
    });

    // Load genres & current user favorites
    $.when(
        $.get('/api/genres'),
        $.get('/api/me/favorite-genres')
    ).done(function(genresRes, favRes) {
        $('#genreChipsLoading').hide();
        allGenres = genresRes[0].data || [];
        userFavorites = (favRes[0].data || []).map(g => g.id);

        let html = '';
        allGenres.forEach(function(g) {
            const isChecked = userFavorites.includes(g.id);
            html += `
                <label class="genre-pill ${isChecked ? 'active' : ''}" style="cursor: pointer; user-select: none;">
                    <input type="checkbox" name="genre_id" value="${g.id}" ${isChecked ? 'checked' : ''} style="display: none;" class="genre-checkbox">
                    ${escapeHtml(g.name)}
                </label>
            `;
        });
        $('#genreChipsContainer').html(html);
    });

    // Toggle chip styling
    $(document).on('change', '.genre-checkbox', function() {
        const $label = $(this).closest('label');
        if ($(this).is(':checked')) {
            $label.addClass('active');
        } else {
            $label.removeClass('active');
        }
    });

    // Save favorite genres
    $('#saveGenresBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Menyimpan...');

        const selected = [];
        $('.genre-checkbox:checked').each(function() {
            selected.push(parseInt($(this).val()));
        });

        $.ajax({
            url: '/api/me/favorite-genres',
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ genre_ids: selected }),
            success: function(res) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>Simpan Genre Favorit</span>');
                showToast('Genre favorit berhasil diperbarui!');
            },
            error: function() {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>Simpan Genre Favorit</span>');
                showToast('Gagal menyimpan genre favorit.', 'error');
            }
        });
    });

    // Save profile and minimum rating
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#saveProfileBtn');
        $btn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: '/api/me',
            type: 'PUT',
            data: {
                name: $('#profileName').val(),
                birth_date: $('#profileBirthDate').val(),
                minimum_rating: $('#prefMinRating').val()
            },
            success: function(res) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>Simpan Perubahan Akun</span>');
                showToast('Profil dan rating minimum berhasil disimpan!');
                setTimeout(() => window.location.reload(), 600);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg><span>Simpan Perubahan Akun</span>');
                showToast(xhr.responseJSON?.message || 'Gagal menyimpan profil.', 'error');
            }
        });
    });
});
</script>
@endsection
