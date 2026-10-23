@extends('layouts.app')

@section('title', 'Riwayat Tonton')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="text-2xl font-bold">Riwayat Tonton</h1>
            <p class="text-sm text-muted">Daftar film yang telah Anda tonton. Membantu algoritma memahami preferensi genre Anda secara otomatis.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul Film</th>
                        <th>Klasifikasi Usia</th>
                        <th>Rating</th>
                        <th>Genre</th>
                        <th>Waktu Ditonton</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--muted-foreground); padding: 3rem 1rem;">
                            Memuat riwayat tonton...
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
    // Algoritma if-else untuk penentuan badge kategori umur
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

    $.get('/api/me/watch-history', function(res) {
        const list = res.data || [];
        const $tbody = $('#historyTableBody');

        if (list.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem 1rem; color: var(--muted-foreground);">
                        Belum ada riwayat tonton. Tonton film di dashboard untuk membangun rekomendasi cerdas Anda!
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        list.forEach(function(item) {
            const m = item.movie;
            if (!m) return;
            const genres = (m.genres || []).map(g => `<span class="badge badge-genre">${escapeHtml(g.name)}</span>`).join(' ');
            const dateStr = item.watched_at ? new Date(item.watched_at).toLocaleDateString('id-ID', {
                day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }) : '-';

            html += `
                <tr>
                    <td>
                        <span class="font-semibold">${escapeHtml(m.title)}</span>
                        <span class="text-xs text-muted" style="margin-left: 0.25rem;">(${m.year})</span>
                    </td>
                    <td>${getAgeBadge(m.age_limit)}</td>
                    <td>
                        <span class="badge-rating">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            ${m.rating}
                        </span>
                    </td>
                    <td><div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">${genres}</div></td>
                    <td class="text-xs text-muted">${dateStr}</td>
                </tr>
            `;
        });
        $tbody.html(html);
    }).fail(function() {
        $('#historyTableBody').html(`
            <tr>
                <td colspan="5" style="text-align: center; color: var(--destructive); padding: 2rem;">
                    Gagal memuat riwayat tonton.
                </td>
            </tr>
        `);
    });
});
</script>
@endsection
