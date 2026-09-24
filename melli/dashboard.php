<?php
require_once 'fungsi.php';
cek_login();

$role = $_SESSION['role'];

// Statistik ringkas (query efisien, hanya ambil yang diperlukan per role)
$stat_masuk = $stat_keluar_hari_ini = $stat_pendapatan_hari_ini = $stat_kendaraan = $stat_area = 0;

if ($role === 'admin' || $role === 'petugas') {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE status = 'masuk'"));
    $stat_masuk = $r['c'];

    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE status='keluar' AND DATE(waktu_keluar) = CURDATE()"));
    $stat_keluar_hari_ini = $r['c'];
}

if ($role === 'admin') {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_kendaraan"));
    $stat_kendaraan = $r['c'];

    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_area_parkir"));
    $stat_area = $r['c'];
}

if ($role === 'owner') {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(biaya_total),0) t FROM tb_transaksi WHERE status='keluar' AND DATE(waktu_keluar) = CURDATE()"));
    $stat_pendapatan_hari_ini = $r['t'];

    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE status='keluar' AND DATE(waktu_keluar) = CURDATE()"));
    $stat_keluar_hari_ini = $r['c'];
}

$menu = menu_sidebar($role);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Dashboard - ParkirAPP</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: #f4f6f9; }
        .sidebar { width: 250px; background: linear-gradient(180deg, #ff7e5f, #feb47b); color: #fff; flex-shrink: 0; }
        .sidebar-header { padding: 25px 20px; font-size: 20px; font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-menu { list-style: none; padding: 20px 0; }
        .sidebar-menu li a { display: block; padding: 12px 25px; color: #fff; text-decoration: none; font-size: 15px; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(0,0,0,0.15); font-weight: bold; }
        .main-content { flex: 1; padding: 30px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topbar h2 { color: #333; }
        .topbar .user-info { font-size: 13px; color: #666; text-align: right; }
        .topbar .user-info b { color: #333; }
        .badge-role { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #ff7e5f; color: #fff; font-size: 11px; margin-left: 5px; }
        .cards { display: flex; gap: 20px; flex-wrap: wrap; }
        .card-stat { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex: 1; min-width: 200px; }
        .card-stat .angka { font-size: 28px; font-weight: bold; color: #ff7e5f; }
        .card-stat .label { font-size: 13px; color: #777; margin-top: 5px; }
        .alert-info { padding: 12px; background: #d1ecf1; color: #0c5460; border-radius: 6px; margin-bottom: 20px; }
        .alert-warning { padding: 12px; background: #fff3cd; color: #856404; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirAPP</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='dashboard.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="topbar">
            <h2>Dashboard</h2>
            <div class="user-info">
                <b><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'User'); ?></b>
                <span class="badge-role"><?= strtoupper(htmlspecialchars($role)); ?></span>
            </div>
        </div>

        <?php if (isset($_GET['akses']) && $_GET['akses'] === 'ditolak'): ?>
            <div class="alert-warning">Anda tidak memiliki akses ke halaman tersebut.</div>
        <?php endif; ?>

        <div class="cards">
            <?php if ($role === 'admin' || $role === 'petugas'): ?>
                <div class="card-stat">
                    <div class="angka"><?= (int) $stat_masuk; ?></div>
                    <div class="label">Kendaraan Sedang Parkir</div>
                </div>
                <div class="card-stat">
                    <div class="angka"><?= (int) $stat_keluar_hari_ini; ?></div>
                    <div class="label">Kendaraan Keluar Hari Ini</div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="card-stat">
                    <div class="angka"><?= (int) $stat_kendaraan; ?></div>
                    <div class="label">Total Data Kendaraan Terdaftar</div>
                </div>
                <div class="card-stat">
                    <div class="angka"><?= (int) $stat_area; ?></div>
                    <div class="label">Area Parkir Terdaftar</div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'owner'): ?>
                <div class="card-stat">
                    <div class="angka">Rp <?= number_format($stat_pendapatan_hari_ini, 0, ',', '.'); ?></div>
                    <div class="label">Pendapatan Hari Ini</div>
                </div>
                <div class="card-stat">
                    <div class="angka"><?= (int) $stat_keluar_hari_ini; ?></div>
                    <div class="label">Transaksi Selesai Hari Ini</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="alert-info" style="margin-top:25px;">
            Selamat datang di Parkir Stasiun Balapan Solo. Gunakan menu di samping untuk mengakses fitur sesuai peran anda.
        </div>
    </div>
</body>
</html>