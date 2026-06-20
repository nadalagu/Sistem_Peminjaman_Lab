<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $aksi    = $_POST['aksi'] ?? '';
    $catatan = trim($_POST['catatan_admin'] ?? '');
    $tgl_kembali_custom = $_POST['tgl_kembali_aktual'] ?? '';

    if (!$id || !$aksi) {
        $error = 'Data tidak valid.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT p.*, b.stok_tersedia FROM peminjaman p JOIN barang b ON p.barang_id = b.id WHERE p.id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $pinjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$pinjam) {
            $error = 'Data peminjaman tidak ditemukan.';
        } elseif ($aksi === 'setuju' && $pinjam['status'] === 'menunggu') {
            // Kurangi stok, set status dipinjam
            $stmt = mysqli_prepare($conn, "UPDATE barang SET stok_tersedia = stok_tersedia - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $pinjam['jumlah'], $pinjam['barang_id']);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE peminjaman SET status = 'dipinjam', catatan_admin = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $catatan, $id);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            $success = 'Peminjaman berhasil disetujui.';

        } elseif ($aksi === 'tolak' && $pinjam['status'] === 'menunggu') {
            $stmt = mysqli_prepare($conn, "UPDATE peminjaman SET status = 'ditolak', catatan_admin = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $catatan, $id);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            $success = 'Peminjaman berhasil ditolak.';

        } elseif ($aksi === 'kembali' && in_array($pinjam['status'], ['dipinjam', 'disetujui'])) {
            // Admin bisa ubah tanggal kembali aktual (bisa lebih awal dari rencana)
            $tgl_aktual = !empty($tgl_kembali_custom) ? $tgl_kembali_custom : date('Y-m-d');

            // Hitung denda berdasarkan keterlambatan
            $denda_calc = hitungDenda($pinjam['tgl_kembali_rencana'], $tgl_aktual, $pinjam['denda_per_hari']);
            $total_denda = $denda_calc['total_denda'];

            $stmt = mysqli_prepare($conn, "UPDATE barang SET stok_tersedia = stok_tersedia + ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $pinjam['jumlah'], $pinjam['barang_id']);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($conn, "UPDATE peminjaman SET status = 'dikembalikan', tgl_kembali_aktual = ?, total_denda = ?, catatan_admin = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'sisi', $tgl_aktual, $total_denda, $catatan, $id);
            mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
            
            // Kunci akun jika ada denda yang belum dibayar
            kunciAkunJikaDenda($pinjam['user_id'], $id);
            
            $success = 'Barang berhasil ditandai dikembalikan pada ' . formatTanggal($tgl_aktual) . '.';
            if ($total_denda > 0) {
                $success .= ' <strong>Denda: ' . formatRupiah($total_denda) . '</strong>';
            }

        } else {
            $error = 'Aksi tidak valid atau status tidak sesuai.';
        }
    }
}

// Ambil semua yang masih aktif (menunggu, disetujui, dipinjam)
$list = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana,
           p.tgl_kembali_aktual, p.status, p.keperluan, p.catatan_admin, p.created_at
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.status IN ('menunggu', 'disetujui', 'dipinjam')
    ORDER BY FIELD(p.status, 'menunggu', 'disetujui', 'dipinjam'), p.created_at ASC
");
$rows_data = [];
while ($r = mysqli_fetch_assoc($list)) $rows_data[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Persetujuan — <?= APP_NAME ?></title>
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
      <h1 class="topbar-title">Persetujuan Peminjaman</h1>
      <p class="topbar-sub">Setujui, tolak, atau tandai pengembalian barang lab.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
      <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="card-section">
    <div class="card-section-header">
      <h6 class="card-section-title"><i class="ph-bold ph-list-checks"></i> Daftar Peminjaman Aktif</h6>
      <span style="font-size:12px;color:var(--text-secondary);"><?= count($rows_data) ?> data</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th class="ps-3">No</th>
            <th>Mahasiswa</th>
            <th>NIM</th>
            <th>Barang</th>
            <th>Jml</th>
            <th>Tgl Pinjam</th>
            <th>Tgl Kembali</th>
            <th>Keperluan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_data)): ?>
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="ph ph-check-circle" style="font-size:36px;display:block;margin-bottom:10px;color:#10b981;"></i>
                Tidak ada peminjaman yang perlu ditindaklanjuti.
              </td>
            </tr>
          <?php else: $no = 1; foreach ($rows_data as $row): ?>
          <tr>
            <td class="ps-3 text-muted"><?= $no++ ?></td>
            <td class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
            <td><code><?= htmlspecialchars($row['nim'] ?? '-') ?></code></td>
            <td>
              <?= htmlspecialchars($row['nama_barang']) ?><br>
              <small class="text-muted"><?= htmlspecialchars($row['kode_barang']) ?></small>
            </td>
            <td><?= $row['jumlah'] ?></td>
            <td><?= formatTanggal($row['tgl_pinjam']) ?></td>
            <td><?= formatTanggal($row['tgl_kembali_rencana']) ?></td>
            <td style="max-width:130px;" class="truncate" title="<?= htmlspecialchars($row['keperluan']) ?>">
              <?= htmlspecialchars($row['keperluan']) ?>
            </td>
            <td><?= badgeStatus($row['status']) ?></td>
            <td>
              <?php if ($row['status'] === 'menunggu'): ?>
                <button class="btn btn-sm btn-success me-1 mb-1" style="font-size:12px;"
                  onclick="bukaModal(<?= $row['id'] ?>, 'setuju', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>', '<?= addslashes(htmlspecialchars($row['nama_barang'])) ?>')">
                  <i class="ph-bold ph-check"></i> Setuju
                </button>
                <button class="btn btn-sm btn-danger mb-1" style="font-size:12px;"
                  onclick="bukaModal(<?= $row['id'] ?>, 'tolak', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>', '<?= addslashes(htmlspecialchars($row['nama_barang'])) ?>')">
                  <i class="ph-bold ph-x"></i> Tolak
                </button>
              <?php elseif (in_array($row['status'], ['dipinjam', 'disetujui'])): ?>
                <button class="btn btn-sm btn-primary" style="font-size:12px;"
                  onclick="bukaModal(<?= $row['id'] ?>, 'kembali', '<?= addslashes(htmlspecialchars($row['nama_lengkap'])) ?>', '<?= addslashes(htmlspecialchars($row['nama_barang'])) ?>', '<?= $row['tgl_kembali_rencana'] ?>')">
                  <i class="ph-bold ph-arrow-counter-clockwise"></i> Kembalikan
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="approval.php" id="formAksi">
        <input type="hidden" name="id"   id="inputId">
        <input type="hidden" name="aksi" id="inputAksi">
        <div class="modal-header">
          <h6 class="modal-title d-flex align-items-center gap-2" id="modalJudul">Konfirmasi</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p id="modalDeskripsi" style="font-size:14px;margin-bottom:16px;"></p>

          <!-- Hanya muncul untuk aksi kembali -->
          <div id="fieldTglKembali" style="display:none;margin-bottom:16px;">
            <label class="form-label">
              <i class="ph ph-calendar" style="color:var(--primary);"></i>
              Tanggal Kembali Aktual
            </label>
            <input type="date" name="tgl_kembali_aktual" id="inputTglKembali" class="form-control">
            <div style="font-size:12px;color:var(--text-secondary);margin-top:5px;">
              <i class="ph ph-info"></i>
              Bisa diisi tanggal lebih awal jika mahasiswa sudah mengembalikan sebelum jadwal.
              Kosongkan untuk memakai tanggal hari ini (<?= date('d/m/Y') ?>).
            </div>
          </div>

          <div>
            <label class="form-label">Catatan Admin <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea name="catatan_admin" id="inputCatatan" class="form-control" rows="2"
              placeholder="Tambahkan catatan jika perlu..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm" id="btnKonfirmasi">Konfirmasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaModal(id, aksi, nama, barang, tglRencana) {
  document.getElementById('inputId').value   = id;
  document.getElementById('inputAksi').value = aksi;
  document.getElementById('inputCatatan').value = '';

  const cfg = {
    setuju:  { judul:'Setujui Peminjaman',    warna:'btn-success', icon:'ph-check-circle',          teks:`Setujui peminjaman <strong>${barang}</strong> oleh <strong>${nama}</strong>?` },
    tolak:   { judul:'Tolak Peminjaman',       warna:'btn-danger',  icon:'ph-x-circle',              teks:`Tolak peminjaman <strong>${barang}</strong> oleh <strong>${nama}</strong>?` },
    kembali: { judul:'Tandai Dikembalikan',    warna:'btn-primary', icon:'ph-arrow-counter-clockwise', teks:`Tandai <strong>${barang}</strong> milik <strong>${nama}</strong> sudah dikembalikan?` },
  };
  const c = cfg[aksi];

  document.getElementById('modalJudul').innerHTML    = `<i class="ph-bold ${c.icon}"></i> ${c.judul}`;
  document.getElementById('modalDeskripsi').innerHTML = c.teks;

  const fieldTgl = document.getElementById('fieldTglKembali');
  if (aksi === 'kembali') {
    fieldTgl.style.display = 'block';
    // Default ke tanggal hari ini
    document.getElementById('inputTglKembali').value = '<?= date('Y-m-d') ?>';
    document.getElementById('inputTglKembali').max   = '<?= date('Y-m-d') ?>';
  } else {
    fieldTgl.style.display = 'none';
    document.getElementById('inputTglKembali').value = '';
  }

  const btn = document.getElementById('btnKonfirmasi');
  btn.className   = `btn btn-sm ${c.warna}`;
  btn.textContent = 'Konfirmasi';

  new bootstrap.Modal(document.getElementById('modalKonfirmasi')).show();
}
</script>
</body>
</html>
