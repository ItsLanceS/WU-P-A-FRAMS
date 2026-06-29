<?php $pageTitle = 'Manual Attendance'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= BASE_URL ?>/?page=attendance&action=index" class="btn btn-outline-secondary me-3">
    <i class="bi bi-camera-video me-1"></i>Live Camera
  </a>
  <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Manual Attendance Entry</h4>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-plus-circle me-2"></i>Record Attendance
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?page=attendance&action=manual">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">Student <span class="text-danger">*</span></label>
            <select name="student_id" class="form-select bg-dark border-secondary text-white" required>
              <option value="">-- Select student --</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['id'] ?>"
                  <?= (($_POST['student_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['student_id'] . ' – ' . $s['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Event</label>
            <select name="event_id" class="form-select bg-dark border-secondary text-white">
              <option value="0">No event (default late cutoff)</option>
              <?php foreach ($eventsForEntry as $event): ?>
                <option value="<?= (int)$event['id'] ?>" <?= ((int)($_POST['event_id'] ?? 0) === (int)$event['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($event['title'] . ' · Late after ' . date('h:i A', strtotime($event['late_time'])), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label">Time In</label>
              <input type="time" name="time_in" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['time_in'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col">
              <label class="form-label">Time Out</label>
              <input type="time" name="time_out" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['time_out'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select bg-dark border-secondary text-white" required>
              <?php foreach (['present','late','absent','excused'] as $st): ?>
                <option value="<?= $st ?>" <?= (($_POST['status'] ?? 'present') === $st) ? 'selected' : '' ?>>
                  <?= ucfirst($st) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control bg-dark border-secondary text-white" rows="2"
                      placeholder="Optional remarks…"><?= htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save me-2"></i>Save Record
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Recent records + edit/delete -->
  <div class="col-lg-7">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-table me-2"></i>Recent Records</span>
        <form method="GET" action="<?= BASE_URL ?>/" class="d-flex gap-2">
          <input type="hidden" name="page" value="attendance">
          <input type="hidden" name="action" value="manual">
          <input type="date" name="filter_date" class="form-control form-control-sm bg-dark border-secondary text-white"
                 value="<?= htmlspecialchars($_GET['filter_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
          <select name="filter_event_id" class="form-select form-select-sm bg-dark border-secondary text-white" style="max-width:220px">
            <option value="0">All events</option>
            <?php foreach ($eventsForFilter as $event): ?>
              <option value="<?= (int)$event['id'] ?>" <?= ((int)($_GET['filter_event_id'] ?? 0) === (int)$event['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
        </form>
      </div>
      <div class="card-body p-0" style="max-height:480px; overflow-y:auto;">
        <?php
        $filterDate = $filterDate ?? date('Y-m-d');
        $filters = ['date' => $filterDate];
        if (!empty($filterEventId)) {
          $filters['event_id'] = (int)$filterEventId;
        }
        $records = (new Attendance())->getAll($filters);
        ?>
        <?php if (empty($records)): ?>
          <div class="text-center text-secondary py-4">No records for this date.</div>
        <?php else: ?>
        <table class="table table-hover table-sm mb-0 align-middle">
          <thead class="table-dark">
            <tr><th>Student</th><th>Event</th><th>Time In</th><th>Time Out</th><th>Status</th><th>By</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
              <td>
                <div class="fw-semibold small"><?= htmlspecialchars($r['student_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-secondary" style="font-size:.72rem"><?= htmlspecialchars($r['sid'], ENT_QUOTES, 'UTF-8') ?></div>
              </td>
              <td class="small text-secondary"><?= htmlspecialchars($r['event_title'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
              <td class="small"><?= $r['time_in']  ? date('h:i A', strtotime($r['time_in']))  : '–' ?></td>
              <td class="small"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '–' ?></td>
              <td>
                <?php $bm = ['present'=>'success','late'=>'warning','absent'=>'danger','excused'=>'info']; ?>
                <span class="badge bg-<?= $bm[$r['status']] ?? 'secondary' ?>"><?= ucfirst($r['status']) ?></span>
              </td>
              <td><small class="text-secondary"><?= $r['marked_by'] === 'manual' ? 'Manual' : 'AI' ?></small></td>
              <td>
                <a href="<?= BASE_URL ?>/?page=attendance&action=edit&id=<?= (int)$r['id'] ?>"
                   class="btn btn-xs btn-outline-primary py-0 px-1 me-1"><i class="bi bi-pencil"></i></a>
                <a href="<?= BASE_URL ?>/?page=attendance&action=delete&id=<?= (int)$r['id'] ?>"
                   class="btn btn-xs btn-outline-danger py-0 px-1"
                   onclick="return confirm('Delete this record?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
