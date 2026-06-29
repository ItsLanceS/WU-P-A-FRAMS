<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 page-shell">
  <div class="card border-0 text-center px-4 py-5" style="max-width: 520px; width: 100%;">
    <div class="display-2 fw-bold text-danger">403</div>
    <h1 class="h3 fw-bold mt-2">Access denied</h1>
    <p class="text-secondary mb-4">You do not have permission to access this page.</p>
    <div class="d-flex justify-content-center gap-2">
      <a href="<?= BASE_URL ?>/?page=dashboard" class="btn btn-primary">Back to dashboard</a>
      <a href="<?= BASE_URL ?>/?page=auth&action=logout" class="btn btn-outline-secondary">Sign out</a>
    </div>
  </div>
</body>
</html>
