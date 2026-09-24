<?php
require_once 'fungsi.php';
cek_role(['admin']);

$pesan_error = '';

// ================== Tambah / Edit Tarif ==================
if (isset($_POST['simpan'])) {
    $jenis_kendaraan = $_POST['jenis_kendaraan'];
    $tarif_per_jam   = isset($_POST['tarif_per_jam']) ? (int) $_POST['tarif_per_jam'] : 0;
    $id_tarif        = isset($_POST['id_tarif']) ? (int) $_POST['id_tarif'] : 0;

    $jenis_valid = ['motor', 'mobil', 'lainnya'];

    if (!in_array($jenis_kendaraan, $jenis_valid)) {
        $pesan_error = "Jenis Kendaraan tidak valid!";
    } elseif ($tarif_per_jam <= 0) {
        $pesan_error = "Tarif per Jam harus lebih dari 0!";
    } else {
        // Cek duplikat jenis kendaraan (selain baris yang sedang diedit)
        $q_cek = mysqli_query($koneksi, "SELECT id_tarif FROM tb_tarif WHERE jenis_kendaraan = '$jenis_kendaraan' AND id_tarif != $id_tarif");
        if (mysqli_fetch_assoc($q_cek)) {
            $pesan_error = "Tarif untuk jenis kendaraan '$jenis_kendaraan' sudah ada!";
        } else {
            if ($id_tarif > 0) {
                $query = "UPDATE tb_tarif SET jenis_kendaraan='$jenis_kendaraan', tarif_per_jam=$tarif_per_jam WHERE id_tarif=$id_tarif";
                $aksi_log = "Mengubah tarif $jenis_kendaraan";
            } else {
                $query = "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES ('$jenis_kendaraan', $tarif_per_jam)";
                $aksi_log = "Menambahkan tarif baru: $jenis_kendaraan";
            }

            if (mysqli_query($koneksi, $query)) {
                catat_log($koneksi, $_SESSION['user_id'], $aksi_log);
                header("Location: tarif.php");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}

// ================== Hapus Tarif ==================
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Cegah hapus tarif yang masih dipakai transaksi
    $q_pakai = mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE id_tarif = $id");
    $pakai = mysqli_fetch_assoc($q_pakai);

    if ($pakai['c'] > 0) {
        $pesan_error = "Tarif ini tidak bisa dihapus karena sudah dipakai pada data transaksi.";
    } else {
        mysqli_query($koneksi, "DELETE FROM tb_tarif WHERE id_tarif = $id");
        catat_log($koneksi, $_SESSION['user_id'], "Menghapus tarif ID: $id");
        header("Location: tarif.php");
        exit;
    }
}

// ================== Ambil Data untuk Edit ==================
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $q  = mysqli_query($koneksi, "SELECT * FROM tb_tarif WHERE id_tarif = $id");
    $edit_data = $q ? mysqli_fetch_assoc($q) : null;
}

// ================== Ambil Daftar Tarif ==================
$daftar_tarif = mysqli_query($koneksi, "SELECT * FROM tb_tarif ORDER BY jenis_kendaraan ASC");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Tarif Parkir - ParkirApp</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .alert { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirApp</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='tarif.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_data ? 'Edit Tarif' : 'Tambah Tarif Parkir' ?></h3>
            <form method="POST">
                <input type="hidden" name="id_tarif" value="<?= htmlspecialchars($edit_data['id_tarif'] ?? '') ?>">
                <div class="form-group">
                    <label>Jenis Kendaraan</label>
                    <select name="jenis_kendaraan" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php foreach (['motor','mobil','lainnya'] as $j): ?>
                            <option value="<?= $j ?>" <?= (($edit_data['jenis_kendaraan'] ?? '') === $j) ? 'selected' : '' ?>><?= ucfirst($j) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tarif per Jam (Rp)</label>
                    <input type="number" name="tarif_per_jam" min="1" step="1" value="<?= htmlspecialchars($edit_data['tarif_per_jam'] ?? '') ?>" placeholder="Contoh: 2000" required>
                </div>
                <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                <?php if ($edit_data): ?>
                    <a href="tarif.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>Daftar Tarif Parkir</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Jenis Kendaraan</th><th>Tarif per Jam</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($daftar_tarif)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= ucfirst(htmlspecialchars($r['jenis_kendaraan'])); ?></td>
                        <td>Rp <?= number_format($r['tarif_per_jam'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="tarif.php?edit=<?= (int) $r['id_tarif']; ?>" class="btn btn-warning">Edit</a>
                            <a href="tarif.php?hapus=<?= (int) $r['id_tarif']; ?>" class="btn btn-danger" onclick="return confirm('Hapus tarif ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>