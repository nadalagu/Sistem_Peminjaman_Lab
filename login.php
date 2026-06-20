<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'config/functions.php';

if (isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php'));
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = bersihkan($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    $error = 'Username dan password wajib diisi.';
  } else {
    $user = loginUser($username, $password);
    if ($user) {
      $_SESSION['user_id']  = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['nama']     = $user['nama_lengkap'];
      $_SESSION['role']     = $user['role'];
      header('Location: ' . BASE_URL . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php'));
      exit;
    } else {
      $error = 'Username atau password salah.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk — <?= APP_NAME ?></title>
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

  <!-- Decorative blobs -->
  <div style="position:absolute;top:-100px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,0.15) 0%,transparent 70%);pointer-events:none;"></div>
  <a href="<?= BASE_URL ?>index.php" style="position:fixed;top:20px;left:24px;display:flex;align-items:center;gap:6px;font-size:13px;color:#6366f1;text-decoration:none;font-weight:600;background:white;padding:8px 14px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border:1px solid #e2e8f0;transition:box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(99,102,241,0.2)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
    <i class="ph-bold ph-arrow-left"></i> Kembali ke Beranda
  </a>
  
  <div class="login-card">
    <!-- Logo -->
    <div style="text-align:center;margin-bottom:28px;">
      <div style="width:60px;height:60px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 8px 20px rgba(99,102,241,0.35);">
        <i class="ph-bold ph-flask" style="color:#fff;font-size:28px;"></i>
      </div>
      <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin:0;">Sistem Peminjaman</h2>
      <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Laboratorium Mesin — Masuk ke akun Anda</p>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'denda'): ?>
      <div class="alert mb-4 d-flex align-items-start gap-2"
        style="background:#fff7ed;border:1.5px solid #fb923c;color:#9a3412;border-radius:10px;font-size:13.5px;">
        <i class="ph-bold ph-warning-circle" style="font-size:18px;margin-top:1px;flex-shrink:0;"></i>
        <div>
          <strong>Akun Anda dikunci sementara.</strong><br>
          Anda memiliki denda keterlambatan yang belum dilunasi.<br>
          Hubungi admin untuk pembayaran, lalu akun dapat digunakan kembali.
        </div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger mb-4 d-flex align-items-center gap-2" style="font-size:13.5px;">
        <i class="ph-bold ph-warning-circle"></i> <?= $error ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-4">
        <label class="form-label">Username</label>
        <div class="input-group">
          <span class="input-group-text"><i class="ph ph-user"></i></span>
          <input type="text" name="username" class="form-control"
            placeholder="Masukkan username" autocomplete="username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mb-5">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="ph ph-lock"></i></span>
          <input type="password" name="password" id="pwInput" class="form-control"
            placeholder="Masukkan password" autocomplete="current-password" required>
          <button type="button" class="btn-toggle-pw" onclick="togglePw()" style="border:1.5px solid #e2e8f0;border-left:none;border-radius:0 8px 8px 0;padding:0 12px;">
            <i class="ph ph-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2"
        style="padding:12px;font-size:15px;font-weight:600;border-radius:10px;">
        <i class="ph-bold ph-sign-in"></i> Masuk
      </button>
    </form>
    <p style="text-align:center;font-size:13px;color:#64748b;margin-top:20px;margin-bottom:0;">
      Belum punya akun?
      <a href="<?= BASE_URL ?>daftar.php" style="color:#6366f1;font-weight:600;">Daftar di sini</a>
    </p>

    <p style="text-align:center;font-size:12px;color:#94a3b8;margin-top:24px;margin-bottom:0;">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>
    </p>
  </div>

  <script>
    function togglePw() {
      const inp = document.getElementById('pwInput');
      const ico = document.getElementById('eyeIcon');
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