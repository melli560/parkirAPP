<?php
require_once 'fungsi.php';
cek_role(['owner']);

// Default periode: hari ini
$dari = isset($_GET['dari']) && $_GET['dari'] !== '' ? $_GET['dari'] : date('Y-m-d');
$sampai = isset($_GET['sampai']) && $_GET['sampai'] !== '' ? $_GET['sampai'] : date('Y-m-d');

// Validasi format tanggal sederhana, fallback ke hari ini kalau tidak valid
function tanggal_valid($tgl) {
    $d = DateTime::createFromFormat('Y-m-d', $tgl);
    return $d && $d->format('Y-m-d') === $tgl;
}
if (!tanggal_valid($dari))   $dari = date('Y-m-d');
if (!tanggal_valid($sampai)) $sampai = date('Y-m-d');

// Pastikan urutan tanggal benar
if ($dari > $sampai) { $tmp = $dari; $dari = $sampai; $sampai = $tmp; }

$dari_esc   = mysqli_real_escape_string($koneksi, $dari);
$sampai_esc = mysqli_real_escape_string($koneksi, $sampai);

// Ringkasan total pada periode yang dipilih
$q_ringkasan = mysqli_query($koneksi, "
    SELECT COUNT(*) jumlah_transaksi, COALESCE(SUM(biaya_total),0) total_pendapatan
    FROM tb_transaksi
    WHERE status = 'keluar' AND DATE(waktu_keluar) BETWEEN '$dari_esc' AND '$sampai_esc'
");
$ringkasan = mysqli_fetch_assoc($q_ringkasan);

// Rekap per jenis kendaraan
$per_jenis = mysqli_query($koneksi, "
    SELECT k.jenis_kendaraan, COUNT(*) jumlah, COALESCE(SUM(t.biaya_total),0) total
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    WHERE t.status = 'keluar' AND DATE(t.waktu_keluar) BETWEEN '$dari_esc' AND '$sampai_esc'
    GROUP BY k.jenis_kendaraan
    ORDER BY total DESC
");

// Detail transaksi pada periode (limit agar tetap ringan)
$detail = mysqli_query($koneksi, "
    SELECT t.id_parkir, t.waktu_masuk, t.waktu_keluar, t.durasi_jam, t.biaya_total,
           k.plat_nomor, k.jenis_kendaraan, a.nama_area, u.nama_lengkap AS nama_petugas
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
    LEFT JOIN tb_user u ON t.id_user = u.id_user
    WHERE t.status = 'keluar' AND DATE(t.waktu_keluar) BETWEEN '$dari_esc' AND '$sampai_esc'
    ORDER BY t.waktu_keluar DESC
    LIMIT 200
");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Rekap Transaksi - Parkir Stasiun Balapan Solo</title>
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
        .filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
        .form-group input { padding: 9px; border: 1px solid #ccc; border-radius: 6px; }
        .btn { padding: 9px 15px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-block; }
        .btn-primary { background: #ff7e5f; }
        .btn-info { background: #17a2b8; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .cards { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 25px; }
        .card-stat { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); flex: 1; min-width: 200px; }
        .card-stat .angka { font-size: 26px; font-weight: bold; color: #ff7e5f; }
        .card-stat .label { font-size: 13px; color: #777; margin-top: 5px; }
        .period-note { font-size: 13px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirAPP</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='rekap.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="card">
            <h3>Filter Periode Rekap</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>Dari Tanggal</label>
                    <input type="date" name="dari" value="<?= htmlspecialchars($dari); ?>">
                </div>
                <div class="form-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="sampai" value="<?= htmlspecialchars($sampai); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
            <div class="period-note">Menampilkan transaksi selesai (kendaraan keluar) dari <?= htmlspecialchars($dari); ?> sampai <?= htmlspecialchars($sampai); ?></div>
        </div>

        <div class="cards">
            <div class="card-stat">
                <div class="angka"><?= (int) $ringkasan['jumlah_transaksi']; ?></div>
                <div class="label">Total Transaksi Selesai</div>
            </div>
            <div class="card-stat">
                <div class="angka">Rp <?= number_format($ringkasan['total_pendapatan'], 0, ',', '.'); ?></div>
                <div class="label">Total Pendapatan</div>
            </div>
        </div>

        <div class="card">
            <h3>Rekap per Jenis Kendaraan</h3>
            <table>
                <thead><tr><th>Jenis Kendaraan</th><th>Jumlah Transaksi</th><th>Total Pendapatan</th></tr></thead>
                <tbody>
                    <?php $ada = false; while ($r = mysqli_fetch_assoc($per_jenis)): $ada = true; ?>
                    <tr>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td><?= (int) $r['jumlah']; ?></td>
                        <td>Rp <?= number_format($r['total'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                        <tr><td colspan="3" style="text-align:center;color:#888;">Tidak ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Detail Transaksi</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Plat Nomor</th><th>Jenis</th><th>Area</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Biaya</th><th>Petugas</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; $ada2=false; while($r = mysqli_fetch_assoc($detail)): $ada2=true; ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><b><?= strtoupper(htmlspecialchars($r['plat_nomor'])); ?></b></td>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td><?= htmlspecialchars($r['nama_area'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($r['waktu_masuk']); ?></td>
                        <td><?= htmlspecialchars($r['waktu_keluar']); ?></td>
                        <td><?= (int) $r['durasi_jam']; ?> jam</td>
                        <td>Rp <?= number_format($r['biaya_total'], 0, ',', '.'); ?></td>
                        <td><?= htmlspecialchars($r['nama_petugas'] ?? '-'); ?></td>
                        <td><a href="struk.php?id=<?= (int) $r['id_parkir']; ?>" target="_blank" class="btn btn-info">Cetak Struk</a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada2): ?>
                        <tr><td colspan="10" style="text-align:center;color:#888;">Tidak ada data pada periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>