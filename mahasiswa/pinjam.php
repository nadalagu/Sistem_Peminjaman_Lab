<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();
$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barang_id           = (int)($_POST['barang_id'] ?? 0);
    $jumlah              = (int)($_POST['jumlah'] ?? 0);
    $tgl_pinjam          = trim($_POST['tgl_pinjam'] ?? '');
    $tgl_kembali_rencana = trim($_POST['tgl_kembali_rencana'] ?? '');
    $keperluan           = trim($_POST['keperluan'] ?? '');

    if (!$barang_id || $jumlah < 1 || !$tgl_pinjam || !$tgl_kembali_rencana || !$keperluan) {
        $error = 'Semua field wajib diisi dengan benar.';
    } elseif ($tgl_kembali_rencana <= $tgl_pinjam) {
        $error = 'Tanggal pengembalian harus setelah tanggal peminjaman.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT nama_barang, stok_tersedia FROM barang WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $barang_id); mysqli_stmt_execute($stmt);
        $barang = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);

        if (!$barang) {
            $error = 'Barang tidak ditemukan.';
        } elseif ($barang['stok_tersedia'] < $jumlah) {
            $error = 'Stok tidak mencukupi. Stok tersedia: ' . (int)$barang['stok_tersedia'] . '.';
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO peminjaman (user_id, barang_id, jumlah, tgl_pinjam, tgl_kembali_rencana, keperluan, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'menunggu', NOW())"
            );
            mysqli_stmt_bind_param($stmt, 'iiisss', $user_id, $barang_id, $jumlah, $tgl_pinjam, $tgl_kembali_rencana, $keperluan);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Permohonan peminjaman <strong>' . htmlspecialchars($barang['nama_barang']) . '</strong> berhasil diajukan dan sedang menunggu persetujuan admin.';
                $barang_id = $jumlah = $tgl_pinjam = $tgl_kembali_rencana = $keperluan = '';
            } else {
                $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$barang_list = mysqli_query($conn,
    "SELECT id, kode_barang, nama_barang, stok_tersedia FROM barang WHERE stok_tersedia > 0 ORDER BY nama_barang ASC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajukan Peminjaman — <?= APP_NAME ?></title>
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
      <h1 class="topbar-title">Ajukan Peminjaman</h1>
      <p class="topbar-sub">Isi formulir berikut untuk meminjam alat laboratorium.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-check-circle"></i> <?= $success ?>
    </div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-12 col-lg-7">
      <div class="card-section">
        <div class="card-section-header">
          <h6 class="card-section-title"><i class="ph-bold ph-clipboard-text"></i> Form Peminjaman</h6>
        </div>
        <div class="p-4">
          <form method="POST" action="pinjam.php">
            <div class="mb-4">
              <label class="form-label">Pilih Barang</label>
              <select name="barang_id" id="barang" class="form-select" required onchange="updateStok(this)">
                <option value="">— Pilih Barang —</option>
                <?php while ($b = mysqli_fetch_assoc($barang_list)): ?>
                  <option value="<?= $b['id'] ?>"
                    data-stok="<?= (int)$b['stok_tersedia'] ?>"
                    <?= (isset($barang_id) && $barang_id == $b['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['nama_barang']) ?>
                    (<?= htmlspecialchars($b['kode_barang']) ?>) — Stok: <?= (int)$b['stok_tersedia'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <div id="stok-info" style="display:none;margin-top:6px;font-size:12.5px;color:var(--text-secondary);">
                <i class="ph ph-info" style="color:var(--primary);"></i>
                Stok tersedia: <strong id="stok-val" style="color:var(--success);"></strong> unit
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Jumlah</label>
              <input type="number" name="jumlah" id="jumlah" class="form-control"
                min="1" required value="<?= htmlspecialchars($jumlah ?? '') ?>"
                placeholder="Masukkan jumlah unit">
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label">Tanggal Peminjaman</label>
                <input type="date" name="tgl_pinjam" id="tgl_pinjam" class="form-control"
                  required min="<?= date('Y-m-d') ?>"
                  value="<?= htmlspecialchars($tgl_pinjam ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal Pengembalian (Rencana)</label>
                <input type="date" name="tgl_kembali_rencana" id="tgl_kembali_rencana" class="form-control"
                  required value="<?= htmlspecialchars($tgl_kembali_rencana ?? '') ?>">
                <small style="font-size:11px;color:var(--text-muted);">Tanggal aktual dicatat oleh admin.</small>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Keperluan</label>
              <textarea name="keperluan" id="keperluan" class="form-control" rows="3" required
                placeholder="Contoh: Praktikum Manufaktur semester 4..."><?= htmlspecialchars($keperluan ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" style="padding:11px 24px;font-size:14px;font-weight:600;">
              <i class="ph-bold ph-paper-plane-tilt"></i> Ajukan Peminjaman
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Info Samping -->
    <div class="col-12 col-lg-5">
      <div class="card-section mb-3">
        <div class="card-section-header">
          <h6 class="card-section-title"><i class="ph-bold ph-info"></i> Panduan Peminjaman</h6>
        </div>
        <div class="p-4">
          <ul style="list-style:none;padding:0;margin:0;font-size:13.5px;color:var(--text-secondary);">
            <li style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
              <i class="ph-bold ph-number-circle-one" style="color:var(--primary);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
              Pilih barang yang ingin dipinjam dari daftar yang tersedia.
            </li>
            <li style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
              <i class="ph-bold ph-number-circle-two" style="color:var(--primary);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
              Isi jumlah sesuai kebutuhan (tidak melebihi stok tersedia).
            </li>
            <li style="padding:8px 0;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
              <i class="ph-bold ph-number-circle-three" style="color:var(--primary);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
              Tentukan tanggal pinjam dan rencana pengembalian.
            </li>
            <li style="padding:8px 0;display:flex;gap:10px;align-items:flex-start;">
              <i class="ph-bold ph-number-circle-four" style="color:var(--primary);font-size:18px;flex-shrink:0;margin-top:1px;"></i>
              Pengajuan akan diproses admin. Pantau status di <a href="riwayat.php">Riwayat</a>.
            </li>
          </ul>
        </div>
      </div>

      <div class="card-section">
        <div class="card-section-header">
          <h6 class="card-section-title"><i class="ph-bold ph-warning-circle" style="color:var(--warning);"></i> Catatan Penting</h6>
        </div>
        <div class="p-4">
          <p style="font-size:13px;color:var(--text-secondary);margin:0;">
            Pengembalian barang lebih awal dapat dilakukan dan tanggal aktual pengembalian akan diperbarui oleh admin.
            Pastikan barang dikembalikan dalam kondisi baik.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateStok(sel) {
  const stok = parseInt(sel.options[sel.selectedIndex]?.dataset?.stok ?? 0);
  const info = document.getElementById('stok-info');
  const val  = document.getElementById('stok-val');
  const qty  = document.getElementById('jumlah');
  if (sel.value && stok > 0) {
    val.textContent = stok;
    info.style.display = 'block';
    qty.max = stok;
  } else {
    info.style.display = 'none';
    qty.removeAttribute('max');
  }
}

document.getElementById('tgl_pinjam').addEventListener('change', function() {
  const kembali = document.getElementById('tgl_kembali_rencana');
  kembali.min = this.value;
  if (kembali.value && kembali.value <= this.value) kembali.value = '';
});
</script>
</body>
</html>
