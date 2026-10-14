@extends('layouts.admin')

@section('title', 'Kelola Genre')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="text-2xl font-bold">Kelola Genre Film</h1>
            <p class="text-sm text-muted">Daftar kategori genre yang digunakan dalam perhitungan rekomendasi.</p>
        </div>
        <button id="addGenreBtn" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>Tambah Genre Baru</span>
        </button>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Nama Genre</th>
                        <th>Slug</th>
                        <th>Jumlah Film Terkait</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="genresTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            Memuat data genre...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Tambah/Ubah Genre -->
<div id="genreFormDialog" class="dialog-overlay">
    <div class="dialog-content" style="max-width: 440px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
            <h3 id="genreFormTitle" class="text-lg">Tambah Genre Baru</h3>
            <button id="closeGenreFormBtn" class="btn btn-ghost btn-sm btn-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="genreAdminForm">
            <input type="hidden" id="formGenreId">
            <div class="card-body">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="genreName">Nama Genre</label>
                    <input type="text" id="genreName" name="name" class="form-input" placeholder="contoh: Thriller Psikologis" required>
                </div>
            </div>
            <div class="card-footer" style="border-top: 1px solid var(--border); justify-content: flex-end; gap: 0.75rem;">
                <button type="button" id="cancelGenreFormBtn" class="btn btn-outline">Batal</button>
                <button type="submit" id="submitGenreFormBtn" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function loadGenres() {
        $.get('/api/admin/genres', function(res) {
            const list = res.data || [];
            if (list.length === 0) {
                $('#genresTableBody').html(`
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            Belum ada genre film.
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            list.forEach(g => {
                html += `
                    <tr>
                        <td class="text-xs text-muted">#${g.id}</td>
                        <td class="font-semibold">${escapeHtml(g.name)}</td>
                        <td class="text-xs text-muted"><code>${escapeHtml(g.slug)}</code></td>
                        <td>
                            <span class="badge badge-genre">${g.movies_count || 0} Film</span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.5rem;">
                                <button class="btn btn-outline btn-sm edit-genre-btn" data-id="${g.id}" data-name="${escapeHtml(g.name)}">Ubah</button>
                                <button class="btn btn-destructive btn-sm delete-genre-btn" data-id="${g.id}" data-name="${escapeHtml(g.name)}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#genresTableBody').html(html);
        });
    }

    $('#addGenreBtn').on('click', function() {
        $('#genreFormTitle').text('Tambah Genre Baru');
        $('#formGenreId').val('');
        $('#genreName').val('');
        $('#genreFormDialog').addClass('active');
    });

    $(document).on('click', '.edit-genre-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $('#genreFormTitle').text('Ubah Nama Genre');
        $('#formGenreId').val(id);
        $('#genreName').val(name);
        $('#genreFormDialog').addClass('active');
    });

    $('#closeGenreFormBtn, #cancelGenreFormBtn, #genreFormDialog').on('click', function(e) {
        if (e.target === this) {
            $('#genreFormDialog').removeClass('active');
        }
    });

    $('#genreAdminForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#formGenreId').val();
        const isEdit = !!id;
        const $btn = $('#submitGenreFormBtn');
        $btn.prop('disabled', true).text('Menyimpan...');

        const url = isEdit ? `/api/admin/genres/${id}` : '/api/admin/genres';
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: { name: $('#genreName').val() },
            success: function(res) {
                $btn.prop('disabled', false).text('Simpan');
                $('#genreFormDialog').removeClass('active');
                showToast(res.message);
                loadGenres();
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Simpan');
                showToast(xhr.responseJSON?.message || 'Gagal menyimpan genre.', 'error');
            }
        });
    });

    $(document).on('click', '.delete-genre-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (confirm(`Yakin ingin menghapus genre "${name}"?`)) {
            $.ajax({
                url: `/api/admin/genres/${id}`,
                type: 'DELETE',
                success: function(res) {
                    showToast(res.message);
                    loadGenres();
                },
                error: function() {
                    showToast('Gagal menghapus genre.', 'error');
                }
            });
        }
    });

    loadGenres();
});
</script>
@endsection
