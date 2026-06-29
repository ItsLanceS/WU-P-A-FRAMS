<?php $pageTitle = 'Students'; ?>
<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Students</h4>
  <a href="<?= BASE_URL ?>/?page=students&action=create" class="btn btn-primary">
    <i class="bi bi-person-plus-fill me-1"></i>Add Student
  </a>
</div>

<!-- Search -->
<form method="GET" action="<?= BASE_URL ?>/" class="mb-3">
  <input type="hidden" name="page" value="students">
  <div class="input-group" style="max-width:360px">
    <input type="text" name="search" class="form-control bg-dark border-secondary text-white"
           placeholder="Search name, ID, or course…"
           value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    <?php if (!empty($_GET['search'])): ?>
      <a href="<?= BASE_URL ?>/?page=students" class="btn btn-outline-danger"><i class="bi bi-x"></i></a>
    <?php endif; ?>
  </div>
</form>

<div class="card border-0">
  <div class="card-body p-0">
    <?php if (empty($students)): ?>
      <div class="text-center text-secondary py-5">
        <i class="bi bi-people fs-1 d-block mb-2"></i>No students found.
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-dark">
          <tr>
            <th>Photo</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Course</th>
            <th>Year / Section</th>
            <th>Face Images</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td>
              <?php if ($s['photo']): ?>
                <img src="<?= UPLOAD_URL . htmlspecialchars($s['photo'], ENT_QUOTES, 'UTF-8') ?>"
                     class="rounded-circle" width="40" height="40" style="object-fit:cover"
                     alt="photo">
              <?php else: ?>
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                  <i class="bi bi-person-fill"></i>
                </div>
              <?php endif; ?>
            </td>
            <td><code><?= htmlspecialchars($s['student_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td class="fw-semibold"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($s['course'] ?? '–', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(($s['year'] ?? '–') . ' / ' . ($s['section'] ?? '–'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $fc = (int)($s['face_count'] ?? 0); ?>
              <span class="badge <?= $fc > 0 ? 'bg-success' : 'bg-warning text-dark' ?>">
                <i class="bi bi-camera me-1"></i><?= $fc ?> image<?= $fc !== 1 ? 's' : '' ?>
              </span>
            </td>
            <td class="text-end">
              <a href="<?= BASE_URL ?>/?page=students&action=edit&id=<?= $s['id'] ?>"
                 class="btn btn-sm btn-outline-primary me-1" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
                    <button type="button" class="btn btn-sm btn-outline-danger js-delete-student"
                      data-student-id="<?= (int)$s['id'] ?>"
                      data-student-name="<?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>"
                      title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header border-danger">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to remove <strong id="deleteName"></strong>?
        This will permanently delete the student and all associated data including face images and attendance records.
      </div>
      <div class="modal-footer border-secondary">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <a id="deleteConfirmBtn" href="#" class="btn btn-danger btn-sm">Delete</a>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDelete(id, name) {
  document.getElementById('deleteName').textContent = name;
  document.getElementById('deleteConfirmBtn').href =
    '<?= BASE_URL ?>/?page=students&action=delete&id=' + id;

  var modalEl = document.getElementById('deleteModal');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-delete-student').forEach(function (button) {
    button.addEventListener('click', function () {
      confirmDelete(button.dataset.studentId, button.dataset.studentName || 'this student');
    });
  });
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
