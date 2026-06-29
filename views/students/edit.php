<?php $pageTitle = 'Edit Student'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= BASE_URL ?>/?page=students" class="btn btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h4 class="mb-0 fw-bold">
    <i class="bi bi-pencil text-primary me-2"></i>
    Edit: <?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?>
  </h4>
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

  <!-- Form -->
  <div class="col-lg-6">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-badge me-2"></i>Student Information
      </div>
      <div class="card-body">
        <form method="POST"
              action="<?= BASE_URL ?>/?page=students&action=edit&id=<?= (int)$student['id'] ?>"
              enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
               <label class="form-label">Student ID (System Generated)</label>
               <input type="hidden" name="student_id"
                 value="<?= htmlspecialchars($_POST['student_id'] ?? $student['student_id'], ENT_QUOTES, 'UTF-8') ?>">
               <input type="text" class="form-control"
                 value="<?= htmlspecialchars($_POST['student_id'] ?? $student['student_id'], ENT_QUOTES, 'UTF-8') ?>" readonly>
               <div class="form-text text-secondary">Student ID is immutable and managed by the system.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['name'] ?? $student['name'], ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['email'] ?? $student['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label">Course</label>
              <input type="text" name="course" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['course'] ?? $student['course'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col">
              <label class="form-label">Year</label>
              <select name="year" class="form-select bg-dark border-secondary text-white">
                <?php foreach (['1st','2nd','3rd','4th','5th'] as $y): ?>
                  <option <?= (($_POST['year'] ?? $student['year']) === $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col">
              <label class="form-label">Section</label>
              <input type="text" name="section" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['section'] ?? $student['section'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">New Profile Photo (optional)</label>
            <input type="file" name="photo" class="form-control bg-dark border-secondary text-white"
                   accept="image/jpeg,image/png,image/webp">
          </div>

          <hr class="border-secondary">

          <div class="mb-3">
            <label class="form-label fw-semibold">
              <i class="bi bi-camera me-1 text-primary"></i>Add More Face Images
              <small class="text-secondary fw-normal">(<?= count($faceImages) ?>/<?= MAX_FACE_IMAGES ?> used)</small>
            </label>
            <?php if (count($faceImages) < MAX_FACE_IMAGES): ?>
            <input type="file" name="face_images[]"
                   class="form-control bg-dark border-secondary text-white"
                   accept="image/jpeg,image/png,image/webp" multiple>
            <?php else: ?>
            <div class="alert alert-warning py-2 mb-0">Maximum face images reached.</div>
            <?php endif; ?>
          </div>

          <!-- Webcam capture -->
          <?php if (count($faceImages) < MAX_FACE_IMAGES): ?>
          <div class="mb-3">
            <button type="button" class="btn btn-outline-info btn-sm" id="webcamToggle">
              <i class="bi bi-camera-video me-1"></i>Capture from Webcam
            </button>
          </div>
          <div id="webcamSection" class="d-none mb-3">
            <video id="enrollVideo" class="w-100 rounded border border-secondary" autoplay muted
                   style="max-height:220px; object-fit:cover;"></video>
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-success" id="captureBtn">
                <i class="bi bi-camera me-1"></i>Capture
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" id="stopWebcam">
                <i class="bi bi-stop-circle me-1"></i>Stop
              </button>
            </div>
            <div id="capturePreview" class="d-flex flex-wrap gap-2 mt-2"></div>
            <input type="hidden" name="webcam_images" id="webcamImagesInput">
          </div>
          <?php endif; ?>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <i class="bi bi-save me-2"></i>Save Changes
            </button>
            <button type="button" class="btn btn-outline-success"
                    onclick="enrollStudent(<?= (int)$student['id'] ?>)" title="Re-enroll face data to AI">
              <i class="bi bi-cpu me-1"></i>Enroll to AI
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Face Images Panel -->
  <div class="col-lg-6">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-images me-2"></i>Enrolled Face Images
        <span class="badge bg-secondary"><?= count($faceImages) ?></span>
      </div>
      <div class="card-body">
        <?php if (empty($faceImages)): ?>
          <div class="text-center text-secondary py-4">
            <i class="bi bi-camera-x fs-1 d-block mb-2"></i>No face images yet.
          </div>
        <?php else: ?>
          <div class="row g-2">
            <?php foreach ($faceImages as $fi): ?>
            <div class="col-4">
              <div class="position-relative">
                <img src="<?= UPLOAD_URL . htmlspecialchars($fi['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                     class="img-fluid rounded border border-secondary"
                     style="height:100px;width:100%;object-fit:cover"
                     alt="face image">
                <a href="<?= BASE_URL ?>/?page=students&action=delete_face&face_id=<?= (int)$fi['id'] ?>&student_id=<?= (int)$student['id'] ?>"
                   class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 px-1"
                   style="font-size:.7rem"
                   onclick="return confirm('Remove this face image?')">
                  <i class="bi bi-x"></i>
                </a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Enroll status -->
        <div id="enrollStatus" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/webcam.js"></script>
<?php if (count($faceImages) < MAX_FACE_IMAGES): ?>
<script>initEnrollWebcam('enrollVideo', 'webcamToggle', 'stopWebcam', 'captureBtn', 'capturePreview', 'webcamImagesInput');</script>
<?php endif; ?>

<script>
function enrollStudent(studentId) {
  const btn = event.currentTarget;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enrolling…';

  fetch('<?= BASE_URL ?>/?page=students&action=enroll&id=' + studentId)
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('enrollStatus');
      if (data.success) {
        el.innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>' + (data.message || 'Enrolled successfully') + '</div>';
      } else {
        el.innerHTML = '<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i>' + (data.error || 'Enrollment failed') + '</div>';
      }
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cpu me-1"></i>Enroll to AI';
    })
    .catch(() => {
      document.getElementById('enrollStatus').innerHTML =
        '<div class="alert alert-danger py-2">Could not reach Python API.</div>';
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-cpu me-1"></i>Enroll to AI';
    });
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
