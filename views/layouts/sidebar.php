<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$role        = $_SESSION['user_role'] ?? 'student';

$navItems = [
    ['page' => 'dashboard',  'icon' => 'speedometer2',     'label' => 'Dashboard',   'roles' => ['admin','teacher','student']],
    ['page' => 'attendance', 'icon' => 'camera-video-fill','label' => 'Live Attendance', 'roles' => ['admin','teacher']],
    ['page' => 'attendance', 'action' => 'manual', 'icon' => 'pencil-square', 'label' => 'Manual Entry', 'roles' => ['admin','teacher']],
    ['page' => 'attendance', 'action' => 'events', 'icon' => 'calendar2-check', 'label' => 'Attendance Events', 'roles' => ['admin','teacher']],
    ['page' => 'teachers',   'icon' => 'person-workspace', 'label' => 'Teachers', 'roles' => ['admin']],
    ['page' => 'students',   'icon' => 'people-fill',      'label' => 'Students',    'roles' => ['admin','teacher']],
    ['page' => 'reports',    'icon' => 'bar-chart-fill',   'label' => 'Reports',     'roles' => ['admin','teacher']],
];
?>

<div id="sidebar-wrapper">
  <div class="sidebar-heading px-3 py-2">
    <a href="<?= BASE_URL ?>/?page=dashboard&action=index" class="sidebar-brand" aria-label="College of Computer Studies">
      <img
        src="<?= BASE_URL ?>/assets/images/ccs-logo-full.png"
        alt="College of Computer Studies"
        class="sidebar-brand-full"
        onerror="this.style.display='none';"
      >
      <img
        src="<?= BASE_URL ?>/assets/images/ccs-logo-mini.png"
        alt="CS"
        class="sidebar-brand-mini"
        onerror="this.style.display='none';"
      >
    </a>
  </div>

  <nav class="sidebar-nav">
    <ul class="nav flex-column px-2">
      <?php foreach ($navItems as $item): ?>
        <?php if (!in_array($role, $item['roles'])) { continue; } ?>
        <?php
          $action  = $item['action'] ?? 'index';
          $href    = BASE_URL . '/?page=' . $item['page'] . '&action=' . $action;
          $active  = ($currentPage === $item['page'] && ($_GET['action'] ?? 'index') === $action);
        ?>
        <li class="nav-item">
          <a href="<?= $href ?>" class="nav-link <?= $active ? 'active' : '' ?>">
            <i class="bi bi-<?= $item['icon'] ?> me-2"></i>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <hr class="border-secondary mx-3 my-2" />

    <ul class="nav flex-column px-2">
      <li class="nav-item">
        <a href="<?= BASE_URL ?>/?page=auth&action=logout" class="nav-link text-danger">
          <i class="bi bi-box-arrow-right me-2"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </nav>
</div>
