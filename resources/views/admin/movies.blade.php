@extends('layouts.admin')

@section('title', 'Kelola Film')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="text-2xl font-bold">Kelola Katalog Film</h1>
            <p class="text-sm text-muted">Tambah, ubah, atau hapus film beserta klasifikasi usia resmi LSF.</p>
        </div>
        <button id="addMovieBtn" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Tambah Film Baru</span>
        </button>
    </div>

    <!-- Filter & Search Bar -->
    <div class="filter-bar">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="adminMovieSearch" class="form-input search-input" placeholder="Cari judul film...">
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <select id="adminAgeFilter" class="form-select" style="width: auto;">
                <option value="">Semua Batas Usia</option>
                <option value="0">SU (Semua Usia)</option>
                <option value="13">13+</option>
                <option value="17">17+</option>
                <option value="21">21+</option>
            </select>
        </div>
    </div>

    <!-- Tabel Film -->
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Judul & Tahun</th>
                        <th>Kategori Usia</th>
                        <th>Rating</th>
                        <th>Harga Film</th>
                        <th>Genre</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="adminMoviesTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--muted-foreground);">
                            Memuat data film...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer" id="adminPaginationFooter" style="display: none;">
            <span class="text-xs text-muted" id="pageInfo">Halaman 1</span>
            <div style="display: flex; gap: 0.5rem;">
                <button id="prevPageBtn" class="btn btn-outline btn-sm">Sebelumnya</button>
                <button id="nextPageBtn" class="btn btn-outline btn-sm">Berikutnya</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Tambah/Ubah Film -->
<div id="movieFormDialog" class="dialog-overlay">
    <div class="dialog-content">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
            <h3 id="movieFormTitle" class="text-lg">Tambah Film Baru</h3>
            <button id="closeMovieFormBtn" class="btn btn-ghost btn-sm btn-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="movieAdminForm" enctype="multipart/form-data">
            <input type="hidden" id="formMovieId" name="id">
            <div class="card-body" style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="movieTitle">Judul Film</label>
                    <input type="text" id="movieTitle" name="title" class="form-input" required>
                </div>

                <div class="grid-cols-2" style="gap: 0.75rem;">
                    <div>
                        <label class="form-label" for="movieYear">Tahun Rilis</label>
                        <input type="number" id="movieYear" name="year" class="form-input" min="1900" max="2100" value="{{ date('Y') }}" required>
                    </div>
                    <div>
                        <label class="form-label" for="movieRating">Rating (0-10)</label>
                        <input type="number" id="movieRating" name="rating" class="form-input" step="0.1" min="0" max="10" value="7.5" required>
                    </div>
                </div>

                <div class="grid-cols-2" style="gap: 0.75rem;">
                    <div>
                        <label class="form-label" for="movieAgeLimit">Batas Usia (LSF)</label>
                        <select id="movieAgeLimit" name="age_limit" class="form-select" required>
                            <option value="0">Di Bawah Umur - SU (Semua Usia)</option>
                            <option value="13">Remaja - 13+</option>
                            <option value="17">Dewasa - 17+</option>
                            <option value="21">Dewasa - 21+</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="moviePrice">Harga Film (Rupiah)</label>
                        <input type="text" id="moviePrice" name="price" class="form-input" placeholder="Contoh: 10.000 atau 25.000" autocomplete="off">
                        <span class="text-xs text-muted" id="pricePreview">Format: RP. 0</span>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="movieSynopsis">Sinopsis</label>
                    <textarea id="movieSynopsis" name="synopsis" class="form-textarea" rows="3"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Pilih Genre</label>
                    <div id="movieFormGenres" style="display: flex; gap: 0.375rem; flex-wrap: wrap;">
                        <!-- Checkbox genre dirender via jQuery -->
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="moviePoster">Unggah Poster (Opsional)</label>
                    <input type="file" id="moviePoster" name="poster" class="form-input" accept="image/*">
                </div>
            </div>
            <div class="card-footer" style="border-top: 1px solid var(--border); justify-content: flex-end; gap: 0.75rem;">
                <button type="button" id="cancelMovieFormBtn" class="btn btn-outline">Batal</button>
                <button type="submit" id="submitMovieFormBtn" class="btn btn-primary">Simpan Film</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let totalPages = 1;
    let allGenres = [];
    let searchDebounce = null;

    // Load genres for modal
    $.get('/api/genres', function(res) {
        allGenres = res.data || [];
        let html = '';
        allGenres.forEach(g => {
            html += `
                <label class="genre-pill" style="cursor: pointer; user-select: none;">
                    <input type="checkbox" name="genres[]" value="${g.id}" class="form-genre-chk" style="display: none;">
                    ${escapeHtml(g.name)}
                </label>
            `;
        });
        $('#movieFormGenres').html(html);
    });

    $(document).on('change', '.form-genre-chk', function() {
        if ($(this).is(':checked')) {
            $(this).closest('label').addClass('active');
        } else {
            $(this).closest('label').removeClass('active');
        }
    });

    function formatRupiah(angka) {
        if (!angka) return '';
        const numberString = angka.toString().replace(/[^0-9]/g, '');
        const sisa = numberString.length % 3;
        let rupiah = numberString.substr(0, sisa);
        const ribuan = numberString.substr(sisa).match(/\d{3}/g);

        if (ribuan) {
            const separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return rupiah;
    }

    $('#moviePrice').on('input', function() {
        const formatted = formatRupiah($(this).val());
        $(this).val(formatted);
        $('#pricePreview').text(formatted ? 'Format: RP. ' + formatted : 'Format: RP. 0 (Gratis)');
    });

    /**
     * Algoritma if-else untuk penentuan badge kategori umur film
     */
    function getAgeBadge(ageLimit) {
        const age = parseInt(ageLimit) || 0;
        if (age < 13) {
            return '<span class="badge badge-su" title="Semua Usia">Di Bawah Umur (SU)</span>';
        } else if (age < 18) {
            return '<span class="badge badge-13" title="13+ Remaja">Remaja (13+)</span>';
        } else {
            return `<span class="badge badge-17" title="Dewasa">Dewasa (${age >= 21 ? '21+' : '18+'})</span>`;
        }
    }

    function loadMovies(page = 1) {
        const q = $('#adminMovieSearch').val();
        const age = $('#adminAgeFilter').val();

        let url = `/api/admin/movies?page=${page}&per_page=10`;
        if (q) url += `&q=${encodeURIComponent(q)}`;
        if (age !== '') url += `&age_limit=${encodeURIComponent(age)}`;

        $.get(url, function(res) {
            const list = res.data || [];
            currentPage = res.current_page;
            totalPages = res.last_page;

            $('#pageInfo').text(`Halaman ${currentPage} dari ${totalPages}`);
            $('#adminPaginationFooter').show();
            $('#prevPageBtn').prop('disabled', currentPage <= 1);
            $('#nextPageBtn').prop('disabled', currentPage >= totalPages);

            if (list.length === 0) {
                $('#adminMoviesTableBody').html(`
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            Tidak ada data film yang cocok.
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            list.forEach(m => {
                const genres = (m.genres || []).map(g => `<span class="badge badge-genre">${escapeHtml(g.name)}</span>`).join(' ');
                html += `
                    <tr>
                        <td class="text-xs text-muted">#${m.id}</td>
                        <td>
                            <div class="font-semibold">${escapeHtml(m.title)}</div>
                            <div class="text-xs text-muted">Tahun: ${m.year}</div>
                        </td>
                        <td>${getAgeBadge(m.age_limit)}</td>
                        <td>
                            <span class="badge-rating">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                ${m.rating}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: #34d399; font-size: 0.875rem;">
                                ${m.formatted_price || 'Gratis'}
                            </span>
                        </td>
                        <td><div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">${genres}</div></td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.5rem;">
                                <button class="btn btn-outline btn-sm edit-movie-btn" data-id="${m.id}">Ubah</button>
                                <button class="btn btn-destructive btn-sm delete-movie-btn" data-id="${m.id}" data-title="${escapeHtml(m.title)}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#adminMoviesTableBody').html(html);
        });
    }

    $('#adminMovieSearch').on('input', function() {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => loadMovies(1), 300);
    });

    $('#adminAgeFilter').on('change', function() {
        loadMovies(1);
    });

    $('#prevPageBtn').on('click', function() {
        if (currentPage > 1) loadMovies(currentPage - 1);
    });

    $('#nextPageBtn').on('click', function() {
        if (currentPage < totalPages) loadMovies(currentPage + 1);
    });

    // Modal Trigger: Tambah Film
    $('#addMovieBtn').on('click', function() {
        $('#movieFormTitle').text('Tambah Film Baru');
        $('#formMovieId').val('');
        $('#movieAdminForm')[0].reset();
        $('#moviePrice').val('');
        $('#pricePreview').text('Format: RP. 0 (Gratis)');
        $('.form-genre-chk').prop('checked', false).closest('label').removeClass('active');
        $('#movieFormDialog').addClass('active');
    });

    // Modal Trigger: Ubah Film
    $(document).on('click', '.edit-movie-btn', function() {
        const id = $(this).data('id');
        $.get(`/api/admin/movies/${id}`, function(res) {
            const m = res.data;
            $('#movieFormTitle').text('Ubah Data Film');
            $('#formMovieId').val(m.id);
            $('#movieTitle').val(m.title);
            $('#movieYear').val(m.year);
            $('#movieRating').val(m.rating);
            $('#movieAgeLimit').val(m.age_limit);
            $('#movieSynopsis').val(m.synopsis);

            const formattedPrice = formatRupiah(m.price || 0);
            $('#moviePrice').val(formattedPrice);
            $('#pricePreview').text(formattedPrice ? 'Format: RP. ' + formattedPrice : 'Format: RP. 0 (Gratis)');

            const activeGenreIds = (m.genres || []).map(g => g.id);
            $('.form-genre-chk').each(function() {
                const val = parseInt($(this).val());
                if (activeGenreIds.includes(val)) {
                    $(this).prop('checked', true).closest('label').addClass('active');
                } else {
                    $(this).prop('checked', false).closest('label').removeClass('active');
                }
            });

            $('#movieFormDialog').addClass('active');
        });
    });

    // Close Modal
    $('#closeMovieFormBtn, #cancelMovieFormBtn, #movieFormDialog').on('click', function(e) {
        if (e.target === this) {
            $('#movieFormDialog').removeClass('active');
        }
    });

    // Submit Form Tambah / Ubah
    $('#movieAdminForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#formMovieId').val();
        const isEdit = !!id;
        const $btn = $('#submitMovieFormBtn');
        $btn.prop('disabled', true).text('Menyimpan...');

        const formData = new FormData(this);
        let url = isEdit ? `/api/admin/movies/${id}` : '/api/admin/movies';

        // Laravel spoof PUT jika ada form-data file
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).text('Simpan Film');
                $('#movieFormDialog').removeClass('active');
                showToast(res.message);
                loadMovies(currentPage);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Simpan Film');
                const errors = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Gagal menyimpan film.';
                if (errors) {
                    msg = Object.values(errors).flat().join('<br>');
                }
                showToast(msg, 'error');
            }
        });
    });

    // Hapus Film
    $(document).on('click', '.delete-movie-btn', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        if (confirm(`Yakin ingin menghapus film "${title}"?`)) {
            $.ajax({
                url: `/api/admin/movies/${id}`,
                type: 'DELETE',
                success: function(res) {
                    showToast(res.message);
                    loadMovies(currentPage);
                },
                error: function() {
                    showToast('Gagal menghapus film.', 'error');
                }
            });
        }
    });

    // Initial load
    loadMovies(1);
});
</script>
@endsection
