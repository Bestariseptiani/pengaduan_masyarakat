<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../includes/layout.php';

$uid     = (int) $_SESSION['user_id'];
$perHal  = 5;
$cari    = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? '';
$halaman = max(1, (int) ($_GET['hal'] ?? 1));

if (!array_key_exists($status, STATUS_LABEL)) {
    $status = '';
}

// Susun kondisi pencarian
$where  = 'user_id = ?';
$params = [$uid];
if ($cari !== '') {
    $like    = '%' . addcslashes($cari, '%_\\') . '%';
    $where  .= ' AND (kode_tiket LIKE ? OR judul LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where  .= ' AND status = ?';
    $params[] = $status;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE $where");
$stmt->execute($params);
$total      = (int) $stmt->fetchColumn();
$totalHal   = max(1, (int) ceil($total / $perHal));
$halaman    = min($halaman, $totalHal);

$stmt = $pdo->prepare("SELECT id, kode_tiket, judul, isi, status, created_at FROM pengaduan WHERE $where ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
foreach ($params as $i => $nilai) {
    $stmt->bindValue($i + 1, $nilai);
}
$stmt->bindValue(count($params) + 1, $perHal, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, ($halaman - 1) * $perHal, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

/** URL halaman lain dengan filter yang sama. */
function url_hal(int $hal, string $cari, string $status): string
{
    return 'riwayat.php?' . http_build_query(array_filter(
        ['q' => $cari, 'status' => $status, 'hal' => $hal > 1 ? $hal : null],
        static fn($v) => $v !== null && $v !== ''
    ));
}

layout_mulai('Riwayat Laporan', 'riwayat', ['topbar' => 'Riwayat Pengaduan']);
?>
      <form method="get" class="toolbar">
        <div class="search-box">
          <?= ikon('search') ?>
          <input type="search" name="q" value="<?= e($cari) ?>" placeholder="Cari ID Tiket atau Topik...">
        </div>
        <select name="status" data-autosubmit aria-label="Filter status">
          <option value="">Semua Status</option>
          <?php foreach (STATUS_LABEL as $kunci => $label): ?>
            <option value="<?= e($kunci) ?>"<?= $status === $kunci ? ' selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-dark btn-sm">Terapkan</button></noscript>
      </form>

      <?php if (!$daftar): ?>
        <div class="card empty">
          <?php if ($cari !== '' || $status !== ''): ?>
            <p>Tidak ada laporan yang cocok dengan pencarian Anda.</p>
            <a href="riwayat.php" class="btn btn-dark btn-sm">Reset Pencarian</a>
          <?php else: ?>
            <p>Anda belum memiliki laporan.</p>
            <a href="buat-pengaduan.php" class="btn btn-red btn-sm">Buat Laporan Pertama</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="r-list">
          <?php foreach ($daftar as $p):
              $t = strtotime($p['created_at']); ?>
            <article class="r-card">
              <div class="r-date">
                <span><?= e(mb_strtoupper(BULAN_PENDEK[(int) date('n', $t)])) ?></span>
                <strong><?= e(date('j', $t)) ?></strong>
              </div>
              <div class="r-body">
                <div class="r-meta">
                  <span class="r-code"><?= e($p['kode_tiket']) ?></span>
                  <?= status_badge($p['status']) ?>
                </div>
                <h3><?= e($p['judul']) ?></h3>
                <p><?= e(potong($p['isi'], 120)) ?></p>
                <a href="detail.php?id=<?= (int) $p['id'] ?>" class="btn btn-dark btn-sm">Lihat Detail</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalHal > 1): ?>
          <nav class="pagination" aria-label="Halaman">
            <?php if ($halaman > 1): ?>
              <a href="<?= e(url_hal($halaman - 1, $cari, $status)) ?>" aria-label="Sebelumnya"><?= ikon('chev-l') ?></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalHal; $i++): ?>
              <?php if ($i === $halaman): ?>
                <span class="active" aria-current="page"><?= $i ?></span>
              <?php else: ?>
                <a href="<?= e(url_hal($i, $cari, $status)) ?>"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <?php if ($halaman < $totalHal): ?>
              <a href="<?= e(url_hal($halaman + 1, $cari, $status)) ?>" aria-label="Berikutnya"><?= ikon('chev-r') ?></a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
<?php layout_selesai(); ?>