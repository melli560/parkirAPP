<?php
require_once 'fungsi.php';
cek_role(['admin']);

$pesan_error = '';
$pesan_sukses = '';

// ================== Tambah / Edit User ==================
if (isset($_POST['simpan'])) {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];
    $role         = $_POST['role'];
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $id_user      = isset($_POST['id_user']) ? (int) $_POST['id_user'] : 0;

    $role_valid = ['admin', 'petugas', 'owner'];

    if ($nama_lengkap === '' || $username === '' || !in_array($role, $role_valid)) {
        $pesan_error = "Nama Lengkap, Username, dan Role wajib diisi dengan benar!";
    } elseif ($id_user === 0 && $password === '') {
        $pesan_error = "Password wajib diisi untuk user baru!";
    } else {
        $username_esc = mysqli_real_escape_string($koneksi, $username);

        // Cek username sudah dipakai user lain
        $q_cek = mysqli_query($koneksi, "SELECT id_user FROM tb_user WHERE username = '$username_esc' AND id_user != $id_user");
        if (mysqli_fetch_assoc($q_cek)) {
            $pesan_error = "Username '$username' sudah digunakan!";
        } else {
            $nama_esc = mysqli_real_escape_string($koneksi, $nama_lengkap);

            if ($id_user > 0) {
                // Mode edit
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE tb_user SET nama_lengkap='$nama_esc', username='$username_esc',
                              password='$hash', role='$role', status_aktif=$status_aktif WHERE id_user=$id_user";
                } else {
                    $query = "UPDATE tb_user SET nama_lengkap='$nama_esc', username='$username_esc',
                              role='$role', status_aktif=$status_aktif WHERE id_user=$id_user";
                }
                $aksi_log = "Mengubah data user: $username";
            } else {
                // Mode tambah
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif)
                          VALUES ('$nama_esc', '$username_esc', '$hash', '$role', $status_aktif)";
                $aksi_log = "Menambahkan user baru: $username";
            }

            if (mysqli_query($koneksi, $query)) {
                catat_log($koneksi, $_SESSION['user_id'], $aksi_log);
                header("Location: user.php");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
}

// ================== Hapus User ==================
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    if ($id === (int) $_SESSION['user_id']) {
        $pesan_error = "Anda tidak dapat menghapus akun anda sendiri!";
    } else {
        $q = mysqli_query($koneksi, "SELECT username FROM tb_user WHERE id_user = $id");
        $u = $q ? mysqli_fetch_assoc($q) : null;

        if (!$u) {
            $pesan_error = "Data user tidak ditemukan.";
        } else {
            // Cek apakah user ini masih punya riwayat data kendaraan/transaksi yang ia input.
            // Kalau ada, hapus akan gagal karena foreign key (RESTRICT) -- lebih baik dicegah
            // di sini dengan pesan yang jelas, daripada membiarkan Fatal error tampil ke user.
            $q_pakai = mysqli_query($koneksi, "SELECT
                (SELECT COUNT(*) FROM tb_kendaraan WHERE id_user = $id) +
                (SELECT COUNT(*) FROM tb_transaksi WHERE id_user = $id) AS jumlah");
            $pakai = mysqli_fetch_assoc($q_pakai);

            if ((int) $pakai['jumlah'] > 0) {
                $pesan_error = "User '{$u['username']}' tidak bisa dihapus karena sudah memiliki riwayat data kendaraan/transaksi. Nonaktifkan saja akunnya lewat tombol Edit (hilangkan centang \"Akun Aktif\").";
            } elseif (mysqli_query($koneksi, "DELETE FROM tb_user WHERE id_user = $id")) {
                catat_log($koneksi, $_SESSION['user_id'], "Menghapus user: " . $u['username']);
                header("Location: user.php");
                exit;
            } else {
                $pesan_error = "Gagal menghapus data: " . mysqli_error($koneksi);
            }
        }
    }
}

// ================== Ambil Data untuk Edit ==================
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $q  = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE id_user = $id");
    $edit_data = $q ? mysqli_fetch_assoc($q) : null;
}

// ================== Ambil Daftar User ==================
$daftar_user = mysqli_query($koneksi, "SELECT * FROM tb_user ORDER BY id_user ASC");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>CRUD User - ParkirApp</title>
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
        .form-check { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
        .btn { padding: 8px 15px; border: none; border-radius: 6px; color: #fff; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-block; }
        .btn-primary { background: #ff7e5f; }
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #000; font-size: 12px; }
        .btn-danger { background: #dc3545; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        .badge-role { background: #cce5ff; color: #004085; }
        .alert { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; }
        .hint { color: #888; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirApp</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='user.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit_data ? 'Edit User' : 'Tambah User' ?></h3>
            <form method="POST">
                <input type="hidden" name="id_user" value="<?= htmlspecialchars($edit_data['id_user'] ?? '') ?>">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($edit_data['nama_lengkap'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($edit_data['username'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password <?= $edit_data ? '(kosongkan jika tidak diubah)' : '' ?></label>
                    <input type="password" name="password" <?= $edit_data ? '' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <?php foreach (['admin','petugas','owner'] as $r): ?>
                            <option value="<?= $r ?>" <?= (($edit_data['role'] ?? '') === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="status_aktif" name="status_aktif" value="1" <?= (!$edit_data || $edit_data['status_aktif']) ? 'checked' : '' ?>>
                    <label for="status_aktif" style="margin:0;">Akun Aktif</label>
                </div>
                <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                <?php if ($edit_data): ?>
                    <a href="user.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>Daftar User</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $no=1; while($r = mysqli_fetch_assoc($daftar_user)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($r['nama_lengkap']); ?></td>
                        <td><?= htmlspecialchars($r['username']); ?></td>
                        <td><span class="badge badge-role"><?= ucfirst(htmlspecialchars($r['role'])); ?></span></td>
                        <td>
                            <?php if ($r['status_aktif']): ?>
                                <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="user.php?edit=<?= (int) $r['id_user']; ?>" class="btn btn-warning">Edit</a>
                            <?php if ((int) $r['id_user'] !== (int) $_SESSION['user_id']): ?>
                                <a href="user.php?hapus=<?= (int) $r['id_user']; ?>" class="btn btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>