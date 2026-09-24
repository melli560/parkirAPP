<?php
require_once 'fungsi.php';
cek_role(['admin', 'petugas', 'owner']);

$id_parkir = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$q_karcis = mysqli_query($koneksi, "
    SELECT t.id_parkir, t.waktu_masuk, t.status,
           k.plat_nomor, k.jenis_kendaraan,
           a.nama_area,
           tr.tarif_per_jam,
           u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
    LEFT JOIN tb_tarif tr ON t.id_tarif = tr.id_tarif
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    WHERE t.id_parkir = $id_parkir
");
$trx = $q_karcis ? mysqli_fetch_assoc($q_karcis) : null;

// Tombol kembali menyesuaikan role
$link_kembali = ($_SESSION['role'] === 'petugas') ? 'transaksi.php' : 'javascript:history.back()';

if (!$trx) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Karcis Tidak Ditemukan - ParkirApp</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
            body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f6f9; }
            .box { background: #fff; padding: 40px 35px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); width: 100%; max-width: 380px; text-align: center; }
            .box .icon { font-size: 40px; margin-bottom: 10px; }
            .box h2 { color: #333; margin-bottom: 8px; }
            .box p { color: #777; font-size: 14px; margin-bottom: 25px; }
            .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 6px; background: #ff7e5f; color: #fff; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon">🎫❓</div>
            <h2>Karcis Tidak Ditemukan</h2>
            <p>Transaksi parkir dengan ID tersebut tidak ada atau sudah dihapus.</p>
            <a href="<?= $link_kembali; ?>" class="btn">&larr; Kembali</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Karcis Parkir #<?= str_pad((int) $trx['id_parkir'], 6, '0', STR_PAD_LEFT); ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 20px auto; font-size: 13px; color: #000; background: #fff; }
    .center { text-align: center; }
    hr { border: none; border-top: 1px dashed #000; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 2px 0; vertical-align: top; }
    .label { width: 42%; }
    .judul { letter-spacing: 2px; font-size: 18px; }
    .kode-besar { font-size: 22px; font-weight: bold; letter-spacing: 1px; margin: 6px 0; }
    .catatan { font-size: 11px; color: #333; line-height: 1.5; }
    .status-note { text-align: center; font-weight: bold; margin: 6px 0; }
    .no-print { margin-top: 20px; text-align: center; }
    .no-print button, .no-print a {
        display: inline-block; margin: 4px; padding: 8px 14px; border: none; border-radius: 6px;
        background: #ff7e5f; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer;
        font-family: 'Segoe UI', sans-serif; font-size: 13px;
    }
    .no-print a.btn-secondary { background: #6c757d; }

    /* Pengaturan cetak: cocok untuk printer biasa maupun printer thermal */
    @page { margin: 5mm; }
    @media print {
        .no-print { display: none !important; }
        html, body { width: auto; margin: 0; padding: 0; }
        body { max-width: 300px; }
    }
</style>
</head>
<body>
    <div class="center">
        <h3 class="judul">PARKIRAPP</h3>
        <div>Parkir Stasiun Balapan Solo</div>
        <div>Karcis Parkir Masuk</div>
    </div>
    <hr>
    <?php if ($trx['status'] === 'keluar'): ?>
        <div class="status-note">*** KENDARAAN SUDAH KELUAR ***</div>
        <hr>
    <?php endif; ?>
    <div class="center">
        <div class="kode-besar">#<?= str_pad((int) $trx['id_parkir'], 6, '0', STR_PAD_LEFT); ?></div>
    </div>
    <hr>
    <table>
        <tr><td class="label">Plat Nomor</td><td>: <b><?= strtoupper(htmlspecialchars($trx['plat_nomor'])); ?></b></td></tr>
        <tr><td class="label">Jenis</td><td>: <?= ucfirst(htmlspecialchars($trx['jenis_kendaraan'])); ?></td></tr>
        <tr><td class="label">Area Parkir</td><td>: <?= htmlspecialchars($trx['nama_area'] ?? '-'); ?></td></tr>
        <tr><td class="label">Jam Masuk</td><td>: <?= htmlspecialchars($trx['waktu_masuk']); ?></td></tr>
        <tr><td class="label">Tarif/Jam</td><td>: Rp <?= number_format($trx['tarif_per_jam'] ?? 0, 0, ',', '.'); ?></td></tr>
        <tr><td class="label">Petugas</td><td>: <?= htmlspecialchars($trx['nama_petugas'] ?? '-'); ?></td></tr>
    </table>
    <hr>
    <div class="catatan center">
        Simpan karcis ini baik-baik.<br>
        Tunjukkan karcis ini saat kendaraan keluar.<br>
        Kehilangan karcis dikenakan biaya sesuai ketentuan.
    </div>
    <hr>
    <div class="no-print">
        <button type="button" onclick="window.print()">🖨️ Cetak Karcis</button>
        <a href="<?= $link_kembali; ?>" class="btn-secondary">Kembali</a>
    </div>

    <?php if (isset($_GET['print'])): ?>
    <script>
        // Otomatis buka dialog cetak setelah halaman selesai dimuat
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
    <?php endif; ?>
</body>
</html>