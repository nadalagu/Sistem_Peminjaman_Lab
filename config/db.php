<?php
// ============================================================
//  config/db.php
//  Konfigurasi & Koneksi Database
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Ganti sesuai user MySQL Anda
define('DB_PASS', '');           // Ganti sesuai password MySQL Anda
define('DB_NAME', 'db_lab_mesin');

// Buat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die('<div style="font-family:sans-serif;padding:20px;color:red;">
        <strong>Koneksi database gagal!</strong><br>
        Error: ' . mysqli_connect_error() . '
    </div>');
}

// Set charset agar karakter Indonesia tampil benar
mysqli_set_charset($conn, 'utf8mb4');