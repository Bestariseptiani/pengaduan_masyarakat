<?php
// Fungsi bantu umum: label status, format tanggal, ikon, dan upload foto.

require_once __DIR__ . '/auth_helper.php';

date_default_timezone_set('Asia/Jakarta');

const STATUS_LABEL = [
    'menunggu'     => 'Menunggu',
    'diverifikasi' => 'Diverifikasi',
    'diproses'     => 'Diproses',
    'selesai'      => 'Selesai',
    'ditolak'      => 'Ditolak',
];

// Teks lencana di bagian atas halaman detail
const STATUS_HEADER = [
    'menunggu'     => 'Menunggu Verifikasi',
    'diverifikasi' => 'Terverifikasi',
    'diproses'     => 'Sedang Diproses',
    'selesai'      => 'Selesai',
    'ditolak'      => 'Ditolak',
];

const BULAN_PENDEK = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
];

const FOTO_MAKS_BYTE = 5 * 1024 * 1024; // 5 MB

function status_label(string $status): string
{
    return STATUS_LABEL[$status] ?? ucfirst($status);
}

function status_badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

/** Contoh hasil: "24 Okt 2026" atau "24 Okt 2026, 14:05". */
function tgl_id(?string $waktu, bool $denganJam = false): string
{
    $t = $waktu ? strtotime($waktu) : false;
    if ($t === false) {
        return '-';
    }
    $teks = date('j', $t) . ' ' . BULAN_PENDEK[(int) date('n', $t)] . ' ' . date('Y', $t);
    return $denganJam ? $teks . ', ' . date('H:i', $t) : $teks;
}

function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama)) ?: [];
    $hasil = '';
    foreach (array_slice($kata, 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil !== '' ? $hasil : '?';
}

function potong(string $teks, int $maks): string
{
    $teks = trim(preg_replace('/\s+/', ' ', $teks));
    return mb_strlen($teks) > $maks ? mb_substr($teks, 0, $maks - 1) . '…' : $teks;
}

/** Ikon SVG (gaya garis) berdasarkan nama. */
function ikon(string $nama, string $kelas = ''): string
{
    static $path = [
        'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'history'  => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'bell'     => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'back'     => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'plus'     => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'open'     => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
        'list'     => '<path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8"/>',
        'upload'   => '<path d="M4 14.9A7 7 0 1 1 15.7 8h1.8a4.5 4.5 0 0 1 2.5 8.2"/><path d="M12 12v9"/><path d="m16 16-4-4-4 4"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'chev-l'   => '<path d="m15 18-6-6 6-6"/>',
        'chev-r'   => '<path d="m9 18 6-6-6-6"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'message'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'help'     => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'x'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    ];

    if (!isset($path[$nama])) {
        return '';
    }
    return '<svg class="ico ' . $kelas . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $path[$nama] . '</svg>';
}

/**
 * Simpan foto bukti ke folder uploads/.
 * Hasil: [true, namaFile|null] jika sukses (null = tidak ada foto), atau [false, pesanError].
 */
function simpan_foto(array $berkas): array
{
    $kode = $berkas['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($kode === UPLOAD_ERR_NO_FILE) {
        return [true, null];
    }
    if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) {
        return [false, 'Ukuran foto melebihi batas yang diizinkan server.'];
    }
    if ($kode !== UPLOAD_ERR_OK) {
        return [false, 'Foto gagal diunggah. Silakan coba lagi.'];
    }
    if ($berkas['size'] > FOTO_MAKS_BYTE) {
        return [false, 'Ukuran foto maksimal 5 MB.'];
    }

    // Periksa isi file sebenarnya, jangan percaya ekstensi atau tipe dari browser
    $info     = @getimagesize($berkas['tmp_name']);
    $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$info['mime'] ?? ''] ?? null;
    if ($ekstensi === null || !is_uploaded_file($berkas['tmp_name'])) {
        return [false, 'Format foto harus JPG atau PNG.'];
    }

    $folder = __DIR__ . '/../uploads';
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        return [false, 'Folder uploads tidak dapat dibuat di server.'];
    }

    $nama = bin2hex(random_bytes(16)) . '.' . $ekstensi;
    if (!move_uploaded_file($berkas['tmp_name'], $folder . '/' . $nama)) {
        return [false, 'Foto gagal disimpan di server.'];
    }
    return [true, $nama];
}

function foto_valid(?string $nama): bool
{
    return $nama !== null && preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nama) === 1;
}

function hapus_foto(?string $nama): void
{
    if (foto_valid($nama)) {
        @unlink(__DIR__ . '/../uploads/' . $nama);
    }
}