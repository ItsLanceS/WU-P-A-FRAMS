<?php $pageTitle = 'Attendance Events'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-calendar2-check text-primary me-2"></i>Attendance Events</h4>
    <small class="text-secondary">Teachers can define event window and late cutoff per day.</small>
  </div>
  <a href="<?= BASE_URL ?>/?page=attendance&action=index" class="btn btn-outline-secondary">
    <i class="bi bi-camera-video me-1"></i>Back To Live Attendance
  </a>
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
        <i class="bi bi-plus-circle me-2"></i>Create Event
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?page=attendance&action=events">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="event_date" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['event_date'] ?? ($filterDate ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label">Time In Starts</label>
              <input type="time" name="time_in_start" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['time_in_start'] ?? '08:00', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col">
              <label class="form-label">Time Out Ends</label>
              <input type="time" name="time_out_end" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['time_out_end'] ?? '17:00', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Late After</label>
            <input type="time" name="late_time" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['late_time'] ?? '08:30', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
            <label class="form-check-label" for="isActive">Active for live attendance</label>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save me-1"></i>Save Event
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-list-check me-2"></i>Events</span>
        <form method="GET" action="<?= BASE_URL ?>/" class="d-flex gap-2">
          <input type="hidden" name="page" value="attendance">
          <input type="hidden" name="action" value="events">
          <input type="date" name="date" class="form-control form-control-sm bg-dark border-secondary text-white"
                 value="<?= htmlspecialchars($filterDate ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
        </form>
      </div>
      <div class="card-body p-0" style="max-height:520px; overflow-y:auto;">
        <?php if (empty($events)): ?>
          <div class="text-center text-secondary py-4">No events for this date.</div>
        <?php else: ?>
          <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-dark">
              <tr>
                <th>Title</th>
                <th>Time In</th>
                <th>Late After</th>
                <th>Time Out</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $event): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-secondary" style="font-size:.72rem"><?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?></div>
                  </td>
                  <td><?= date('h:i A', strtotime($event['time_in_start'])) ?></td>
                  <td><span class="badge bg-warning text-dark"><?= date('h:i A', strtotime($event['late_time'])) ?></span></td>
                  <td><?= date('h:i A', strtotime($event['time_out_end'])) ?></td>
                  <td>
                    <?php if ((int)$event['is_active'] === 1): ?>
                      <span class="badge bg-success">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
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
