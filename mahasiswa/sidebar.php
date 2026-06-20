<?php
// mahasiswa/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar d-flex flex-column" id="sidebar">
  <div class="sidebar-user" style="padding-top:20px;">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
    <div>
      <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
      <div class="user-role"><i class="ph ph-graduation-cap" style="font-size:10px;margin-right:2px;"></i>Mahasiswa</div>
    </div>
  </div>

  <hr class="sidebar-divider">
  <div class="sidebar-section">Menu</div>

  <nav class="sidebar-nav flex-grow-1">
    <ul class="nav flex-column" style="list-style:none;padding:0;margin:0;">
      <li>
        <a href="<?= BASE_URL ?>mahasiswa/dashboard.php"
           class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-squares-four"></i> Dashboard
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>mahasiswa/daftar_barang.php"
           class="nav-link <?= $current === 'daftar_barang.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-package"></i> Daftar Barang
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>mahasiswa/pinjam.php"
           class="nav-link <?= $current === 'pinjam.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-clipboard-text"></i> Ajukan Peminjaman
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>mahasiswa/riwayat.php"
           class="nav-link <?= $current === 'riwayat.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-clock-counter-clockwise"></i> Riwayat Peminjaman
        </a>
      </li>
      <li>
        <a href="<?= BASE_URL ?>mahasiswa/profil.php"
           class="nav-link <?= $current === 'profil.php' ? 'active' : '' ?>">
          <i class="ph-bold ph-user-circle"></i> Profil Saya
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
