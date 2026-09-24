<?php
require_once 'fungsi.php';
cek_role(['petugas']);

// Hasil scan barcode berupa teks (angka id_parkir, misal "000123").
// Ambil angkanya saja, buang karakter lain kalau ada (spasi, dsb dari scanner).
$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';
$id_parkir = (int) preg_replace('/[^0-9]/', '', $kode);

if ($id_parkir > 0) {
    // Pastikan transaksi itu ada dan memang masih berstatus 'masuk' (belum dibayar)
    $q = mysqli_query($koneksi, "SELECT id_parkir FROM tb_transaksi WHERE id_parkir = $id_parkir AND status = 'masuk' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        header("Location: bayar.php?id=$id_parkir");
        exit;
    }
}

// Kode tidak valid / kendaraan sudah keluar / tidak ditemukan
header("Location: transaksi.php?scan_gagal=1");
exit;