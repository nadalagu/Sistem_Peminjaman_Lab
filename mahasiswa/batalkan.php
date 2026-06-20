<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();

$user_id = $_SESSION['user_id'];

// Hanya terima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: riwayat.php');
    exit;
}

$peminjaman_id = isset($_POST['peminjaman_id']) ? (int) $_POST['peminjaman_id'] : 0;

if ($peminjaman_id <= 0) {
    header('Location: riwayat.php?error=invalid');
    exit;
}

// Pastikan peminjaman milik user ini dan statusnya masih 'menunggu'
$stmt = mysqli_prepare($conn,
    "SELECT id, status FROM peminjaman
     WHERE id = ? AND user_id = ? AND status = 'menunggu'
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'ii', $peminjaman_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pinjam = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pinjam) {
    // Tidak ditemukan / bukan miliknya / status sudah berubah
    header('Location: riwayat.php?error=notfound');
    exit;
}

// Update status menjadi 'dibatalkan'
$stmt2 = mysqli_prepare($conn,
    "UPDATE peminjaman SET status = 'dibatalkan' WHERE id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($stmt2, 'ii', $peminjaman_id, $user_id);
$ok = mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

if ($ok) {
    header('Location: riwayat.php?success=dibatalkan');
} else {
    header('Location: riwayat.php?error=gagal');
}
exit;