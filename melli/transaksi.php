<?php
require_once 'fungsi.php';
cek_role(['petugas']);

$pesan_error = '';

// ================== Catat Kendaraan Masuk ==================
if (isset($_POST['masuk'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $id_area         = isset($_POST['id_area']) ? (int) $_POST['id_area'] : 0;

    if ($plat_nomor === '' || $jenis_kendaraan === '' || $id_area <= 0) {
        $pesan_error = "Plat Nomor, Jenis Kendaraan, dan Area Parkir wajib diisi!";
    } else {
        $plat_esc  = mysqli_real_escape_string($koneksi, $plat_nomor);
        $jenis_esc = mysqli_real_escape_string($koneksi, $jenis_kendaraan);

        // Cek kapasitas area masih tersedia
        $q_area = mysqli_query($koneksi, "SELECT kapasitas, terisi FROM tb_area_parkir WHERE id_area = $id_area");
        $area_row = $q_area ? mysqli_fetch_assoc($q_area) : null;

        // Cek kendaraan dengan plat yang sama belum sedang parkir (status masuk)
        $q_aktif = mysqli_query($koneksi, "
            SELECT t.id_parkir FROM tb_transaksi t
            JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
            WHERE k.plat_nomor = '$plat_esc' AND t.status = 'masuk' LIMIT 1
        ");

        // Cari id_tarif berdasarkan jenis kendaraan
        $q_tarif = mysqli_query($koneksi, "SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = '$jenis_esc' LIMIT 1");
        $tarif   = $q_tarif ? mysqli_fetch_assoc($q_tarif) : null;

        if (!$area_row) {
            $pesan_error = "Area parkir tidak ditemukan.";
        } elseif ((int) $area_row['terisi'] >= (int) $area_row['kapasitas']) {
            $pesan_error = "Area parkir ini sudah penuh, pilih area lain.";
        } elseif (mysqli_fetch_assoc($q_aktif)) {
            $pesan_error = "Kendaraan dengan plat '$plat_nomor' masih tercatat sedang parkir.";
        } elseif (!$tarif) {
            $pesan_error = "Tarif untuk jenis kendaraan '$jenis_kendaraan' belum diatur. Hubungi Admin.";
        } else {
            $id_tarif = (int) $tarif['id_tarif'];
            $id_user  = (int) $_SESSION['user_id'];

            // Cari data kendaraan yang sudah pernah terdaftar, kalau belum ada buat baru
            $q_kendaraan = mysqli_query($koneksi, "SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = '$plat_esc' LIMIT 1");
            $kendaraan_row = $q_kendaraan ? mysqli_fetch_assoc($q_kendaraan) : null;

            if ($kendaraan_row) {
                $id_kendaraan = (int) $kendaraan_row['id_kendaraan'];
            } else {
                mysqli_query($koneksi, "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, id_user)
                    VALUES ('$plat_esc', '$jenis_esc', $id_user)");
                $id_kendaraan = mysqli_insert_id($koneksi);
            }

            $insert_transaksi = mysqli_query($koneksi, "INSERT INTO tb_transaksi
                (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area)
                VALUES ($id_kendaraan, NOW(), $id_tarif, 'masuk', $id_user, $id_area)");

            if ($insert_transaksi) {
                mysqli_query($koneksi, "UPDATE tb_area_parkir SET terisi = terisi + 1 WHERE id_area = $id_area");
                $id_parkir_baru = mysqli_insert_id($koneksi);
                catat_log($koneksi, $id_user, "Kendaraan masuk: $plat_nomor ($jenis_kendaraan)");
                header("Location: karcis.php?id=" . $id_parkir_baru);
                exit;
            } else {
                $pesan_error = "Gagal menyimpan transaksi: " . mysqli_error($koneksi);
            }
        }
    }
}

// ================== Set Keluar Kendaraan ==================
if (isset($_GET['keluar'])) {
    $id_parkir = (int) $_GET['keluar'];

    $q = mysqli_query($koneksi, "SELECT t.waktu_masuk, t.id_area, tr.tarif_per_jam
        FROM tb_transaksi t
        JOIN tb_tarif tr ON t.id_tarif = tr.id_tarif
        WHERE t.id_parkir = $id_parkir AND t.status = 'masuk'");
    $trx = $q ? mysqli_fetch_assoc($q) : null;

    if ($trx) {
        $durasi_jam = (int) ceil((strtotime('now') - strtotime($trx['waktu_masuk'])) / 3600);
        if ($durasi_jam < 1) $durasi_jam = 1;
        $biaya_total = $durasi_jam * (float) $trx['tarif_per_jam'];

        mysqli_query($koneksi, "UPDATE tb_transaksi
            SET waktu_keluar = NOW(), durasi_jam = $durasi_jam, biaya_total = $biaya_total, status = 'keluar'
            WHERE id_parkir = $id_parkir");

        mysqli_query($koneksi, "UPDATE tb_area_parkir SET terisi = GREATEST(terisi - 1, 0) WHERE id_area = " . (int) $trx['id_area']);

        catat_log($koneksi, $_SESSION['user_id'], "Kendaraan keluar, transaksi ID: $id_parkir");
    }

    header("Location: struk.php?id=" . $id_parkir);
    exit;
}

// ================== Cetak Struk ==================
// Halaman struk kini ditangani bersama oleh struk.php (dipakai juga oleh admin & owner)
if (isset($_GET['struk'])) {
    header("Location: struk.php?id=" . (int) $_GET['struk']);
    exit;
}

// Ambil daftar jenis kendaraan dari tabel Tarif
$jenis_list = mysqli_query($koneksi, "SELECT DISTINCT jenis_kendaraan FROM tb_tarif ORDER BY jenis_kendaraan");

// Ambil daftar area parkir yang masih ada slot kosong
$area_list = mysqli_query($koneksi, "SELECT id_area, nama_area, kapasitas, terisi FROM tb_area_parkir ORDER BY nama_area");

// Ambil transaksi yang sedang berjalan (status masuk) - limit agar query tetap ringan
$sedang_parkir = mysqli_query($koneksi, "
    SELECT t.id_parkir, t.waktu_masuk, k.plat_nomor, k.jenis_kendaraan, a.nama_area
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    LEFT JOIN tb_area_parkir a ON t.id_area = a.id_area
    WHERE t.status = 'masuk'
    ORDER BY t.waktu_masuk DESC
    LIMIT 100
");

// Riwayat transaksi selesai hari ini
$riwayat_hari_ini = mysqli_query($koneksi, "
    SELECT t.id_parkir, t.waktu_masuk, t.waktu_keluar, t.biaya_total, k.plat_nomor, k.jenis_kendaraan
    FROM tb_transaksi t
    JOIN tb_kendaraan k ON t.id_kendaraan = k.id_kendaraan
    WHERE t.status = 'keluar' AND DATE(t.waktu_keluar) = CURDATE()
    ORDER BY t.waktu_keluar DESC
    LIMIT 50
");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Transaksi Parkir - ParkirApp</title>
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
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        .btn { padding: 8px 15px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-block; }
        .btn-primary { background: #ff7e5f; }
        .btn-danger { background: #dc3545; font-size: 12px; }
        .btn-info { background: #17a2b8; font-size: 12px; }
        .btn-success { background: #28a745; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .alert { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .hint { color: #888; font-size: 12px; margin-top: 5px; }
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
        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['scan_gagal'])): ?>
            <div class="alert">Kode barcode tidak ditemukan, atau kendaraan tersebut sudah keluar sebelumnya.</div>
        <?php endif; ?>

        <div class="card">
            <h3>📷 Scan Barcode Kendaraan Keluar</h3>
            <form method="GET" action="bayar_scan.php" style="display:flex; gap:10px;">
                <input type="text" name="kode" placeholder="Scan atau ketik kode barcode di karcis..." autofocus autocomplete="off" style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>
            <p class="hint">Arahkan barcode scanner ke kode di karcis parkir, atau ketik manual nomor karcisnya lalu Enter.</p>
        </div>

        <div class="card">
            <h3>Catat Kendaraan Masuk</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Plat Nomor</label>
                    <input type="text" name="plat_nomor" placeholder="Contoh: B 1234 CD" required style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Jenis Kendaraan</label>
                    <?php if ($jenis_list && mysqli_num_rows($jenis_list) > 0): ?>
                        <select name="jenis_kendaraan" required>
                            <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                                <option value="<?= htmlspecialchars($j['jenis_kendaraan']); ?>"><?= ucfirst(htmlspecialchars($j['jenis_kendaraan'])); ?></option>
                            <?php endwhile; ?>
                        </select>
                    <?php else: ?>
                        <select disabled><option>Belum ada data tarif, hubungi Admin</option></select>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Area Parkir</label>
                    <?php if ($area_list && mysqli_num_rows($area_list) > 0): ?>
                        <select name="id_area" required>
                            <?php mysqli_data_seek($area_list, 0); while ($a = mysqli_fetch_assoc($area_list)):
                                $penuh = (int) $a['terisi'] >= (int) $a['kapasitas'];
                            ?>
                                <option value="<?= $a['id_area']; ?>" <?= $penuh ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($a['nama_area']); ?> (<?= $a['terisi']; ?>/<?= $a['kapasitas']; ?>)<?= $penuh ? ' - PENUH' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    <?php else: ?>
                        <select disabled><option>Belum ada data area, hubungi Admin</option></select>
                    <?php endif; ?>
                </div>
                <button type="submit" name="masuk" class="btn btn-primary"
                    <?= (!$jenis_list || mysqli_num_rows($jenis_list) === 0 || !$area_list || mysqli_num_rows($area_list) === 0) ? 'disabled' : '' ?>>
                    Simpan Kendaraan Masuk
                </button>
            </form>
        </div>

        <div class="card">
            <h3>Kendaraan Sedang Parkir</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Plat Nomor</th><th>Jenis</th><th>Area</th><th>Jam Masuk</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($sedang_parkir)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><b><?= strtoupper(htmlspecialchars($r['plat_nomor'])); ?></b></td>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td><?= htmlspecialchars($r['nama_area'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($r['waktu_masuk']); ?></td>
                        <td>
                            <a href="karcis.php?id=<?= (int) $r['id_parkir']; ?>" target="_blank" class="btn btn-success">Cetak Karcis</a>
                            <a href="struk.php?id=<?= (int) $r['id_parkir']; ?>" target="_blank" class="btn btn-info">Cetak Struk</a>
                            <a href="transaksi.php?keluar=<?= (int) $r['id_parkir']; ?>" class="btn btn-danger" onclick="return confirm('Kendaraan ini akan keluar?')">Set Keluar</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($sedang_parkir) === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:#888;">Tidak ada kendaraan yang sedang parkir.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Riwayat Transaksi Selesai Hari Ini</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Plat Nomor</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Biaya</th><th>Struk</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($riwayat_hari_ini)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><b><?= strtoupper(htmlspecialchars($r['plat_nomor'])); ?></b></td>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td><?= htmlspecialchars($r['waktu_masuk']); ?></td>
                        <td><?= htmlspecialchars($r['waktu_keluar']); ?></td>
                        <td>Rp <?= number_format($r['biaya_total'], 0, ',', '.'); ?></td>
                        <td><a href="struk.php?id=<?= (int) $r['id_parkir']; ?>" target="_blank" class="btn btn-info">Cetak Ulang</a></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($riwayat_hari_ini) === 0): ?>
                        <tr><td colspan="7" style="text-align:center;color:#888;">Belum ada transaksi selesai hari ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>