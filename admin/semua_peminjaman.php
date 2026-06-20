<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

$filter = bersihkan($_GET['status'] ?? '');
$where  = $filter ? "WHERE p.status = '" . mysqli_real_escape_string($conn, $filter) . "'" : '';

$list = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana,
           p.tgl_kembali_aktual, p.status, p.keperluan, p.catatan_admin, p.created_at
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    $where
    ORDER BY p.created_at DESC
");
$rows_data = [];
while ($r = mysqli_fetch_assoc($list)) $rows_data[] = $r;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Semua Peminjaman — <?= APP_NAME ?></title>
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
        <h1 class="topbar-title">Semua Peminjaman</h1>
        <p class="topbar-sub">Kelola dan pantau seluruh data peminjaman.</p>
      </div>
      <a href="laporan_pdf.php" class="btn btn-danger d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;">
        <i class="ph-bold ph-file-pdf"></i> Cetak Laporan
      </a>
    </div>

    <!-- Filter Pills -->
    <div class="mb-4 d-flex gap-2 flex-wrap">
      <a href="semua_peminjaman.php" class="filter-pill <?= !$filter ? 'active' : '' ?>">
        <i class="ph ph-list"></i> Semua
      </a>
      <a href="?status=menunggu" class="filter-pill <?= $filter === 'menunggu'     ? 'active' : '' ?>">
        <i class="ph ph-clock-countdown"></i> Menunggu
      </a>
      <a href="?status=disetujui" class="filter-pill <?= $filter === 'disetujui'    ? 'active' : '' ?>">
        <i class="ph ph-thumbs-up"></i> Disetujui
      </a>
      <a href="?status=dipinjam" class="filter-pill <?= $filter === 'dipinjam'     ? 'active' : '' ?>">
        <i class="ph ph-wrench"></i> Dipinjam
      </a>
      <a href="?status=dikembalikan" class="filter-pill <?= $filter === 'dikembalikan' ? 'active' : '' ?>">
        <i class="ph ph-check-circle"></i> Dikembalikan
      </a>
      <a href="?status=ditolak" class="filter-pill <?= $filter === 'ditolak'      ? 'active' : '' ?>">
        <i class="ph ph-x-circle"></i> Ditolak
      </a>
      <a href="?status=dibatalkan" class="filter-pill <?= $filter === 'dibatalkan'   ? 'active' : '' ?>">
        <i class="ph ph-prohibit"></i> Dibatalkan
      </a>
    </div>

    <div class="card-section">
      <div class="card-section-header">
        <h6 class="card-section-title"><i class="ph-bold ph-clipboard-text"></i> Data Peminjaman</h6>
      </div>
      <div class="table-responsive" style="padding:12px;">
        <table class="table table-hover mb-0" id="tblPeminjaman" style="width:100%">
          <thead>
            <tr>
              <th style="width:40px">No</th>
              <th>Mahasiswa</th>
              <th>NIM</th>
              <th>Barang</th>
              <th>Jml</th>
              <th>Tgl Pinjam</th>
              <th>Tgl Kembali</th>
              <th>Kembali Aktual</th>
              <th>Status</th>
              <th>Catatan Admin</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows_data as $row): ?>
              <tr>
                <td class="text-muted"></td>
                <td class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                <td><code><?= htmlspecialchars($row['nim'] ?? '-') ?></code></td>
                <td>
                  <?= htmlspecialchars($row['nama_barang']) ?><br>
                  <small class="text-muted"><?= htmlspecialchars($row['kode_barang']) ?></small>
                </td>
                <td><?= $row['jumlah'] ?></td>
                <td><?= formatTanggal($row['tgl_pinjam']) ?></td>
                <td><?= formatTanggal($row['tgl_kembali_rencana']) ?></td>
                <td><?= $row['tgl_kembali_aktual'] ? formatTanggal($row['tgl_kembali_aktual']) : '<span class="text-muted">-</span>' ?></td>
                <td><?= badgeStatus($row['status']) ?></td>
                <td style="font-size:12px;color:var(--text-secondary)"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    var table = $('#tblPeminjaman').DataTable({
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
      }]
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