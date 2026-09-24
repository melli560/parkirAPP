<?php
require_once 'fungsi.php';
cek_role(['admin']);

$pesan_error = '';

// ================== Tambah / Edit Area ==================
if (isset($_POST['simpan'])) {
    $nama_area = trim($_POST['nama_area']);
    $kapasitas = isset($_POST['kapasitas']) ? (int) $_POST['kapasitas'] : 0;
    $id_area   = isset($_POST['id_area']) ? (int) $_POST['id_area'] : 0;

    if ($nama_area === '') {
        $pesan_error = "Nama Area wajib diisi!";
    } elseif ($kapasitas <= 0) {
        $pesan_error = "Kapasitas Maksimal harus lebih dari 0!";
    } else {
        $nama_area_esc = mysqli_real_escape_string($koneksi, $nama_area);

        if ($id_area > 0) {
            // Saat edit, kapasitas tidak boleh lebih kecil dari jumlah yang sedang terisi
            $q_now = mysqli_query($koneksi, "SELECT terisi FROM tb_area_parkir WHERE id_area = $id_area");
            $now = $q_now ? mysqli_fetch_assoc($q_now) : null;

            if ($now && $kapasitas < (int) $now['terisi']) {
                $pesan_error = "Kapasitas tidak boleh lebih kecil dari jumlah kendaraan yang sedang terisi (" . $now['terisi'] . ").";
            } else {
                $query = "UPDATE tb_area_parkir SET nama_area='$nama_area_esc', kapasitas=$kapasitas WHERE id_area=$id_area";
                $aksi_log = "Mengubah data area parkir: $nama_area";
            }
        } else {
            $query = "INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES ('$nama_area_esc', $kapasitas, 0)";
            $aksi_log = "Menambahkan area parkir baru: $nama_area";
        }

        if (!$pesan_error && mysqli_query($koneksi, $query)) {
            catat_log($koneksi, $_SESSION['user_id'], $aksi_log);
            header("Location: area.php");
            exit;
        } elseif (!$pesan_error) {
            $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// ================== Hapus Area ==================
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    $q_pakai = mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_transaksi WHERE id_area = $id");
    $pakai = mysqli_fetch_assoc($q_pakai);

    if ($pakai['c'] > 0) {
        $pesan_error = "Area ini tidak bisa dihapus karena sudah memiliki riwayat transaksi.";
    } else {
        mysqli_query($koneksi, "DELETE FROM tb_area_parkir WHERE id_area = $id");
        catat_log($koneksi, $_SESSION['user_id'], "Menghapus area parkir ID: $id");
        header("Location: area.php");
        exit;
    }
}

// ================== Ambil Data untuk Edit ==================
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $q  = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir WHERE id_area = $id");
    $edit_data = $q ? mysqli_fetch_assoc($q) : null;
}

// ================== Ambil Daftar Area ==================
$daftar_area = mysqli_query($koneksi, "SELECT * FROM tb_area_parkir ORDER BY id_area ASC");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Area Parkir - Parkir Stasuin Balapan Solo</title>
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
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        .btn { padding: 8px 15px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-block; }
        .btn-primary { background: #ff7e5f; }
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #000; font-size: 12px; }
        .btn-danger { background: #dc3545; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .alert { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .progress-wrap { background: #eee; border-radius: 6px; overflow: hidden; height: 8px; width: 100px; }
        .progress-bar { background: #ff7e5f; height: 100%; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirAPP</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='area.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_data ? 'Edit Area' : 'Tambah Area Parkir' ?></h3>
            <form method="POST">
                <input type="hidden" name="id_area" value="<?= htmlspecialchars($edit_data['id_area'] ?? '') ?>">
                <div class="form-group">
                    <label>Nama Area</label>
                    <input type="text" name="nama_area" value="<?= htmlspecialchars($edit_data['nama_area'] ?? '') ?>" placeholder="Contoh: Area A - Motor" required>
                </div>
                <div class="form-group">
                    <label>Kapasitas Maksimal</label>
                    <input type="number" name="kapasitas" min="1" step="1" value="<?= htmlspecialchars($edit_data['kapasitas'] ?? '') ?>" placeholder="Contoh: 50" required>
                </div>
                <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                <?php if ($edit_data): ?>
                    <a href="area.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>Daftar Area Parkir</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Nama Area</th><th>Kapasitas</th><th>Terisi</th><th>Sisa Slot</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($daftar_area)):
                        $kap = (int) $r['kapasitas']; $terisi = (int) $r['terisi'];
                        $persen = $kap > 0 ? round(($terisi / $kap) * 100) : 0;
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($r['nama_area']); ?></td>
                        <td><?= $kap; ?> Kendaraan</td>
                        <td>
                            <?= $terisi; ?>
                            <div class="progress-wrap"><div class="progress-bar" style="width:<?= min($persen,100) ?>%;"></div></div>
                        </td>
                        <td><?= max($kap - $terisi, 0); ?></td>
                        <td>
                            <a href="area.php?edit=<?= (int) $r['id_area']; ?>" class="btn btn-warning">Edit</a>
                            <a href="area.php?hapus=<?= (int) $r['id_area']; ?>" class="btn btn-danger" onclick="return confirm('Hapus area ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>