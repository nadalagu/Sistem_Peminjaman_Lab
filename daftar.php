<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'config/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = bersihkan($_POST['nama_lengkap'] ?? '');
    $username     = bersihkan($_POST['username'] ?? '');
    $email        = bersihkan($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi'] ?? '';

    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek username sudah dipakai
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'"));
        if ($cek) {
            $error = 'Username sudah digunakan, pilih yang lain.';
        } else {
            // Cek email sudah dipakai
            $cek_email = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'"));
            if ($cek_email) {
                $error = 'Email sudah terdaftar.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $nama_esc     = mysqli_real_escape_string($conn, $nama_lengkap);
                $username_esc = mysqli_real_escape_string($conn, $username);
                $email_esc    = mysqli_real_escape_string($conn, $email);

                $sql = "INSERT INTO users (nama_lengkap, username, email, password, role) 
                        VALUES ('$nama_esc', '$username_esc', '$email_esc', '$hash', 'mahasiswa')";

                if (mysqli_query($conn, $sql)) {
                    $success = 'Akun berhasil dibuat! Silakan masuk.';
                } else {
                    $error = 'Gagal mendaftar, coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .input-group-text {
            background: transparent;
            border-right: none;
            border-color: #e2e8f0;
            color: #94a3b8;
        }

        .input-group .form-control {
            border-left: none;
            padding-left: 0;
        }

        .input-group:focus-within .input-group-text {
            border-color: #6366f1;
        }

        .input-group:focus-within .form-control {
            border-color: #6366f1;
        }

        .btn-toggle-pw {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0 12px;
        }
    </style>
</head>

<body class="login-page">

    <div style="position:absolute;top:-100px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,0.15) 0%,transparent 70%);pointer-events:none;"></div>

    <a href="<?= BASE_URL ?>index.php" style="position:fixed;top:20px;left:24px;display:flex;align-items:center;gap:6px;font-size:13px;color:#6366f1;text-decoration:none;font-weight:600;background:white;padding:8px 14px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border:1px solid #e2e8f0;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(99,102,241,0.2)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
        <i class="ph-bold ph-arrow-left"></i> Kembali ke Beranda
    </a>

    <div class="login-card" style="max-width:460px;">
        <!-- Logo -->
        <div style="text-align:center;margin-bottom:28px;">
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 8px 20px rgba(99,102,241,0.35);">
                <i class="ph-bold ph-user-plus" style="color:#fff;font-size:28px;"></i>
            </div>
            <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Daftar Akun</h2>
            <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Laboratorium Mesin — Buat akun mahasiswa baru</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4 d-flex align-items-center gap-2" style="font-size:13.5px;">
                <i class="ph-bold ph-warning-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert mb-4 d-flex align-items-start gap-2"
                style="background:#f0fdf4;border:1.5px solid #4ade80;color:#166534;border-radius:10px;font-size:13.5px;">
                <i class="ph-bold ph-check-circle" style="font-size:18px;margin-top:1px;flex-shrink:0;"></i>
                <div>
                    <?= $success ?>
                    <a href="<?= BASE_URL ?>login.php" style="color:#166534;font-weight:600;">Masuk sekarang →</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ph ph-identification-card"></i></span>
                        <input type="text" name="nama_lengkap" class="form-control"
                            placeholder="Masukkan nama lengkap"
                            value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ph ph-user"></i></span>
                        <input type="text" name="username" class="form-control"
                            placeholder="Buat username unik"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                            placeholder="Masukkan email aktif"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ph ph-lock"></i></span>
                        <input type="password" name="password" id="pwInput" class="form-control"
                            placeholder="Minimal 6 karakter" required>
                        <button type="button" class="btn-toggle-pw" onclick="togglePw('pwInput','eyeIcon1')"
                            style="border:1.5px solid #e2e8f0;border-left:none;border-radius:0 8px 8px 0;padding:0 12px;">
                            <i class="ph ph-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                        <input type="password" name="konfirmasi" id="pwInput2" class="form-control"
                            placeholder="Ulangi password" required>
                        <button type="button" class="btn-toggle-pw" onclick="togglePw('pwInput2','eyeIcon2')"
                            style="border:1.5px solid #e2e8f0;border-left:none;border-radius:0 8px 8px 0;padding:0 12px;">
                            <i class="ph ph-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2"
                    style="padding:12px;font-size:15px;font-weight:600;border-radius:10px;">
                    <i class="ph-bold ph-user-plus"></i> Daftar Sekarang
                </button>
            </form>
        <?php endif; ?>

        <p style="text-align:center;font-size:13px;color:#64748b;margin-top:20px;margin-bottom:0;">
            Sudah punya akun?
            <a href="<?= BASE_URL ?>login.php" style="color:#6366f1;font-weight:600;">Masuk di sini</a>
        </p>

        <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:16px;margin-bottom:0;">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>
        </p>
    </div>

    <script>
        function togglePw(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.className = 'ph ph-eye-slash';
            } else {
                inp.type = 'password';
                ico.className = 'ph ph-eye';
            }
        }
    </script>
</body>

</html>