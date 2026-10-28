<?php
// Kerangka halaman untuk area masyarakat: sidebar + topbar.
// Pemakaian di halaman:  layout_mulai('Judul', 'menu-aktif', [...opsi]);  ...isi...  layout_selesai();

require_once __DIR__ . '/helpers.php';

/**
 * @param string $judul  Judul tab browser
 * @param string $menu   dashboard | buat | riwayat | profil
 * @param array  $opsi   topbar (teks judul atas), kembali (URL tombol kembali), lencana (HTML di kanan judul)
 */
function layout_mulai(string $judul, string $menu, array $opsi = []): void
{
    global $pdo;

    $topbar  = $opsi['topbar'] ?? $judul;
    $kembali = $opsi['kembali'] ?? null;
    $lencana = $opsi['lencana'] ?? '';
    $nama    = $_SESSION['nama'] ?? '';

    // Titik merah di lonceng: ada laporan yang berubah status dalam 7 hari terakhir
    $adaNotif = false;
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE user_id = ? AND status <> 'menunggu' AND updated_at >= ?");
        $stmt->execute([$_SESSION['user_id'], date('Y-m-d H:i:s', strtotime('-7 days'))]);
        $adaNotif = (int) $stmt->fetchColumn() > 0;
    }

    $menuUtama = [
        'dashboard' => ['dashboard.php', 'home', 'Dashboard'],
        'buat'      => ['buat-pengaduan.php', 'edit', 'Buat Laporan'],
        'riwayat'   => ['riwayat.php', 'history', 'Riwayat Laporan'],
    ];
    $flash = flash_get();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?> - LaporHub</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<input type="checkbox" id="nav-toggle" class="nav-toggle">
<div class="app">

  <aside class="sidebar">
    <a href="../index.html" class="logo">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3v18h2v-7h5l1 2h6V5h-5l-1-2H5z"/></svg>
      <span>Lapor<em>Hub</em></span>
    </a>

    <p class="nav-label">Menu Utama</p>
    <nav class="nav">
      <?php foreach ($menuUtama as $kunci => [$url, $ikon, $teks]): ?>
        <a href="<?= e($url) ?>" class="nav-link<?= $menu === $kunci ? ' active' : '' ?>"><?= ikon($ikon) ?> <?= e($teks) ?></a>
      <?php endforeach; ?>
    </nav>

    <p class="nav-label">Pengguna</p>
    <nav class="nav">
      <a href="profil.php" class="nav-link<?= $menu === 'profil' ? ' active' : '' ?>"><?= ikon('user') ?> Profil Saya</a>
      <form method="post" action="../auth/logout.php">
        <?= csrf_field() ?>
        <button type="submit" class="nav-link danger"><?= ikon('logout') ?> Keluar</button>
      </form>
    </nav>

    <div class="help-box">
      <small>Butuh Bantuan?</small>
      <a href="#"><?= ikon('help') ?> Pusat Bantuan</a>
    </div>
  </aside>

  <label for="nav-toggle" class="scrim" aria-hidden="true"></label>

  <div class="main">
    <header class="topbar">
      <label for="nav-toggle" class="burger" aria-label="Buka menu"><span></span><span></span><span></span></label>
      <?php if ($kembali): ?>
        <a href="<?= e($kembali) ?>" class="back" aria-label="Kembali"><?= ikon('back') ?></a>
      <?php endif; ?>
      <h1><?= e($topbar) ?></h1>
      <?= $lencana ?>

      <div class="topbar-right">
        <a href="riwayat.php" class="bell" aria-label="Notifikasi">
          <?= ikon('bell') ?>
          <?php if ($adaNotif): ?><i class="dot"></i><?php endif; ?>
        </a>
        <div class="user-meta">
          <strong><?= e($nama) ?></strong>
          <small>Masyarakat</small>
        </div>
        <span class="avatar"><?= e(inisial($nama)) ?></span>
      </div>
    </header>

    <main class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipe']) ?>" role="status"><?= e($flash['pesan']) ?></div>
      <?php endif; ?>
<?php
}

function layout_selesai(): void
{
    ?>
    </main>
  </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
<?php
}