<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

$success = '';
$error   = '';

// Tambah user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
  $username     = bersihkan($_POST['username'] ?? '');
  $nama_lengkap = bersihkan($_POST['nama_lengkap'] ?? '');
  $nim          = bersihkan($_POST['nim'] ?? '');
  $no_hp        = bersihkan($_POST['no_hp'] ?? '');
  $email        = bersihkan($_POST['email'] ?? '');
  $role         = bersihkan($_POST['role'] ?? 'mahasiswa');
  $password     = $_POST['password'] ?? '';

  if (!$username || !$nama_lengkap || !$password) {
    $error = 'Username, nama lengkap, dan password wajib diisi.';
  } elseif (strlen($password) < 6) {
    $error = 'Password minimal 6 karakter.';
  } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid.';
  } else {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, nama_lengkap, nim, no_hp, email, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sssssss', $username, $hash, $nama_lengkap, $nim, $no_hp, $email, $role);
    if (mysqli_stmt_execute($stmt)) {
      $success = "User <strong>$nama_lengkap</strong> berhasil ditambahkan.";
    } else {
      $error = 'Gagal menambah user. Username mungkin sudah digunakan.';
    }
    mysqli_stmt_close($stmt);
  }
}

// Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'edit') {
  $id           = (int)($_POST['id'] ?? 0);
  $nama_lengkap = bersihkan($_POST['nama_lengkap'] ?? '');
  $nim          = bersihkan($_POST['nim'] ?? '');
  $no_hp        = bersihkan($_POST['no_hp'] ?? '');
  $email        = bersihkan($_POST['email'] ?? '');
  $role         = bersihkan($_POST['role'] ?? 'mahasiswa');
  $password     = $_POST['password'] ?? '';

  if (!$nama_lengkap) {
    $error = 'Nama lengkap wajib diisi.';
  } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Format email tidak valid.';
  } else {
    if (!empty($password)) {
      if (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
      } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap=?, nim=?, no_hp=?, email=?, role=?, password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $nama_lengkap, $nim, $no_hp, $email, $role, $hash, $id);
        if (mysqli_stmt_execute($stmt)) {
          $success = 'Data user berhasil diperbarui (termasuk password).';
        } else {
          $error = 'Gagal memperbarui data.';
        }
        mysqli_stmt_close($stmt);
      }
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap=?, nim=?, no_hp=?, email=?, role=? WHERE id=?");
      mysqli_stmt_bind_param($stmt, 'sssssi', $nama_lengkap, $nim, $no_hp, $email, $role, $id);
      if (mysqli_stmt_execute($stmt)) {
        $success = 'Data user berhasil diperbarui.';
      } else {
        $error = 'Gagal memperbarui data.';
      }
      mysqli_stmt_close($stmt);
    }
  }
}

// Hapus user
if (isset($_GET['hapus'])) {
  $id = (int)$_GET['hapus'];
  if ($id === (int)$_SESSION['user_id']) {
    $error = 'Tidak bisa menghapus akun yang sedang digunakan.';
  } else {
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = 'User berhasil dihapus.';
  }
}

// Buka kunci akun mahasiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'buka_kunci') {
  $id = (int)($_POST['id'] ?? 0);
  $stmt = mysqli_prepare(
    $conn,
    "UPDATE users SET akun_terkunci = 0, alasan_kunci = NULL WHERE id = ?"
  );
  mysqli_stmt_bind_param($stmt, 'i', $id);
  if (mysqli_stmt_execute($stmt)) {
    $success = 'Akun mahasiswa berhasil dibuka kembali.';
  } else {
    $error = 'Gagal membuka akun.';
  }
  mysqli_stmt_close($stmt);
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola User — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>

<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div>
        <h1 class="topbar-title">Kelola User</h1>
        <p class="topbar-sub">Tambah, edit, atau hapus pengguna sistem.</p>
      </div>
      <button class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;"
        data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="ph-bold ph-user-plus"></i> Tambah User
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
        <h6 class="card-section-title"><i class="ph-bold ph-users"></i> Daftar Pengguna</h6>
      </div>
      <div class="table-responsive p-2">
        <table class="table table-hover mb-0" id="tblUser" style="width:100%">
          <thead>
            <tr>
              <th class="ps-3">No</th>
              <th>Username</th>
              <th>Nama Lengkap</th>
              <th>NIM</th>
              <th>No. HP</th>
              <th>Role</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
              <tr>
                <td class="ps-3"></td>
                <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;background:<?= $u['role'] === 'admin' ? '#fef3c7' : 'var(--primary-light)' ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:<?= $u['role'] === 'admin' ? '#92400e' : 'var(--primary)' ?>;flex-shrink:0;">
                      <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                      <?php if ($u['id'] == $_SESSION['user_id']): ?>
                        <span class="badge" style="background:#e0e7ff;color:#4338ca;font-size:10px;">Anda</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= $u['nim'] ? htmlspecialchars($u['nim']) : '<span class="text-muted">-</span>' ?></td>
                <td><?= $u['no_hp'] ? htmlspecialchars($u['no_hp']) : '<span class="text-muted">-</span>' ?></td>
                <td>
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="badge" style="background:#fef3c7;color:#92400e;"><i class="ph ph-shield-check"></i> Admin</span>
                  <?php else: ?>
                    <span class="badge" style="background:var(--primary-light);color:var(--primary);"><i class="ph ph-graduation-cap"></i> Mahasiswa</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($u['role'] === 'mahasiswa' && $u['akun_terkunci'] == 1): ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;
                 border-radius:6px;font-size:12px;font-weight:600;
                 background:#fee2e2;color:#dc2626;">
                      <i class="ph-bold ph-lock-simple"></i> Terkunci
                    </span>
                  <?php else: ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;
                 border-radius:6px;font-size:12px;font-weight:600;
                 background:#d1fae5;color:#059669;">
                      <i class="ph-bold ph-lock-simple-open"></i> Aktif
                    </span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn btn-sm" style="background:#eff6ff;color:#3b82f6;font-size:12px;padding:6px 12px;border:none;margin-right:6px;"
                    onclick="bukaEdit(<?= htmlspecialchars(json_encode($u)) ?>)">
                    <i class="ph-bold ph-pencil-simple"></i> Edit
                  </button>
                  <?php if ($u['role'] === 'mahasiswa' && $u['akun_terkunci'] == 1): ?>
                    <button class="btn btn-sm" style="background:#fef3c7;color:#d97706;font-size:12px;padding:6px 12px;border:none;margin-right:6px;"
                      onclick="bukaKunci(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>')">
                      <i class="ph-bold ph-lock-simple-open"></i> Buka Kunci
                    </button>
                  <?php endif; ?>
                  <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <a href="?hapus=<?= $u['id'] ?>" class="btn btn-sm" style="background:#fff1f2;color:#ef4444;font-size:12px;padding:6px 12px;border:none;"
                      onclick="return confirm('Yakin hapus user <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>?')">
                      <i class="ph-bold ph-trash"></i> Hapus
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
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
            <h6 class="modal-title d-flex align-items-center gap-2"><i class="ph-bold ph-user-plus" style="color:var(--primary)"></i> Tambah User</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Unik, tanpa spasi">
              </div>
              <div class="col-12">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">NIM</label>
                <input type="text" name="nim" class="form-control" placeholder="Opsional">
              </div>
              <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control" placeholder="Opsional">
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Opsional (untuk notifikasi email)">
              </div>
              <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                  <option value="mahasiswa">Mahasiswa</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Min. 6 karakter">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary btn-sm">Tambah User</button>
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
            <h6 class="modal-title d-flex align-items-center gap-2"><i class="ph-bold ph-pencil-simple" style="color:var(--primary)"></i> Edit User</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="editNama" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">NIM</label>
                <input type="text" name="nim" id="editNim" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" id="editHp" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="editEmail" class="form-control" placeholder="Untuk notifikasi email">
              </div>
              <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role" id="editRole" class="form-select">
                  <option value="mahasiswa">Mahasiswa</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Password Baru <span class="text-muted fw-normal">(kosongkan jika tidak diubah)</span></label>
                <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter">
              </div>
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

  <!-- Modal Buka Kunci -->
  <div class="modal fade" id="modalBukaKunci" tabindex="-1">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="aksi" value="buka_kunci">
          <input type="hidden" name="id" id="bukaKunciId">
          <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
            <h6 class="modal-title d-flex align-items-center gap-2">
              <i class="ph-bold ph-lock-simple-open" style="color:#d97706;"></i> Buka Kunci Akun
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center" style="padding:24px 20px;">
            <div style="width:56px;height:56px;background:#fef3c7;border-radius:50%;
                      display:flex;align-items:center;justify-content:center;
                      margin:0 auto 14px;">
              <i class="ph-bold ph-lock-simple-open" style="font-size:28px;color:#d97706;"></i>
            </div>
            <div style="font-weight:600;font-size:14px;color:#0f172a;margin-bottom:6px;">
              Buka akun <span id="bukaKunciNama" style="color:#d97706;"></span>?
            </div>
            <div style="font-size:12.5px;color:#64748b;">
              Mahasiswa akan dapat login kembali setelah akun dibuka.
              Pastikan denda sudah dilunasi sebelum membuka akun.
            </div>
          </div>
          <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm"
              style="background:#d97706;color:#fff;border:none;padding:6px 16px;border-radius:8px;">
              <i class="ph-bold ph-lock-simple-open"></i> Buka Kunci
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    var tableUser = $('#tblUser').DataTable({
      language: {
        emptyTable: "Tidak ada data yang tersedia",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
        infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
        lengthMenu: "Tampilkan _MENU_ entri",
        loadingRecords: "Memuat...",
        processing: "Memproses...",
        search: "Cari:",
        zeroRecords: "Tidak ditemukan data yang sesuai",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        }
      },
      pageLength: 15,
      order: [
        [1, 'asc']
      ],
      columnDefs: [{
        targets: 0,
        orderable: false,
        searchable: false
      }, {
        targets: 7,
        orderable: false,
        searchable: false
      }]
    });
    tableUser.on('draw', function() {
      tableUser.column(0, {
        page: 'current'
      }).nodes().each(function(cell, i) {
        cell.innerHTML = tableUser.page.info().start + i + 1;
      });
    }).draw();

    function bukaEdit(u) {
      document.getElementById('editId').value = u.id;
      document.getElementById('editNama').value = u.nama_lengkap;
      document.getElementById('editNim').value = u.nim || '';
      document.getElementById('editHp').value = u.no_hp || '';
      document.getElementById('editEmail').value = u.email || '';
      document.getElementById('editRole').value = u.role;
      new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function bukaKunci(id, nama) {
      document.getElementById('bukaKunciId').value = id;
      document.getElementById('bukaKunciNama').textContent = nama;
      new bootstrap.Modal(document.getElementById('modalBukaKunci')).show();
    }
  </script>
</body>

</html>