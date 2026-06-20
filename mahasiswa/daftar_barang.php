<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();
$user_id = $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id, kode_barang, nama_barang, deskripsi, stok_total, stok_tersedia, kondisi FROM barang ORDER BY nama_barang ASC");
mysqli_stmt_execute($stmt);
$barang_list = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Barang — <?= APP_NAME ?></title>
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
      <h1 class="topbar-title">Daftar Barang</h1>
      <p class="topbar-sub">Lihat semua peralatan laboratorium yang tersedia untuk dipinjam.</p>
    </div>
    <a href="pinjam.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;">
      <i class="ph-bold ph-plus-circle"></i> Ajukan Peminjaman
    </a>
  </div>

  <div class="card-section">
    <div class="card-section-header">
      <h6 class="card-section-title"><i class="ph-bold ph-package"></i> Peralatan Laboratorium</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th class="ps-3">No</th>
            <th>Kode</th>
            <th>Nama Barang</th>
            <th>Deskripsi</th>
            <th>Stok Total</th>
            <th>Stok Tersedia</th>
            <th>Kondisi</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($barang_list) > 0): $no = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($barang_list)): ?>
            <tr>
              <td class="ps-3 text-muted"><?= $no++ ?></td>
              <td><code><?= htmlspecialchars($row['kode_barang']) ?></code></td>
              <td class="fw-semibold"><?= htmlspecialchars($row['nama_barang']) ?></td>
              <td style="font-size:12.5px;color:var(--text-secondary);max-width:180px;" class="truncate"
                title="<?= htmlspecialchars($row['deskripsi'] ?? '') ?>">
                <?= htmlspecialchars($row['deskripsi'] ?? '-') ?>
              </td>
              <td><?= $row['stok_total'] ?></td>
              <td>
                <span class="fw-semibold" style="color:<?= $row['stok_tersedia'] == 0 ? '#ef4444' : '#10b981' ?>">
                  <?= $row['stok_tersedia'] ?>
                </span>
                <?php if ($row['stok_tersedia'] == 0): ?>
                  <span class="badge ms-1" style="background:#fee2e2;color:#991b1b;font-size:10px;">Habis</span>
                <?php endif; ?>
              </td>
              <td><?= badgeKondisi($row['kondisi']) ?></td>
              <td>
                <?php if ($row['stok_tersedia'] > 0): ?>
                  <a href="pinjam.php?barang_id=<?= $row['id'] ?>" class="btn btn-sm"
                    style="background:var(--primary-light);color:var(--primary);font-size:12px;padding:4px 10px;border:none;">
                    <i class="ph ph-arrow-right"></i> Pinjam
                  </a>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--text-muted);">Tidak tersedia</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="ph ph-package" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                Belum ada data barang.
              </td>
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
