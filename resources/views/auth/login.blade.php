@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="text-xl font-bold">Masuk ke Akun</h2>
        <p class="text-sm text-muted">Masukkan username dan kata sandi Anda</p>
    </div>
    <div class="card-body">
        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username Anda" required autocomplete="username" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" id="submitLoginBtn" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span>Masuk</span>
            </button>
        </form>
    </div>
    <div class="card-footer" style="justify-content: center; border-top: 1px solid var(--border);">
        <p class="text-sm text-muted">
            Belum memiliki akun? <a href="{{ route('register') }}" style="color: var(--foreground); font-weight: 500; text-decoration: underline;">Daftar di sini</a>
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#submitLoginBtn');
        $btn.prop('disabled', true).text('Memproses...');

        $.ajax({
            url: '/login',
            type: 'POST',
            data: {
                username: $('#username').val(),
                password: $('#password').val()
            },
            success: function(res) {
                localStorage.setItem('auth_token', res.token);
                showToast(res.message);
                setTimeout(() => {
                    if (res.user.role === 'admin') {
                        window.location.href = '/admin';
                    } else {
                        window.location.href = '/dashboard';
                    }
                }, 400);
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg><span>Masuk</span>');
                const msg = xhr.responseJSON?.message || 'Login gagal. Periksa username atau password.';
                showToast(msg, 'error');
            }
        });
    });
});
</script>
@endsection
