// Skrip kecil untuk area masyarakat. Semua halaman tetap berfungsi tanpa file ini.

// Kirim formulir otomatis saat pilihan filter berubah
document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
  el.addEventListener('change', function () { el.form.submit(); });
});

// Konfirmasi sebelum aksi berbahaya (mis. tarik laporan)
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!window.confirm(form.dataset.confirm)) e.preventDefault();
  });
});

// Kotak unggah foto: nama file, pratinjau, dan pemeriksaan ukuran/format di sisi browser
(function () {
  var input = document.getElementById('foto');
  if (!input) return;

  var zona = input.closest('.dropzone');
  var judul = zona.querySelector('[data-dz-title]');
  var gambar = zona.querySelector('.dz-preview');
  var galat = document.querySelector('[data-foto-error]');
  var teksAwal = judul.textContent;

  function tampilkanGalat(pesan) {
    galat.textContent = pesan;
    galat.hidden = !pesan;
  }

  function reset() {
    input.value = '';
    judul.textContent = teksAwal;
    gambar.hidden = true;
    gambar.removeAttribute('src');
    zona.classList.remove('has-file');
  }

  input.addEventListener('change', function () {
    tampilkanGalat('');
    var berkas = input.files[0];
    if (!berkas) return reset();

    if (['image/jpeg', 'image/png'].indexOf(berkas.type) === -1) {
      reset();
      return tampilkanGalat('Format foto harus JPG atau PNG.');
    }
    if (berkas.size > parseInt(input.dataset.maks, 10)) {
      reset();
      return tampilkanGalat('Ukuran foto maksimal 5 MB.');
    }

    judul.textContent = berkas.name;
    gambar.src = URL.createObjectURL(berkas);
    gambar.hidden = false;
    zona.classList.add('has-file');
  });

  ['dragenter', 'dragover'].forEach(function (nama) {
    zona.addEventListener(nama, function (e) { e.preventDefault(); zona.classList.add('drag'); });
  });
  ['dragleave', 'drop'].forEach(function (nama) {
    zona.addEventListener(nama, function () { zona.classList.remove('drag'); });
  });
})();