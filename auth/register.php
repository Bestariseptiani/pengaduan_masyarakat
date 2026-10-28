<?php
require __DIR__ . '/../includes/auth_helper.php';

if (is_logged_in()) {
    header('Location: ' . home_for_role($_SESSION['role']));
    exit;
}

$old    = ['nik' => '', 'nama' => '', 'email' => '', 'no_hp' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nik']   = trim($_POST['nik'] ?? '');
    $old['nama']  = trim(preg_replace('/\s+/', ' ', $_POST['nama'] ?? ''));
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['no_hp'] = preg_replace('/[\s-]/', '', $_POST['no_hp'] ?? '');
    $password     = $_POST['password'] ?? '';
    $setuju       = isset($_POST['setuju']);

    if (!csrf_valid()) {
        $errors['umum'] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    if (!preg_match('/^\d{16}$/', $old['nik'])) {
        $errors['nik'] = 'NIK harus 16 digit angka.';
    }
    if (mb_strlen($old['nama']) < 3 || mb_strlen($old['nama']) > 100
        || !preg_match('/^[\p{L}\s.\'-]+$/u', $old['nama'])) {
        $errors['nama'] = 'Nama 3-100 huruf, tanpa angka atau simbol aneh.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100) {
        $errors['email'] = 'Format email tidak valid.';
    }
    if (!preg_match('/^(\+62|62|0)8\d{8,12}$/', $old['no_hp'])) {
        $errors['no_hp'] = 'Nomor HP tidak valid (contoh: 081234567890).';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password maksimal 72 karakter.';
    }
    if (!$setuju) {
        $errors['setuju'] = 'Anda harus menyetujui syarat dan kebijakan privasi.';
    }

    if (!$errors) {
        require __DIR__ . '/../config/koneksi.php';

        // Cek email / NIK yang sudah terdaftar
        $cek = $pdo->prepare('SELECT email, nik FROM users WHERE email = ? OR nik = ?');
        $cek->execute([$old['email'], $old['nik']]);
        foreach ($cek->fetchAll() as $baris) {
            if ($baris['email'] === $old['email']) {
                $errors['email'] = 'Email sudah terdaftar.';
            }
            if ($baris['nik'] === $old['nik']) {
                $errors['nik'] = 'NIK sudah terdaftar.';
            }
        }
    }

    if (!$errors) {
        try {
            $simpan = $pdo->prepare(
                'INSERT INTO users (nama, nik, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $simpan->execute([
                $old['nama'],
                $old['nik'],
                $old['email'],
                $old['no_hp'],
                password_hash($password, PASSWORD_DEFAULT),
                'masyarakat',
            ]);

            flash_set('success', 'Pendaftaran berhasil. Silakan masuk dengan akun Anda.');
            header('Location: login.php');
            exit;
        } catch (PDOException $ex) {
            // 23000 = pelanggaran UNIQUE (dua orang mendaftar bersamaan)
            $errors['umum'] = $ex->getCode() === '23000'
                ? 'Email atau NIK sudah terdaftar.'
                : 'Pendaftaran gagal. Silakan coba lagi.';
        }
    }
}

/** Tampilkan pesan error di bawah field. */
function field_error(array $errors, string $nama): string
{
    return isset($errors[$nama]) ? '<p class="field-error">' . e($errors[$nama]) . '</p>' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar - LaporHub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth">

  <main class="auth-card wide">

    <aside class="auth-aside">
      <a href="../index.html" class="logo logo-light">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3v18h2v-7h5l1 2h6V5h-5l-1-2H5z"/></svg>
        <span>Lapor<em>Hub</em></span>
      </a>
      <h2>Bergabunglah<br>Bersama Kami</h2>
      <p>Mulailah berkontribusi untuk perbaikan layanan publik dengan mendaftarkan akun Anda secara resmi.</p>
      <div class="aside-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.400 9.700 8 11 4.600-1.300 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
        <span>Data Anda dilindungi oleh sistem keamanan tinggi.</span>
      </div>
    </aside>

    <section class="auth-form">
      <div class="auth-head left">
        <h1>Pendaftaran Akun</h1>
        <p>Lengkapi data diri Anda sesuai KTP yang berlaku.</p>
      </div>

      <?php if (isset($errors['umum'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>

        <div class="grid-2">
          <div class="field">
            <label for="nik">NIK (Nomor Induk Kependudukan)</label>
            <div class="input plain<?= isset($errors['nik']) ? ' invalid' : '' ?>">
              <input type="text" id="nik" name="nik" placeholder="16 digit angka" inputmode="numeric"
                     maxlength="16" pattern="\d{16}" value="<?= e($old['nik']) ?>" required>
            </div>
            <?= field_error($errors, 'nik') ?>
          </div>

          <div class="field">
            <label for="nama">Nama Lengkap</label>
            <div class="input plain<?= isset($errors['nama']) ? ' invalid' : '' ?>">
              <input type="text" id="nama" name="nama" placeholder="Sesuai KTP" maxlength="100"
                     autocomplete="name" value="<?= e($old['nama']) ?>" required>
            </div>
            <?= field_error($errors, 'nama') ?>
          </div>

          <div class="field">
            <label for="email">Email</label>
            <div class="input plain<?= isset($errors['email']) ? ' invalid' : '' ?>">
              <input type="email" id="email" name="email" placeholder="example@mail.com" maxlength="100"
                     autocomplete="email" value="<?= e($old['email']) ?>" required>
            </div>
            <?= field_error($errors, 'email') ?>
          </div>

          <div class="field">
            <label for="no_hp">Nomor HP</label>
            <div class="input plain<?= isset($errors['no_hp']) ? ' invalid' : '' ?>">
              <input type="tel" id="no_hp" name="no_hp" placeholder="081234567890" maxlength="16"
                     autocomplete="tel" value="<?= e($old['no_hp']) ?>" required>
            </div>
            <?= field_error($errors, 'no_hp') ?>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input plain<?= isset($errors['password']) ? ' invalid' : '' ?>">
            <input type="password" id="password" name="password" placeholder="Min. 8 Karakter"
                   minlength="8" maxlength="72" autocomplete="new-password" required>
            <button type="button" class="eye" data-toggle-password="password" aria-label="Tampilkan password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <?= field_error($errors, 'password') ?>
        </div>

        <div class="check">
          <label>
            <input type="checkbox" name="setuju" value="1" required>
            <span>Saya setuju dengan <a href="#" class="link-red">Syarat &amp; Ketentuan</a> serta <a href="#" class="link-red">Kebijakan Privasi</a> LaporHub.</span>
          </label>
          <?= field_error($errors, 'setuju') ?>
        </div>

        <button type="submit" class="btn btn-red">Daftar Akun</button>
      </form>

      <p class="switch">Sudah punya akun? <a href="login.php" class="link-red">Masuk di sini</a></p>
    </section>

  </main>

  <script src="../assets/js/auth.js"></script>
</body>
</html>