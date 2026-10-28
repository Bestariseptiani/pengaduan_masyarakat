<?php
require __DIR__ . '/../includes/auth_helper.php';

if (is_logged_in()) {
    header('Location: ' . home_for_role($_SESSION['role']));
    exit;
}

$portalAdmin = (($_GET['portal'] ?? '') === 'admin');
$error       = '';
$identifier  = '';
$flash       = flash_get();

const MAKS_GAGAL  = 5;    // percobaan gagal sebelum dikunci
const DURASI_KUNCI = 300; // detik (5 menit)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($_SESSION['login_kunci'] ?? 0) > time()) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi beberapa menit lagi.';
    } elseif ($identifier === '' || $password === '') {
        $error = 'Email/NIK dan password wajib diisi.';
    } else {
        require __DIR__ . '/../config/koneksi.php';

        $stmt = $pdo->prepare(
            'SELECT id, nama, role, password, is_aktif FROM users WHERE email = ? OR nik = ? LIMIT 1'
        );
        $stmt->execute([strtolower($identifier), $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_aktif']) {
                $error = 'Akun Anda dinonaktifkan. Silakan hubungi petugas.';
            } elseif ($portalAdmin && $user['role'] === 'masyarakat') {
                $error = 'Akun ini bukan akun petugas. Gunakan halaman login masyarakat.';
            } else {
                session_regenerate_id(true);
                unset($_SESSION['login_gagal'], $_SESSION['login_kunci']);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['nama']    = $user['nama'];
                $_SESSION['role']    = $user['role'];

                header('Location: ' . home_for_role($user['role']));
                exit;
            }
        } else {
            $_SESSION['login_gagal'] = ($_SESSION['login_gagal'] ?? 0) + 1;
            if ($_SESSION['login_gagal'] >= MAKS_GAGAL) {
                $_SESSION['login_kunci'] = time() + DURASI_KUNCI;
                $_SESSION['login_gagal'] = 0;
            }
            $error = 'Email/NIK atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $portalAdmin ? 'Portal Admin' : 'Masuk' ?> - LaporHub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth">

  <main class="auth-card">
    <a href="../index.html" class="logo">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3v18h2v-7h5l1 2h6V5h-5l-1-2H5z"/></svg>
      <span>Lapor<em>Hub</em></span>
    </a>

    <div class="auth-head">
      <h1><?= $portalAdmin ? 'Portal Admin' : 'Selamat Datang Kembali' ?></h1>
      <p><?= $portalAdmin ? 'Khusus petugas dan administrator.' : 'Masuk untuk memantau laporan Anda.' ?></p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['tipe']) ?>" role="status"><?= e($flash['pesan']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="identifier">Email atau NIK</label>
        <div class="input">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input type="text" id="identifier" name="identifier" placeholder="Masukkan Email atau NIK"
                 value="<?= e($identifier) ?>" autocomplete="username" required autofocus>
        </div>
      </div>

      <div class="field">
        <div class="label-row">
          <label for="password">Password</label>
          <a href="#" class="link-red small">Lupa Password?</a>
        </div>
        <div class="input">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
          <input type="password" id="password" name="password" placeholder="Masukkan Password"
                 autocomplete="current-password" required>
          <button type="button" class="eye" data-toggle-password="password" aria-label="Tampilkan password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-dark">Masuk</button>
    </form>

    <?php if (!$portalAdmin): ?>
      <p class="switch">Belum punya akun? <a href="register.php" class="link-red">Daftar Sekarang</a></p>
    <?php endif; ?>

    <div class="divider"></div>

    <?php if ($portalAdmin): ?>
      <a href="login.php" class="portal-link">Login Masyarakat</a>
    <?php else: ?>
      <a href="login.php?portal=admin" class="portal-link">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0H2Zm17-8 .5 1.300 1.300.5-1.300.5-.5 1.300-.5-1.300-1.300-.5 1.300-.5.5-1.300Z"/></svg>
        Portal Admin
      </a>
    <?php endif; ?>
  </main>

  <script src="../assets/js/auth.js"></script>
</body>
</html>