<?php
// ============================================================
// Front Controller – FRAMS
// ============================================================
define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';

// ── Session hardening ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ── CSRF token ────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Autoloader ────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    foreach ([BASE_PATH . '/controllers/', BASE_PATH . '/models/'] as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── Routing ───────────────────────────────────────────────
$page   = preg_replace('/[^a-z_]/', '', strtolower($_GET['page']   ?? 'dashboard'));
$action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? 'index'));

// Public pages (no auth required)
$public = ['auth'];

if (!in_array($page, $public) && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/?page=auth&action=login');
    exit;
}

$routes = [
    'auth'       => 'AuthController',
    'dashboard'  => 'DashboardController',
    'teachers'   => 'TeacherController',
    'students'   => 'StudentController',
    'attendance' => 'AttendanceController',
    'reports'    => 'ReportController',
    'api'        => 'ApiController',
];

if (isset($routes[$page])) {
    $controllerClass = $routes[$page];
    $controller      = new $controllerClass();
    if (method_exists($controller, $action)) {
        $controller->{$action}();
    } else {
        http_response_code(404);
        include BASE_PATH . '/views/404.php';
    }
} else {
    http_response_code(404);
    include BASE_PATH . '/views/404.php';
}
