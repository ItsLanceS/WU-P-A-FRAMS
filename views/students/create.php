<?php $pageTitle = 'Add Student'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= BASE_URL ?>/?page=students" class="btn btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus-fill text-primary me-2"></i>Add Student</h4>
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
  <!-- Student Info Form -->
  <div class="col-lg-6">
    <div class="card border-0 h-100">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-badge me-2"></i>Student Information
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?page=students&action=create"
              enctype="multipart/form-data" id="studentForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
               <label class="form-label">Student ID (System Generated)</label>
               <input type="text" class="form-control"
                 value="<?= htmlspecialchars($generatedStudentId ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 readonly>
               <div class="form-text text-secondary">Assigned automatically using format FRAMS-XXXX-XXXX.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Juan Dela Cruz" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="student@school.edu">
          </div>
          <div class="row g-2 mb-3">
            <div class="col">
              <label class="form-label">Course</label>
              <input type="text" name="course" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['course'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="BSCS">
            </div>
            <div class="col">
              <label class="form-label">Year</label>
              <select name="year" class="form-select bg-dark border-secondary text-white">
                <?php foreach (['1st','2nd','3rd','4th','5th'] as $y): ?>
                  <option <?= ($_POST['year'] ?? '') === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col">
              <label class="form-label">Section</label>
              <input type="text" name="section" class="form-control bg-dark border-secondary text-white"
                     value="<?= htmlspecialchars($_POST['section'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     placeholder="A">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="photo" class="form-control bg-dark border-secondary text-white"
                   accept="image/jpeg,image/png,image/webp">
          </div>

          <hr class="border-secondary">

          <!-- Face image upload -->
          <div class="mb-3">
            <label class="form-label fw-semibold">
              <i class="bi bi-camera me-1 text-primary"></i>
              Face Images for Recognition
              <small class="text-secondary fw-normal">(up to <?= MAX_FACE_IMAGES ?>)</small>
            </label>
            <input type="file" name="face_images[]" id="faceImagesInput"
                   class="form-control bg-dark border-secondary text-white"
                   accept="image/jpeg,image/png,image/webp" multiple>
            <div class="form-text text-secondary">
              Upload 3–5 clear face photos from different angles for best accuracy.
            </div>
          </div>

          <!-- Webcam capture for face images -->
          <div class="mb-3">
            <button type="button" class="btn btn-outline-info btn-sm" id="webcamToggle">
              <i class="bi bi-camera-video me-1"></i>Capture from Webcam
            </button>
          </div>
          <div id="webcamSection" class="d-none mb-3">
            <video id="enrollVideo" class="w-100 rounded border border-secondary" autoplay muted
                   style="max-height:240px; object-fit:cover;"></video>
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

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save me-2"></i>Save Student
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Tips -->
  <div class="col-lg-6">
    <div class="card border-0 border-start border-info border-3">
      <div class="card-body">
        <h6 class="fw-bold text-info"><i class="bi bi-lightbulb me-2"></i>Tips for Better Recognition</h6>
        <ul class="text-secondary small mb-0">
          <li>Use well-lit, clear photos.</li>
          <li>Include frontal view, slight left, and slight right angles.</li>
          <li>Avoid sunglasses, hats, or heavy filters.</li>
          <li>Upload 3–5 images per student.</li>
          <li>After saving, click <strong>Enroll to AI</strong> to train the model.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/webcam.js"></script>
<script>initEnrollWebcam('enrollVideo', 'webcamToggle', 'stopWebcam', 'captureBtn', 'capturePreview', 'webcamImagesInput');</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
