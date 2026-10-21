@extends('layouts.app')

@section('title', 'Riwayat Transaksi & Struk Pembelian')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="text-2xl font-bold">Riwayat Transaksi & Bukti Pembelian</h1>
            <p class="text-sm text-muted">Daftar seluruh pembelian film berbayar beserta struk resmi yang dapat dicetak atau didownload.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            <span>Katalog Rekomendasi</span>
        </a>
    </div>

    <!-- Tabel Transaksi -->
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Film</th>
                        <th>Tanggal Pembayaran</th>
                        <th>Metode Bayar</th>
                        <th>Total Nominal</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--muted-foreground);">
                            Memuat riwayat transaksi...
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
    let currentReceiptData = null;

    function loadTransactions() {
        $.get('/api/me/transactions', function(res) {
            const list = res.data || [];
            const $tbody = $('#transactionsTableBody');

            if (list.length === 0) {
                $tbody.html(`
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3.5rem 1rem; color: var(--muted-foreground);">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                <span>Belum ada transaksi pembelian film.</span>
                                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">Beli Film Sekarang</a>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }

            let html = '';
            list.forEach(trx => {
                const movie = trx.movie || {};
                html += `
                    <tr>
                        <td>
                            <span style="font-family: monospace; font-weight: 700; color: #38bdf8;">
                                ${escapeHtml(trx.order_number)}
                            </span>
                        </td>
                        <td>
                            <div class="font-semibold">${escapeHtml(movie.title || '-')}</div>
                            <div class="text-xs text-muted">Tahun: ${movie.year || '-'} | Kategori: ${movie.age_category || '-'}</div>
                        </td>
                        <td class="text-sm">${trx.formatted_paid_at}</td>
                        <td>
                            <span class="badge" style="background: rgba(255, 255, 255, 0.08); color: var(--foreground); font-weight: 500;">
                                ${escapeHtml(trx.payment_method)}
                            </span>
                        </td>
                        <td>
                            <strong style="color: #34d399; font-size: 0.95rem;">
                                ${trx.formatted_amount}
                            </strong>
                        </td>
                        <td>
                            <span class="badge badge-su" style="font-size: 0.75rem; letter-spacing: 0.02em;">
                                LUNAS
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline btn-sm view-trx-receipt-btn" data-order="${trx.order_number}" style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                <span>Lihat / Cetak Struk</span>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $tbody.html(html);
        }).fail(function() {
            showToast('Gagal memuat riwayat transaksi.', 'error');
        });
    }

    // Render & Tampilkan Struk
    function renderAndShowReceipt(trx) {
        currentReceiptData = trx;
        $('#recOrderNumber').text(trx.order_number);
        $('#recDate').text(trx.formatted_paid_at || new Date().toLocaleString('id-ID'));
        $('#recUserName').text(trx.user?.name || '{{ auth()->user()->name }}');
        $('#recUserCategory').text(trx.user?.age_category || '{{ auth()->user()->age_category }}');
        $('#recPaymentMethod').text(trx.payment_method);
        $('#recMovieTitle').text(trx.movie?.title || '-');
        $('#recMoviePrice').text(trx.formatted_amount || 'RP. 0');
        $('#recMovieMeta').text(`Tahun: ${trx.movie?.year || '-'} | Kategori Usia: ${trx.movie?.age_category || '-'}`);
        $('#recTotalAmount').text(trx.formatted_amount || 'RP. 0');
        $('#receiptDialog').addClass('active');
    }

    // Klik tombol Lihat / Cetak Struk
    $(document).on('click', '.view-trx-receipt-btn', function() {
        const orderNumber = $(this).data('order');
        if (!orderNumber) return;
        $.get(`/api/transactions/${orderNumber}`, function(res) {
            renderAndShowReceipt(res.data);
        }).fail(function() {
            showToast('Gagal memuat data struk transaksi.', 'error');
        });
    });

    // Tutup Struk Modal
    $('#closeReceiptModalBtn, #receiptDialog').on('click', function(e) {
        if (e.target === this) {
            $('#receiptDialog').removeClass('active');
        }
    });

    // Cetak Struk (PDF / Printer)
    $('#printReceiptBtn').on('click', function() {
        window.print();
    });

    // Download Struk (File Teks)
    $('#downloadReceiptBtn').on('click', function() {
        if (!currentReceiptData) return;
        const trx = currentReceiptData;
        const text = `================================================
          REKOMFILM INDONESIA
     BUKTI TRANSAKSI PEMBELIAN FILM RESMI
================================================
STATUS PEMBAYARAN: LUNAS / BERHASIL
NO. TRANSAKSI    : ${trx.order_number}
TANGGAL & WAKTU  : ${trx.formatted_paid_at}
NAMA PEMBELI     : ${trx.user?.name || '{{ auth()->user()->name }}'}
KATEGORI USIA    : ${trx.user?.age_category || '{{ auth()->user()->age_category }}'}
METODE BAYAR     : ${trx.payment_method}
------------------------------------------------
RINCIAN FILM:
Judul Film       : ${trx.movie?.title || '-'}
Tahun Rilis      : ${trx.movie?.year || '-'}
Kategori Usia    : ${trx.movie?.age_category || '-'}
Harga Film       : ${trx.formatted_amount}
------------------------------------------------
TOTAL PEMBAYARAN : ${trx.formatted_amount}
================================================
Terima kasih telah melakukan pembelian di RekomFilm.
Simpan struk ini sebagai bukti transaksi resmi.
================================================`;

        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Struk-${trx.order_number}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    loadTransactions();
});
</script>
@endsection
