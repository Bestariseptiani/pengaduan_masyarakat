// Tombol mata: tampilkan / sembunyikan password.
// Formulir tetap berfungsi tanpa file ini.
document.querySelectorAll('[data-toggle-password]').forEach(function (tombol) {
  tombol.addEventListener('click', function () {
    var input = document.getElementById(tombol.dataset.togglePassword);
    if (!input) return;
    var tampil = input.type === 'password';
    input.type = tampil ? 'text' : 'password';
    tombol.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    tombol.classList.toggle('on', tampil);
  });
});