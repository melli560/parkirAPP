<?php
require_once 'fungsi.php';
cek_role(['admin']);

$pesan_error = '';

// ================== Tambah / Edit Kendaraan ==================
if (isset($_POST['simpan'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan']);
    $warna           = trim($_POST['warna'] ?? '');
    $pemilik         = trim($_POST['pemilik'] ?? '');
    $id_kendaraan    = isset($_POST['id_kendaraan']) ? (int) $_POST['id_kendaraan'] : 0;

    if ($plat_nomor === '' || $jenis_kendaraan === '') {
        $pesan_error = "Plat Nomor dan Jenis Kendaraan wajib diisi!";
    } else {
        $plat_esc   = mysqli_real_escape_string($koneksi, $plat_nomor);
        $jenis_esc  = mysqli_real_escape_string($koneksi, $jenis_kendaraan);
        $warna_esc  = mysqli_real_escape_string($koneksi, $warna);
        $pemilik_esc = mysqli_real_escape_string($koneksi, $pemilik);
        $id_user    = (int) $_SESSION['user_id'];

        if ($id_kendaraan > 0) {
            $query = "UPDATE tb_kendaraan SET plat_nomor='$plat_esc', jenis_kendaraan='$jenis_esc',
                      warna='$warna_esc', pemilik='$pemilik_esc' WHERE id_kendaraan=$id_kendaraan";
            $aksi_log = "Mengubah data kendaraan: $plat_nomor";
        } else {
            $query = "INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user)
                      VALUES ('$plat_esc', '$jenis_esc', '$warna_esc', '$pemilik_esc', $id_user)";
            $aksi_log = "Menambahkan data kendaraan baru: $plat_nomor";
        }

        if (mysqli_query($koneksi, $query)) {
            catat_log($koneksi, $_SESSION['user_id'], $aksi_log);
            header("Location: kendaraan.php");
            exit;
        } else {
            $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// ================== Hapus Kendaraan ==================
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $q_pakai = mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE id_kendaraan = $id");
    $pakai = mysqli_fetch_assoc($q_pakai);

    if ($pakai['c'] > 0) {
        $pesan_error = "Kendaraan ini tidak bisa dihapus karena sudah memiliki riwayat transaksi.";
    } else {
        mysqli_query($koneksi, "DELETE FROM tb_kendaraan WHERE id_kendaraan = $id");
        catat_log($koneksi, $_SESSION['user_id'], "Menghapus data kendaraan ID: $id");
        header("Location: kendaraan.php");
        exit;
    }
}

// ================== Ambil Data untuk Edit ==================
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $q  = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan WHERE id_kendaraan = $id");
    $edit_data = $q ? mysqli_fetch_assoc($q) : null;
}

// ================== Ambil Daftar Kendaraan ==================
$daftar_kendaraan = mysqli_query($koneksi, "SELECT * FROM tb_kendaraan ORDER BY id_kendaraan DESC");

// Ambil jenis kendaraan dari tb_tarif agar konsisten dengan tarif yang tersedia
$jenis_list = mysqli_query($koneksi, "SELECT DISTINCT jenis_kendaraan FROM tb_tarif ORDER BY jenis_kendaraan");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>CRUD Kendaraan - ParkirAPP</title>
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
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #000; font-size: 12px; }
        .btn-danger { background: #dc3545; font-size: 12px; }
        .btn-info { background: #17a2b8; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .alert { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .hint { color: #888; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirAPP</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='kendaraan.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_data ? 'Edit Kendaraan' : 'Tambah Data Kendaraan' ?></h3>
            <form method="POST">
                <input type="hidden" name="id_kendaraan" value="<?= htmlspecialchars($edit_data['id_kendaraan'] ?? '') ?>">
                <div class="form-group">
                    <label>Plat Nomor</label>
                    <input type="text" name="plat_nomor" value="<?= htmlspecialchars($edit_data['plat_nomor'] ?? '') ?>" placeholder="Contoh: B 1234 CD" required style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Jenis Kendaraan</label>
                    <?php if ($jenis_list && mysqli_num_rows($jenis_list) > 0): ?>
                        <select name="jenis_kendaraan" required>
                            <?php mysqli_data_seek($jenis_list, 0); while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                                <option value="<?= htmlspecialchars($j['jenis_kendaraan']); ?>" <?= (($edit_data['jenis_kendaraan'] ?? '') === $j['jenis_kendaraan']) ? 'selected' : '' ?>><?= ucfirst(htmlspecialchars($j['jenis_kendaraan'])); ?></option>
                            <?php endwhile; ?>
                        </select>
                    <?php else: ?>
                        <select disabled><option>Belum ada data, isi dulu di menu Tarif Parkir</option></select>
                        <p class="hint">Tambahkan jenis kendaraan lewat menu <a href="tarif.php">CRUD Tarif Parkir</a> dulu.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Warna</label>
                    <input type="text" name="warna" value="<?= htmlspecialchars($edit_data['warna'] ?? '') ?>" placeholder="Contoh: Hitam">
                </div>
                <div class="form-group">
                    <label>Nama Pemilik</label>
                    <input type="text" name="pemilik" value="<?= htmlspecialchars($edit_data['pemilik'] ?? '') ?>" placeholder="Contoh: Budi Santoso">
                </div>
                <button type="submit" name="simpan" class="btn btn-primary"
                    <?= (!$jenis_list || mysqli_num_rows($jenis_list) === 0) ? 'disabled' : '' ?>>
                    Simpan
                </button>
                <?php if ($edit_data): ?>
                    <a href="kendaraan.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>Daftar Kendaraan Terdaftar</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Plat Nomor</th><th>Jenis</th><th>Warna</th><th>Pemilik</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($daftar_kendaraan)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><b><?= strtoupper(htmlspecialchars($r['plat_nomor'])); ?></b></td>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td><?= htmlspecialchars($r['warna'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($r['pemilik'] ?? '-'); ?></td>
                        <td>
                            <a href="riwayat_kendaraan.php?id=<?= (int) $r['id_kendaraan']; ?>" class="btn btn-info">Riwayat &amp; Struk</a>
                            <a href="kendaraan.php?edit=<?= (int) $r['id_kendaraan']; ?>" class="btn btn-warning">Edit</a>
                            <a href="kendaraan.php?hapus=<?= (int) $r['id_kendaraan']; ?>" class="btn btn-danger" onclick="return confirm('Hapus data kendaraan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>