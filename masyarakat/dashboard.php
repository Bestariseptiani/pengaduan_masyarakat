<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../includes/layout.php';

$uid = (int) $_SESSION['user_id'];

// Hitung laporan per status
$hitung = array_fill_keys(array_keys(STATUS_LABEL), 0);
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS jml FROM pengaduan WHERE user_id = ? GROUP BY status');
$stmt->execute([$uid]);
foreach ($stmt->fetchAll() as $baris) {
    $hitung[$baris['status']] = (int) $baris['jml'];
}

$total    = array_sum($hitung);
$selesai  = $hitung['selesai'];
// "Diproses" = semua laporan yang masih berjalan (belum selesai dan belum ditolak)
$diproses = $hitung['menunggu'] + $hitung['diverifikasi'] + $hitung['diproses'];

$stmt = $pdo->prepare('SELECT id, kode_tiket, judul, status, created_at FROM pengaduan WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
$stmt->execute([$uid]);
$terbaru = $stmt->fetchAll();

layout_mulai('Dashboard', 'dashboard', ['topbar' => 'Ringkasan Dashboard']);
?>
      <section class="hero-card">
        <h2>Halo, <?= e($_SESSION['nama']) ?>! 👋</h2>
        <p>Selamat datang di portal LaporHub. Pantau terus status pengaduan Anda dan bantu kami meningkatkan kualitas layanan publik.</p>
        <a href="buat-pengaduan.php" class="btn btn-red"><?= ikon('plus') ?> Buat Laporan Baru</a>
        <svg class="hero-flag" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3v18h2v-7h5l1 2h6V5h-5l-1-2H5z"/></svg>
      </section>

      <section class="stats">
        <article class="stat-card navy">
          <div class="stat-top"><span class="stat-icon"><?= ikon('folder') ?></span><span class="stat-label">Total Laporan</span></div>
          <strong><?= $total ?></strong>
        </article>
        <article class="stat-card orange">
          <div class="stat-top"><span class="stat-icon"><?= ikon('clock') ?></span><span class="stat-label">Diproses</span></div>
          <strong><?= $diproses ?></strong>
        </article>
        <article class="stat-card green">
          <div class="stat-top"><span class="stat-icon"><?= ikon('check-circle') ?></span><span class="stat-label">Selesai</span></div>
          <strong><?= $selesai ?></strong>
        </article>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h3>Laporan Terbaru</h3>
          <?php if ($terbaru): ?><a href="riwayat.php" class="link-red">Lihat Semua</a><?php endif; ?>
        </div>

        <?php if (!$terbaru): ?>
          <div class="empty">
            <p>Anda belum memiliki laporan.</p>
            <a href="buat-pengaduan.php" class="btn btn-red btn-sm">Buat Laporan Pertama</a>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="tbl">
              <thead>
                <tr><th>ID Tiket</th><th>Topik</th><th>Tanggal</th><th>Status</th><th class="center">Aksi</th></tr>
              </thead>
              <tbody>
                <?php foreach ($terbaru as $p): ?>
                  <tr>
                    <td><strong><?= e($p['kode_tiket']) ?></strong></td>
                    <td><?= e(potong($p['judul'], 40)) ?></td>
                    <td><?= e(tgl_id($p['created_at'])) ?></td>
                    <td><?= status_badge($p['status']) ?></td>
                    <td class="center"><a href="detail.php?id=<?= (int) $p['id'] ?>" class="icon-link" aria-label="Lihat detail"><?= ikon('open') ?></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
<?php layout_selesai(); ?>