<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login – <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body class="login-page d-flex align-items-center justify-content-center min-vh-100">

<div class="login-card card border-0 shadow-lg" style="width:100%;max-width:420px">
  <div class="card-body p-5">

    <div class="text-center mb-4">
      <div class="login-icon mb-3">
        <img
          src="<?= BASE_URL ?>/assets/images/ccs-logo-mini.png"
          alt="College of Computer Studies"
          class="login-logo"
        >
      </div>
      <h2 class="fw-bold"><?= APP_NAME ?></h2>
      <p class="text-secondary small">Facial Recognition Attendance System</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/?page=auth&action=login" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

      <div class="mb-3">
        <label class="form-label fw-semibold" for="email">Email address</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary">
            <i class="bi bi-envelope-fill text-secondary"></i>
          </span>
          <input type="email" id="email" name="email" class="form-control bg-dark border-secondary text-white"
                 placeholder="you@example.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                 required autofocus />
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold" for="password">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary">
            <i class="bi bi-lock-fill text-secondary"></i>
          </span>
          <input type="password" id="password" name="password" class="form-control bg-dark border-secondary text-white"
                 placeholder="••••••••" required />
          <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-door-open me-2"></i>Sign In
      </button>
    </form>

    <div class="text-center mt-4">
      <small class="text-secondary">
        Default credentials:<br>
        admin@frams.com / password<br>
        teacher@frams.com / password
      </small>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type  = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      pwd.type  = 'password';
      icon.className = 'bi bi-eye';
    }
  });
</script>
</body>
</html>
