<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';
requireMahasiswa();

// Notifikasi sekarang permanen dan tidak dihapus setelah dibaca.
// Endpoint ini disimpan untuk kompatibilitas, tapi tidak lagi melakukan update apapun.
// Notifikasi otomatis hilang hanya ketika status peminjaman berubah menjadi selesai/dibatalkan.

echo json_encode(['ok' => true]);