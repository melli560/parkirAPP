# parkirAPP
# 🅿️ ParkirApp - Sistem Informasi Parkir Digital

Aplikasi manajemen parkir untuk **Parkir Stasiun Balapan Solo**, dibangun dengan PHP native + MySQL (MySQLi) tanpa framework berat, sehingga ringan, mudah dipahami, dan cocok dikembangkan lebih lanjut.

Aplikasi ini mengelola seluruh alur operasional parkir stasiun: pencatatan kendaraan masuk & keluar, perhitungan tarif otomatis, cetak struk, rekap pendapatan, hingga **landing page publik** yang menampilkan info area parkir, tarif resmi, grafik pendapatan bulanan, dan ulasan dari pengguna.

**Live demo:** [https://parkirsolo.free.nf/login.php]
**Repository:** [github.com/melli560/parkirAPP](https://github.com/melli560/parkirAPP)

---

## 1. Struktur Folder

```
parkir/
├── assets/
│   └── img/
│       └── bg-parkir.jpg          (foto hero landing page)
├── koneksi.php                    (koneksi database MySQLi + session_start)
├── fungsi.php                     (cek_login, cek_role, catat_log, menu_sidebar)
├── index.php                      (landing page publik: area, tarif, grafik, ulasan)
├── login.php                      (halaman login seluruh role)
├── logout.php                     (proses logout + catat log)
├── dashboard.php                  (dashboard ringkas sesuai role yang login)
├── user.php                       (CRUD User — khusus admin)
├── tarif.php                      (CRUD Tarif Parkir — khusus admin)
├── area.php                       (CRUD Area Parkir — khusus admin)
├── kendaraan.php                  (CRUD Data Kendaraan — khusus admin)
├── log.php                        (Log Aktivitas seluruh user — khusus admin)
├── transaksi.php                  (Catat kendaraan masuk/keluar — khusus petugas)
├── riwayat_kendaraan.php          (Riwayat transaksi per kendaraan — admin & owner)
├── struk.php                      (Cetak struk parkir masuk/keluar — semua role)
├── rekap.php                      (Rekap transaksi & pendapatan periode — khusus owner)
└── db_parkir.sql                  (file SQL untuk import database)
```

> **Catatan:** aplikasi memakai struktur flat (satu file per fitur) dengan pembatasan akses per halaman lewat `cek_role([...])`, bukan folder terpisah per role — lebih sederhana untuk project skala menengah seperti sistem parkir stasiun ini.

---

## 2. Instalasi Lokal (XAMPP)

1. Copy folder `parkir` ke dalam `htdocs` (`C:\xampp\htdocs\parkir`).
2. Buka **phpMyAdmin**, buat database baru bernama **`parkir`**, lalu **Import** file `db_parkir.sql` (tabel & akun demo akan otomatis dibuat).
3. Buka `koneksi.php`, sesuaikan bila perlu:

```php
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "parkir";
```

4. Aktifkan Apache & MySQL lewat XAMPP Control Panel.
5. Akses aplikasi melalui `http://localhost/parkir/`.

---

## 3. Deploy ke Hosting (InfinityFree)

Project ini sudah live di **InfinityFree** dengan domain `parkirsolo.free.nf`. Ringkasan langkah deploy-nya:

1. Upload **seluruh isi** folder `parkir` (bukan foldernya) langsung ke folder `htdocs` lewat File Manager atau FTP InfinityFree.
2. Buat database MySQL baru lewat panel InfinityFree (nama database & user otomatis diberi prefix, misal `if0_xxxxxxx_parkir`), lalu import `db_parkir.sql` lewat phpMyAdmin bawaan InfinityFree.
3. Sesuaikan `koneksi.php` dengan kredensial yang diberikan InfinityFree:

```php
$host   = "sqlXXX.infinityfree.com";   // sesuai info panel
$user   = "if0_xxxxxxx";
$pass   = "••••••••";
$dbname = "if0_xxxxxxx_parkir";
```

4. Pastikan seluruh link relatif (`assets/img/...`, `login.php`, dsb.) tetap konsisten dengan struktur upload di root domain hosting.

---

## 4. Akun Default (Seeder)

| Role    | Username  | Password    | Keterangan                        |
| ------- | --------- | ----------- | ---------------------------------- |
| Admin   | `admin`   | `admin123`  | akses penuh ke seluruh master data |
| Petugas | `petugas` | `petugas123`| operasional transaksi harian       |
| Owner   | `owner`   | `owner123`  | monitoring rekap & pendapatan      |

Password tersimpan ter-hash (bcrypt) di tabel `tb_user`. Jika lupa password akun seeder, buat ulang hash-nya dengan `password_hash()` PHP lalu update kolom `password` pada tabel `tb_user` lewat phpMyAdmin. **Segera ganti password default** setelah deploy ke hosting publik.

---

## 5. Hak Akses Fitur

| Fitur                                  | Admin | Petugas | Owner |
| --------------------------------------- | :---: | :-----: | :---: |
| Login / Logout                          |   ✔   |    ✔    |   ✔   |
| Lihat Dashboard                         |   ✔   |    ✔    |   ✔   |
| CRUD User                               |   ✔   |         |       |
| CRUD Tarif Parkir                       |   ✔   |         |       |
| CRUD Area Parkir                        |   ✔   |         |       |
| CRUD Data Kendaraan                     |   ✔   |         |       |
| Akses Log Aktivitas                     |   ✔   |         |       |
| Catat Kendaraan Masuk                   |       |    ✔    |       |
| Catat Kendaraan Keluar                  |       |    ✔    |       |
| Cetak Struk                             |   ✔   |    ✔    |   ✔   |
| Riwayat Kendaraan                       |   ✔   |         |   ✔   |
| Rekap Transaksi & Pendapatan            |       |         |   ✔   |
| Lihat Landing Page (area, tarif, ulasan)| publik / semua pengunjung situs |   |   |
| Kirim Ulasan & Rating (landing page)    | publik / semua pengunjung situs |   |   |

---

## 6. Skema Database

File: `db_parkir.sql`. Tabel utama:

| Tabel              | Fungsi                                                                 |
| ------------------ | ------------------------------------------------------------------------ |
| `tb_user`           | Data akun (`role`: admin, petugas, owner), status aktif                 |
| `tb_kendaraan`      | Data kendaraan terdaftar (plat nomor, jenis, warna, pemilik)             |
| `tb_area_parkir`    | Area/zona parkir beserta kapasitas & jumlah slot terisi                  |
| `tb_tarif`          | Master tarif parkir per jam, per jenis kendaraan                         |
| `tb_transaksi`      | Transaksi parkir masuk/keluar, durasi & biaya total                      |
| `tb_log_aktivitas`  | Log aktivitas seluruh user (login, CRUD, dsb.)                           |
| `tb_ulasan`         | Ulasan & rating publik dari pengunjung landing page                      |

Relasi antar tabel dijaga dengan **FOREIGN KEY** (`tb_transaksi` ↔ `tb_kendaraan`/`tb_tarif`/`tb_area_parkir`/`tb_user`).

---

## 7. Alur Kerja Aplikasi

1. **Pengunjung publik** membuka `index.php` (landing page): melihat daftar area parkir yang tersedia, informasi tarif resmi, grafik pendapatan bulanan, video profil, serta bisa mengirim ulasan & rating.
2. **Petugas** login dan mencatat kendaraan masuk di `transaksi.php` — slot area berkurang otomatis & struk masuk tercetak lewat `struk.php`.
3. Saat kendaraan keluar, petugas memproses di halaman yang sama — sistem menghitung durasi & biaya parkir berdasarkan tarif per jam, lalu struk pembayaran final dicetak.
4. **Admin** mengelola seluruh master data (user, tarif, area, kendaraan) dan memantau log aktivitas seluruh pengguna lewat `log.php`.
5. **Owner** memantau dashboard pendapatan harian dan dapat melihat rekap transaksi pada rentang tanggal tertentu lewat `rekap.php`, lengkap dengan opsi cetak struk ulang.

---

Dibuat untuk kebutuhan Sistem Informasi Parkir **Stasiun Balapan Solo**, sebagai project pembelajaran pengembangan aplikasi berbasis PHP native + MySQL.
