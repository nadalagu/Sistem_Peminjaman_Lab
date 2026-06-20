<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * kirim_notif_h1.php
 * 
 * Script untuk mengirim email notifikasi H-1 pengembalian barang.
 * Jalankan via cron job setiap hari, misalnya pukul 08:00 pagi.
 * 
 * Cara setup cron job (Linux/cPanel):
 *   0 8 * * * php /path/to/project/kirim_notif_h1.php
 * 
 * Atau bisa dipanggil manual via browser (sebaiknya dilindungi token):
 *   http://localhost/lab-peminjaman/kirim_notif_h1.php?token=RAHASIA123
 */

// ===== KEAMANAN: Token agar tidak sembarang orang bisa akses via browser =====
define('CRON_TOKEN', 'RAHASIA123'); // Ganti dengan token acak yang kuat!

$is_cli    = (php_sapi_name() === 'cli');
$is_valid  = $is_cli || (isset($_GET['token']) && $_GET['token'] === CRON_TOKEN);

if (!$is_valid) {
    http_response_code(403);
    die('Akses ditolak.');
}

// ===== LOAD KONFIGURASI =====
// Sesuaikan path ini dengan lokasi project Anda
define('BASE_PATH', __DIR__ . '/');
define('BASE_URL',  'http://localhost/lab-peminjaman/'); // Ganti sesuai URL production

require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'config/functions.php';
require_once BASE_PATH . 'config/email.php';

// ===== LOGGING =====
$log_dir  = BASE_PATH . 'logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . 'notif_h1_' . date('Y-m') . '.log';

function logNotif($pesan) {
    global $log_file;
    $waktu = date('Y-m-d H:i:s');
    $baris = "[{$waktu}] {$pesan}" . PHP_EOL;
    file_put_contents($log_file, $baris, FILE_APPEND);
    if (php_sapi_name() === 'cli') {
        echo $baris;
    }
}

// ===== MULAI PROSES =====
logNotif("===== MULAI PROSES NOTIFIKASI H-1 =====");

// Ambil semua peminjaman yang H-1 & belum terkirim notifnya
$query = "
    SELECT 
        p.id            AS peminjaman_id,
        p.tgl_kembali_rencana,
        p.user_id,
        u.nama_lengkap  AS nama_mahasiswa,
        u.email         AS email_mahasiswa,
        b.nama_barang,
        b.kode_barang
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.status              = 'dipinjam'
      AND p.tgl_kembali_rencana = CURDATE() + INTERVAL 1 DAY
      AND p.notif_h1_terkirim   = 0
      AND u.email IS NOT NULL
      AND u.email != ''
";

$result = mysqli_query($conn, $query);

if (!$result) {
    logNotif("ERROR query: " . mysqli_error($conn));
    exit(1);
}

$total     = mysqli_num_rows($result);
$berhasil  = 0;
$gagal     = 0;

logNotif("Ditemukan {$total} peminjaman yang perlu dinotifikasi.");

if ($total === 0) {
    logNotif("Tidak ada notifikasi yang perlu dikirim hari ini.");
    logNotif("===== SELESAI =====");
    exit(0);
}

// ===== KIRIM EMAIL SATU PER SATU =====
while ($row = mysqli_fetch_assoc($result)) {
    $peminjaman_id   = (int)$row['peminjaman_id'];
    $email           = $row['email_mahasiswa'];
    $nama_mahasiswa  = $row['nama_mahasiswa'];
    $nama_barang     = $row['nama_barang'];
    $kode_barang     = $row['kode_barang'];
    $tgl_kembali     = $row['tgl_kembali_rencana'];

    logNotif("Mengirim ke: {$nama_mahasiswa} <{$email}> | Barang: {$nama_barang} ({$kode_barang})");

    // Kirim email
    $terkirim = kirimEmailH1($email, $nama_mahasiswa, $nama_barang, $kode_barang, $tgl_kembali);

    if ($terkirim) {
        // Tandai notif sudah terkirim di database
        $stmt = mysqli_prepare($conn,
            "UPDATE peminjaman SET notif_h1_terkirim = 1 WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $peminjaman_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        logNotif("  ✓ BERHASIL dikirim ke {$email}");
        $berhasil++;
    } else {
        logNotif("  ✗ GAGAL dikirim ke {$email} (cek konfigurasi mail server)");
        $gagal++;
    }

    // Jeda kecil agar tidak spam mail server
    usleep(200000); // 0.2 detik
}

// ===== RINGKASAN =====
logNotif("-------------------------------------------");
logNotif("Selesai. Berhasil: {$berhasil} | Gagal: {$gagal} | Total: {$total}");
logNotif("===== SELESAI =====");

mysqli_close($conn);