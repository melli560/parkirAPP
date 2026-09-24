<?php
require_once 'fungsi.php';
cek_login();

catat_log($koneksi, $_SESSION['user_id'], "Logout dari sistem");

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
