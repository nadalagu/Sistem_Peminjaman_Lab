<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

$jenis  = $_GET['jenis']  ?? 'peminjaman';
$dari   = $_GET['dari']   ?? '';
$sampai = $_GET['sampai'] ?? '';

$where = "WHERE 1=1";
if ($jenis === 'pengembalian') {
    $where .= " AND p.status = 'dikembalikan'";
}
if ($dari)   $where .= " AND DATE(p.created_at) >= '" . mysqli_real_escape_string($conn, $dari) . "'";
if ($sampai) $where .= " AND DATE(p.created_at) <= '" . mysqli_real_escape_string($conn, $sampai) . "'";

$result = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana,
           p.tgl_kembali_aktual, p.status, p.keperluan, p.catatan_admin, p.created_at
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    $where
    ORDER BY p.created_at DESC
");

$judul = $jenis === 'pengembalian' ? 'Laporan Pengembalian Alat' : 'Laporan Peminjaman Alat';
$rows_data = [];
while ($r = mysqli_fetch_assoc($result)) $rows_data[] = $r;
$total = count($rows_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $judul ?> — <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    /* ====== PRINT STYLES ====== */
    @media print {
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

      body { margin: 0; padding: 0; background: #fff !important; font-size: 11px; font-family: Arial, sans-serif; }

      .sidebar, .no-print { display: none !important; }

      .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
      }

      .print-area {
        display: block !important;
        padding: 16px 20px;
      }

      .print-header {
        text-align: center;
        border-bottom: 2px solid #333;
        padding-bottom: 10px;
        margin-bottom: 14px;
      }
      .print-header h2 { font-size: 15px; margin: 0 0 3px 0; font-weight: 700; }
      .print-header h3 { font-size: 13px; margin: 0 0 3px 0; font-weight: 600; }
      .print-header p  { font-size: 10px; margin: 0; color: #555; }

      .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-top: 10px;
      }
      .print-table th {
        background: #1e293b !important;
        color: #fff !important;
        padding: 6px 7px;
        text-align: left;
        font-size: 9.5px;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        white-space: nowrap;
      }
      .print-table td {
        padding: 5px 7px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
        word-break: break-word;
      }
      .print-table tbody tr:nth-child(even) td { background: #f8fafc !important; }

      .badge-print {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 700;
        white-space: nowrap;
      }
      .badge-menunggu    { background:#fef3c7!important; color:#92400e!important; }
      .badge-disetujui   { background:#cffafe!important; color:#0e7490!important; }
      .badge-dipinjam    { background:#e0e7ff!important; color:#3730a3!important; }
      .badge-dikembalikan{ background:#d1fae5!important; color:#065f46!important; }
      .badge-ditolak     { background:#fee2e2!important; color:#991b1b!important; }

      .print-footer {
        margin-top: 14px;
        font-size: 10px;
        color: #555;
        border-top: 1px solid #ccc;
        padding-top: 8px;
        display: flex;
        justify-content: space-between;
      }

      /* Kolom khusus pengembalian */
      .col-no    { width: 22px; }
      .col-nama  { width: 90px; }
      .col-nim   { width: 65px; }
      .col-barang{ width: 100px; }
      .col-jml   { width: 26px; text-align:center; }
      .col-tgl   { width: 52px; white-space: nowrap; }
      .col-status{ width: 62px; }
      .col-catatan{ width: 80px; }
    }

    /* ====== SCREEN STYLES (halaman preview) ====== */
    @media screen {
      .print-area { display: none; }
      .screen-area { display: block; }
    }
    @media print {
      .screen-area { display: none !important; }
      .print-area  { display: block !important; }
    }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">

  <!-- ===== SCREEN: UI Filter & Preview ===== -->
  <div class="screen-area">
    <div class="topbar no-print">
      <div>
        <h1 class="topbar-title"><?= $judul ?></h1>
        <p class="topbar-sub">Atur filter lalu klik <strong>Cetak / Save PDF</strong>.</p>
      </div>
      <div class="d-flex gap-2">
        <button onclick="window.print()"
          class="btn btn-danger d-flex align-items-center gap-2"
          style="border-radius:10px;padding:10px 18px;">
          <i class="ph-bold ph-printer"></i> Cetak / Save PDF
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="card-section mb-4 no-print">
      <div class="card-section-header">
        <h6 class="card-section-title"><i class="ph-bold ph-funnel"></i> Filter Laporan</h6>
      </div>
      <div class="p-3">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Jenis Laporan</label>
            <select name="jenis" class="form-select">
              <option value="peminjaman"   <?= $jenis==='peminjaman'   ? 'selected':'' ?>>Semua Peminjaman</option>
              <option value="pengembalian" <?= $jenis==='pengembalian' ? 'selected':'' ?>>Pengembalian Saja</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
              <i class="ph-bold ph-magnifying-glass"></i> Tampilkan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Preview tabel di layar -->
    <div class="card-section">
      <div class="card-section-header">
        <h6 class="card-section-title">
          <i class="ph-bold ph-file-text"></i> Preview — <?= $total ?> data
        </h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:12.5px;">
          <thead>
            <tr>
              <th class="ps-3">No</th>
              <th>Mahasiswa</th>
              <th>NIM</th>
              <th>Barang</th>
              <th>Jml</th>
              <th>Tgl Pinjam</th>
              <th>Rencana Kembali</th>
              <?php if ($jenis==='pengembalian'): ?><th>Kembali Aktual</th><?php endif; ?>
              <th>Status</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows_data)): ?>
              <tr><td colspan="10" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
            <?php else: $no=1; foreach ($rows_data as $row): ?>
            <tr>
              <td class="ps-3 text-muted"><?= $no++ ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td><code><?= htmlspecialchars($row['nim'] ?? '-') ?></code></td>
              <td><?= htmlspecialchars($row['nama_barang']) ?><br><small class="text-muted"><?= htmlspecialchars($row['kode_barang']) ?></small></td>
              <td><?= $row['jumlah'] ?></td>
              <td><?= formatTanggal($row['tgl_pinjam']) ?></td>
              <td><?= formatTanggal($row['tgl_kembali_rencana']) ?></td>
              <?php if ($jenis==='pengembalian'): ?>
              <td><?= $row['tgl_kembali_aktual'] ? formatTanggal($row['tgl_kembali_aktual']) : '-' ?></td>
              <?php endif; ?>
              <td><?= badgeStatus($row['status']) ?></td>
              <td style="max-width:120px;font-size:12px;color:var(--text-secondary)"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <div style="padding:10px 22px;font-size:12px;color:var(--text-secondary);border-top:1px solid var(--border);">
        Dicetak oleh: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> pada <?= date('d/m/Y H:i') ?>
      </div>
    </div>
  </div><!-- /screen-area -->


  <!-- ===== PRINT AREA: yang benar-benar dicetak ===== -->
  <div class="print-area">
    <div class="print-header">
      <h2><?= APP_NAME ?></h2>
      <h3><?= $judul ?></h3>
      <p>
        <?php if ($dari || $sampai): ?>
          Periode: <?= $dari ? formatTanggal($dari) : '...' ?> s/d <?= $sampai ? formatTanggal($sampai) : '...' ?> &nbsp;|&nbsp;
        <?php endif; ?>
        Total: <?= $total ?> data &nbsp;|&nbsp; Dicetak: <?= date('d/m/Y H:i') ?>
      </p>
    </div>

    <table class="print-table">
      <thead>
        <tr>
          <th class="col-no">#</th>
          <th class="col-nama">Mahasiswa</th>
          <th class="col-nim">NIM</th>
          <th class="col-barang">Barang</th>
          <th class="col-jml">Jml</th>
          <th class="col-tgl">Tgl Pinjam</th>
          <th class="col-tgl">Rencana Kembali</th>
          <?php if ($jenis==='pengembalian'): ?>
          <th class="col-tgl">Kembali Aktual</th>
          <?php endif; ?>
          <th class="col-status">Status</th>
          <th class="col-catatan">Catatan Admin</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows_data)): ?>
          <tr><td colspan="10" style="text-align:center;padding:20px;color:#888;">Tidak ada data.</td></tr>
        <?php else: $no=1; foreach ($rows_data as $row):
          $badge_class = 'badge-' . $row['status'];
          $badge_label = [
            'menunggu'=>'Menunggu','disetujui'=>'Disetujui','dipinjam'=>'Dipinjam',
            'dikembalikan'=>'Dikembalikan','ditolak'=>'Ditolak'
          ][$row['status']] ?? ucfirst($row['status']);
        ?>
        <tr>
          <td class="col-no"><?= $no++ ?></td>
          <td class="col-nama"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
          <td class="col-nim"><?= htmlspecialchars($row['nim'] ?? '-') ?></td>
          <td class="col-barang">
            <?= htmlspecialchars($row['nama_barang']) ?>
            <br><span style="color:#888;font-size:9px;"><?= htmlspecialchars($row['kode_barang']) ?></span>
          </td>
          <td class="col-jml" style="text-align:center;"><?= $row['jumlah'] ?></td>
          <td class="col-tgl"><?= formatTanggal($row['tgl_pinjam']) ?></td>
          <td class="col-tgl"><?= formatTanggal($row['tgl_kembali_rencana']) ?></td>
          <?php if ($jenis==='pengembalian'): ?>
          <td class="col-tgl"><?= $row['tgl_kembali_aktual'] ? formatTanggal($row['tgl_kembali_aktual']) : '-' ?></td>
          <?php endif; ?>
          <td class="col-status">
            <span class="badge-print <?= $badge_class ?>"><?= $badge_label ?></span>
          </td>
          <td class="col-catatan"><?= htmlspecialchars($row['catatan_admin'] ?? '-') ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="print-footer">
      <span>Dicetak oleh: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></span>
      <span><?= APP_NAME ?> — <?= date('d/m/Y H:i') ?></span>
    </div>
  </div><!-- /print-area -->

</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
