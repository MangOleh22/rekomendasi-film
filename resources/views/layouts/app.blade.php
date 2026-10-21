<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rekomendasi Film') - Sistem Rekomendasi Film</title>
    
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#09090b">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/jquery.min.js"></script>
    <script>
        const authToken = localStorage.getItem('auth_token');
        const ajaxHeaders = {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        };
        if (authToken) {
            ajaxHeaders['Authorization'] = 'Bearer ' + authToken;
        }
        $.ajaxSetup({
            headers: ajaxHeaders
        });

        // Register Service Worker PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.error('ServiceWorker gagal:', err);
                });
            });
        }
    </script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="{{ route('dashboard') }}" class="brand-logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                        <line x1="7" y1="2" x2="7" y2="22"></line>
                        <line x1="17" y1="2" x2="17" y2="22"></line>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <line x1="2" y1="7" x2="7" y2="7"></line>
                        <line x1="2" y1="17" x2="7" y2="17"></line>
                        <line x1="17" y1="17" x2="22" y2="17"></line>
                        <line x1="17" y1="7" x2="22" y2="7"></line>
                    </svg>
                    <span>RekomFilm</span>
                </a>

                @auth
                <nav class="nav-links">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <span>Rekomendasi</span>
                    </a>
                    <a href="{{ route('history') }}" class="nav-link {{ request()->routeIs('history') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Riwayat Tonton</span>
                    </a>
                    <a href="{{ route('transactions') }}" class="nav-link {{ request()->routeIs('transactions') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Transaksi</span>
                    </a>
                    <a href="{{ route('preferences') }}" class="nav-link {{ request()->routeIs('preferences') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                        <span>Preferensi</span>
                    </a>

                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin*') ? 'active' : '' }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Panel Admin</span>
                    </a>
                    @endif

                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-left: 0.5rem; padding-left: 0.75rem; border-left: 1px solid var(--border);">
                        <span class="text-sm font-semibold" style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ auth()->user()->name }}
                        </span>
                        <button id="logoutBtn" class="btn btn-ghost btn-sm" title="Keluar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </button>
                    </div>
                </nav>
                @endauth
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 2rem; padding-bottom: 3rem;">
        @yield('content')
    </main>

    <!-- Modal Detail Film -->
    <div id="movieDetailDialog" class="dialog-overlay">
        <div class="dialog-content">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                <h3 id="modalMovieTitle" class="text-lg">Detail Film</h3>
                <button id="closeModalBtn" class="btn btn-ghost btn-sm btn-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="card-body" id="modalMovieBody">
                <!-- Diisi via jQuery -->
            </div>
        </div>
    </div>

    <!-- Modal Pembayaran Film -->
    <div id="paymentDialog" class="dialog-overlay">
        <div class="dialog-content" style="max-width: 500px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                <h3 class="text-lg" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    <span>Pembelian & Pembayaran Film</span>
                </h3>
                <button id="closePaymentModalBtn" class="btn btn-ghost btn-sm btn-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="card-body">
                <div style="background: #141418; border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; margin-bottom: 1.25rem;">
                    <div class="text-xs text-muted" style="text-transform: uppercase; font-weight: 600;">Film yang akan dibeli</div>
                    <div id="payMovieTitle" style="font-size: 1.1rem; font-weight: 700; margin: 0.35rem 0; color: var(--foreground);">-</div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; pt-2; border-top: 1px dashed var(--border); padding-top: 0.5rem;">
                        <span class="text-sm text-muted">Total Tagihan:</span>
                        <span id="payMoviePrice" style="font-size: 1.25rem; font-weight: 800; color: #34d399;">RP. 0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600;">Pilih Metode Pembayaran</label>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <label class="payment-method-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; background: #16161a;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <input type="radio" name="payment_method" value="QRIS" checked>
                                <span style="font-weight: 600;">QRIS (Instant)</span>
                            </div>
                            <span class="text-xs text-muted">GoPay / OVO / Dana / ShopeePay</span>
                        </label>
                        <label class="payment-method-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; background: #16161a;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <input type="radio" name="payment_method" value="BCA Virtual Account">
                                <span style="font-weight: 600;">BCA Virtual Account</span>
                            </div>
                            <span class="text-xs text-muted">Otomatis Terverifikasi</span>
                        </label>
                        <label class="payment-method-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; background: #16161a;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <input type="radio" name="payment_method" value="Mandiri Virtual Account">
                                <span style="font-weight: 600;">Mandiri Virtual Account</span>
                            </div>
                            <span class="text-xs text-muted">Livin' by Mandiri</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <button type="button" id="cancelPayBtn" class="btn btn-outline">Batal</button>
                    <button type="button" id="confirmPaymentBtn" class="btn btn-primary" style="background: #10b981; color: #ffffff; border-color: #10b981; font-weight: 600;">
                        <span>Bayar Sekarang</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Struk Bukti Transaksi -->
    <div id="receiptDialog" class="dialog-overlay">
        <div class="dialog-content" style="max-width: 520px;">
            <div class="card-header no-print" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                <h3 class="text-lg" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Bukti Transaksi (Struk Pembelian)</span>
                </h3>
                <button id="closeReceiptModalBtn" class="btn btn-ghost btn-sm btn-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="card-body">
                <!-- Kontainer Struk yang dicetak -->
                <div id="printableReceipt" class="receipt-paper">
                    <div class="receipt-header">
                        <div class="receipt-logo">REKOMFILM INDONESIA</div>
                        <div class="receipt-subtitle">Bukti Pembayaran Resmi Pembelian Film</div>
                        <div class="receipt-badge-status">LUNAS / BERHASIL</div>
                    </div>

                    <div class="receipt-divider"></div>

                    <div class="receipt-meta-grid">
                        <div class="receipt-row">
                            <span class="receipt-label">No. Transaksi</span>
                            <span class="receipt-val" id="recOrderNumber">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Tanggal Transaksi</span>
                            <span class="receipt-val" id="recDate">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Nama Akun</span>
                            <span class="receipt-val" id="recUserName">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Kategori Usia Akun</span>
                            <span class="receipt-val" id="recUserCategory">-</span>
                        </div>
                        <div class="receipt-row">
                            <span class="receipt-label">Metode Pembayaran</span>
                            <span class="receipt-val" id="recPaymentMethod">-</span>
                        </div>
                    </div>

                    <div class="receipt-divider"></div>

                    <div class="receipt-items">
                        <div class="receipt-item-row" style="font-weight: 600; margin-bottom: 0.25rem;">
                            <span id="recMovieTitle">-</span>
                            <span id="recMoviePrice">RP. 0</span>
                        </div>
                        <div class="text-xs text-muted" id="recMovieMeta" style="margin-bottom: 0.5rem;">
                            <!-- Tahun & Kategori -->
                        </div>
                    </div>

                    <div class="receipt-divider"></div>

                    <div class="receipt-total-row">
                        <span>TOTAL PEMBAYARAN</span>
                        <span id="recTotalAmount" style="font-size: 1.25rem; font-weight: 800; color: #10b981;">RP. 0</span>
                    </div>

                    <div class="receipt-footer">
                        <p>Simpan struk ini sebagai bukti transaksi resmi Anda.</p>
                        <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">Terima kasih atas pembelian Anda di RekomFilm.</p>
                    </div>
                </div>

                <!-- Action Button untuk Cetak & Download -->
                <div class="no-print" style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <button type="button" id="downloadReceiptBtn" class="btn btn-outline" style="display: flex; align-items: center; gap: 0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Download Struk (Teks)</span>
                    </button>
                    <button type="button" id="printReceiptBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.35rem;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Cetak Struk (PDF)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="/js/app.js"></script>
    @yield('scripts')
</body>
</html>
