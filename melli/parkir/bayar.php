<?php
require_once 'fungsi.php';
cek_role(['petugas']);

$id_parkir = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil transaksi yang masih berstatus 'masuk' (belum dibayar/belum keluar)
$q = mysqli_query($koneksi, "SELECT t.id_parkir, t.waktu_masuk, t.id_area, tr.tarif_per_jam,
        k.plat_nomor, k.jenis_kendaraan
    FROM tb_transaksi t
    JOIN tb_tarif tr ON t.id_tarif = tr.id_tarif
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    WHERE t.id_parkir = $id_parkir AND t.status = 'masuk'");
$trx = $q ? mysqli_fetch_assoc($q) : null;

if (!$trx) {
    header("Location: transaksi.php");
    exit;
}

// Hitung estimasi durasi & biaya (preview saja, BELUM disimpan ke database)
$durasi_estimasi = (int) ceil((strtotime('now') - strtotime($trx['waktu_masuk'])) / 3600);
if ($durasi_estimasi < 1) $durasi_estimasi = 1;
$biaya_estimasi = $durasi_estimasi * (float) $trx['tarif_per_jam'];

// String data untuk QR code simulasi (bukan format QRIS/EMVCo resmi, hanya representasi info transaksi)
$data_qr = "PARKIR-QRIS|ID:{$trx['id_parkir']}|PLAT:{$trx['plat_nomor']}|TOTAL:{$biaya_estimasi}";
$qr_url  = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($data_qr);

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Pembayaran - ParkirApp</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f6f9; }
        .sidebar { width: 250px; background: linear-gradient(180deg, #ff7e5f, #feb47b); color: #fff; flex-shrink: 0; }
        .sidebar-header { padding: 25px 20px; font-size: 20px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-menu { list-style: none; padding: 20px 0; }
        .sidebar-menu li a { display: block; padding: 12px 25px; color: #fff; text-decoration: none; font-size: 15px; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(0,0,0,0.15); font-weight: bold; }
        .main-content { flex: 1; padding: 30px; display: flex; justify-content: center; }
        .card { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); max-width: 420px; width: 100%; height: fit-content; }
        .plat { font-size: 24px; font-weight: bold; text-align: center; }
        .sub { text-align: center; color: #777; font-size: 13px; margin-top: 4px; margin-bottom: 20px; }
        .ringkasan { background: #f8f9fb; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
        .ringkasan div { display: flex; justify-content: space-between; font-size: 14px; padding: 4px 0; }
        .ringkasan .total { font-size: 20px; font-weight: bold; color: #ff7e5f; border-top: 1px dashed #ddd; margin-top: 8px; padding-top: 10px; }
        .metode { display: flex; gap: 12px; margin-bottom: 10px; }
        .btn-metode {
            flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 8px; background: #fff;
            cursor: pointer; font-weight: bold; font-size: 14px; text-align: center; color: #333;
        }
        .btn-metode:hover { border-color: #ff7e5f; }
        .btn-metode.active { border-color: #ff7e5f; background: #fff3ef; color: #ff7e5f; }
        .panel-qris { display: none; text-align: center; margin-top: 15px; }
        .panel-qris.show { display: block; }
        .panel-qris img { border: 1px solid #eee; border-radius: 8px; margin: 10px 0; }
        .panel-qris .catatan { font-size: 12px; color: #888; margin-bottom: 15px; }
        .btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; text-align: center; margin-top: 6px; }
        .btn-primary { background: #ff7e5f; }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirApp</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='transaksi.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="card">
            <div class="plat"><?= strtoupper(htmlspecialchars($trx['plat_nomor'])); ?></div>
            <div class="sub"><?= ucfirst(htmlspecialchars($trx['jenis_kendaraan'])); ?> &middot; Masuk <?= htmlspecialchars($trx['waktu_masuk']); ?></div>

            <div class="ringkasan">
                <div><span>Durasi Parkir</span><span><?= $durasi_estimasi; ?> jam</span></div>
                <div><span>Tarif per Jam</span><span>Rp <?= number_format($trx['tarif_per_jam'], 0, ',', '.'); ?></span></div>
                <div class="total"><span>Total Bayar</span><span>Rp <?= number_format($biaya_estimasi, 0, ',', '.'); ?></span></div>
            </div>

            <div class="metode">
                <div class="btn-metode active" id="opt-tunai" onclick="pilihMetode('tunai')">💵 Tunai</div>
                <div class="btn-metode" id="opt-qris" onclick="pilihMetode('qris')">📱 QRIS</div>
            </div>

            <div id="panel-tunai">
                <a href="transaksi.php?keluar=<?= $id_parkir; ?>&metode=tunai"
                   class="btn btn-primary" onclick="return confirm('Konfirmasi pembayaran tunai diterima?')">
                   Konfirmasi Bayar Tunai
                </a>
            </div>

            <div id="panel-qris" class="panel-qris">
                <img src="<?= htmlspecialchars($qr_url); ?>" width="220" height="220" alt="QR Code Pembayaran">
                <div class="catatan">Simulasi QRIS &mdash; minta pelanggan scan kode ini dengan aplikasi e-wallet/m-banking, lalu klik konfirmasi di bawah setelah pembayaran diterima.</div>
                <a href="transaksi.php?keluar=<?= $id_parkir; ?>&metode=qris"
                   class="btn btn-primary" onclick="return confirm('Konfirmasi pembayaran QRIS sudah diterima?')">
                   Konfirmasi Pembayaran Diterima
                </a>
            </div>

            <a href="transaksi.php" class="btn btn-secondary">&larr; Batal</a>
        </div>
    </div>

    <script>
        function pilihMetode(metode) {
            document.getElementById('opt-tunai').classList.toggle('active', metode === 'tunai');
            document.getElementById('opt-qris').classList.toggle('active', metode === 'qris');
            document.getElementById('panel-tunai').style.display = metode === 'tunai' ? 'block' : 'none';
            document.getElementById('panel-qris').classList.toggle('show', metode === 'qris');
        }
    </script>
</body>
</html>
