<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/functions.php';

requireAdmin();

// Statistik utama
$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
      COUNT(*) AS total,
      SUM(status = 'menunggu')     AS menunggu,
      SUM(status = 'dipinjam')     AS dipinjam,
      SUM(status = 'dikembalikan') AS selesai,
      SUM(status = 'ditolak')      AS ditolak,
      SUM(status = 'dibatalkan')   AS dibatalkan
    FROM peminjaman
"));

$total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM barang"))['total'];
$total_user   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='mahasiswa'"))['total'];

// 5 peminjaman menunggu terbaru
$menunggu_list = mysqli_query($conn, "
    SELECT p.id, u.nama_lengkap, u.nim, b.nama_barang, b.kode_barang,
           p.jumlah, p.tgl_pinjam, p.tgl_kembali_rencana, p.status, p.created_at
    FROM peminjaman p
    JOIN users  u ON p.user_id   = u.id
    JOIN barang b ON p.barang_id = b.id
    WHERE p.status = 'menunggu'
    ORDER BY p.created_at ASC
    LIMIT 5
");

// Data grafik harian (30 hari terakhir)
$chart_hari         = [];
$chart_pinjam_hari  = [];
$chart_kembali_hari = [];
for ($i = 29; $i >= 0; $i--) {
  $tgl          = date('Y-m-d', strtotime("-$i days"));
  $chart_hari[] = date('d M', strtotime("-$i days"));

  $r  = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE tgl_pinjam = '$tgl'
         AND status NOT IN ('ditolak','dibatalkan')"
  ));
  $chart_pinjam_hari[] = (int)($r['total'] ?? 0);

  $r2 = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE tgl_kembali_aktual = '$tgl'
         AND status = 'dikembalikan'"
  ));
  $chart_kembali_hari[] = (int)($r2['total'] ?? 0);
}

// Data grafik per bulan (12 bulan terakhir)
$chart_bulan   = [];
$chart_pinjam  = [];
$chart_kembali = [];
for ($i = 11; $i >= 0; $i--) {
  $bulan         = date('Y-m', strtotime("-$i months"));
  $chart_bulan[] = date('M Y', strtotime("-$i months"));

  $r  = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE DATE_FORMAT(tgl_pinjam,'%Y-%m') = '$bulan'
         AND status NOT IN ('ditolak','dibatalkan')"
  ));
  $chart_pinjam[] = (int)($r['total'] ?? 0);

  $r2 = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE DATE_FORMAT(tgl_kembali_aktual,'%Y-%m') = '$bulan'
         AND status = 'dikembalikan'"
  ));
  $chart_kembali[] = (int)($r2['total'] ?? 0);
}

// Data grafik per tahun (5 tahun terakhir)
$chart_tahun         = [];
$chart_pinjam_tahun  = [];
$chart_kembali_tahun = [];
for ($i = 4; $i >= 0; $i--) {
  $tahun         = date('Y', strtotime("-$i years"));
  $chart_tahun[] = $tahun;

  $r  = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE YEAR(tgl_pinjam) = '$tahun'
         AND status NOT IN ('ditolak','dibatalkan')"
  ));
  $chart_pinjam_tahun[] = (int)($r['total'] ?? 0);

  $r2 = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM peminjaman
         WHERE YEAR(tgl_kembali_aktual) = '$tahun'
         AND status = 'dikembalikan'"
  ));
  $chart_kembali_tahun[] = (int)($r2['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — <?= APP_NAME ?></title>
  <link href="<?= BASE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/icons/bold/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/icons/regular/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    .btn-sw {
      background: none;
      border: none;
      padding: 4px 12px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      color: var(--text-secondary);
      transition: background .15s, color .15s;
    }

    .btn-sw.active {
      background: #fff;
      color: var(--text-primary);
      border: 0.5px solid rgba(0, 0, 0, 0.1);
      font-weight: 500;
    }
  </style>
</head>

<body>

  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div>
        <h1 class="topbar-title">Dashboard Admin</h1>
        <p class="topbar-sub">Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></p>
      </div>
      <a href="approval.php" class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:10px;padding:10px 18px;">
        <i class="ph-bold ph-check-circle"></i> Persetujuan
        <?php if ($stats['menunggu'] > 0): ?>
          <span class="badge bg-danger"><?= $stats['menunggu'] ?></span>
        <?php endif; ?>
      </a>
    </div>

    <!-- Statistik Row 1 -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-lg-3">
        <div class="stat-card c-primary">
          <div class="stat-icon"><i class="ph-bold ph-clipboard-text"></i></div>
          <div>
            <div class="stat-label">Total Keseluruhan</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-warning">
          <div class="stat-icon"><i class="ph-bold ph-clock-countdown"></i></div>
          <div>
            <div class="stat-label">Menunggu</div>
            <div class="stat-value"><?= $stats['menunggu'] ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-cyan">
          <div class="stat-icon"><i class="ph-bold ph-wrench"></i></div>
          <div>
            <div class="stat-label">Sedang Dipinjam</div>
            <div class="stat-value"><?= $stats['dipinjam'] ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-success">
          <div class="stat-icon"><i class="ph-bold ph-check-circle"></i></div>
          <div>
            <div class="stat-label">Selesai / Kembali</div>
            <div class="stat-value"><?= $stats['selesai'] ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistik Row 2 -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="stat-card c-violet">
          <div class="stat-icon"><i class="ph-bold ph-package"></i></div>
          <div>
            <div class="stat-label">Total Barang</div>
            <div class="stat-value"><?= $total_barang ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-primary">
          <div class="stat-icon"><i class="ph-bold ph-users"></i></div>
          <div>
            <div class="stat-label">Total Mahasiswa</div>
            <div class="stat-value"><?= $total_user ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-danger">
          <div class="stat-icon"><i class="ph-bold ph-x-circle"></i></div>
          <div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-value"><?= $stats['ditolak'] ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card c-warning">
          <div class="stat-icon"><i class="ph-bold ph-prohibit"></i></div>
          <div>
            <div class="stat-label">Dibatalkan</div>
            <div class="stat-value"><?= $stats['dibatalkan'] ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- GRAFIK -->
    <div class="row g-3 mb-4" style="align-items:stretch;">
      <div class="col-12 col-lg-8">
        <div class="chart-card h-100" style="display:flex;flex-direction:column;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <div>
              <div class="chart-title">
                <i class="ph-bold ph-chart-line" style="color:var(--primary)"></i>
                Peminjaman &amp; Pengembalian
              </div>
              <p class="chart-sub" id="chartSubLabel">Data 30 hari terakhir</p>
            </div>
            <div class="d-flex gap-1" style="background:var(--bg-soft,#f3f4f6);border-radius:8px;padding:3px;">
              <button class="btn-sw active" id="btnHari" onclick="switchChart('hari')">Harian</button>
              <button class="btn-sw" id="btnBulan" onclick="switchChart('bulan')">Bulanan</button>
              <button class="btn-sw" id="btnTahun" onclick="switchChart('tahun')">Tahunan</button>
            </div>
          </div>
          <div style="flex:1;position:relative;min-height:220px;">
            <canvas id="chartPeminjaman"></canvas>
          </div>
          <div class="d-flex gap-3 mt-2" style="font-size:12px;color:var(--text-secondary);">
            <span style="display:flex;align-items:center;gap:5px;">
              <span style="display:inline-block;width:16px;height:3px;border-radius:2px;background:#6366f1;"></span>
              Peminjaman
            </span>
            <span style="display:flex;align-items:center;gap:5px;">
              <span style="display:inline-block;width:16px;height:0;border-top:2px dashed #10b981;"></span>
              Pengembalian
            </span>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="chart-card h-100" style="display:flex;flex-direction:column;">
          <div class="chart-title mb-0">
            <i class="ph-bold ph-chart-donut" style="color:var(--secondary)"></i> Distribusi Status
          </div>
          <p class="chart-sub">Semua Aktivitas</p>

          <div style="display:flex;align-items:center;gap:20px;flex:1;">
            <!-- Donut -->
            <div style="position:relative;width:160px;height:160px;flex-shrink:0;">
              <canvas id="chartDonut"></canvas>
              <div id="donutCenter" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                <div style="font-size:22px;font-weight:600;line-height:1;" id="donutNum"><?= $stats['total'] ?></div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:2px;" id="donutPct"></div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:1px;" id="donutLbl">Total</div>
              </div>
            </div>

            <!-- Legend dengan progress bar -->
            <div style="flex:1;display:flex;flex-direction:column;gap:7px;">
              <?php
              $donut_items = [
                ['Menunggu',   $stats['menunggu'],   '#6366f1'],
                ['Dipinjam',   $stats['dipinjam'],   '#06b6d4'],
                ['Selesai',    $stats['selesai'],    '#10b981'],
                ['Ditolak',    $stats['ditolak'],    '#f43f5e'],
                ['Dibatalkan', $stats['dibatalkan'], '#f59e0b'],
              ];
              $max_val = max(array_column($donut_items, 1)) ?: 1;
              foreach ($donut_items as $i => [$lbl, $val, $clr]):
                $pct   = $stats['total'] > 0 ? round($val / $stats['total'] * 100) : 0;
                $bar_w = round($val / $max_val * 100);
              ?>
                <div style="display:flex;align-items:center;gap:7px;padding:5px 7px;border-radius:7px;cursor:default;transition:background .12s;"
                  onmouseenter="highlightDonut(<?= $i ?>)" onmouseleave="resetDonut()"
                  onmouseover="this.style.background='var(--bg-soft,#f3f4f6)'" onmouseout="this.style.background='transparent'">
                  <span style="width:9px;height:9px;border-radius:2px;background:<?= $clr ?>;flex-shrink:0;"></span>
                  <span style="font-size:11px;color:var(--text-secondary);flex:1;"><?= $lbl ?></span>
                  <div style="width:60px;background:rgba(0,0,0,0.06);border-radius:4px;height:3px;overflow:hidden;">
                    <div style="width:<?= $bar_w ?>%;height:3px;border-radius:4px;background:<?= $clr ?>;"></div>
                  </div>
                  <span style="font-size:12px;font-weight:600;min-width:20px;text-align:right;"><?= $val ?></span>
                  <span style="font-size:11px;color:var(--text-secondary);min-width:28px;text-align:right;"><?= $pct ?>%</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Menunggu Persetujuan -->
    <div class="card-section">
      <div class="card-section-header">
        <h6 class="card-section-title">
          <i class="ph-bold ph-clock-countdown"></i> Menunggu Persetujuan
        </h6>
        <a href="approval.php" style="font-size:13px;color:var(--primary);text-decoration:none;font-weight:500;">
          Lihat semua <i class="ph ph-arrow-right"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="ps-3">Mahasiswa</th>
              <th>NIM</th>
              <th>Barang</th>
              <th>Jml</th>
              <th>Tgl Pinjam</th>
              <th>Tgl Kembali</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($menunggu_list) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($menunggu_list)): ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                  <td><code><?= htmlspecialchars($row['nim']) ?></code></td>
                  <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                  <td><?= $row['jumlah'] ?></td>
                  <td><?= formatTanggal($row['tgl_pinjam']) ?></td>
                  <td><?= formatTanggal($row['tgl_kembali_rencana']) ?></td>
                  <td>
                    <a href="approval.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" style="font-size:12px;padding:4px 12px;">
                      <i class="ph ph-arrow-right"></i> Proses
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ph ph-check-circle" style="font-size:32px;display:block;margin-bottom:8px;color:#10b981;"></i>
                  Tidak ada peminjaman yang menunggu persetujuan.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>assets/js/chart.umd.js"></script>
  <script>
    const dataHari = {
      labels: <?= json_encode($chart_hari) ?>,
      pinjam: <?= json_encode($chart_pinjam_hari) ?>,
      kembali: <?= json_encode($chart_kembali_hari) ?>
    };
    const dataBulan = {
      labels: <?= json_encode($chart_bulan) ?>,
      pinjam: <?= json_encode($chart_pinjam) ?>,
      kembali: <?= json_encode($chart_kembali) ?>
    };
    const dataTahun = {
      labels: <?= json_encode($chart_tahun) ?>,
      pinjam: <?= json_encode($chart_pinjam_tahun) ?>,
      kembali: <?= json_encode($chart_kembali_tahun) ?>
    };

    let lineInstance = null;

    function buildLineChart(data) {
      if (lineInstance) lineInstance.destroy();
      const ctx = document.getElementById('chartPeminjaman').getContext('2d');

      const gP = ctx.createLinearGradient(0, 0, 0, 240);
      gP.addColorStop(0, 'rgba(99,102,241,0.25)');
      gP.addColorStop(1, 'rgba(99,102,241,0)');

      const gK = ctx.createLinearGradient(0, 0, 0, 240);
      gK.addColorStop(0, 'rgba(16,185,129,0.15)');
      gK.addColorStop(1, 'rgba(16,185,129,0)');

      lineInstance = new Chart(ctx, {
        data: {
          labels: data.labels,
          datasets: [{
              type: 'line',
              label: 'Peminjaman',
              data: data.pinjam,
              borderColor: '#6366f1',
              backgroundColor: gP,
              borderWidth: 2,
              fill: true,
              tension: 0.4,
              pointRadius: 4,
              pointBackgroundColor: '#6366f1',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointHoverRadius: 6,
              order: 1,
            },
            {
              type: 'line',
              label: 'Pengembalian',
              data: data.kembali,
              borderColor: '#10b981',
              backgroundColor: gK,
              borderWidth: 2,
              borderDash: [5, 3],
              fill: true,
              tension: 0.4,
              pointRadius: 4,
              pointBackgroundColor: '#10b981',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointHoverRadius: 6,
              order: 2,
            },
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: '#fff',
              titleColor: '#1f2937',
              bodyColor: '#6b7280',
              borderColor: 'rgba(0,0,0,0.08)',
              borderWidth: 1,
              padding: 10,
              callbacks: {
                title: t => t[0].label,
                label: item => {
                  if (item.datasetIndex === 2) return null;
                  return ` ${item.dataset.label}: ${item.raw}`;
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#9ca3af',
                font: {
                  size: 11
                },
                maxRotation: 45,
                autoSkip: true,
                maxTicksLimit: 15
              },
              border: {
                display: false
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0,0,0,0.04)'
              },
              ticks: {
                color: '#9ca3af',
                font: {
                  size: 11
                },
                padding: 6,
                stepSize: 1
              },
              border: {
                display: false
              }
            }
          },
          animation: {
            duration: 500,
            easing: 'easeInOutQuart'
          }
        }
      });
    }

    function switchChart(mode) {
      const map = {
        hari: {
          data: dataHari,
          label: 'Data 30 hari terakhir'
        },
        bulan: {
          data: dataBulan,
          label: 'Data 12 bulan terakhir'
        },
        tahun: {
          data: dataTahun,
          label: 'Data 5 tahun terakhir'
        },
      };
      ['Hari', 'Bulan', 'Tahun'].forEach(m =>
        document.getElementById('btn' + m).classList.toggle('active', m.toLowerCase() === mode)
      );
      document.getElementById('chartSubLabel').textContent = map[mode].label;
      buildLineChart(map[mode].data);
    }

    // Default tampilkan harian
    buildLineChart(dataHari);

    // Donut chart
    const donutColors = ['#6366f1', '#06b6d4', '#10b981', '#f43f5e', '#f59e0b'];
    const donutLabels = ['Menunggu', 'Dipinjam', 'Selesai', 'Ditolak', 'Dibatalkan'];
    const donutValues = [<?= $stats['menunggu'] ?>, <?= $stats['dipinjam'] ?>, <?= $stats['selesai'] ?>, <?= $stats['ditolak'] ?>, <?= $stats['dibatalkan'] ?>];
    const donutTotal = <?= $stats['total'] ?>;
    let donutInstance = null;

    const ctx2 = document.getElementById('chartDonut').getContext('2d');
    donutInstance = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: donutLabels,
        datasets: [{
          data: donutValues,
          backgroundColor: donutColors,
          borderWidth: 3,
          borderColor: 'transparent',
          hoverBorderColor: 'transparent',
          hoverOffset: 6,
          borderRadius: 4,
          spacing: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            enabled: false
          }
        },
        animation: {
          duration: 600,
          easing: 'easeInOutQuart'
        }
      }
    });

    function highlightDonut(idx) {
      const pct = Math.round(donutValues[idx] / donutTotal * 100);
      document.getElementById('donutNum').textContent = donutValues[idx];
      document.getElementById('donutPct').textContent = pct + '%';
      document.getElementById('donutLbl').textContent = donutLabels[idx];
      donutInstance.data.datasets[0].backgroundColor =
        donutColors.map((c, i) => i === idx ? c : c + '30');
      donutInstance.update('none');
    }

    function resetDonut() {
      document.getElementById('donutNum').textContent = donutTotal;
      document.getElementById('donutPct').textContent = '';
      document.getElementById('donutLbl').textContent = 'Total';
      donutInstance.data.datasets[0].backgroundColor = donutColors;
      donutInstance.update('none');
    }
  </script>
</body>

</html>