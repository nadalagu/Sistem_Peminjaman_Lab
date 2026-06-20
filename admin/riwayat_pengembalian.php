<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

// Ambil semua yang dikembalikan ATAU disetujui/dipinjam yang sudah punya tgl_kembali_aktual
$list = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana,
           p.tgl_kembali_aktual, p.status, p.keperluan, p.catatan_admin, p.created_at
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.status = 'dikembalikan'
    ORDER BY p.tgl_kembali_aktual DESC
");
$total = mysqli_num_rows($list);
$rows_data = [];
while ($r = mysqli_fetch_assoc($list)) $rows_data[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Pengembalian — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    /* Fix DataTables sort icon & numbering column */
    table.dataTable thead .sorting::before,
    table.dataTable thead .sorting::after,
    table.dataTable thead .sorting_asc::before,
    table.dataTable thead .sorting_asc::after,
    table.dataTable thead .sorting_desc::before,
    table.dataTable thead .sorting_desc::after { display: none !important; }
    table.dataTable thead th { padding-right: 14px !important; }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div>
      <h1 class="topbar-title">Riwayat Pengembalian</h1>
      <p class="topbar-sub">Total <strong><?= $total ?></strong> barang telah dikembalikan.</p>
    </div>
    <a href="laporan_pdf.php?jenis=pengembalian" target="_blank"
       class="btn btn-danger d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;">
      <i class="ph-bold ph-file-pdf"></i> Export PDF
    </a>
  </div>

  <div class="card-section">
    <div class="card-section-header">
      <h6 class="card-section-title"><i class="ph-bold ph-arrow-counter-clockwise"></i> Data Pengembalian</h6>
    </div>
    <div class="table-responsive" style="padding:12px;">
      <table class="table table-hover mb-0" id="tblKembali" style="width:100%">
        <thead>
          <tr>
            <th style="width:40px">No</th>
            <th>Mahasiswa</th>
            <th>NIM</th>
            <th>Barang</th>
            <th>Jml</th>
            <th>Tgl Pinjam</th>
            <th>Rencana Kembali</th>
            <th>Kembali Aktual</th>
            <th>Keterangan</th>
            <th>Catatan Admin</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows_data as $row):
            $tgl_rencana = !empty($row['tgl_kembali_rencana']) ? strtotime($row['tgl_kembali_rencana']) : null;
            $tgl_aktual  = !empty($row['tgl_kembali_aktual'])  ? strtotime($row['tgl_kembali_aktual'])  : null;
            $lebih_awal  = ($tgl_aktual && $tgl_rencana && $tgl_aktual < $tgl_rencana);
            $terlambat   = ($tgl_aktual && $tgl_rencana && $tgl_aktual > $tgl_rencana);
          ?>
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
            <td><strong><?= $tgl_aktual ? formatTanggal($row['tgl_kembali_aktual']) : '-' ?></strong></td>
            <td>
              <?php if ($lebih_awal): ?>
                <span class="badge" style="background:#d1fae5;color:#065f46;">
                  <i class="ph ph-arrow-up"></i> Lebih Awal
                </span>
              <?php elseif ($terlambat): ?>
                <span class="badge" style="background:#fee2e2;color:#991b1b;">
                  <i class="ph ph-warning"></i> Terlambat
                </span>
              <?php elseif ($tgl_aktual): ?>
                <span class="badge" style="background:#e0e7ff;color:#4338ca;">Tepat Waktu</span>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-secondary)">
              <?= htmlspecialchars($row['catatan_admin'] ?? '-') ?>
            </td>
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
var table = $('#tblKembali').DataTable({
  language: {
    emptyTable:     "Tidak ada data yang tersedia",
    info:           "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
    infoEmpty:      "Menampilkan 0 sampai 0 dari 0 entri",
    infoFiltered:   "(disaring dari _MAX_ entri keseluruhan)",
    lengthMenu:     "Tampilkan _MENU_ entri",
    loadingRecords: "Memuat...",
    processing:     "Memproses...",
    search:         "Cari:",
    zeroRecords:    "Tidak ditemukan data yang sesuai",
    paginate: {
      first:    "Pertama",
      last:     "Terakhir",
      next:     "Selanjutnya",
      previous: "Sebelumnya"
    }
  },
  pageLength: 15,
  order: [[7, 'desc']],
  columnDefs: [
    { targets: 0, orderable: false, searchable: false }
  ]
});

// Nomor urut otomatis, tidak terpengaruh sort/pagination
table.on('draw', function() {
  table.column(0, { page: 'current' }).nodes().each(function(cell, i) {
    cell.innerHTML = table.page.info().start + i + 1;
  });
}).draw();
</script>
</body>
</html>
