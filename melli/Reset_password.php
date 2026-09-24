<?php
// ===============================================
// SKRIP SEKALI PAKAI - Generate hash password
// Cara pakai:
// 1. Simpan file ini di C:\xampp\htdocs\parkir\reset_password.php
// 2. Buka di browser: http://localhost/parkir/reset_password.php
// 3. Copy hasil query UPDATE yang muncul, jalankan di phpMyAdmin > tab SQL
// 4. SETELAH SELESAI, HAPUS FILE INI dari server (jangan dibiarkan online)
// ===============================================

$akun = [
    'admin'   => 'admin123',
    'petugas' => 'petugas123',
    'owner'   => 'owner123',
];

echo "<h3>Jalankan query berikut satu per satu di phpMyAdmin (tab SQL):</h3><pre>";
foreach ($akun as $username => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "UPDATE tb_user SET password='$hash' WHERE username='$username';\n\n";
}
echo "</pre>";
echo "<p style='color:red;font-weight:bold;'>Setelah dijalankan, HAPUS file reset_password.php ini dari server!</p>";