<?php
require __DIR__ . '/../includes/auth_helper.php';

// Logout hanya lewat tombol (POST) dengan token CSRF, supaya tidak bisa dipicu dari link luar.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    header('Location: ' . (is_logged_in() ? home_for_role($_SESSION['role']) : 'login.php'));
    exit;
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Mulai sesi baru hanya untuk pesan "berhasil keluar"
session_start();
flash_set('success', 'Anda telah keluar. Sampai jumpa kembali.');
header('Location: login.php');
exit;