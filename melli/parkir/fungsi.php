<?php
/**
 * fungsi.php
 * Kumpulan fungsi bantu yang dipakai di banyak halaman:
 * - cek_login()   : pastikan user sudah login
 * - cek_role()    : batasi halaman hanya untuk role tertentu
 * - catat_log()   : simpan aktivitas user ke tb_log_aktivitas
 */

require_once __DIR__ . '/koneksi.php';

// Pastikan user sudah login, kalau belum lempar ke halaman login
function cek_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Batasi akses halaman hanya untuk role tertentu.
// Contoh pemakaian di atas halaman: cek_role(['admin']);
function cek_role($allowed_roles) {
    cek_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: dashboard.php?akses=ditolak");
        exit;
    }
}

// Simpan aktivitas ke tb_log_aktivitas
function catat_log($koneksi, $id_user, $aktivitas) {
    $id_user_int   = (int) $id_user;
    $aktivitas_esc = mysqli_real_escape_string($koneksi, $aktivitas);
    mysqli_query($koneksi, "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas)
        VALUES ($id_user_int, '$aktivitas_esc', NOW())");
}

// Label menu sidebar per role, dipakai di semua halaman agar konsisten
function menu_sidebar($role) {
    $menu = [
        ['href' => 'dashboard.php', 'label' => 'Dashboard', 'roles' => ['admin','petugas','owner']],
        ['href' => 'user.php',      'label' => 'CRUD User', 'roles' => ['admin']],
        ['href' => 'tarif.php',     'label' => 'CRUD Tarif Parkir', 'roles' => ['admin']],
        ['href' => 'area.php',      'label' => 'CRUD Area Parkir', 'roles' => ['admin']],
        ['href' => 'kendaraan.php', 'label' => 'CRUD Kendaraan', 'roles' => ['admin']],
        ['href' => 'log.php',       'label' => 'Akses Log Aktivitas', 'roles' => ['admin']],
        ['href' => 'transaksi.php', 'label' => 'Transaksi Parkir', 'roles' => ['petugas']],
        ['href' => 'rekap.php',     'label' => 'Rekap Transaksi', 'roles' => ['owner']],
        ['href' => 'logout.php',    'label' => 'Logout', 'roles' => ['admin','petugas','owner']],
    ];
    $hasil = [];
    foreach ($menu as $m) {
        if (in_array($role, $m['roles'])) {
            $hasil[] = $m;
        }
    }
    return $hasil;
}