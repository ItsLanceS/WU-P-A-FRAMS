<?php
// ============================================================
// Application Configuration
// ============================================================

define('APP_NAME',    'FRAMS');
define('APP_TITLE',   'Facial Recognition Attendance System');
define('APP_VERSION', '1.0.0');

// Dynamically resolve base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = dirname($_SERVER['SCRIPT_NAME']);
$base     = rtrim($script, '/\\');
define('BASE_URL', $protocol . '://' . $host . $base);

// Python Flask API
define('PYTHON_API_URL', 'http://127.0.0.1:5000');
define('PYTHON_API_TIMEOUT', 10); // seconds

// Uploads
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'faces' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL',  BASE_URL  . '/uploads/faces/');
define('MAX_FACE_IMAGES', 10);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

// Attendance thresholds
define('LATE_TIME',            '08:30:00'); // Time after which status = late
define('RECOGNITION_MIN_CONF', 55.0);       // Minimum confidence % to accept

// Timezone
date_default_timezone_set('Asia/Manila');
