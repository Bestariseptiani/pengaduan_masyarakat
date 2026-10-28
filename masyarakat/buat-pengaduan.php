<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../includes/layout.php';

$uid = (int) $_SESSION['user_id'];

$kategori = $pdo->query('SELECT id, nama FROM kategori ORDER BY id')->fetchAll();
$idKategori = array_map('intval', array_column($kategori, 'id'));

$old    = ['kategori' => '', 'tanggal' => '', 'judul' => '', 'lokasi' => '', 'isi' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['kategori'] = (string) ($_POST['kategori'] ?? '');
    $old['tanggal']  = trim($_POST['tanggal'] ?? '');
    $old['judul']    = trim(preg_replace('/\s+/', ' ', $_POST['judul'] ?? ''));
    $old['lokasi']   = trim(preg_replace('/\s+/', ' ', $_POST['lokasi'] ?? ''));
    $old['isi']      = trim($_POST['isi'] ?? '');

    if (!csrf_valid()) {
        $errors['umum'] = 'Sesi formulir tidak valid atau ukuran kiriman terlalu besar. Muat ulang halaman lalu coba lagi.';
    }

    if (!in_array((int) $old['kategori'], $idKategori, true)) {
        $errors['kategori'] = 'Pilih kategori laporan.';
    }

    $tgl = DateTime::createFromFormat('Y-m-d', $old['tanggal']);
    if (!$tgl || $tgl->format('Y-m-d') !== $old['tanggal']) {
        $errors['tanggal'] = 'Tanggal kejadian tidak valid.';
    } elseif ($old['tanggal'] > date('Y-m-d')) {
        $errors['tanggal'] = 'Tanggal kejadian tidak boleh di masa depan.';
    }

    if (mb_strlen($old['judul']) < 5 || mb_strlen($old['judul']) > 150) {
        $errors['judul'] = 'Judul laporan 5-150 karakter.';
    }
    if (mb_strlen($old['lokasi']) < 5 || mb_strlen($old['lokasi']) > 255) {
        $errors['lokasi'] = 'Lokasi kejadian 5-255 karakter.';
    }
    if (mb_strlen($old['isi']) < 20) {
        $errors['isi'] = 'Detail laporan minimal 20 karakter agar petugas dapat memahaminya.';
    } elseif (mb_strlen($old['isi']) > 5000) {
        $errors['isi'] = 'Detail laporan maksimal 5000 karakter.';
    }

    $namaFoto = null;
    if (!$errors) {
        [$ok, $hasil] = simpan_foto($_FILES['foto'] ?? []);
        if ($ok) {
            $namaFoto = $hasil;
        } else {
            $errors['foto'] = $hasil;
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Kode tiket sementara, lalu diganti dengan kode berbasis ID agar pasti unik
            $simpan = $pdo->prepare(
                'INSERT INTO pengaduan (kode_tiket, user_id, kategori_id, judul, isi, lokasi, tanggal_kejadian, foto)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $simpan->execute([
                'TMP-' . bin2hex(random_bytes(6)), $uid, (int) $old['kategori'],
                $old['judul'], $old['isi'], $old['lokasi'], $old['tanggal'], $namaFoto,
            ]);
            $id   = (int) $pdo->lastInsertId();
            $kode = sprintf('ADU-%s-%04d', date('Y'), $id);

            $pdo->prepare('UPDATE pengaduan SET kode_tiket = ? WHERE id = ?')->execute([$kode, $id]);
            $pdo->prepare('INSERT INTO riwayat_status (pengaduan_id, status, catatan, diubah_oleh) VALUES (?, ?, ?, ?)')
                ->execute([$id, 'menunggu', 'Laporan diterima sistem', $uid]);

            $pdo->commit();

            flash_set('success', 'Laporan berhasil dikirim. Kode tiket Anda: ' . $kode);
            header('Location: detail.php?id=' . $id);
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            hapus_foto($namaFoto);
            $errors['umum'] = 'Laporan gagal disimpan. Silakan coba lagi.';
        }
    }
}

function galat(array $errors, string $kunci): string
{
    return isset($errors[$kunci]) ? '<p class="field-error">' . e($errors[$kunci]) . '</p>' : '';
}

layout_mulai('Buat Laporan', 'buat', ['topbar' => 'Form Pengaduan Baru']);
?>
      <section class="card narrow">
        <div class="form-head">
          <h2>Tulis Laporan Anda</h2>
          <p>Berikan detail informasi yang akurat untuk mempercepat proses tindak lanjut.</p>
        </div>

        <?php if (isset($errors['umum'])): ?>
          <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
          <?= csrf_field() ?>

          <div class="grid-2">
            <div class="field">
              <label for="kategori"><?= ikon('list') ?> Kategori Laporan</label>
              <select id="kategori" name="kategori" class="<?= isset($errors['kategori']) ? 'invalid' : '' ?>" required>
                <option value="">Pilih Kategori</option>
                <?php foreach ($kategori as $k): ?>
                  <option value="<?= (int) $k['id'] ?>"<?= (string) $k['id'] === $old['kategori'] ? ' selected' : '' ?>><?= e($k['nama']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= galat($errors, 'kategori') ?>
            </div>

            <div class="field">
              <label for="tanggal"><?= ikon('calendar') ?> Tanggal Kejadian</label>
              <input type="date" id="tanggal" name="tanggal" max="<?= date('Y-m-d') ?>"
                     value="<?= e($old['tanggal']) ?>" class="<?= isset($errors['tanggal']) ? 'invalid' : '' ?>" required>
              <?= galat($errors, 'tanggal') ?>
            </div>
          </div>

          <div class="field">
            <label for="judul"><?= ikon('edit') ?> Judul Laporan</label>
            <input type="text" id="judul" name="judul" maxlength="150" placeholder="Contoh: Jalan Rusak di Jl. Merdeka"
                   value="<?= e($old['judul']) ?>" class="<?= isset($errors['judul']) ? 'invalid' : '' ?>" required>
            <?= galat($errors, 'judul') ?>
          </div>

          <div class="field">
            <label for="lokasi"><?= ikon('pin') ?> Lokasi Kejadian</label>
            <input type="text" id="lokasi" name="lokasi" maxlength="255" placeholder="Contoh: Jl. Merdeka No. 12, Kelurahan Gambir"
                   value="<?= e($old['lokasi']) ?>" class="<?= isset($errors['lokasi']) ? 'invalid' : '' ?>" required>
            <?= galat($errors, 'lokasi') ?>
          </div>

          <div class="field">
            <label for="isi"><?= ikon('file') ?> Detail Laporan</label>
            <textarea id="isi" name="isi" rows="6" maxlength="5000" placeholder="Ceritakan kronologi kejadian secara detail..."
                      class="<?= isset($errors['isi']) ? 'invalid' : '' ?>" required><?= e($old['isi']) ?></textarea>
            <?= galat($errors, 'isi') ?>
          </div>

          <div class="field">
            <label for="foto"><?= ikon('image') ?> Foto Bukti <span class="opsional">(opsional)</span></label>
            <label class="dropzone" for="foto">
              <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" data-maks="5242880">
              <?= ikon('upload', 'dz-icon') ?>
              <span class="dz-title" data-dz-title>Klik atau seret foto ke sini</span>
              <span class="dz-hint">Format: JPG, PNG (Maks. 5MB)</span>
              <img class="dz-preview" alt="" hidden>
            </label>
            <p class="field-error" data-foto-error<?= isset($errors['foto']) ? '' : ' hidden' ?>><?= e($errors['foto'] ?? '') ?></p>
          </div>

          <div class="form-actions">
            <a href="dashboard.php" class="btn btn-ghost">Batal</a>
            <button type="submit" class="btn btn-red">Kirim Laporan</button>
          </div>
        </form>
      </section>
<?php layout_selesai(); ?>