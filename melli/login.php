<?php
require_once 'fungsi.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$pesan_error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $pesan_error = "Username dan Password wajib diisi!";
    } else {
        $username_esc = mysqli_real_escape_string($koneksi, $username);
        $q = mysqli_query($koneksi, "SELECT * FROM tb_user WHERE username = '$username_esc' LIMIT 1");
        $u = $q ? mysqli_fetch_assoc($q) : null;

        if (!$u) {
            $pesan_error = "Username tidak ditemukan!";
        } elseif ((int) ($u['status_aktif'] ?? 0) !== 1) {
            $pesan_error = "Akun anda tidak aktif. Hubungi Admin.";
        } elseif (!password_verify($password, $u['password'] ?? '')) {
            $pesan_error = "Password salah!";
        } else {
            // Login berhasil
            $_SESSION['user_id']      = $u['id_user'];
            $_SESSION['nama_lengkap'] = $u['nama_lengkap'];
            $_SESSION['username']     = $u['username'];
            $_SESSION['role']         = $u['role'];

            catat_log($koneksi, $u['id_user'], "Login ke sistem");

            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><title>Login - ParkirApp</title>
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
        .hint { margin-top: 18px; font-size: 12px; color: #999; text-align: center; line-height: 1.6; }
        .back-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: 13px; color: #ff7e5f; text-decoration: none; font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
        .register-link {
            display: block; text-align: center; margin-top: 10px;
            font-size: 13px; color: #444;
        }
        .register-link a { color: #ff7e5f; text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>ParkirAPP</h2>
        <p class="sub">Sistem Informasi Parkir</p>

        <?php if ($pesan_error): ?>
            <div class="alert"><?= htmlspecialchars($pesan_error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Masuk</button>
        </form>

        <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>

        <div class="hint">
            Akun demo:<br>
            admin / admin &middot; petugas / petugas123 &middot; owner / owner123
        </div>

        <a href="index.php" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</body>
</html>