<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireLogin();
$user_id = $_SESSION['user_id'];

// Ambil info user dan alasan kunci
$stmt = mysqli_prepare($conn, "SELECT nama_lengkap, akun_terkunci, alasan_kunci FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Ambil denda yang belum dibayar
$stmt = mysqli_prepare($conn, 
    "SELECT p.id, b.nama_barang, b.kode_barang, p.total_denda, p.tgl_kembali_rencana
     FROM peminjaman p 
     JOIN barang b ON p.barang_id = b.id
     WHERE p.user_id = ? AND p.total_denda > 0 AND p.denda_dibayar = 0
     ORDER BY p.total_denda DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$denda_list = [];
$total_denda = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $denda_list[] = $row;
    $total_denda += (int)$row['total_denda'];
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Terkunci — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #2d2d88 0%, #120750 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .lock-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .lock-icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
        }
        .lock-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .lock-message {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .denda-section {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        .denda-title {
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .denda-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #fecaca;
            font-size: 13px;
        }
        .denda-item:last-child {
            border-bottom: none;
        }
        .denda-total {
            background: #fca5a5;
            color: white;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-text {
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 8px;
            padding: 15px;
            font-size: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #4338ca;
        }
        .btn-logout {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: #764ba2;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="lock-card">
        <div class="lock-icon">
            <i class="ph-bold ph-lock-simple" style="color:#dc2626;"></i>
        </div>
        
        <div class="lock-title">Akun Anda Terkunci</div>
        
        <div class="lock-message">
            <?= htmlspecialchars($user['alasan_kunci']) ?>
        </div>

        <?php if (!empty($denda_list)): ?>
        <div class="denda-section">
            <div class="denda-title">
                <i class="ph-bold ph-warning-circle" style="margin-right:6px;"></i>
                Daftar Denda yang Belum Dibayar
            </div>
            
            <?php foreach ($denda_list as $denda): ?>
            <div class="denda-item">
                <div>
                    <strong><?= htmlspecialchars($denda['nama_barang']) ?></strong><br>
                    <small style="color:#a0aec0;"><?= htmlspecialchars($denda['kode_barang']) ?></small>
                </div>
                <strong style="color:#dc2626;"><?= formatRupiah($denda['total_denda']) ?></strong>
            </div>
            <?php endforeach; ?>
            
            <div class="denda-total">
                <span>Total Denda</span>
                <span><?= formatRupiah($total_denda) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="info-text">
            <i class="ph-bold ph-info" style="margin-right:6px;"></i>
            <strong>Hubungi admin laboratorium</strong> untuk melunasi denda dan meminta pembukaan kembali akun Anda.
        </div>

        <a href="<?= BASE_URL ?>logout.php" class="btn-logout">
            <i class="ph-bold ph-sign-out" style="margin-right:6px;"></i> Logout
        </a>
    </div>
</body>
</html>
