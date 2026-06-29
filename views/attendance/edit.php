<?php $pageTitle = 'Edit Attendance'; ?>
<?php include_once BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div class="d-flex align-items-center gap-3">
    <a href="<?= BASE_URL ?>/?page=attendance&action=manual" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Attendance</h4>
      <small class="text-secondary"><?= htmlspecialchars($record['student_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($record['sid'], ENT_QUOTES, 'UTF-8') ?></small>
    </div>
  </div>
  <span class="badge bg-secondary-subtle text-light border border-secondary"><?= htmlspecialchars($record['date'], ENT_QUOTES, 'UTF-8') ?></span>
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
  <div class="col-lg-7">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-journal-check me-2"></i>Update Record
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?page=attendance&action=edit&id=<?= (int)$record['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label" for="timeIn">Time In</label>
              <input type="time" id="timeIn" name="time_in" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars(!empty($_POST['time_in']) ? $_POST['time_in'] : substr((string)($record['time_in'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="timeOut">Time Out</label>
              <input type="time" id="timeOut" name="time_out" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars(!empty($_POST['time_out']) ? $_POST['time_out'] : substr((string)($record['time_out'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="eventId">Event</label>
            <select id="eventId" name="event_id" class="form-select bg-dark border-secondary text-white">
              <option value="0">No event (default late cutoff)</option>
              <?php foreach ($eventsForDate as $event): ?>
                <option value="<?= (int)$event['id'] ?>" <?= ((int)($_POST['event_id'] ?? ($record['event_id'] ?? 0)) === (int)$event['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($event['title'] . ' · Late after ' . date('h:i A', strtotime($event['late_time'])), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label" for="attendanceStatus">Status</label>
            <select id="attendanceStatus" name="status" class="form-select bg-dark border-secondary text-white">
              <?php foreach (['present', 'late', 'absent', 'excused'] as $status): ?>
                <option value="<?= $status ?>" <?= (($_POST['status'] ?? $record['status']) === $status) ? 'selected' : '' ?>>
                  <?= ucfirst($status) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label" for="attendanceNotes">Notes</label>
            <textarea id="attendanceNotes" name="notes" rows="4" class="form-control bg-dark border-secondary text-white"
                      placeholder="Optional remarks"><?= htmlspecialchars($_POST['notes'] ?? ($record['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-2"></i>Save Changes
            </button>
            <a href="<?= BASE_URL ?>/?page=attendance&action=manual" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 h-100">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-vcard me-2"></i>Record Details
      </div>
      <div class="card-body">
        <dl class="row mb-0 small">
          <dt class="col-4 text-secondary">Student</dt>
          <dd class="col-8 fw-semibold"><?= htmlspecialchars($record['student_name'], ENT_QUOTES, 'UTF-8') ?></dd>

          <dt class="col-4 text-secondary">Student ID</dt>
          <dd class="col-8"><?= htmlspecialchars($record['sid'], ENT_QUOTES, 'UTF-8') ?></dd>

          <dt class="col-4 text-secondary">Course</dt>
          <dd class="col-8"><?= htmlspecialchars($record['course'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></dd>

          <dt class="col-4 text-secondary">Section</dt>
          <dd class="col-8"><?= htmlspecialchars($record['section'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></dd>

          <dt class="col-4 text-secondary">Marked By</dt>
          <dd class="col-8 text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $record['marked_by']), ENT_QUOTES, 'UTF-8') ?></dd>

          <dt class="col-4 text-secondary">Confidence</dt>
          <dd class="col-8"><?= !empty($record['confidence']) ? htmlspecialchars((string)$record['confidence'], ENT_QUOTES, 'UTF-8') . '%' : 'N/A' ?></dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>
