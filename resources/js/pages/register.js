import { authPage } from './auth';

export default (root) => authPage(root, {
  title: 'Buat akun', desc: 'Tanggal lahir dipakai untuk menyaring film sesuai usia.', submitText: 'Daftar', endpoint: '/register',
  fields: [
    ['name', 'Nama lengkap', 'text', 'name'], ['username', 'Username', 'text', 'username'],
    ['birth_date', 'Tanggal lahir', 'date', 'bday'], ['password', 'Kata sandi (min. 6)', 'password', 'new-password'],
  ],
  footer: 'Sudah punya akun? <a href="/login" class="text-foreground underline">Masuk</a>',
});
