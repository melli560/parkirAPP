<?php
require_once 'fungsi.php';
cek_role(['admin', 'owner']);

$id_kendaraan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$q_kendaraan = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE id_kendaraan = $id_kendaraan");
$kendaraan = $q_kendaraan ? mysqli_fetch_assoc($q_kendaraan) : null;

if (!$kendaraan) {
    die("Data kendaraan tidak ditemukan.");
}

$riwayat = mysqli_query($koneksi, "
    SELECT t.id_parkir, t.waktu_masuk, t.waktu_keluar, t.durasi_jam, t.biaya_total, t.status,
           a.nama_area, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    WHERE t.id_kendaraan = $id_kendaraan
    ORDER BY t.waktu_masuk DESC
");

$menu = menu_sidebar($_SESSION['role']);
$halaman_kembali = $_SESSION['role'] === 'owner' ? 'rekap.php' : 'kendaraan.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Riwayat Kendaraan - ParkirAPP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f6f9; }
        .sidebar { width: 250px; background: linear-gradient(180deg, #ff7e5f, #feb47b); color: #fff; flex-shrink: 0; }
        .sidebar-header { padding: 25px 20px; font-size: 20px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-menu { list-style: none; padding: 20px 0; }
        .sidebar-menu li a { display: block; padding: 12px 25px; color: #fff; text-decoration: none; font-size: 15px; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(0,0,0,0.15); font-weight: bold; }
        .main-content { flex: 1; padding: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .btn { padding: 8px 15px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-block; }
        .btn-primary { background: #ff7e5f; }
        .btn-secondary { background: #6c757d; }
        .btn-info { background: #17a2b8; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-masuk { background: #fff3cd; color: #856404; }
        .badge-keluar { background: #d4edda; color: #155724; }
        .info-plat { font-size: 22px; font-weight: bold; }
        .info-sub { color: #777; font-size: 13px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirAPP</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="card">
            <div class="info-plat"><?= strtoupper(htmlspecialchars($kendaraan['plat_nomor'])); ?></div>
            <div class="info-sub">
                <?= ucfirst(htmlspecialchars($kendaraan['jenis_kendaraan'])); ?>
                &middot; <?= htmlspecialchars($kendaraan['warna'] ?? '-'); ?>
                &middot; Pemilik: <?= htmlspecialchars($kendaraan['pemilik'] ?? '-'); ?>
            </div>
            <a href="<?= $halaman_kembali; ?>" class="btn btn-secondary" style="margin-top:15px;">&larr; Kembali</a>
        </div>

        <div class="card">
            <h3>Riwayat Transaksi Parkir</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Waktu Masuk</th><th>Waktu Keluar</th><th>Area</th><th>Durasi</th><th>Biaya</th><th>Petugas</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; $ada = false; while ($r = mysqli_fetch_assoc($riwayat)): $ada = true; ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($r['waktu_masuk']); ?></td>
                        <td><?= htmlspecialchars($r['waktu_keluar'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($r['nama_area'] ?? '-'); ?></td>
                        <td><?= $r['status'] === 'keluar' ? ((int) $r['durasi_jam'] . ' jam') : '-'; ?></td>
                        <td><?= $r['status'] === 'keluar' ? ('Rp ' . number_format($r['biaya_total'], 0, ',', '.')) : '-'; ?></td>
                        <td><?= htmlspecialchars($r['nama_petugas'] ?? '-'); ?></td>
                        <td>
                            <?php if ($r['status'] === 'keluar'): ?>
                                <span class="badge badge-keluar">Selesai</span>
                            <?php else: ?>
                                <span class="badge badge-masuk">Sedang Parkir</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="struk.php?id=<?= (int) $r['id_parkir']; ?>" target="_blank" class="btn btn-info">Cetak Struk</a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                        <tr><td colspan="9" style="text-align:center;color:#888;">Belum ada riwayat transaksi untuk kendaraan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>