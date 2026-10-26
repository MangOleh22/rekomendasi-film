import { authPage } from './auth';

export default (root) => authPage(root, {
  title: 'Masuk', desc: 'Gunakan username atau email Anda.', submitText: 'Masuk', endpoint: '/login',
  fields: [['username', 'Username', 'text', 'username'], ['password', 'Kata sandi', 'password', 'current-password']],
  footer: 'Belum punya akun? <a href="/register" class="text-foreground underline">Daftar</a>',
});
