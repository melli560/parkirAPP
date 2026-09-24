<?php
require_once 'fungsi.php';
cek_role(['admin']);

// Pagination sederhana agar query tetap efisien untuk data besar
$per_halaman = 25;
$halaman     = isset($_GET['halaman']) ? max(1, (int) $_GET['halaman']) : 1;
$offset      = ($halaman - 1) * $per_halaman;

$total_row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM tb_log_aktivitas"));
$total_data = (int) $total_row['c'];
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));

$daftar_log = mysqli_query($koneksi, "
    SELECT l.*, u.nama_lengkap, u.username, u.role
    FROM tb_log_aktivitas l
    JOIN tb_user u ON l.id_user = u.id_user
    ORDER BY l.waktu_aktivitas DESC
    LIMIT $per_halaman OFFSET $offset
");

$menu = menu_sidebar($_SESSION['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Log Aktivitas - ParkirApp</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; font-size: 14px; }
        table th { background: #ff7e5f; color: #fff; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #cce5ff; color: #004085; }
        .pagination { margin-top: 20px; display: flex; gap: 6px; }
        .pagination a, .pagination span {
            padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px;
            background: #eee; color: #333;
        }
        .pagination a.active, .pagination span.active { background: #ff7e5f; color: #fff; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ParkirApp</div>
        <ul class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <li><a href="<?= $m['href']; ?>" class="<?= $m['href']==='log.php'?'active':''; ?>"><?= $m['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="card">
            <h3>Log Aktivitas Pengguna</h3>
            <table>
                <thead>
                    <tr><th>No</th><th>Waktu</th><th>Nama User</th><th>Role</th><th>Aktivitas</th></tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while($r = mysqli_fetch_assoc($daftar_log)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($r['waktu_aktivitas']); ?></td>
                        <td><?= htmlspecialchars($r['nama_lengkap']); ?> (<?= htmlspecialchars($r['username']); ?>)</td>
                        <td><span class="badge"><?= ucfirst(htmlspecialchars($r['role'])); ?></span></td>
                        <td><?= htmlspecialchars($r['aktivitas']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($total_data === 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">Belum ada aktivitas tercatat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_halaman > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                    <a href="log.php?halaman=<?= $i ?>" class="<?= $i === $halaman ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
