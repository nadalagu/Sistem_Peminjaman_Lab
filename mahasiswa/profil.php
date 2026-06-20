<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();
$user_id = $_SESSION['user_id'];

$success = '';
$error   = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = bersihkan($_POST['nama_lengkap'] ?? '');
    $nim   = bersihkan($_POST['nim'] ?? '');
    $no_hp = bersihkan($_POST['no_hp'] ?? '');
    $pw_lama  = $_POST['pw_lama']  ?? '';
    $pw_baru  = $_POST['pw_baru']  ?? '';
    $pw_ulang = $_POST['pw_ulang'] ?? '';

    if (!$nama) {
        $error = 'Nama lengkap wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap=?, nim=?, no_hp=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $nama, $nim, $no_hp, $user_id);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
        $_SESSION['nama'] = $nama;

        if (!empty($pw_baru)) {
            $stmt2 = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
            mysqli_stmt_bind_param($stmt2, 'i', $user_id); mysqli_stmt_execute($stmt2);
            $usr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2)); mysqli_stmt_close($stmt2);
            if (!password_verify($pw_lama, $usr['password'])) {
                $error = 'Password lama tidak cocok.';
            } elseif ($pw_baru !== $pw_ulang) {
                $error = 'Konfirmasi password baru tidak cocok.';
            } elseif (strlen($pw_baru) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } else {
                $hash = password_hash($pw_baru, PASSWORD_BCRYPT);
                $stmt3 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt3, 'si', $hash, $user_id);
                mysqli_stmt_execute($stmt3); mysqli_stmt_close($stmt3);
                $success = 'Profil dan password berhasil diperbarui.';
            }
        } else {
            $success = 'Profil berhasil diperbarui.';
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT username, nama_lengkap, nim, no_hp FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id); mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Saya — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h1 class="topbar-title">Profil Saya</h1>
      <p class="topbar-sub">Kelola informasi akun dan keamanan Anda.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-check-circle"></i> <?= $success ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-warning-circle"></i> <?= $error ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Avatar Card -->
    <div class="col-12 col-md-4">
      <div class="card-section p-4 text-center">
        <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:36px;font-weight:800;color:#fff;">
          <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
        </div>
        <div style="font-size:17px;font-weight:700;color:var(--text-primary);"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
        <div style="font-size:13px;color:var(--text-secondary);margin-top:4px;">@<?= htmlspecialchars($user['username']) ?></div>
        <span class="badge mt-2" style="background:var(--primary-light);color:var(--primary);">
          <i class="ph ph-graduation-cap"></i> Mahasiswa
        </span>
        <hr style="border-color:var(--border);margin:18px 0;">
        <div class="text-start">
          <div style="font-size:12px;color:var(--text-secondary);margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Info Akun</div>
          <div style="font-size:13px;color:var(--text-primary);margin-bottom:8px;">
            <i class="ph ph-identification-card" style="color:var(--primary);margin-right:6px;"></i>
            NIM: <?= htmlspecialchars($user['nim'] ?: '-') ?>
          </div>
          <div style="font-size:13px;color:var(--text-primary);">
            <i class="ph ph-phone" style="color:var(--primary);margin-right:6px;"></i>
            HP: <?= htmlspecialchars($user['no_hp'] ?: '-') ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Edit -->
    <div class="col-12 col-md-8">
      <form method="POST">
        <!-- Data Diri -->
        <div class="card-section mb-4">
          <div class="card-section-header">
            <h6 class="card-section-title"><i class="ph-bold ph-user-circle"></i> Data Diri</h6>
          </div>
          <div class="p-4">
            <div class="mb-3">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="nama_lengkap" class="form-control"
                value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">NIM</label>
                <input type="text" name="nim" class="form-control"
                  value="<?= htmlspecialchars($user['nim'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control"
                  value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Ganti Password -->
        <div class="card-section mb-4">
          <div class="card-section-header">
            <h6 class="card-section-title"><i class="ph-bold ph-lock-key"></i> Ganti Password</h6>
          </div>
          <div class="p-4">
            <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;">Kosongkan jika tidak ingin mengganti password.</p>
            <div class="mb-3">
              <label class="form-label">Password Lama</label>
              <input type="password" name="pw_lama" class="form-control" placeholder="Masukkan password lama">
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Password Baru</label>
                <input type="password" name="pw_baru" class="form-control" placeholder="Min. 6 karakter">
              </div>
              <div class="col-md-6">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="pw_ulang" class="form-control" placeholder="Ulangi password baru">
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" style="padding:10px 24px;">
          <i class="ph-bold ph-floppy-disk"></i> Simpan Perubahan
        </button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
