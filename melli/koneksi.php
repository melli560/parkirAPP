<?php
/**
 * koneksi.php
 * Koneksi ke database + memulai session.
 * Sesuaikan $host, $user, $pass, $dbname dengan konfigurasi server anda.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "parkir";

$koneksi = mysqli_connect($host, $user, $pass, $dbname);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");