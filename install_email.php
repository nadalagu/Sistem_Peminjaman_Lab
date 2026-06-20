<?php
/**
 * Setup Email Database Columns (No Login Required)
 * Jalankan file ini untuk setup kolom database yang dibutuhkan
 */

require_once 'config/config.php';
require_once 'config/db.php';

$status = '';
$error = '';
$results = [];

// Step 1: Cek dan tambahkan kolom email ke table users
$check_email = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email'");
if (mysqli_num_rows($check_email) == 0) {
    $sql_email = "ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE NULL AFTER no_hp";
    if (mysqli_query($conn, $sql_email)) {
        $results[] = ['status' => 'success', 'message' => '✅ Kolom email berhasil ditambahkan ke table users'];
    } else {
        $results[] = ['status' => 'error', 'message' => '❌ Error menambahkan kolom email: ' . mysqli_error($conn)];
    }
} else {
    $results[] = ['status' => 'info', 'message' => '✅ Kolom email sudah ada di table users'];
}  

// Step 3: Tampilkan status
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Email System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 700px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 30px;
            text-align: center;
        }
        .card-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .card-body {
            padding: 30px;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 15px;
            border: none;
            display: flex;
            align-items: center;
            padding: 15px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-list {
            list-style: none;
            padding: 0;
        }
        .status-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .status-item.success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }
        .status-item.error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .status-item.info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }
        .status-icon {
            font-size: 20px;
            min-width: 30px;
        }
        .status-text {
            flex: 1;
        }
        .next-steps {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #667eea;
        }
        .next-steps h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .next-steps ol {
            margin-bottom: 0;
        }
        .next-steps li {
            margin-bottom: 10px;
            color: #333;
        }
        .code-block {
            background-color: #f4f4f4;
            padding: 10px 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 10px 0;
            border-left: 3px solid #667eea;
            overflow-x: auto;
        }
        .btn-group-custom {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn-group-custom a, .btn-group-custom button {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>📧 Setup Email System</h1>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Sistem Peminjaman Lab Mesin</p>
            </div>
            <div class="card-body">
                <!-- Status Results -->
                <div class="status-list">
                    <?php foreach ($results as $result): ?>
                        <div class="status-item <?= $result['status'] ?>">
                            <div class="status-icon">
                                <?php 
                                    switch($result['status']) {
                                        case 'success': echo '✅'; break;
                                        case 'error': echo '❌'; break;
                                        case 'info': echo 'ℹ️'; break;
                                    }
                                ?>
                            </div>
                            <div class="status-text">
                                <?= $result['message'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Next Steps -->
                <div class="next-steps">
                    <h3>📋 Langkah Selanjutnya:</h3>
                    <ol>
                        <li>
                            <strong>Edit konfigurasi email</strong><br>
                            Buka file <code>config/email.php</code> dan ubah:
                            <div class="code-block">define('MAIL_FROM_ADDRESS', 'lab.mesin@example.com'); // Ganti dengan email lab<br>define('LAB_ADDRESS', 'Laboratorium Mesin'); // Ganti alamat lab<br>define('LAB_PHONE', '(021) 1234567'); // Ganti nomor telepon</div>
                        </li>
                        <li>
                            <strong>Update email mahasiswa</strong><br>
                            <a href="admin/kelola_user.php" class="btn btn-sm btn-info">Kelola User</a> → Edit setiap mahasiswa dan isi email mereka
                        </li>
                        <li>
                            <strong>Test sistem</strong><br>
                            Buat peminjaman dengan deadline besok, mahasiswa akan dapat email H-1
                        </li>
                    </ol>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group-custom">
                    <a href="admin/kelola_user.php" class="btn btn-primary">👥 Kelola User</a>
                    <a href="admin/dashboard.php" class="btn btn-secondary">📊 Dashboard Admin</a>
                </div>

                <!-- Info Box -->
                <div class="alert alert-info" style="margin-top: 20px; margin-bottom: 0;">
                    <strong>ℹ️ Info:</strong> Email akan dikirim otomatis kepada mahasiswa H-1 sebelum deadline pengembalian barang. Pastikan setiap mahasiswa sudah memiliki email yang valid di sistem.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
