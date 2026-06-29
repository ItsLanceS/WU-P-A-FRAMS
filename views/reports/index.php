<?php $pageTitle = 'Reports'; ?>
<?php include_once BASE_PATH . '/views/layouts/header.php'; ?>

<?php
$totalRecords = count($records);
$statusCounts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
foreach ($records as $recordRow) {
    $status = $recordRow['status'] ?? '';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

$queryForExport = http_build_query([
    'page' => 'reports',
    'action' => 'index',
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'student_id' => $studentId,
    'status' => $statusF,
    'course' => $courseF,
    'export' => 'csv',
]);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Attendance Reports</h4>
    <small class="text-secondary">Filter, review, and export attendance records.</small>
  </div>
  <a href="<?= BASE_URL ?>/?<?= $queryForExport ?>" class="btn btn-success">
    <i class="bi bi-filetype-csv me-2"></i>Export CSV
  </a>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 stat-card h-100">
      <div class="card-body">
        <small class="text-secondary text-uppercase">Records</small>
        <div class="display-6 fw-bold"><?= $totalRecords ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 stat-card h-100 border-success-subtle">
      <div class="card-body">
        <small class="text-secondary text-uppercase">Present</small>
        <div class="display-6 fw-bold text-success"><?= $statusCounts['present'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 stat-card h-100 border-warning-subtle">
      <div class="card-body">
        <small class="text-secondary text-uppercase">Late</small>
        <div class="display-6 fw-bold text-warning"><?= $statusCounts['late'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 stat-card h-100 border-danger-subtle">
      <div class="card-body">
        <small class="text-secondary text-uppercase">Absent / Excused</small>
        <div class="display-6 fw-bold text-danger"><?= $statusCounts['absent'] + $statusCounts['excused'] ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 mb-4">
  <div class="card-header bg-transparent border-secondary fw-semibold">
    <i class="bi bi-funnel-fill me-2"></i>Filters
  </div>
  <div class="card-body">
    <form method="GET" action="<?= BASE_URL ?>/" class="row g-3 align-items-end">
      <input type="hidden" name="page" value="reports">
      <input type="hidden" name="action" value="index">

      <div class="col-md-3">
        <label class="form-label" for="reportDateFrom">Date From</label>
        <input type="date" id="reportDateFrom" name="date_from" class="form-control bg-dark border-secondary text-white"
               value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="reportDateTo">Date To</label>
        <input type="date" id="reportDateTo" name="date_to" class="form-control bg-dark border-secondary text-white"
               value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label" for="reportStudent">Student</label>
        <select id="reportStudent" name="student_id" class="form-select bg-dark border-secondary text-white">
          <option value="">All students</option>
          <?php foreach ($students as $student): ?>
            <option value="<?= (int)$student['id'] ?>" <?= $studentId === (int)$student['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($student['student_id'] . ' · ' . $student['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="reportStatus">Status</label>
        <select id="reportStatus" name="status" class="form-select bg-dark border-secondary text-white">
          <option value="">All statuses</option>
          <?php foreach (['present', 'late', 'absent', 'excused'] as $status): ?>
            <option value="<?= $status ?>" <?= $statusF === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="reportCourse">Course</label>
        <select id="reportCourse" name="course" class="form-select bg-dark border-secondary text-white">
          <option value="">All courses</option>
          <?php foreach ($courses as $course): ?>
            <option value="<?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?>" <?= $courseF === $course ? 'selected' : '' ?>>
              <?= htmlspecialchars($course, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search me-2"></i>Apply Filters
        </button>
        <a href="<?= BASE_URL ?>/?page=reports&action=index" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card border-0">
  <div class="card-header bg-transparent border-secondary fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="bi bi-table me-2"></i>Filtered Records</span>
    <small class="text-secondary"><?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?> to <?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?></small>
  </div>
  <div class="card-body p-0">
    <?php if (empty($records)): ?>
      <div class="text-center text-secondary py-5">
        <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>No records match the current filters.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-dark">
            <tr>
              <th>Date</th>
              <th>Student</th>
              <th>Course</th>
              <th>In</th>
              <th>Out</th>
              <th>Status</th>
              <th>Method</th>
              <th>Confidence</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($records as $row): ?>
              <?php $badgeMap = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger', 'excused' => 'info']; ?>
              <tr>
                <td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="text-secondary small"><?= htmlspecialchars($row['sid'], ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td><?= htmlspecialchars(trim(($row['course'] ?? '') . ' ' . ($row['section'] ?? '')) ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= !empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '–' ?></td>
                <td><?= !empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '–' ?></td>
                <td><span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>"><?= ucfirst($row['status']) ?></span></td>
                <td class="text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $row['marked_by']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= !empty($row['confidence']) ? htmlspecialchars((string)$row['confidence'], ENT_QUOTES, 'UTF-8') . '%' : '–' ?></td>
                <td class="text-secondary small"><?= htmlspecialchars($row['notes'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>
