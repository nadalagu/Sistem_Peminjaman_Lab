<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();
updateDendaOtomatis();

$msg_sukses = '';
$msg_error  = '';

// ── KONFIRMASI LUNAS ──────────────────────────────────────────
if (isset($_GET['konfirmasi']) && is_numeric($_GET['konfirmasi'])) {
    $pid = (int)$_GET['konfirmasi'];

    // Ambil data peminjaman
    $stmt = mysqli_prepare($conn,
        "SELECT p.id, p.user_id, p.total_denda, u.nama_lengkap
         FROM peminjaman p JOIN users u ON p.user_id = u.id
         WHERE p.id = ? AND p.total_denda > 0 AND p.denda_dibayar = 0 LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $pid);
    mysqli_stmt_execute($stmt);
    $pinjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($pinjam) {
        // Tandai lunas
        $stmt = mysqli_prepare($conn,
            "UPDATE peminjaman SET denda_dibayar = 1 WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Cek apakah mahasiswa masih punya denda lain
        $stmt = mysqli_prepare($conn,
            "SELECT COUNT(*) AS n FROM peminjaman
             WHERE user_id = ? AND total_denda > 0 AND denda_dibayar = 0"
        );
        mysqli_stmt_bind_param($stmt, 'i', $pinjam['user_id']);
        mysqli_stmt_execute($stmt);
        $sisa = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];
        mysqli_stmt_close($stmt);

        // Buka kunci jika tidak ada denda tersisa
        if ($sisa === 0) {
            $stmt = mysqli_prepare($conn,
                "UPDATE users SET akun_terkunci = 0, alasan_kunci = NULL WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, 'i', $pinjam['user_id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msg_sukses = "Denda " . htmlspecialchars($pinjam['nama_lengkap']) .
                          " (" . formatRupiah($pinjam['total_denda']) . ") telah dikonfirmasi lunas & akun dibuka.";
        } else {
            $msg_sukses = "Denda " . htmlspecialchars($pinjam['nama_lengkap']) .
                          " (" . formatRupiah($pinjam['total_denda']) . ") telah dikonfirmasi lunas. Masih ada " .
                          $sisa . " denda lain.";
        }
    } else {
        $msg_error = "Data tidak ditemukan atau denda sudah lunas.";
    }
}

// ── BUKA AKUN MANUAL ─────────────────────────────────────────
if (isset($_GET['buka_akun']) && is_numeric($_GET['buka_akun'])) {
    $uid = (int)$_GET['buka_akun'];
    $stmt = mysqli_prepare($conn,
        "UPDATE users SET akun_terkunci = 0, alasan_kunci = NULL WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'i', $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg_sukses = "Akun mahasiswa berhasil dibuka.";
}

// ── AMBIL DATA ────────────────────────────────────────────────
// Semua denda belum lunas
$denda_belum = mysqli_query($conn, "
    SELECT p.id, u.id AS user_id, u.nama_lengkap, u.nim, u.akun_terkunci,
           b.nama_barang, b.kode_barang,
           p.tgl_kembali_rencana, p.tgl_kembali_aktual,
           p.denda_per_hari, p.total_denda, p.status,
           DATEDIFF(IFNULL(p.tgl_kembali_aktual, CURDATE()), p.tgl_kembali_rencana) AS hari_terlambat
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.total_denda > 0 AND p.denda_dibayar = 0
    ORDER BY p.total_denda DESC
");

// Riwayat denda sudah lunas (20 terakhir)
$denda_lunas = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim,
           b.nama_barang, b.kode_barang,
           p.tgl_kembali_rencana, p.tgl_kembali_aktual,
           p.total_denda, p.status,
           DATEDIFF(p.tgl_kembali_aktual, p.tgl_kembali_rencana) AS hari_terlambat
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.total_denda > 0 AND p.denda_dibayar = 1
    ORDER BY p.updated_at DESC
    LIMIT 20
");

// Ringkasan
$ringkasan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
      SUM(CASE WHEN denda_dibayar = 0 THEN total_denda ELSE 0 END) AS belum_lunas,
      SUM(CASE WHEN denda_dibayar = 1 THEN total_denda ELSE 0 END) AS sudah_lunas,
      COUNT(CASE WHEN denda_dibayar = 0 THEN 1 END) AS jml_belum,
      COUNT(CASE WHEN denda_dibayar = 1 THEN 1 END) AS jml_lunas
    FROM peminjaman WHERE total_denda > 0
"));

// Akun terkunci
$akun_terkunci = mysqli_query($conn, "
    SELECT u.id, u.nama_lengkap, u.nim, u.alasan_kunci,
           (SELECT SUM(total_denda) FROM peminjaman
            WHERE user_id = u.id AND total_denda > 0 AND denda_dibayar = 0) AS total_denda
    FROM users u WHERE u.akun_terkunci = 1 AND u.role = 'mahasiswa'
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Denda — <?= APP_NAME ?></title>
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
        <h1 class="topbar-title">Manajemen Denda</h1>
        <p class="topbar-sub">Konfirmasi pembayaran & kelola akun terkunci</p>
      </div>
    </div>

    <?php if ($msg_sukses): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ph-bold ph-check-circle me-2"></i><?= $msg_sukses ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ph-bold ph-warning-circle me-2"></i><?= $msg_error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card c-danger">
          <div class="stat-icon"><i class="ph-bold ph-warning-circle"></i></div>
          <div>
            <div class="stat-label">Belum Lunas</div>
            <div class="stat-value"><?= $ringkasan['jml_belum'] ?? 0 ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-danger">
          <div class="stat-icon"><i class="ph-bold ph-currency-circle-dollar"></i></div>
          <div>
            <div class="stat-label">Nominal Belum Lunas</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($ringkasan['belum_lunas'] ?? 0) ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-success">
          <div class="stat-icon"><i class="ph-bold ph-check-circle"></i></div>
          <div>
            <div class="stat-label">Sudah Lunas</div>
            <div class="stat-value"><?= $ringkasan['jml_lunas'] ?? 0 ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-success">
          <div class="stat-icon"><i class="ph-bold ph-currency-circle-dollar"></i></div>
          <div>
            <div class="stat-label">Total Terkumpul</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($ringkasan['sudah_lunas'] ?? 0) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Akun Terkunci -->
    <?php
    $akun_terkunci_arr = [];
    while ($u = mysqli_fetch_assoc($akun_terkunci)) $akun_terkunci_arr[] = $u;
    if (!empty($akun_terkunci_arr)):
    ?>
    <div class="card-section mb-4">
      <div class="card-section-header">
        <h6 class="card-section-title">
          <i class="ph-bold ph-lock-simple" style="color:#f43f5e;"></i>
          Akun Terkunci (<?= count($akun_terkunci_arr) ?>)
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="ps-3">Mahasiswa</th>
              <th>NIM</th>
              <th>Sisa Denda</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($akun_terkunci_arr as $u): ?>
            <tr>
              <td class="ps-3 fw-semibold"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
              <td><code><?= htmlspecialchars($u['nim']) ?></code></td>
              <td>
                <?php if ($u['total_denda'] > 0): ?>
                  <strong style="color:#f43f5e;"><?= formatRupiah($u['total_denda']) ?></strong>
                <?php else: ?>
                  <span class="text-muted">Tidak ada denda</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="denda.php?buka_akun=<?= $u['id'] ?>"
                   class="btn btn-sm btn-warning"
                   style="font-size:12px;padding:4px 12px;"
                   onclick="return confirm('Buka paksa akun <?= htmlspecialchars($u['nama_lengkap']) ?>? (tanpa konfirmasi denda)')">
                  <i class="ph ph-lock-open"></i> Buka Akun
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Denda Belum Lunas -->
    <div class="card-section mb-4">
      <div class="card-section-header">
        <h6 class="card-section-title">
          <i class="ph-bold ph-clock" style="color:#f43f5e;"></i> Denda Belum Lunas
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="ps-3">Mahasiswa</th>
              <th>NIM</th>
              <th>Barang</th>
              <th>Tgl Rencana</th>
              <th>Hari Terlambat</th>
              <th>Denda/Hari</th>
              <th>Total Denda</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($denda_belum) > 0):
              // reset pointer (sudah di-fetch untuk count di card, fetch ulang)
              while ($d = mysqli_fetch_assoc($denda_belum)): ?>
            <tr>
              <td class="ps-3 fw-semibold"><?= htmlspecialchars($d['nama_lengkap']) ?></td>
              <td><code><?= htmlspecialchars($d['nim']) ?></code></td>
              <td>
                <?= htmlspecialchars($d['nama_barang']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($d['kode_barang']) ?></small>
              </td>
              <td><?= formatTanggal($d['tgl_kembali_rencana']) ?></td>
              <td>
                <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:12px;">
                  <?= $d['hari_terlambat'] ?> hari
                </span>
              </td>
              <td><?= formatRupiah($d['denda_per_hari']) ?></td>
              <td><strong style="color:#f43f5e;"><?= formatRupiah($d['total_denda']) ?></strong></td>
              <td><?= badgeStatus($d['status']) ?></td>
              <td>
                <a href="denda.php?konfirmasi=<?= $d['id'] ?>"
                   class="btn btn-sm btn-success"
                   style="font-size:12px;padding:4px 12px;"
                   onclick="return confirm('Konfirmasi denda <?= htmlspecialchars($d['nama_lengkap']) ?> sebesar <?= formatRupiah($d['total_denda']) ?> sudah dibayar?')">
                  <i class="ph ph-check"></i> Konfirmasi Lunas
                </a>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="ph ph-check-circle" style="font-size:32px;display:block;margin-bottom:8px;color:#10b981;"></i>
                Tidak ada denda yang belum lunas.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Riwayat Denda Lunas -->
    <div class="card-section mb-4">
      <div class="card-section-header">
        <h6 class="card-section-title">
          <i class="ph-bold ph-check-circle" style="color:#10b981;"></i> Riwayat Denda Lunas
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="ps-3">Mahasiswa</th>
              <th>NIM</th>
              <th>Barang</th>
              <th>Hari Terlambat</th>
              <th>Total Denda</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($denda_lunas) > 0):
              while ($d = mysqli_fetch_assoc($denda_lunas)): ?>
            <tr>
              <td class="ps-3 fw-semibold"><?= htmlspecialchars($d['nama_lengkap']) ?></td>
              <td><code><?= htmlspecialchars($d['nim']) ?></code></td>
              <td>
                <?= htmlspecialchars($d['nama_barang']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($d['kode_barang']) ?></small>
              </td>
              <td><?= $d['hari_terlambat'] ?> hari</td>
              <td>
                <strong style="color:#10b981;"><?= formatRupiah($d['total_denda']) ?></strong>
                <span class="badge" style="background:#d1fae5;color:#065f46;font-size:11px;margin-left:4px;">Lunas</span>
              </td>
              <td><?= badgeStatus($d['status']) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat denda lunas.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>