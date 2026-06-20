<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $kode      = bersihkan($_POST['kode_barang'] ?? '');
    $nama      = bersihkan($_POST['nama_barang'] ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $stok      = (int)($_POST['stok_total'] ?? 0);
    $kondisi   = bersihkan($_POST['kondisi'] ?? 'baik');

    if (!$kode || !$nama || $stok < 1) {
        $error = 'Kode, nama, dan stok wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO barang (kode_barang, nama_barang, deskripsi, stok_total, stok_tersedia, kondisi) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssiiss', $kode, $nama, $deskripsi, $stok, $stok, $kondisi);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Barang <strong>$nama</strong> berhasil ditambahkan.";
        } else {
            $error = 'Gagal menambah barang. Kode mungkin sudah digunakan.';
        }
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
    $id        = (int)($_POST['id'] ?? 0);
    $nama      = bersihkan($_POST['nama_barang'] ?? '');
    $deskripsi = bersihkan($_POST['deskripsi'] ?? '');
    $stok      = (int)($_POST['stok_total'] ?? 0);
    $kondisi   = bersihkan($_POST['kondisi'] ?? 'baik');

    $stmt = mysqli_prepare($conn, "UPDATE barang SET nama_barang=?, deskripsi=?, stok_total=?, kondisi=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssisi', $nama, $deskripsi, $stok, $kondisi, $id);
    if (mysqli_stmt_execute($stmt)) { $success = 'Data barang berhasil diperbarui.'; }
    else { $error = 'Gagal memperbarui data.'; }
    mysqli_stmt_close($stmt);
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = mysqli_prepare($conn, "DELETE FROM barang WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = 'Barang berhasil dihapus.';
}

$barang_list = mysqli_query($conn, "SELECT * FROM barang ORDER BY kode_barang ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventaris — <?= APP_NAME ?></title>
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
      <h1 class="topbar-title">Inventaris Barang</h1>
      <p class="topbar-sub">Kelola data barang dan peralatan laboratorium.</p>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;"
      data-bs-toggle="modal" data-bs-target="#modalTambah">
      <i class="ph-bold ph-plus-circle"></i> Tambah Barang
    </button>
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

  <div class="card-section">
    <div class="card-section-header">
      <h6 class="card-section-title"><i class="ph-bold ph-package"></i> Daftar Barang Lab</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th class="ps-3">Kode</th>
            <th>Nama Barang</th>
            <th>Stok Total</th>
            <th>Stok Tersedia</th>
            <th>Kondisi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($barang_list) > 0): ?>
            <?php while ($b = mysqli_fetch_assoc($barang_list)): ?>
            <tr>
              <td class="ps-3"><code><?= htmlspecialchars($b['kode_barang']) ?></code></td>
              <td>
                <span class="fw-semibold"><?= htmlspecialchars($b['nama_barang']) ?></span>
                <?php if ($b['deskripsi']): ?>
                  <br><small class="text-muted"><?= htmlspecialchars($b['deskripsi']) ?></small>
                <?php endif; ?>
              </td>
              <td><?= $b['stok_total'] ?></td>
              <td>
                <span class="fw-semibold" style="color:<?= $b['stok_tersedia'] == 0 ? '#ef4444' : '#10b981' ?>">
                  <?= $b['stok_tersedia'] ?>
                </span>
                <?php if ($b['stok_tersedia'] == 0): ?>
                  <span class="badge ms-1" style="background:#fee2e2;color:#991b1b;font-size:10px;">Habis</span>
                <?php endif; ?>
              </td>
              <td><?= badgeKondisi($b['kondisi']) ?></td>
              <td>
                <button class="btn btn-sm me-1" style="background:#eff6ff;color:#3b82f6;font-size:12px;padding:4px 10px;border:none;"
                  onclick="bukaEdit(<?= htmlspecialchars(json_encode($b)) ?>)">
                  <i class="ph-bold ph-pencil-simple"></i> Edit
                </button>
                <a href="?hapus=<?= $b['id'] ?>" class="btn btn-sm" style="background:#fff1f2;color:#ef4444;font-size:12px;padding:4px 10px;border:none;"
                  onclick="return confirm('Yakin hapus barang ini?')">
                  <i class="ph-bold ph-trash"></i> Hapus
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
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

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="aksi" value="tambah">
        <div class="modal-header">
          <h6 class="modal-title d-flex align-items-center gap-2"><i class="ph-bold ph-plus-circle" style="color:var(--primary)"></i> Tambah Barang</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Kode Barang</label>
            <input type="text" name="kode_barang" class="form-control" placeholder="Contoh: ALT-011" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea name="deskripsi" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Stok Total</label>
            <input type="number" name="stok_total" class="form-control" min="1" required>
          </div>
          <div>
            <label class="form-label">Kondisi</label>
            <select name="kondisi" class="form-select">
              <option value="baik">Baik</option>
              <option value="rusak ringan">Rusak Ringan</option>
              <option value="rusak berat">Rusak Berat</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm">Simpan Barang</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="aksi" value="edit">
        <input type="hidden" name="id" id="editId">
        <div class="modal-header">
          <h6 class="modal-title d-flex align-items-center gap-2"><i class="ph-bold ph-pencil-simple" style="color:var(--primary)"></i> Edit Barang</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" id="editNama" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" id="editDeskripsi" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Stok Total</label>
            <input type="number" name="stok_total" id="editStok" class="form-control" min="1" required>
          </div>
          <div>
            <label class="form-label">Kondisi</label>
            <select name="kondisi" id="editKondisi" class="form-select">
              <option value="baik">Baik</option>
              <option value="rusak ringan">Rusak Ringan</option>
              <option value="rusak berat">Rusak Berat</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function bukaEdit(b) {
  document.getElementById('editId').value        = b.id;
  document.getElementById('editNama').value      = b.nama_barang;
  document.getElementById('editDeskripsi').value = b.deskripsi || '';
  document.getElementById('editStok').value      = b.stok_total;
  document.getElementById('editKondisi').value   = b.kondisi;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
</body>
</html>
