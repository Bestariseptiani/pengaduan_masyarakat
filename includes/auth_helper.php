<?php
// Fungsi bantu untuk sesi, CSRF, flash message, dan pembatasan akses.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/** Escape output HTML (cegah XSS). */
function e(?string $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

/* ---------- Flash message (pesan sekali tampil) ---------- */
function flash_set(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- Status login & hak akses ---------- */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Tujuan halaman setelah login (dipanggil dari folder auth/). */
function home_for_role(string $role): string
{
    return $role === 'masyarakat'
        ? '../masyarakat/dashboard.php'
        : '../admin/dashboard.php';
}

/** Pakai di halaman yang wajib login (dipanggil dari folder masyarakat/ atau admin/). */
function require_role(string ...$roles): void
{
    // Cegah halaman tersimpan di cache, supaya tombol "back" setelah logout tidak menampilkannya lagi.
    header('Cache-Control: no-store, no-cache, must-revalidate');

    if (!is_logged_in()) {
        header('Location: ../auth/login.php');
        exit;
    }
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}