<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();
$user_id = $_SESSION['user_id'];

// Update denda otomatis tiap kali mahasiswa buka dashboard
updateDendaOtomatis();

$notif_denda = [];
$stmt = mysqli_prepare(
  $conn,
  "SELECT p.id, p.tgl_kembali_rencana, p.total_denda, b.nama_barang, b.kode_barang
     FROM peminjaman p JOIN barang b ON p.barang_id = b.id
     WHERE p.user_id = ? AND p.status = 'dipinjam' 
     AND p.total_denda > 0 AND p.denda_dibayar = 0"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $notif_denda[] = $row;
mysqli_stmt_close($stmt);

// Ambil notifikasi yang belum dibaca
$semua_notif = getSemuaNotifikasiMahasiswa($user_id);
$jumlah_notif = countNotifBelumDibaca($user_id);

// Tandai notif dibaca setelah diambil (akan ditampilkan sekali lewat JS)
// Kita tandai via AJAX setelah modal ditutup mahasiswa

// Statistik
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$total_pinjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = ? AND status = 'menunggu'");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$total_menunggu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = ? AND status = 'dipinjam'");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$total_aktif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM peminjaman WHERE user_id = ? AND status = 'dikembalikan'");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$total_selesai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

// Cek apakah ada denda aktif yang belum lunas
$stmt = mysqli_prepare(
  $conn,
  "SELECT SUM(total_denda) AS total_denda_aktif
     FROM peminjaman
     WHERE user_id = ? AND status = 'dipinjam' AND total_denda > 0 AND denda_dibayar = 0"
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$denda_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$total_denda_aktif = (int)($denda_row['total_denda_aktif'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT p.id, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana, p.tgl_kembali_aktual,
           p.status, p.keperluan, p.total_denda, p.denda_dibayar
    FROM peminjaman p JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5
");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    /* ---- Notif Modal ---- */
    .notif-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      z-index: 1060;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
      animation: fadeIn .2s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0
      }

      to {
        opacity: 1
      }
    }

    .notif-card {
      background: #fff;
      border-radius: 24px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 80px rgba(0, 0, 0, .22);
      overflow: hidden;
      animation: slideUp .3s cubic-bezier(.34, 1.56, .64, 1);
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0
      }

      to {
        transform: translateY(0);
        opacity: 1
      }
    }

    .notif-header {
      padding: 22px 24px 16px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    .notif-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, .12);
    }

    .notif-icon.acc {
      background: #d1fae5;
      color: #059669;
    }

    .notif-icon.rej {
      background: #fee2e2;
      color: #dc2626;
    }

    .notif-title {
      font-weight: 700;
      font-size: 16px;
      margin-bottom: 2px;
    }

    .notif-sub {
      font-size: 13px;
      color: #64748b;
    }

    .notif-body {
      padding: 0 24px 16px;
    }

    .notif-info-box {
      background: #f8fafc;
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 12px;
      font-size: 13px;
      color: #334155;
    }

    .notif-info-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 5px 0;
      border-bottom: 1px solid #e2e8f0;
    }

    .notif-info-row:last-child {
      border-bottom: none;
    }

    .notif-info-label {
      color: #94a3b8;
      font-size: 12px;
    }

    .notif-denda-box {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 12px;
    }

    .notif-denda-title {
      font-weight: 600;
      font-size: 13px;
      color: #c2410c;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .notif-denda-rule {
      font-size: 12.5px;
      color: #7c2d12;
      line-height: 1.7;
    }

    .notif-catatan {
      background: #f1f5f9;
      border-left: 3px solid #6366f1;
      border-radius: 0 8px 8px 0;
      padding: 10px 14px;
      font-size: 13px;
      color: #334155;
      margin-bottom: 12px;
    }

    .notif-catatan-label {
      font-size: 11px;
      font-weight: 600;
      color: #6366f1;
      margin-bottom: 3px;
    }

    .notif-footer {
      padding: 0 24px 22px;
    }

    .notif-counter {
      font-size: 11px;
      color: #94a3b8;
      margin-bottom: 8px;
      text-align: center;
    }

    /* ---- Banner denda aktif ---- */
    .denda-banner {
      background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%);
      border: 1.5px solid #fb923c;
      border-radius: 12px;
      padding: 14px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }

    .denda-banner-icon {
      font-size: 28px;
      color: #ea580c;
      flex-shrink: 0;
    }

    .denda-banner-title {
      font-weight: 700;
      font-size: 14px;
      color: #9a3412;
    }

    .denda-banner-sub {
      font-size: 12.5px;
      color: #c2410c;
      margin-top: 2px;
    }
  </style>
</head>

<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar" style="display:flex;align-items:center;justify-content:space-between;">
      <div style="min-width:0;">
        <h1 class="topbar-title">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></h1>
        <p class="topbar-sub">Selamat datang di Sistem Peminjaman Lab Mesin.</p>
      </div>
      <div class="d-flex align-items-center gap-3" style="flex-shrink:0;">
        <div style="position:relative;">
          <button onclick="bukaNotifPanel()"
            style="width:42px;height:42px;border-radius:12px;border:1.5px solid #e2e8f0;
           background:#fff;display:flex;align-items:center;justify-content:center;
           cursor:pointer;color:#64748b;font-size:20px;">
            <i class="ph-bold ph-bell"></i>
          </button>
          <?php if ($jumlah_notif > 0): ?>
            <span id="notifBadge" style="position:absolute;top:-4px;right:-4px;background:#ef4444;
          color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;
          border-radius:50%;display:flex;align-items:center;justify-content:center;
          border:2px solid #fff;">
              <?= $jumlah_notif ?>
            </span>
          <?php endif; ?>
        </div>
        <a href="pinjam.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;">
          <i class="ph-bold ph-plus-circle"></i> Ajukan Peminjaman
        </a>
      </div>
    </div>

    <?php if ($total_denda_aktif > 0): ?>
      <!-- Banner peringatan denda aktif -->
      <div class="denda-banner">
        <i class="ph-bold ph-warning-circle denda-banner-icon"></i>
        <div style="flex:1;">
          <div class="denda-banner-title">⚠ Anda memiliki denda keterlambatan!</div>
          <div class="denda-banner-sub">
            Total denda saat ini: <strong><?= formatRupiah($total_denda_aktif) ?></strong>.
            Segera kembalikan alat dan hubungi admin untuk pembayaran.
            Akun Anda akan dikunci jika denda belum dilunasi setelah pengembalian.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card c-primary">
          <div class="stat-icon"><i class="ph-bold ph-clipboard-text"></i></div>
          <div>
            <div class="stat-label">Semua Aktivitas</div>
            <div class="stat-value"><?= $total_pinjam ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-warning">
          <div class="stat-icon"><i class="ph-bold ph-clock-countdown"></i></div>
          <div>
            <div class="stat-label">Menunggu</div>
            <div class="stat-value"><?= $total_menunggu ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-cyan">
          <div class="stat-icon"><i class="ph-bold ph-wrench"></i></div>
          <div>
            <div class="stat-label">Sedang Dipinjam</div>
            <div class="stat-value"><?= $total_aktif ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-success">
          <div class="stat-icon"><i class="ph-bold ph-check-circle"></i></div>
          <div>
            <div class="stat-label">Selesai</div>
            <div class="stat-value"><?= $total_selesai ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <a href="pinjam.php" class="card-section d-flex align-items-center gap-14 p-3 text-decoration-none" style="gap:14px;">
          <div style="width:44px;height:44px;background:var(--primary-light);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="ph-bold ph-clipboard-text" style="color:var(--primary);font-size:20px;"></i>
          </div>
          <div>
            <div style="font-weight:600;font-size:13.5px;color:var(--text-primary);">Ajukan Peminjaman</div>
            <div style="font-size:12px;color:var(--text-secondary);">Pinjam alat laboratorium</div>
          </div>
          <i class="ph ph-arrow-right ms-auto" style="color:var(--text-muted);"></i>
        </a>
      </div>
      <div class="col-12 col-md-4">
        <a href="daftar_barang.php" class="card-section d-flex align-items-center p-3 text-decoration-none" style="gap:14px;">
          <div style="width:44px;height:44px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="ph-bold ph-package" style="color:var(--secondary);font-size:20px;"></i>
          </div>
          <div>
            <div style="font-weight:600;font-size:13.5px;color:var(--text-primary);">Daftar Barang</div>
            <div style="font-size:12px;color:var(--text-secondary);">Lihat alat yang tersedia</div>
          </div>
          <i class="ph ph-arrow-right ms-auto" style="color:var(--text-muted);"></i>
        </a>
      </div>
      <div class="col-12 col-md-4">
        <a href="riwayat.php" class="card-section d-flex align-items-center p-3 text-decoration-none" style="gap:14px;">
          <div style="width:44px;height:44px;background:#cffafe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="ph-bold ph-clock-counter-clockwise" style="color:var(--accent);font-size:20px;"></i>
          </div>
          <div>
            <div style="font-weight:600;font-size:13.5px;color:var(--text-primary);">Riwayat</div>
            <div style="font-size:12px;color:var(--text-secondary);">Lihat history peminjaman</div>
          </div>
          <i class="ph ph-arrow-right ms-auto" style="color:var(--text-muted);"></i>
        </a>
      </div>
    </div>

    <!-- Tabel Peminjaman Terbaru -->
    <div class="card-section">
      <div class="card-section-header">
        <h6 class="card-section-title"><i class="ph-bold ph-clock-counter-clockwise"></i> Peminjaman Terbaru</h6>
        <a href="riwayat.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:500;">
          Lihat semua <i class="ph ph-arrow-right"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="ps-3">Barang</th>
              <th>Jml</th>
              <th>Tgl Pinjam</th>
              <th>Tgl Kembali</th>
              <th>Status</th>
              <th>Denda</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($recent) > 0): ?>
              <?php while ($r = mysqli_fetch_assoc($recent)): ?>
                <tr>
                  <td class="ps-3">
                    <span class="fw-semibold"><?= htmlspecialchars($r['nama_barang']) ?></span><br>
                    <code style="font-size:10px;"><?= htmlspecialchars($r['kode_barang']) ?></code>
                  </td>
                  <td><?= $r['jumlah'] ?></td>
                  <td><?= formatTanggal($r['tgl_pinjam']) ?></td>
                  <td><?= formatTanggal($r['tgl_kembali_rencana']) ?></td>
                  <td><?= badgeStatus($r['status']) ?></td>
                  <td>
                    <?php if ($r['total_denda'] > 0): ?>
                      <span style="font-size:12px;font-weight:600;color:<?= $r['denda_dibayar'] ? '#059669' : '#dc2626' ?>;">
                        <?= formatRupiah($r['total_denda']) ?>
                        <?php if ($r['denda_dibayar']): ?>
                          <br><span style="font-size:10px;color:#059669;">✓ Lunas</span>
                        <?php endif; ?>
                      </span>
                    <?php else: ?>
                      <span style="font-size:12px;color:#94a3b8;">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($r['status'] === 'menunggu'): ?>
                      <button class="btn btn-sm btn-danger" style="font-size:12px;"
                        onclick="bukaBatalModal(<?= $r['id'] ?>, '<?= addslashes(htmlspecialchars($r['nama_barang'])) ?>')">
                        <i class="ph-bold ph-x"></i> Batalkan
                      </button>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="ph ph-clipboard-text" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                  Belum ada peminjaman. <a href="pinjam.php">Ajukan sekarang →</a>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ============================================================
     PANEL NOTIFIKASI BELL
     ============================================================ -->
  <div id="notifPanelOverlay" onclick="tutupNotifPanel()"
    style="display:none;position:fixed;inset:0;z-index:1059;background:rgba(0,0,0,0.3);"></div>

  <div id="notifPanel" style="
  display:none;position:fixed;top:70px;right:20px;width:380px;max-width:95vw;
  background:#fff;border-radius:16px;box-shadow:0 12px 48px rgba(0,0,0,0.18);
  z-index:1060;overflow:hidden;max-height:80vh;flex-direction:column;">

    <!-- Header panel -->
    <div style="padding:16px 20px 12px;border-bottom:1px solid #f1f5f9;
              display:flex;align-items:center;justify-content:space-between;">
      <div style="font-weight:700;font-size:15px;color:#0f172a;">
        <i class="ph-bold ph-bell" style="color:#6366f1;margin-right:6px;"></i>Notifikasi
      </div>
    </div>

    <!-- List notifikasi -->
    <div id="notifList" style="overflow-y:auto;flex:1;">
      <?php if (empty($semua_notif)): ?>
        <div style="padding:40px 20px;text-align:center;color:#94a3b8;">
          <i class="ph ph-bell-slash" style="font-size:36px;display:block;margin-bottom:8px;"></i>
          Tidak ada notifikasi baru
        </div>
      <?php else: ?>
        <?php foreach ($semua_notif as $n): ?>
          <?php
          // Tentukan icon, warna, judul berdasarkan tipe
          if ($n['tipe'] === 'ajuan') {
            $icon = 'ph-clock';
            $warna = '#f59e0b';
            $bg = '#fef3c7';
            $judul = 'Peminjaman Diajukan';
            $sub = 'Menunggu persetujuan admin';
          } elseif ($n['tipe'] === 'status' && $n['status'] === 'dipinjam') {
            $icon = 'ph-check-circle';
            $warna = '#059669';
            $bg = '#d1fae5';
            $judul = 'Peminjaman Disetujui';
            $sub = 'Alat siap Anda gunakan';
          } elseif ($n['tipe'] === 'status' && $n['status'] === 'ditolak') {
            $icon = 'ph-x-circle';
            $warna = '#dc2626';
            $bg = '#fee2e2';
            $judul = 'Peminjaman Ditolak';
            $sub = $n['catatan_admin'] ? htmlspecialchars($n['catatan_admin']) : 'Lihat catatan admin';
          } elseif ($n['tipe'] === 'h1') {
            $icon = 'ph-alarm';
            $warna = '#d97706';
            $bg = '#fef3c7';
            $judul = 'Pengingat H-1 Pengembalian';
            $sub = 'Besok batas waktu pengembalian!';
          } else { // denda
            $icon = 'ph-warning-circle';
            $warna = '#dc2626';
            $bg = '#fee2e2';
            $judul = 'Denda Belum Lunas';
            $sub = 'Hubungi admin untuk pembayaran';
          }
          $waktu = !empty($n['waktu']) ? date('d M Y, H:i', strtotime($n['waktu'])) : '';
          ?>
          <div style="padding:14px 20px;border-bottom:1px solid #f8fafc;
                    display:flex;gap:12px;align-items:flex-start;
                    background:<?= ($n['tipe'] === 'denda' || ($n['tipe'] === 'status' && $n['status'] === 'ditolak')) ? '#fffbfb' : '#fff' ?>;">
            <div style="width:38px;height:38px;border-radius:50%;background:<?= $bg ?>;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="ph-bold <?= $icon ?>" style="color:<?= $warna ?>;font-size:18px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:600;font-size:13px;color:#0f172a;"><?= $judul ?></div>
              <div style="font-size:12px;color:#64748b;margin:2px 0;">
                <?= htmlspecialchars($n['nama_barang']) ?>
                <code style="font-size:10px;"><?= htmlspecialchars($n['kode_barang']) ?></code>
              </div>
              <div style="font-size:11.5px;color:<?= $warna ?>;font-weight:500;"><?= $sub ?></div>
              <?php if ($n['tipe'] === 'denda' && $n['total_denda'] > 0): ?>
                <div style="font-size:11px;font-weight:700;color:#dc2626;margin-top:2px;">
                  Total: <?= formatRupiah($n['total_denda']) ?>
                </div>
              <?php endif; ?>
              <?php if ($n['tipe'] === 'h1'): ?>
                <div style="font-size:11px;color:#dc2626;margin-top:2px;">
                  Batas: <?= formatTanggal($n['tgl_kembali_rencana']) ?>
                </div>
              <?php endif; ?>
              <?php if ($waktu): ?>
                <div style="font-size:10.5px;color:#cbd5e1;margin-top:4px;">
                  <i class="ph ph-clock"></i> <?= $waktu ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Footer -->
    <div style="padding:12px 20px;border-top:1px solid #f1f5f9;text-align:center;">
      <a href="riwayat.php" style="font-size:12.5px;color:#6366f1;text-decoration:none;font-weight:600;">
        Lihat semua riwayat peminjaman →
      </a>
    </div>
  </div>

  <script>
    function bukaNotifPanel() {
      const panel = document.getElementById('notifPanel');
      const overlay = document.getElementById('notifPanelOverlay');
      const isOpen = panel.style.display === 'flex';
      panel.style.display = isOpen ? 'none' : 'flex';
      overlay.style.display = isOpen ? 'none' : 'block';
    }

    function tutupNotifPanel() {
      document.getElementById('notifPanel').style.display = 'none';
      document.getElementById('notifPanelOverlay').style.display = 'none';
    }

    // Pop-up denda otomatis hanya jika ada denda aktif
    <?php if ($total_denda_aktif > 0): ?>
      window.addEventListener('DOMContentLoaded', () => {
        // Tampilkan pop-up denda seperti sebelumnya tapi lebih ringkas
        document.getElementById('dendaPopupOverlay').style.display = 'flex';
      });
    <?php endif; ?>
  </script>

  <!-- Pop-up denda (muncul otomatis jika ada denda) -->
  <?php if ($total_denda_aktif > 0): ?>
    <div id="dendaPopupOverlay" class="notif-overlay" style="display:none;">
      <div class="notif-card">
        <div class="notif-header">
          <div class="notif-icon rej"><i class="ph-bold ph-warning-circle"></i></div>
          <div>
            <div class="notif-title">⚠️ Denda Belum Lunas!</div>
            <div class="notif-sub">Segera hubungi admin untuk pembayaran.</div>
          </div>
        </div>
        <div class="notif-body">
          <div class="notif-info-box">
            <?php foreach ($notif_denda as $d): ?>
              <div class="notif-info-row">
                <span class="notif-info-label"><?= htmlspecialchars($d['nama_barang']) ?></span>
                <span style="font-weight:700;color:#dc2626;"><?= formatRupiah($d['total_denda']) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="notif-info-row" style="border-top:1px solid #e2e8f0;margin-top:6px;padding-top:6px;">
              <span class="notif-info-label">Total</span>
              <span style="font-weight:700;color:#dc2626;"><?= formatRupiah($total_denda_aktif) ?></span>
            </div>
          </div>
        </div>
        <div class="notif-footer">
          <button onclick="logoutKarenaDenda()" class="btn w-100"
            style="background:#dc2626;color:#fff;border-radius:10px;font-weight:600;padding:12px;">
            Mengerti & Keluar
          </button>
        </div>
      </div>
    </div>

    <script>
      function logoutKarenaDenda() {
        fetch('<?= BASE_URL ?>logout.php', {
            method: 'POST'
          })
          .finally(() => {
            window.location.href = '<?= BASE_URL ?>login.php?msg=denda';
          });
      }
    </script>
  <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Modal Batalkan -->
  <div class="modal fade" id="modalBatalDashboard" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="riwayat.php">
          <input type="hidden" name="id" id="batalIdDashboard">
          <input type="hidden" name="aksi" value="batal">
          <div class="modal-header">
            <h6 class="modal-title d-flex align-items-center gap-2">
              <i class="ph-bold ph-x-circle"></i> Batalkan Peminjaman
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="batalDeskripsIDashboard" style="font-size:14px;"></p>
            <p class="text-muted" style="font-size:12px;">
              <i class="ph ph-info"></i> Pembatalan hanya bisa dilakukan selama status masih <strong>menunggu</strong>.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kembali</button>
            <button type="submit" class="btn btn-danger btn-sm">Ya, Batalkan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function bukaBatalModal(id, barang) {
      document.getElementById('batalIdDashboard').value = id;
      document.getElementById('batalDeskripsIDashboard').innerHTML =
        `Apakah Anda yakin ingin membatalkan peminjaman <strong>${barang}</strong>?`;
      new bootstrap.Modal(document.getElementById('modalBatalDashboard')).show();
    }
  </script>
</body>

</html>