<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - LaporHub</title>
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
      <h1>Halo, <?= e($_SESSION['nama']) ?></h1>
      <p>Anda berhasil masuk sebagai masyarakat.</p>
    </div>

    <p class="note">Halaman ini masih kerangka. Fitur buat pengaduan dan riwayat laporan akan ditambahkan di tahap berikutnya.</p>

    <form method="post" action="../auth/logout.php">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-dark">Keluar</button>
    </form>
  </main>
</body>
</html>