<?php $pageTitle = 'Teacher Accounts'; ?>
<?php include_once BASE_PATH . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace text-primary me-2"></i>Teacher Accounts</h4>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="card border-0">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-person-plus-fill me-2"></i>Create Teacher Account
      </div>
      <div class="card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/?page=teachers&action=index">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label for="teacher_name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input id="teacher_name" type="text" name="name" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Teacher name" required>
          </div>

          <div class="mb-3">
            <label for="teacher_email" class="form-label">Email <span class="text-danger">*</span></label>
            <input id="teacher_email" type="email" name="email" class="form-control bg-dark border-secondary text-white"
                   value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="teacher@school.edu" required>
          </div>

          <div class="mb-3">
            <label for="teacher_password" class="form-label">Password <span class="text-danger">*</span></label>
            <input id="teacher_password" type="password" name="password" class="form-control bg-dark border-secondary text-white"
                   minlength="6" required>
          </div>

          <div class="mb-3">
            <label for="teacher_confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input id="teacher_confirm_password" type="password" name="confirm_password" class="form-control bg-dark border-secondary text-white"
                   minlength="6" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-circle me-2"></i>Create Teacher Account
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0 h-100">
      <div class="card-header bg-transparent border-secondary fw-semibold">
        <i class="bi bi-list-ul me-2"></i>Existing Teachers
      </div>
      <div class="card-body p-0">
        <?php if (empty($teachers)): ?>
          <div class="text-center text-secondary py-5">
            <i class="bi bi-person-workspace fs-1 d-block mb-2"></i>No teacher accounts found.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-dark">
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($teachers as $teacher): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($teacher['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <?php if ((int)$teacher['is_active'] === 1): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($teacher['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                      <a href="<?= BASE_URL ?>/?page=teachers&action=edit&id=<?= (int)$teacher['id'] ?>"
                         class="btn btn-sm btn-outline-primary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="POST" action="<?= BASE_URL ?>/?page=teachers&action=toggle_active"
                            class="d-inline"
                            onsubmit="return confirm('<?= (int)$teacher['is_active'] === 1 ? 'Deactivate' : 'Activate' ?> <?= htmlspecialchars(addslashes($teacher['name']), ENT_QUOTES, 'UTF-8') ?>?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int)$teacher['id'] ?>">
                        <button type="submit" title="<?= (int)$teacher['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>"
                                class="btn btn-sm <?= (int)$teacher['is_active'] === 1 ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                          <i class="bi bi-<?= (int)$teacher['is_active'] === 1 ? 'person-dash' : 'person-check' ?>"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include_once BASE_PATH . '/views/layouts/footer.php'; ?>
