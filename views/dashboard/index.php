<?php $pageTitle = 'Dashboard'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard</h4>
    <small class="text-secondary"><?= date('l, F j, Y') ?></small>
  </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['label'=>'Total Students', 'value'=>$totalStudents,     'icon'=>'people-fill',      'color'=>'primary'],
    ['label'=>'Present Today',  'value'=>$presentToday,      'icon'=>'check-circle-fill','color'=>'success'],
    ['label'=>'Absent Today',   'value'=>$absentToday,       'icon'=>'x-circle-fill',    'color'=>'danger'],
    ['label'=>'Late Today',     'value'=>$lateToday,         'icon'=>'clock-fill',        'color'=>'warning'],
  ];
  foreach ($cards as $c): ?>
  <div class="col-6 col-xl-3">
    <div class="card border-0 stat-card dashboard-stat-card dashboard-stat-card-<?= htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8') ?> h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon dashboard-stat-icon dashboard-stat-icon-<?= htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8') ?>">
          <i class="bi bi-<?= $c['icon'] ?>"></i>
        </div>
        <div>
          <div class="fs-2 fw-bold"><?= number_format((int)$c['value']) ?></div>
          <div class="text-secondary small"><?= $c['label'] ?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="card border-0 chart-card h-100">
      <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-graph-up text-primary me-2"></i>Weekly Attendance</span>
      </div>
      <div class="card-body p-3 pt-2 chart-card-body chart-card-body-weekly">
        <canvas id="weeklyChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card border-0 chart-card h-100">
      <div class="card-header bg-transparent border-secondary">
        <span class="fw-semibold"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Today's Breakdown</span>
      </div>
      <div class="card-body p-3 pt-2 d-flex align-items-center justify-content-center chart-card-body chart-card-body-pie">
        <canvas id="todayPie"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Recent Attendance -->
<div class="card border-0">
  <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-clock-history text-primary me-2"></i>Recent Marks Today</span>
    <a href="<?= BASE_URL ?>/?page=attendance&action=index" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-camera-video me-1"></i>Open Camera
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($recentMarks)): ?>
      <div class="text-center text-secondary py-5">
        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
        No attendance marked today yet.
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table id="recentMarksTable" class="table table-hover mb-0 align-middle">
        <thead class="table-dark">
          <tr>
            <th>Student</th><th>ID</th><th>Time In</th><th>Time Out</th><th>Status</th><th>Confidence</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentMarks as $r): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars($r['sid'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= $r['time_in']  ? date('h:i A', strtotime($r['time_in']))  : '–' ?></td>
            <td><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '–' ?></td>
            <td>
              <?php
              $badgeMap = ['present'=>'success','late'=>'warning','absent'=>'danger','excused'=>'info'];
              $badge    = $badgeMap[$r['status']] ?? 'secondary';
              ?>
              <span class="badge bg-<?= $badge ?>"><?= ucfirst($r['status']) ?></span>
            </td>
            <td><?= $r['confidence'] ? $r['confidence'] . '%' : '–' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div id="recentMarksPagination" class="recent-marks-pagination d-flex justify-content-between align-items-center px-3 py-2 border-top" hidden>
      <small id="recentMarksPageInfo" class="text-secondary mb-0"></small>
      <div class="d-flex gap-2">
        <button id="recentMarksPrev" type="button" class="btn btn-sm btn-outline-secondary">Prev</button>
        <button id="recentMarksNext" type="button" class="btn btn-sm btn-outline-secondary">Next</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
const weeklyData = <?= json_encode(array_values($weeklyStats)) ?>;

window.addEventListener('load', function () {
  (function initRecentMarksPagination() {
    const table = document.getElementById('recentMarksTable');
    const pager = document.getElementById('recentMarksPagination');
    const info = document.getElementById('recentMarksPageInfo');
    const prevBtn = document.getElementById('recentMarksPrev');
    const nextBtn = document.getElementById('recentMarksNext');
    if (!table || !pager || !info || !prevBtn || !nextBtn) {
      return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const pageSize = 5;
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
    let currentPage = 1;

    if (totalRows <= pageSize) {
      return;
    }

    pager.hidden = false;

    function renderPage(page) {
      currentPage = Math.min(Math.max(page, 1), totalPages);
      const start = (currentPage - 1) * pageSize;
      const end = start + pageSize;

      rows.forEach(function (row, index) {
        row.style.display = index >= start && index < end ? '' : 'none';
      });

      info.textContent = 'Showing ' + (start + 1) + '-' + Math.min(end, totalRows) + ' of ' + totalRows;
      prevBtn.disabled = currentPage === 1;
      nextBtn.disabled = currentPage === totalPages;
    }

    prevBtn.addEventListener('click', function () {
      renderPage(currentPage - 1);
    });

    nextBtn.addEventListener('click', function () {
      renderPage(currentPage + 1);
    });

    renderPage(1);
  })();

  if (typeof Chart === 'undefined') {
    return;
  }

  // Weekly Attendance Trend Chart
  (function () {
    const chartEl = document.getElementById('weeklyChart');
    if (!chartEl) {
      return;
    }

    const labels = weeklyData.map(d => {
      const dt = new Date(d.date);
      return dt.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    });
    const toNumber = value => {
      const n = Number(value);
      return Number.isFinite(n) ? n : 0;
    };
    const present = weeklyData.map(d => toNumber(d.present));
    const absent  = weeklyData.map(d => toNumber(d.absent));
    const late    = weeklyData.map(d => toNumber(d.late));
    const excused = weeklyData.map(d => toNumber(d.excused));
    const useBarChart = labels.length <= 2;

    new Chart(chartEl, {
      type: useBarChart ? 'bar' : 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Present',
            data: present,
            borderColor: '#2dc46d',
            backgroundColor: useBarChart ? 'rgba(45, 196, 109, 0.86)' : 'rgba(45, 196, 109, 0.18)',
            fill: !useBarChart,
            tension: 0.38,
            pointRadius: useBarChart ? 0 : 4,
            pointHoverRadius: useBarChart ? 0 : 5,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#2dc46d',
            pointBorderWidth: 2,
            borderWidth: useBarChart ? 1 : 3,
            borderRadius: useBarChart ? 12 : 0,
            barThickness: useBarChart ? 34 : undefined,
          },
          {
            label: 'Late',
            data: late,
            borderColor: '#f1be39',
            backgroundColor: useBarChart ? 'rgba(241, 190, 57, 0.88)' : 'rgba(241, 190, 57, 0.18)',
            fill: false,
            tension: 0.34,
            pointRadius: useBarChart ? 0 : 3,
            pointHoverRadius: useBarChart ? 0 : 4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#f1be39',
            pointBorderWidth: 2,
            borderWidth: useBarChart ? 1 : 2,
            borderDash: useBarChart ? [] : [6, 4],
            borderRadius: useBarChart ? 12 : 0,
            barThickness: useBarChart ? 34 : undefined,
          },
          {
            label: 'Absent',
            data: absent,
            borderColor: '#e25b5b',
            backgroundColor: useBarChart ? 'rgba(226, 91, 91, 0.78)' : 'rgba(226, 91, 91, 0.08)',
            fill: false,
            tension: 0.32,
            pointRadius: useBarChart ? 0 : 3,
            pointHoverRadius: useBarChart ? 0 : 4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#e25b5b',
            pointBorderWidth: 2,
            borderWidth: useBarChart ? 1 : 2,
            borderDash: useBarChart ? [] : [4, 4],
            borderRadius: useBarChart ? 12 : 0,
            barThickness: useBarChart ? 34 : undefined,
          },
          {
            label: 'Excused',
            data: excused,
            borderColor: '#5b8def',
            backgroundColor: useBarChart ? 'rgba(91, 141, 239, 0.78)' : 'rgba(91, 141, 239, 0.08)',
            fill: false,
            tension: 0.3,
            pointRadius: useBarChart ? 0 : 3,
            pointHoverRadius: useBarChart ? 0 : 4,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#5b8def',
            pointBorderWidth: 2,
            borderWidth: useBarChart ? 1 : 2,
            borderDash: useBarChart ? [] : [2, 4],
            borderRadius: useBarChart ? 12 : 0,
            barThickness: useBarChart ? 34 : undefined,
          },
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          legend: {
            labels: {
              color: '#4e6f5c',
              usePointStyle: true,
              boxWidth: 8,
              padding: 14,
            }
          },
          tooltip: {
            backgroundColor: 'rgba(18, 37, 26, 0.92)',
            titleColor: '#f4fff7',
            bodyColor: '#d9f5e3',
            padding: 10,
            displayColors: true,
          }
        },
        scales: {
          x: {
            ticks: { color: '#4e6f5c' },
            grid: { display: false }
          },
          y: {
            ticks: {
              color: '#4e6f5c',
              precision: 0,
            },
            grid: { color: 'rgba(43,101,68,0.12)' },
            beginAtZero: true,
            suggestedMax: Math.max(...present, ...absent, ...late, ...excused, 1) + 1,
          },
        }
      }
    });
  })();

  // Today Pie Chart
  (function () {
    const chartEl = document.getElementById('todayPie');
    if (!chartEl) {
      return;
    }

    const presentCount = <?= (int)$presentToday ?>;
    const absentCount  = <?= (int)$absentToday ?>;
    const lateCount    = <?= (int)$lateToday ?>;
    const excusedCount = <?= (int)$excusedToday ?>;

    new Chart(chartEl, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'Absent', 'Late', 'Excused'],
        datasets: [{
          data: [presentCount, absentCount, lateCount, excusedCount],
          backgroundColor: ['#2dc46d', '#e25b5b', '#f1be39', '#5b8def'],
          borderWidth: 2,
          borderColor: '#f6fff8',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#4e6f5c',
              boxWidth: 10,
              padding: 10,
            }
          }
        }
      }
    });
  })();
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
