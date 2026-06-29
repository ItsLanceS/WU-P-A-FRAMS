<?php
class TeacherController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index(): void
    {
        $this->requireAuth(['admin']);
        $errors   = [];
        $teachers = $this->userModel->getByRole('teacher');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $input  = $this->extractCreateInput($_POST);
                $errors = $this->validateCreateInput($input);

                if (empty($errors)) {
                    $this->userModel->create([
                        'name'     => $input['name'],
                        'email'    => $input['email'],
                        'password' => $input['password'],
                        'role'     => 'teacher',
                    ]);

                    $this->setFlash('success', 'Teacher account created successfully.');
                    $this->redirect('teachers');
                }
            }
        }

        $this->renderIndexView($teachers, $errors);
    }

    // ── Edit ──────────────────────────────────────────────

    public function edit(): void
    {
        $this->requireAuth(['admin']);
        $id      = (int)($_GET['id'] ?? 0);
        $teacher = $this->userModel->findById($id);
        if (!$teacher || $teacher['role'] !== 'teacher') {
            http_response_code(404);
            include_once BASE_PATH . '/views/404.php';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $input  = $this->extractEditInput($_POST);
                $errors = $this->validateEditInput($input, $id);

                if (empty($errors)) {
                    $this->userModel->update($id, $input['name'], $input['email']);
                    if ($input['password'] !== '') {
                        $this->userModel->updatePassword($id, $input['password']);
                    }
                    $this->setFlash('success', 'Teacher account updated successfully.');
                    $this->redirect('teachers');
                }
            }
        }

        $this->renderEditView($teacher, $errors);
    }

    // ── Toggle active ──────────────────────────────────────

    public function toggle_active(): void
    {
        $this->requireAuth(['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrf()) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('teachers');
        }

        $id      = (int)($_POST['id'] ?? 0);
        $teacher = $this->userModel->findById($id);
        if (!$teacher || $teacher['role'] !== 'teacher') {
            http_response_code(404);
            include_once BASE_PATH . '/views/404.php';
            return;
        }

        $this->userModel->toggleActive($id);
        $label = (int)$teacher['is_active'] === 1 ? 'deactivated' : 'activated';
        $this->setFlash('success', "Teacher account {$label} successfully.");
        $this->redirect('teachers');
    }

    // ── Render helpers ─────────────────────────────────────

    private function renderIndexView(array $teachers, array $errors): void
    {
        // Keep variables in this scope for the included template.
        $teachers = array_values($teachers);
        $errors   = array_values($errors);
        include_once BASE_PATH . '/views/teachers/index.php';
    }

    private function renderEditView(array $teacher, array $errors): void
    {
        // Strip the password hash before exposing $teacher to the template.
        unset($teacher['password']);
        $errors = array_values($errors);
        include_once BASE_PATH . '/views/teachers/edit.php';
    }

    private function extractCreateInput(array $post): array
    {
        return [
            'name' => trim($post['name'] ?? ''),
            'email' => trim($post['email'] ?? ''),
            'password' => trim($post['password'] ?? ''),
            'confirm_password' => trim($post['confirm_password'] ?? ''),
        ];
    }

    private function validateCreateInput(array $input): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors[] = 'Teacher name is required.';
        }

        if ($input['email'] === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } elseif ($this->userModel->findAnyByEmail($input['email'])) {
            $errors[] = 'Email is already in use.';
        }

        if ($input['password'] === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($input['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($input['confirm_password'] === '') {
            $errors[] = 'Confirm password is required.';
        } elseif ($input['password'] !== $input['confirm_password']) {
            $errors[] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    private function extractEditInput(array $post): array
    {
        return [
            'name'             => trim($post['name'] ?? ''),
            'email'            => trim($post['email'] ?? ''),
            'password'         => trim($post['password'] ?? ''),
            'confirm_password' => trim($post['confirm_password'] ?? ''),
        ];
    }

    private function validateEditInput(array $input, int $excludeId): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors[] = 'Teacher name is required.';
        }

        if ($input['email'] === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        } elseif ($this->userModel->isEmailTaken($input['email'], $excludeId)) {
            $errors[] = 'Email is already in use by another account.';
        }

        if ($input['password'] !== '') {
            if (strlen($input['password']) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($input['password'] !== $input['confirm_password']) {
                $errors[] = 'Password confirmation does not match.';
            }
        }

        return $errors;
    }

    private function requireAuth(array $roles): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/?page=auth&action=login');
            exit;
        }
        if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
            http_response_code(403);
            include_once BASE_PATH . '/views/403.php';
            exit;
        }
    }

    private function verifyCsrf(): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
    }

    private function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    private function redirect(string $page, string $action = 'index', array $extra = []): void
    {
        $query = http_build_query(array_merge(['page' => $page, 'action' => $action], $extra));
        header('Location: ' . BASE_URL . '/?' . $query);
        exit;
    }
}
