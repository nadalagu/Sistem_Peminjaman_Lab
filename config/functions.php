<?php
// ============================================================
//  config/functions.php
// ============================================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'mahasiswa/dashboard.php');
        exit;
    }
}

function requireMahasiswa() {
    requireLogin();
    if ($_SESSION['role'] !== 'mahasiswa') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    }
    // Cek apakah akun terkunci karena denda
    cekAkunTerkunci();
}

/**
 * Cek apakah akun mahasiswa terkunci.
 * Jika terkunci, redirect ke halaman khusus "akun terkunci".
 * Kecuali jika sudah berada di halaman akun_terkunci.php itu sendiri.
 */
function cekAkunTerkunci() {
    global $conn;
    if (!isset($_SESSION['user_id'])) return;

    // Hindari redirect loop
    $current = basename($_SERVER['PHP_SELF']);
    if ($current === 'akun_terkunci.php') return;

    $stmt = mysqli_prepare($conn, "SELECT akun_terkunci, alasan_kunci FROM users WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($user && $user['akun_terkunci'] == 1) {
        header('Location: ' . BASE_URL . 'mahasiswa/akun_terkunci.php');
        exit;
    }
}

function loginUser($username, $password) {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "SELECT id, username, nama_lengkap, role, password, akun_terkunci FROM users WHERE username = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if ($user && password_verify($password, $user['password'])) return $user;
    return false;
}

function bersihkan($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $ts = strtotime($tanggal);
    if ($ts === false) return '-';
    return date('d/m/Y', $ts);
}

function formatRupiah($angka) {
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

function badgeStatus($status) {
    $map = [
        'menunggu'     => ['background:#fef3c7;color:#92400e;',  'Menunggu'],
        'disetujui'    => ['background:#cffafe;color:#0e7490;',  'Disetujui'],
        'ditolak'      => ['background:#fee2e2;color:#991b1b;',  'Ditolak'],
        'dipinjam'     => ['background:#e0e7ff;color:#3730a3;',  'Dipinjam'],
        'dikembalikan' => ['background:#d1fae5;color:#065f46;',  'Dikembalikan'],
        'dibatalkan'   => ['background:#f1f5f9;color:#475569;',  'Dibatalkan'],
    ];
    $b = $map[$status] ?? ['background:#f1f5f9;color:#475569;', ucfirst($status)];
    return "<span style='display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;{$b[0]}'>{$b[1]}</span>";
}

function badgeKondisi($kondisi) {
    $map = [
        'baik'         => ['background:#d1fae5;color:#065f46;', 'Baik'],
        'rusak ringan' => ['background:#fef3c7;color:#92400e;', 'Rusak Ringan'],
        'rusak berat'  => ['background:#fee2e2;color:#991b1b;', 'Rusak Berat'],
    ];
    $b = $map[$kondisi] ?? ['background:#f1f5f9;color:#475569;', ucfirst($kondisi)];
    return "<span style='display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;{$b[0]}'>{$b[1]}</span>";
}

/**
 * Hitung denda keterlambatan.
 * Denda = Rp10.000 x jumlah hari terlambat (hari penuh).
 * Jika tgl_kembali_aktual diisi, pakai itu; jika tidak, pakai hari ini.
 *
 * @param string $tgl_kembali_rencana  Format Y-m-d
 * @param string|null $tgl_kembali_aktual Format Y-m-d atau null
 * @param int $denda_per_hari Rp per hari (default 10000)
 * @return array ['hari_terlambat' => int, 'total_denda' => int]
 */
function hitungDenda($tgl_kembali_rencana, $tgl_kembali_aktual = null, $denda_per_hari = 10000) {
    $tgl_rencana = new DateTime($tgl_kembali_rencana);
    $tgl_aktual  = $tgl_kembali_aktual ? new DateTime($tgl_kembali_aktual) : new DateTime('today');

    // Hitung selisih hari (positif = terlambat)
    $diff = (int)$tgl_rencana->diff($tgl_aktual)->format('%r%a');

    if ($diff <= 0) {
        return ['hari_terlambat' => 0, 'total_denda' => 0];
    }

    return [
        'hari_terlambat' => $diff,
        'total_denda'    => $diff * $denda_per_hari,
    ];
}


/**
 * Tandai notifikasi sebagai sudah dibaca.
 */
function tandaiNotifDibaca($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "UPDATE peminjaman SET notif_dibaca = 1 WHERE user_id = ? AND notif_dibaca = 0 AND status IN ('dipinjam','ditolak')"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Cek dan update total_denda untuk semua peminjaman yang terlambat (status dipinjam).
 * Jalankan saat admin membuka halaman atau saat mahasiswa login.
 */
function updateDendaOtomatis() {
    global $conn;
    $result = mysqli_query($conn,
        "SELECT id, tgl_kembali_rencana, denda_per_hari FROM peminjaman
         WHERE status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()"
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $d = hitungDenda($row['tgl_kembali_rencana'], null, $row['denda_per_hari']);
        if ($d['total_denda'] > 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE peminjaman SET total_denda = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, 'ii', $d['total_denda'], $row['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Kunci akun mahasiswa jika ada denda yang belum dibayar.
 * Dijalankan setelah barang dikembalikan (status = 'dikembalikan').
 * 
 * @param int $user_id ID mahasiswa
 * @param int $peminjaman_id ID peminjaman yang dikembalikan (untuk log/referensi)
 * @return bool true jika akun dikunci, false jika tidak ada denda
 */
function kunciAkunJikaDenda($user_id, $peminjaman_id = 0) {
    global $conn;
    
    // Cek total denda yang belum dibayar
    $stmt = mysqli_prepare($conn, 
        "SELECT SUM(total_denda) AS total_denda 
         FROM peminjaman 
         WHERE user_id = ? AND total_denda > 0 AND denda_dibayar = 0"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    
    $total_denda = (int)($result['total_denda'] ?? 0);
    
    if ($total_denda > 0) {
        // Ada denda, kunci akun
        $alasan = "Terdapat denda keterlambatan pengembalian barang sebesar " . formatRupiah($total_denda) . " yang belum dibayar.";
        $stmt = mysqli_prepare($conn,
            "UPDATE users SET akun_terkunci = 1, alasan_kunci = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'si', $alasan, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    }
    
    return false;
}

/**
 * Ambil SEMUA notifikasi untuk panel bell mahasiswa.
 * Menggabungkan: ajuan baru, disetujui/ditolak, H-1 kembali, denda aktif.
 */
function getSemuaNotifikasiMahasiswa($user_id) {
    global $conn;
    $notifs = [];

    // 1. Peminjaman baru diajukan (status menunggu)
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, 'ajuan' AS tipe, p.status, p.created_at AS waktu,
               p.tgl_kembali_rencana, b.nama_barang, b.kode_barang,
               p.jumlah, p.keperluan, NULL AS total_denda, NULL AS catatan_admin
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.user_id = ? 
          AND p.status = 'menunggu'
        ORDER BY p.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $notifs[] = $row;
    mysqli_stmt_close($stmt);

    // 2. Disetujui / ditolak (status dipinjam atau ditolak)
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, 'status' AS tipe, p.status, p.updated_at AS waktu,
               p.tgl_kembali_rencana, b.nama_barang, b.kode_barang,
               p.jumlah, p.keperluan, p.total_denda, p.catatan_admin
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.user_id = ?
          AND p.status IN ('dipinjam', 'ditolak')
        ORDER BY p.updated_at DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $notifs[] = $row;
    mysqli_stmt_close($stmt);

    // 3. H-1 pengembalian (besok batas kembali, belum terkirim)
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, 'h1' AS tipe, p.status, NOW() AS waktu,
               p.tgl_kembali_rencana, b.nama_barang, b.kode_barang,
               p.jumlah, p.keperluan, p.total_denda, NULL AS catatan_admin
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.user_id = ?
          AND p.status = 'dipinjam'
          AND p.tgl_kembali_rencana = CURDATE() + INTERVAL 1 DAY
          AND p.notif_h1_terkirim = 0
        ORDER BY p.tgl_kembali_rencana ASC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) {
        $notifs[] = $row;
        // Tandai sudah terkirim agar tidak muncul lagi
        $upd = mysqli_prepare($conn, "UPDATE peminjaman SET notif_h1_terkirim = 1 WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'i', $row['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }
    mysqli_stmt_close($stmt);

    // 4. Denda aktif belum lunas
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, 'denda' AS tipe, p.status, p.updated_at AS waktu,
               p.tgl_kembali_rencana, b.nama_barang, b.kode_barang,
               p.jumlah, p.keperluan, p.total_denda, NULL AS catatan_admin
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.user_id = ?
          AND p.status = 'dipinjam'
          AND p.total_denda > 0
          AND p.denda_dibayar = 0
        ORDER BY p.total_denda DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $notifs[] = $row;
    mysqli_stmt_close($stmt);

    return $notifs;
}

/**
 * Tandai notif ajuan sudah dibaca.
 */
function tandaiNotifAjuanDibaca($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn,
        "UPDATE peminjaman SET notif_ajuan_dibaca = 1 
         WHERE user_id = ? AND status = 'menunggu' AND notif_ajuan_dibaca = 0"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Hitung total notif belum dibaca untuk badge bell.
 */
function countNotifBelumDibaca($user_id) {
    global $conn;
    $total = 0;

    // Hitung notifikasi aktif: menunggu, dipinjam, ditolak
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS n FROM peminjaman 
         WHERE user_id = ? AND status IN ('menunggu', 'dipinjam', 'ditolak')"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];
    mysqli_stmt_close($stmt);

    // H-1
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS n FROM peminjaman 
         WHERE user_id = ? AND status = 'dipinjam' 
           AND tgl_kembali_rencana = CURDATE() + INTERVAL 1 DAY 
           AND notif_h1_terkirim = 0"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $total += (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];
    mysqli_stmt_close($stmt);

    // Denda aktif
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS n FROM peminjaman 
         WHERE user_id = ? AND status = 'dipinjam' AND total_denda > 0 AND denda_dibayar = 0"
    );
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $total += (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];
    mysqli_stmt_close($stmt);

    return $total;
}