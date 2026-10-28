<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../includes/layout.php';

$uid = (int) $_SESSION['user_id'];
$id  = (int) ($_GET['id'] ?? 0);

// Hanya pemilik laporan yang boleh melihatnya
$stmt = $pdo->prepare(
    'SELECT p.*, k.nama AS kategori
     FROM pengaduan p JOIN kategori k ON k.id = p.kategori_id
     WHERE p.id = ? AND p.user_id = ?'
);
$stmt->execute([$id, $uid]);
$p = $stmt->fetch();

if (!$p) {
    http_response_code(404);
    layout_mulai('Laporan Tidak Ditemukan', 'riwayat', ['topbar' => 'Detail Laporan', 'kembali' => 'riwayat.php']);
    echo '<div class="card empty"><p>Laporan tidak ditemukan atau bukan milik Anda.</p>'
        . '<a href="riwayat.php" class="btn btn-dark btn-sm">Kembali ke Riwayat</a></div>';
    layout_selesai();
    exit;
}

// Tarik laporan: hanya selama masih menunggu verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tarik') {
    if (!csrf_valid()) {
        flash_set('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
    } elseif ($p['status'] !== 'menunggu') {
        flash_set('error', 'Laporan yang sudah diverifikasi tidak dapat ditarik.');
    } else {
        $pdo->prepare("DELETE FROM pengaduan WHERE id = ? AND user_id = ? AND status = 'menunggu'")->execute([$id, $uid]);
        hapus_foto($p['foto']);
        flash_set('success', 'Laporan ' . $p['kode_tiket'] . ' telah ditarik.');
        header('Location: riwayat.php');
        exit;
    }
    header('Location: detail.php?id=' . $id);
    exit;
}

// Riwayat status -> tanggal pertama tiap status
$stmt = $pdo->prepare('SELECT status, catatan, created_at FROM riwayat_status WHERE pengaduan_id = ? ORDER BY id');
$stmt->execute([$id]);
$tanggalStatus = [];
foreach ($stmt->fetchAll() as $r) {
    $tanggalStatus[$r['status']] ??= $r['created_at'];
}

// Susun langkah timeline
$urut = ['menunggu' => 0, 'diverifikasi' => 1, 'diproses' => 2, 'selesai' => 3];
if ($p['status'] === 'ditolak') {
    $langkah = [
        ['menunggu', 'Laporan Terkirim', 'Laporan diterima sistem'],
        ['ditolak', 'Ditolak', $p['alasan_penolakan'] ?: 'Laporan tidak dapat diproses'],
    ];
    $posisi = 1;
} else {
    $langkah = [
        ['menunggu', 'Laporan Terkirim', 'Laporan diterima sistem'],
        ['diverifikasi', 'Diverifikasi', 'Petugas memeriksa kelengkapan laporan'],
        ['diproses', 'Dalam Proses', 'Sedang ditangani oleh dinas terkait'],
        ['selesai', 'Selesai', 'Laporan telah selesai ditangani'],
    ];
    $posisi = $urut[$p['status']] ?? 0;
}

// Tanggapan petugas
$stmt = $pdo->prepare(
    'SELECT t.isi_tanggapan, t.created_at, u.nama, u.role
     FROM tanggapan t JOIN users u ON u.id = t.petugas_id
     WHERE t.pengaduan_id = ? ORDER BY t.id'
);
$stmt->execute([$id]);
$tanggapan = $stmt->fetchAll();

layout_mulai('Detail ' . $p['kode_tiket'], 'riwayat', [
    'topbar'  => 'Detail Laporan #' . $p['kode_tiket'],
    'kembali' => 'riwayat.php',
    'lencana' => '<span class="badge badge-' . e($p['status']) . ' badge-top">' . e(STATUS_HEADER[$p['status']] ?? $p['status']) . '</span>',
]);
?>
      <div class="detail-grid">
        <div class="detail-main">

          <section class="card">
            <div class="detail-head">
              <div>
                <h2><?= e($p['judul']) ?></h2>
                <p class="meta">Kategori: <strong><?= e($p['kategori']) ?></strong> &middot; Kejadian pada <?= e(tgl_id($p['tanggal_kejadian'])) ?> &middot; Dilaporkan <?= e(tgl_id($p['created_at'])) ?></p>
              </div>
              <div class="ticket">
                <small>ID Tiket</small>
                <strong><?= e($p['kode_tiket']) ?></strong>
              </div>
            </div>

            <h4 class="section-label">Isi Laporan</h4>
            <p class="isi"><?= nl2br(e($p['isi'])) ?></p>

            <h4 class="section-label">Lokasi</h4>
            <p class="lokasi"><?= ikon('pin') ?> <?= e($p['lokasi']) ?></p>

            <h4 class="section-label">Foto Bukti</h4>
            <?php if (foto_valid($p['foto'])): ?>
              <div class="fotos">
                <a href="../uploads/<?= e($p['foto']) ?>" target="_blank" rel="noopener">
                  <img src="../uploads/<?= e($p['foto']) ?>" alt="Foto bukti laporan <?= e($p['kode_tiket']) ?>">
                </a>
              </div>
            <?php else: ?>
              <p class="muted">Tidak ada foto bukti yang dilampirkan.</p>
            <?php endif; ?>
          </section>

          <section class="card">
            <h3 class="card-title"><?= ikon('message') ?> Tanggapan Petugas</h3>
            <?php if (!$tanggapan): ?>
              <p class="muted">Belum ada tanggapan dari petugas.</p>
            <?php else: ?>
              <?php foreach ($tanggapan as $t): ?>
                <div class="reply">
                  <span class="avatar sm"><?= e(inisial($t['nama'])) ?></span>
                  <div class="reply-body">
                    <div class="reply-head">
                      <strong><?= e($t['nama']) ?></strong>
                      <small><?= e(tgl_id($t['created_at'], true)) ?></small>
                    </div>
                    <div class="bubble"><?= nl2br(e($t['isi_tanggapan'])) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </section>

        </div>

        <aside class="detail-side">
          <section class="card">
            <h3 class="side-title">Status Laporan</h3>
            <ol class="timeline">
              <?php foreach ($langkah as $i => [$kunci, $judulLangkah, $deskripsi]):
                  if ($p['status'] === 'ditolak' && $kunci === 'ditolak') {
                      $keadaan = 'failed';
                  } elseif ($i < $posisi || ($i === $posisi && $p['status'] === 'selesai')) {
                      $keadaan = 'done';
                  } elseif ($i === $posisi) {
                      $keadaan = 'active';
                  } else {
                      $keadaan = 'pending';
                  }
                  $tgl = $tanggalStatus[$kunci] ?? null;
                  $sub = ($keadaan === 'done' && $tgl) ? tgl_id($tgl, true) : $deskripsi;
              ?>
                <li class="<?= $keadaan ?>">
                  <span class="dot"><?= $keadaan === 'done' ? ikon('check') : ($keadaan === 'failed' ? ikon('x') : '') ?></span>
                  <strong><?= e($judulLangkah) ?></strong>
                  <small><?= e($sub) ?></small>
                </li>
              <?php endforeach; ?>
            </ol>

            <?php if ($p['status'] === 'menunggu'): ?>
              <form method="post" data-confirm="Tarik laporan ini? Laporan akan dihapus dan tidak dapat dikembalikan.">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="tarik">
                <button type="submit" class="btn btn-outline-red btn-block">Tarik Laporan</button>
              </form>
              <p class="side-note">Laporan hanya dapat ditarik selama masih menunggu verifikasi petugas.</p>
            <?php else: ?>
              <p class="side-note">Laporan yang sudah diproses petugas tidak dapat ditarik.</p>
            <?php endif; ?>
          </section>
        </aside>
      </div>
<?php layout_selesai(); ?>