<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 Not Found</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 page-shell">
  <div class="card border-0 text-center px-4 py-5" style="max-width: 520px; width: 100%;">
    <div class="display-2 fw-bold text-warning">404</div>
    <h1 class="h3 fw-bold mt-2">Page not found</h1>
    <p class="text-secondary mb-4">The route you requested does not exist in this FRAMS build.</p>
    <a href="<?= BASE_URL ?>/?page=dashboard" class="btn btn-primary">Back to dashboard</a>
  </div>
</body>
</html>