<?php
// admin/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar d-flex flex-column">
  <div class="sidebar-user" style="padding-top:20px;">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
      <div class="user-role">
        <i class="ph ph-shield-check" style="font-size:10px;margin-right:2px;"></i>Admin Lab
      </div>
    </div>
  </div>

  <hr class="sidebar-divider">
  <div class="sidebar-section">Menu Utama</div>

  <nav class="sidebar-nav flex-grow-1">
    <ul class="nav flex-column" style="list-style:none;padding:0;margin:0;">
      <li>
        <a href="<?= BASE_URL ?>admin/dashboard.php"
          class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-squares-four"></i> Dashboard
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>admin/approval.php"
          class="nav-link <?= $current === 'approval.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-check-circle"></i> Persetujuan
        </a>
      </li>
    </ul>

    <div class="sidebar-section" style="margin-top:8px;">Data</div>
    <ul class="nav flex-column" style="list-style:none;padding:0;margin:0;">
      <li>
        <a href="<?= BASE_URL ?>admin/inventaris.php"
          class="nav-link <?= $current === 'inventaris.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-package"></i> Inventaris Barang
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>admin/semua_peminjaman.php"
          class="nav-link <?= $current === 'semua_peminjaman.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-clipboard-text"></i> Semua Peminjaman
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>admin/riwayat_pengembalian.php"
          class="nav-link <?= $current === 'riwayat_pengembalian.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-arrow-counter-clockwise"></i> Riwayat Pengembalian
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>admin/denda.php"
          class="nav-link <?= $current === 'denda.php' ? 'active' : '' ?>"
          style="display:flex;align-items:center;justify-content:space-between;">
          <span style="display:flex;align-items:center;gap:8px;">
            <i class="ph-bold ph-warning-circle"></i> Manajemen Denda
          </span>
          <?php
          $n_denda = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COUNT(*) AS n FROM peminjaman WHERE total_denda > 0 AND denda_dibayar = 0"
          ))['n'];
          if ($n_denda > 0): ?>
            <span class="badge bg-danger"><?= $n_denda ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>admin/kelola_user.php"
          class="nav-link <?= $current === 'kelola_user.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-users"></i> Kelola User
        </a>
      </li>
    </ul>

    <div class="sidebar-section" style="margin-top:8px;">Laporan</div>
    <ul class="nav flex-column" style="list-style:none;padding:0;margin:0;">
      <li>
        <a href="<?= BASE_URL ?>admin/laporan_pdf.php"
          class="nav-link <?= $current === 'laporan_pdf.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-file-pdf"></i> Cetak Laporan PDF
        </a>
      </li>
    </ul>
  </nav>

  <hr class="sidebar-divider">
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>logout.php" class="nav-link logout-link">
      <i class="ph-bold ph-sign-out"></i> Keluar
    </a>
  </div>
</div>