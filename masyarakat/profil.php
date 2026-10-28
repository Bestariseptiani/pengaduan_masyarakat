<?php
require __DIR__ . '/../includes/auth_helper.php';
require_role('masyarakat');
require __DIR__ . '/../config/koneksi.php';
require __DIR__ . '/../includes/layout.php';

$uid = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT nama, nik, email, no_hp, password FROM users WHERE id = ?');
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ../auth/login.php');
    exit;
}

$errProfil = [];
$errSandi  = [];
$nama  = $user['nama'];
$no_hp = (string) $user['no_hp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if (!csrf_valid()) {
        flash_set('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
        header('Location: profil.php');
        exit;
    }

    if ($aksi === 'profil') {
        $nama  = trim(preg_replace('/\s+/', ' ', $_POST['nama'] ?? ''));
        $no_hp = preg_replace('/[\s-]/', '', $_POST['no_hp'] ?? '');

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100 || !preg_match('/^[\p{L}\s.\'-]+$/u', $nama)) {
            $errProfil['nama'] = 'Nama 3-100 huruf, tanpa angka atau simbol aneh.';
        }
        if (!preg_match('/^(\+62|62|0)8\d{8,12}$/', $no_hp)) {
            $errProfil['no_hp'] = 'Nomor HP tidak valid (contoh: 081234567890).';
        }
        if (!$errProfil) {
            $pdo->prepare('UPDATE users SET nama = ?, no_hp = ? WHERE id = ?')->execute([$nama, $no_hp, $uid]);
            $_SESSION['nama'] = $nama;
            flash_set('success', 'Profil berhasil diperbarui.');
            header('Location: profil.php');
            exit;
        }
    } elseif ($aksi === 'sandi') {
        $lama   = $_POST['sandi_lama'] ?? '';
        $baru   = $_POST['sandi_baru'] ?? '';
        $ulangi = $_POST['sandi_ulangi'] ?? '';

        if (!password_verify($lama, $user['password'])) {
            $errSandi['sandi_lama'] = 'Password lama salah.';
        }
        if (strlen($baru) < 8 || strlen($baru) > 72) {
            $errSandi['sandi_baru'] = 'Password baru 8-72 karakter.';
        } elseif ($baru === $lama) {
            $errSandi['sandi_baru'] = 'Password baru harus berbeda dari password lama.';
        }
        if ($baru !== $ulangi) {
            $errSandi['sandi_ulangi'] = 'Konfirmasi password tidak sama.';
        }
        if (!$errSandi) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($baru, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash_set('success', 'Password berhasil diganti.');
            header('Location: profil.php');
            exit;
        }
    }
}

function galat(array $errors, string $kunci): string
{
    return isset($errors[$kunci]) ? '<p class="field-error">' . e($errors[$kunci]) . '</p>' : '';
}

layout_mulai('Profil Saya', 'profil', ['topbar' => 'Profil Saya']);
?>
      <section class="card narrow">
        <div class="form-head">
          <h2>Data Diri</h2>
          <p>NIK dan email tidak dapat diubah. Hubungi petugas jika ada kesalahan data.</p>
        </div>

        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="profil">

          <div class="grid-2">
            <div class="field">
              <label for="nik">NIK</label>
              <input type="text" id="nik" value="<?= e($user['nik']) ?>" disabled>
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
            </div>
            <div class="field">
              <label for="nama">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($nama) ?>" class="<?= isset($errProfil['nama']) ? 'invalid' : '' ?>" required>
              <?= galat($errProfil, 'nama') ?>
            </div>
            <div class="field">
              <label for="no_hp">Nomor HP</label>
              <input type="tel" id="no_hp" name="no_hp" maxlength="16" value="<?= e($no_hp) ?>" class="<?= isset($errProfil['no_hp']) ? 'invalid' : '' ?>" required>
              <?= galat($errProfil, 'no_hp') ?>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-red">Simpan Perubahan</button>
          </div>
        </form>
      </section>

      <section class="card narrow gap-top">
        <div class="form-head">
          <h2>Ganti Password</h2>
          <p>Gunakan password yang kuat dan tidak dipakai di situs lain.</p>
        </div>

        <form method="post" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="sandi">

          <div class="field">
            <label for="sandi_lama">Password Lama</label>
            <input type="password" id="sandi_lama" name="sandi_lama" autocomplete="current-password" class="<?= isset($errSandi['sandi_lama']) ? 'invalid' : '' ?>" required>
            <?= galat($errSandi, 'sandi_lama') ?>
          </div>

          <div class="grid-2">
            <div class="field">
              <label for="sandi_baru">Password Baru</label>
              <input type="password" id="sandi_baru" name="sandi_baru" minlength="8" maxlength="72" placeholder="Min. 8 Karakter" autocomplete="new-password" class="<?= isset($errSandi['sandi_baru']) ? 'invalid' : '' ?>" required>
              <?= galat($errSandi, 'sandi_baru') ?>
            </div>
            <div class="field">
              <label for="sandi_ulangi">Ulangi Password Baru</label>
              <input type="password" id="sandi_ulangi" name="sandi_ulangi" autocomplete="new-password" class="<?= isset($errSandi['sandi_ulangi']) ? 'invalid' : '' ?>" required>
              <?= galat($errSandi, 'sandi_ulangi') ?>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-dark">Ganti Password</button>
          </div>
        </form>
      </section>
<?php layout_selesai(); ?>