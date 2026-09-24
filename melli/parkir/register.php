<?php
require_once 'fungsi.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$pesan_error   = '';
$pesan_sukses  = '';

if (isset($_POST['register'])) {
    $nama_lengkap     = trim($_POST['nama_lengkap']);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $konfirmasi_pass  = $_POST['konfirmasi_password'];

    if ($nama_lengkap === '' || $username === '' || $password === '' || $konfirmasi_pass === '') {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif (strlen($username) < 4) {
        $pesan_error = "Username minimal 4 karakter!";
    } elseif (strlen($password) < 6) {
        $pesan_error = "Password minimal 6 karakter!";
    } elseif ($password !== $konfirmasi_pass) {
        $pesan_error = "Konfirmasi password tidak sama!";
    } else {
        $username_esc = mysqli_real_escape_string($koneksi, $username);
        $cek = mysqli_query($koneksi, "SELECT id_user FROM tb_user WHERE username = '$username_esc' LIMIT 1");

        if ($cek && mysqli_num_rows($cek) > 0) {
            $pesan_error = "Username sudah digunakan, silakan pilih username lain!";
        } else {
            $nama_esc     = mysqli_real_escape_string($koneksi, $nama_lengkap);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role          = 'petugas'; // role default untuk pendaftar baru
            $status_aktif  = 0;         // menunggu aktivasi oleh admin

            $insert = mysqli_query($koneksi, "
                INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif)
                VALUES ('$nama_esc', '$username_esc', '$password_hash', '$role', $status_aktif)
            ");

            if ($insert) {
                $pesan_sukses = "Pendaftaran berhasil! Akun anda menunggu aktivasi oleh Admin sebelum dapat digunakan untuk login.";
            } else {
                $pesan_error = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Registrasi - ParkirApp</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background:
                linear-gradient(135deg, rgba(255,126,95,0.75), rgba(254,180,123,0.75)),
                url('assets/bg.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            padding: 20px 0;
        }
        .login-box {
            background: #fff; padding: 40px 35px; border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15); width: 100%; max-width: 360px;
        }
        .login-box h2 { text-align: center; margin-bottom: 5px; color: #333; }
        .login-box p.sub { text-align: center; color: #888; font-size: 13px; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #444; }
        .form-group input {
            width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;
        }
        .btn-login {
            width: 100%; padding: 11px; border: none; border-radius: 6px; background: #ff7e5f;
            color: #fff; font-weight: bold; font-size: 15px; cursor: pointer;
        }
        .btn-login:hover { background: #ff6a45; }
        .alert { padding: 10px 12px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .alert-sukses { padding: 10px 12px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .hint { margin-top: 18px; font-size: 12px; color: #999; text-align: center; line-height: 1.6; }
        .back-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: 13px; color: #ff7e5f; text-decoration: none; font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
        .login-link {
            display: block; text-align: center; margin-top: 10px;
            font-size: 13px; color: #444;
        }
        .login-link a { color: #ff7e5f; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>ParkirAPP</h2>
        <p class="sub">Daftar Akun Baru</p>

        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <?php if ($pesan_sukses): ?>
            <div class="alert-sukses"><?= htmlspecialchars($pesan_sukses); ?></div>
        <?php endif; ?>

        <?php if (!$pesan_sukses): ?>
        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="konfirmasi_password" required>
            </div>
            <button type="submit" name="register" class="btn-login">Daftar</button>
        </form>
        <?php endif; ?>

        <p class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>

        <a href="index.php" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</body>
</html>