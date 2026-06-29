<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME, ENT_QUOTES, 'UTF-8') ?> – <?= APP_NAME ?></title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <!-- Custom styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body>
<div class="d-flex" id="wrapper">

  <?php include BASE_PATH . '/views/layouts/sidebar.php'; ?>

  <div id="page-content-wrapper" class="flex-grow-1">

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg app-navbar border-bottom px-4">
      <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <span class="navbar-brand mb-0 fw-semibold">
        <i class="bi bi-camera-video-fill text-primary me-2"></i><?= "WUP-FRAMS"?>
      </span>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="badge bg-secondary" id="pythonStatus" title="Python API status">
          <i class="bi bi-circle-fill text-warning me-1" style="font-size:.55rem"></i> Checking…
        </span>
        <div class="d-flex align-items-center gap-2">
          <span class="btn btn-outline-secondary d-flex align-items-center gap-2" title="Current user">
            <i class="bi bi-person-circle fs-5"></i>
            <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            <small class="badge bg-primary"><?= htmlspecialchars(ucfirst($_SESSION['user_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </span>
        </div>
      </div>
    </nav>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash'])): ?>
      <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show m-3 mb-0" role="alert">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <main class="p-4">
