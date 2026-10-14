@extends('layouts.admin')

@section('title', 'Kelola Pengguna')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div>
        <h1 class="text-2xl font-bold">Kelola Akun Pengguna</h1>
        <p class="text-sm text-muted">Daftar pengguna terdaftar, usia yang dihitung dari tanggal lahir, dan pengaturan peran akses.</p>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nama & Email</th>
                        <th>Tanggal Lahir / Usia</th>
                        <th>Rating Min</th>
                        <th>Peran (Role)</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            Memuat data pengguna...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function loadUsers() {
        $.get('/api/admin/users', function(res) {
            const list = res.data || [];
            if (list.length === 0) {
                $('#usersTableBody').html(`
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--muted-foreground);">
                            Belum ada data pengguna.
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            list.forEach(u => {
                const isAdmin = u.role === 'admin';
                const roleBadge = isAdmin 
                    ? '<span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Admin</span>'
                    : '<span class="badge" style="background: var(--secondary); color: var(--muted-foreground); border: 1px solid var(--border);">User</span>';

                const nextRole = isAdmin ? 'user' : 'admin';
                const toggleBtnText = isAdmin ? 'Jadikan User Biasa' : 'Jadikan Admin';

                html += `
                    <tr>
                        <td class="text-xs text-muted">#${u.id}</td>
                        <td>
                            <div class="font-semibold">${escapeHtml(u.name)}</div>
                            <div class="text-xs text-muted">@${escapeHtml(u.username)}</div>
                        </td>
                        <td>
                            <div>${u.birth_date ? u.birth_date.split('T')[0] : '-'}</div>
                            <div class="text-xs text-muted">${u.age} Tahun</div>
                        </td>
                        <td>${u.minimum_rating || 0.0}</td>
                        <td>${roleBadge}</td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline btn-sm toggle-role-btn" data-id="${u.id}" data-role="${nextRole}">
                                ${toggleBtnText}
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#usersTableBody').html(html);
        });
    }

    $(document).on('click', '.toggle-role-btn', function() {
        const id = $(this).data('id');
        const role = $(this).data('role');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: `/api/admin/users/${id}`,
            type: 'PUT',
            data: { role: role },
            success: function(res) {
                showToast(res.message);
                loadUsers();
            },
            error: function() {
                $btn.prop('disabled', false);
                showToast('Gagal memperbarui peran pengguna.', 'error');
            }
        });
    });

    loadUsers();
});
</script>
@endsection
