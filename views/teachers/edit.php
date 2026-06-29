<?php $pageTitle = 'Edit Teacher'; ?>
<?php include_once BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= BASE_URL ?>/?page=teachers" class="btn btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h4 class="mb-0 fw-bold">
    <i class="bi bi-pencil text-primary me-2"></i>
    Edit: <?= htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8') ?>
  </h4>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- Edit form -->
  <div class="col-lg-6">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-gear me-2"></i>Account Details
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/?page=teachers&action=edit&id=<?= (int)$teacher['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input id="edit_name" type="text" name="name"
                   class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['name'] ?? $teacher['name'], ENT_QUOTES, 'UTF-8') ?>"
                   required>
          </div>

          <div class="mb-3">
            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
            <input id="edit_email" type="email" name="email"
                   class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['email'] ?? $teacher['email'], ENT_QUOTES, 'UTF-8') ?>"
                   required>
          </div>

          <hr class="border-secondary">
          <p class="text-secondary small mb-2">Leave password fields blank to keep the current password.</p>

          <div class="mb-3">
            <label for="edit_password" class="form-label">New Password</label>
            <input id="edit_password" type="password" name="password"
                   class="form-control bg-dark border-secondary text-white"
                   minlength="6" autocomplete="new-password">
          </div>

          <div class="mb-3">
            <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
            <input id="edit_confirm_password" type="password" name="confirm_password"
                   class="form-control bg-dark border-secondary text-white"
                   minlength="6" autocomplete="new-password">
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-save me-2"></i>Save Changes
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Status panel -->
  <div class="col-lg-6">
    <div class="card border-0 border-start border-3 <?= (int)$teacher['is_active'] === 1 ? 'border-success' : 'border-secondary' ?>">
      <div class="card-body">
        <h6 class="fw-bold mb-1"><i class="bi bi-toggle-on me-2"></i>Account Status</h6>
        <p class="text-secondary small mb-3">
          Current status:
          <?php if ((int)$teacher['is_active'] === 1): ?>
            <span class="badge bg-success">Active</span> – this teacher can log in.
          <?php else: ?>
            <span class="badge bg-secondary">Inactive</span> – this teacher cannot log in.
          <?php endif; ?>
        </p>
        <form method="POST" action="<?= BASE_URL ?>/?page=teachers&action=toggle_active">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id" value="<?= (int)$teacher['id'] ?>">
          <button type="submit"
                  class="btn btn-sm <?= (int)$teacher['is_active'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>">
            <i class="bi bi-<?= (int)$teacher['is_active'] === 1 ? 'person-dash' : 'person-check' ?> me-1"></i>
            <?= (int)$teacher['is_active'] === 1 ? 'Deactivate Account' : 'Activate Account' ?>
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>
