@extends('layouts.auth')

@section('title', 'Daftar Akun Baru')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="text-xl font-bold">Buat Akun Baru</h2>
        <p class="text-sm text-muted">Daftar untuk mendapatkan rekomendasi film yang disesuaikan dengan usia Anda</p>
    </div>
    <div class="card-body">
        <form id="registerForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="contoh: budi_santoso (tanpa spasi)" required pattern="[A-Za-z0-9_\-]+" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-input" placeholder="Nama Lengkap Anda" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6">
            </div>

            <div class="form-group">
                <label class="form-label" for="birth_date">Tanggal Lahir</label>
                <input type="date" id="birth_date" name="birth_date" class="form-input" required max="{{ date('Y-m-d', strtotime('-1 day')) }}">
                <span class="text-xs text-muted" style="display: block; margin-top: 0.25rem;">
                    Batas usia film disaring secara otomatis berdasarkan tanggal lahir Anda (LSF: SU, 13+, 17+, 21+).
                </span>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between;">
                    <label class="form-label" for="minimum_rating">Rating Film Minimum</label>
                    <span id="ratingVal" class="text-xs font-semibold" style="color: #facc15;">0.0</span>
                </div>
                <input type="range" id="minimum_rating" name="minimum_rating" min="0" max="10" step="0.5" value="0.0" style="width: 100%; accent-color: var(--foreground);">
            </div>

            <button type="submit" id="submitRegisterBtn" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>Daftar Akun</span>
            </button>
        </form>
    </div>
    <div class="card-footer" style="justify-content: center; border-top: 1px solid var(--border);">
        <p class="text-sm text-muted">
            Sudah memiliki akun? <a href="{{ route('login') }}" style="color: var(--foreground); font-weight: 500; text-decoration: underline;">Masuk di sini</a>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#minimum_rating').on('input', function() {
        $('#ratingVal').text(parseFloat($(this).val()).toFixed(1));
    });

    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#submitRegisterBtn');
        $btn.prop('disabled', true).text('Mendaftarkan...');

        $.ajax({
            url: '/register',
            type: 'POST',
            data: {
                username: $('#username').val(),
                name: $('#name').val(),
                password: $('#password').val(),
                birth_date: $('#birth_date').val(),
                minimum_rating: $('#minimum_rating').val()
            },
            success: function(res) {
                localStorage.setItem('auth_token', res.token);
                showToast(res.message);
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 500);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg><span>Daftar Akun</span>');
                const errors = xhr.responseJSON?.errors;
                let msg = xhr.responseJSON?.message || 'Registrasi gagal.';
                if (errors) {
                    msg = Object.values(errors).flat().join('<br>');
                }
                showToast(msg, 'error');
            }
        });
    });
});
</script>
@endsection
