<?php $pageTitle = 'Live Attendance'; ?>
<?php include_once BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-bold"><i class="bi bi-camera-video-fill text-primary me-2"></i>Live Attendance</h4>
    <small class="text-secondary" id="liveClock"><?= date('h:i:s A') ?></small>
  </div>
  <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
    <select id="eventSelect" class="form-select form-select-sm bg-dark border-secondary text-white" style="width: 260px;">
      <option value="0">No event (use default late cutoff)</option>
      <?php foreach ($eventsToday as $event): ?>
        <option value="<?= (int)$event['id'] ?>" <?= ((int)$selectedEventId === (int)$event['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($event['title'] . ' (' . date('h:i A', strtotime($event['time_in_start'])) . ' - ' . date('h:i A', strtotime($event['time_out_end'])) . ', late after ' . date('h:i A', strtotime($event['late_time'])) . ')', ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-check form-switch mb-0 me-1">
      <input class="form-check-input" type="checkbox" id="modeToggle" checked>
      <label class="form-check-label text-secondary small" for="modeToggle">Time-In Mode</label>
    </div>
    <a href="<?= BASE_URL ?>/?page=attendance&action=events" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-calendar2-plus me-1"></i>Manage Events
    </a>
    <a href="<?= BASE_URL ?>/?page=attendance&action=manual" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-pencil-square me-1"></i>Manual Entry
    </a>
  </div>
</div>

<div class="row g-4">

  <!-- Camera Panel -->
  <div class="col-lg-7">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-camera me-2"></i>Camera Feed</span>
        <div class="d-flex gap-2 align-items-center">
          <span id="faceCount" class="badge bg-secondary">No face</span>
          <button id="startBtn" class="btn btn-sm btn-success">
            <i class="bi bi-play-fill me-1"></i>Start
          </button>
          <button id="stopBtn" class="btn btn-sm btn-danger d-none">
            <i class="bi bi-stop-fill me-1"></i>Stop
          </button>
        </div>
      </div>
      <div class="card-body p-0 position-relative" style="background:#111">
        <video id="attendanceVideo" class="w-100 rounded-bottom"
               autoplay muted style="max-height:400px; object-fit:cover; display:block;"></video>
        <canvas id="overlayCanvas" class="position-absolute top-0 start-0 w-100 h-100"
                style="pointer-events:none;"></canvas>
        <div id="scanOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center d-none"
             style="background:rgba(0,0,0,.6)">
          <div class="text-center text-white">
            <div class="spinner-border text-primary mb-2"></div>
            <div>Recognizing…</div>
          </div>
        </div>
      </div>
      <div class="card-footer bg-transparent border-secondary">
        <div class="d-flex gap-2 align-items-center">
          <label class="text-secondary small me-2" for="captureInterval">Interval:</label>
          <select id="captureInterval" class="form-select form-select-sm bg-dark border-secondary text-white" style="width:auto">
            <option value="2000">2 sec</option>
            <option value="3000" selected>3 sec</option>
            <option value="5000">5 sec</option>
            <option value="10000">10 sec</option>
          </select>
          <label class="text-secondary small ms-3 me-2" for="modalDelaySelect">Popup delay:</label>
          <select id="modalDelaySelect" class="form-select form-select-sm bg-dark border-secondary text-white" style="width:auto">
            <option value="2000">2 sec</option>
            <option value="3000" selected>3 sec</option>
            <option value="5000">5 sec</option>
            <option value="0">Stay open</option>
          </select>
          <small class="text-secondary ms-auto" id="lastCapture"></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Result + Log Panel -->
  <div class="col-lg-5">
    <!-- Recognition Result -->
    <div class="card border-0 mb-3">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-bounding-box me-2"></i>Recognition Result
      </div>
      <div class="card-body" id="recognitionResult">
        <div class="text-center text-secondary py-3">
          <i class="bi bi-person-circle fs-1 d-block mb-2"></i>
          Waiting for camera…
        </div>
      </div>
    </div>

    <!-- Today's log -->
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-list-check me-2"></i>Today's Log</span>
        <button class="btn btn-sm btn-outline-secondary" onclick="refreshLog()">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
      </div>
      <div class="card-body p-0" style="max-height:320px; overflow-y:auto;" id="logContainer">
        <?php include_once BASE_PATH . '/views/attendance/_log_rows.php'; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="attendanceRecordModal" tabindex="-1" aria-labelledby="attendanceRecordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-secondary">
        <h5 class="modal-title" id="attendanceRecordModalLabel">
          <i class="bi bi-check-circle-fill text-success me-2"></i>Attendance Recorded
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex gap-3 align-items-start">
          <img id="recordStudentPhoto" src="" alt="Student" class="attendance-record-photo">
          <div class="flex-grow-1">
            <div class="fw-bold fs-5" id="recordStudentName">Student Name</div>
            <div class="text-secondary small mb-2" id="recordStudentSid">ID</div>
            <div class="small"><span class="text-secondary">Course:</span> <span id="recordStudentCourse">N/A</span></div>
            <div class="small"><span class="text-secondary">Year / Section:</span> <span id="recordStudentYearSection">N/A</span></div>
            <div class="small"><span class="text-secondary">Status:</span> <span id="recordAttendanceStatus">Recorded</span></div>
            <div class="small"><span class="text-secondary">Recorded at:</span> <span id="recordAttendanceTime">--:--</span></div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/webcam.js"></script>
<script>
  // Live clock
  setInterval(() => {
    const d = new Date();
    document.getElementById('liveClock').textContent =
      d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }, 1000);

  initAttendanceWebcam({
    videoEl:        'attendanceVideo',
    overlayCanvas:  'overlayCanvas',
    startBtn:       'startBtn',
    stopBtn:        'stopBtn',
    scanOverlay:    'scanOverlay',
    resultEl:       'recognitionResult',
    faceCountEl:    'faceCount',
    lastCaptureEl:  'lastCapture',
    intervalSelect: 'captureInterval',
    modeToggle:     'modeToggle',
    eventSelect:    'eventSelect',
    logContainer:   'logContainer',
    attendanceModalId: 'attendanceRecordModal',
    modalDelaySelect: 'modalDelaySelect',
    apiUrl:         '<?= BASE_URL ?>/?page=api&action=recognize',
    logUrl:         '<?= BASE_URL ?>/?page=attendance&action=log_partial&event_id=' + (document.getElementById('eventSelect').value || 0),
  });

  function refreshLog() {
    const eventId = document.getElementById('eventSelect').value || 0;
    fetch('<?= BASE_URL ?>/?page=attendance&action=log_partial&event_id=' + eventId)
      .then(r => r.text())
      .then(html => { document.getElementById('logContainer').innerHTML = html; });
  }

  document.getElementById('eventSelect').addEventListener('change', function () {
    const eventId = this.value || 0;
    const url = new URL(window.location.href);
    url.searchParams.set('event_id', eventId);
    window.history.replaceState({}, '', url.toString());
    refreshLog();
  });
</script>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>
