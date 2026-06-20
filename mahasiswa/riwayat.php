<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireMahasiswa();
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id) {
    $stmt = mysqli_prepare(
      $conn,
      "UPDATE peminjaman SET status = 'dibatalkan' 
             WHERE id = ? AND user_id = ? AND status = 'menunggu'"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }
  header('Location: riwayat.php');
  exit;
}
$stmt = mysqli_prepare($conn, "
    SELECT p.id, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana,
           p.tgl_kembali_aktual, p.status, p.keperluan, p.catatan_admin, p.created_at
    FROM peminjaman p JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = ? ORDER BY p.created_at DESC
");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$rows_data = [];
while ($r = mysqli_fetch_assoc($result)) $rows_data[] = $r;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Peminjaman — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    table.dataTable thead .sorting::before,
    table.dataTable thead .sorting::after,
    table.dataTable thead .sorting_asc::before,
    table.dataTable thead .sorting_asc::after,
    table.dataTable thead .sorting_desc::before,
    table.dataTable thead .sorting_desc::after {
      display: none !important;
    }

    table.dataTable thead th {
      padding-right: 14px !important;
    }
  </style>
</head>

<body>
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div>
        <h1 class="topbar-title">Riwayat Peminjaman</h1>
        <p class="topbar-sub">Lihat semua history peminjaman Anda.</p>
      </div>
    </div>

    <div class="card-section">
      <div class="card-section-header">
        <h6 class="card-section-title"><i class="ph-bold ph-clock-counter-clockwise"></i> Semua Riwayat</h6>
      </div>
      <div class="table-responsive" style="padding:12px;">
        <table class="table table-hover mb-0" id="tblRiwayat" style="width:100%">
          <thead>
            <tr>
              <th style="width:40px">No</th>
              <th>Barang</th>
              <th>Jml</th>
              <th>Tgl Pinjam</th>
              <th>Rencana Kembali</th>
              <th>Kembali Aktual</th>
              <th>Status</th>
              <th>Catatan Admin</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows_data as $r): ?>
              <tr>
                <td class="text-muted"></td>
                <td>
                  <span class="fw-semibold"><?= htmlspecialchars($r['nama_barang']) ?></span><br>
                  <code style="font-size:10px;"><?= htmlspecialchars($r['kode_barang']) ?></code>
                </td>
                <td><?= $r['jumlah'] ?></td>
                <td data-order="<?= $r['tgl_pinjam'] ?>"><?= formatTanggal($r['tgl_pinjam']) ?></td>
                <td><?= formatTanggal($r['tgl_kembali_rencana']) ?></td>
                <td><?= $r['tgl_kembali_aktual'] ? formatTanggal($r['tgl_kembali_aktual']) : '<span class="text-muted">-</span>' ?></td>
                <td><?= badgeStatus($r['status']) ?></td>
                <td style="font-size:12px;color:var(--text-secondary);"><?= htmlspecialchars($r['catatan_admin'] ?? '-') ?></td>
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
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="modal fade" id="modalBatal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="riwayat.php">
          <input type="hidden" name="id" id="batalId">
          <input type="hidden" name="aksi" value="batal">
          <div class="modal-header">
            <h6 class="modal-title d-flex align-items-center gap-2">
              <i class="ph-bold ph-x-circle"></i> Batalkan Peminjaman
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="batalDeskripsi" style="font-size:14px;"></p>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    function bukaBatalModal(id, barang) {
      document.getElementById('batalId').value = id;
      document.getElementById('batalDeskripsi').innerHTML =
        `Apakah Anda yakin ingin membatalkan peminjaman <strong>${barang}</strong>?`;
      new bootstrap.Modal(document.getElementById('modalBatal')).show();
    }
    var table = $('#tblRiwayat').DataTable({
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
      pageLength: 10,
      order: [
        [3, 'desc']
      ],
      columnDefs: [{
          targets: 0,
          orderable: false,
          searchable: false
        },
        {
          targets: -1,
          orderable: false,
          searchable: false
        } // kolom Aksi
      ]
    });
    table.on('draw', function() {
      table.column(0, {
        page: 'current'
      }).nodes().each(function(cell, i) {
        cell.innerHTML = table.page.info().start + i + 1;
      });
    }).draw();
  </script>
</body>

</html>