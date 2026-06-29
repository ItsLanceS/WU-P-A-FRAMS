<?php
class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF check
            if (!$this->verifyCsrf()) {
                $error = 'Invalid request token. Please try again.';
            } else {
                $email    = trim($_POST['email']    ?? '');
                $password = trim($_POST['password'] ?? '');

                if (empty($email) || empty($password)) {
                    $error = 'Email and password are required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Invalid email address.';
                } else {
                    $user = $this->userModel->findByEmail($email);
                    if ($user && password_verify($password, $user['password'])) {
                        // Regenerate session to prevent fixation
                        session_regenerate_id(true);
                        $_SESSION['user_id']   = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        $this->redirect('dashboard');
                    } else {
                        $error = 'Invalid email or password.';
                    }
                }
            }
        }

        include BASE_PATH . '/views/auth/login.php';
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        $this->redirect('auth', 'login');
    }

    // ── Helpers ───────────────────────────────────────────

    private function redirect(string $page, string $action = 'index'): void
    {
        header('Location: ' . BASE_URL . '/?page=' . $page . '&action=' . $action);
        exit;
    }

    private function verifyCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
