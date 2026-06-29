<?php
class StudentController
{
    private Student  $studentModel;
    private FaceData $faceModel;

    public function __construct()
    {
        $this->studentModel = new Student();
        $this->faceModel    = new FaceData();
    }

    // ── List ──────────────────────────────────────────────

    public function index(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $search   = trim($_GET['search'] ?? '');
        $students = $this->studentModel->getAll($search);
        include BASE_PATH . '/views/students/index.php';
    }

    // ── Create ────────────────────────────────────────────

    public function create(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $errors = [];
        $generatedStudentId = $this->studentModel->generateStudentId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $errors = $this->validateStudentInput($_POST, 0, false);
                if (empty($errors)) {
                    $photo = $this->handlePhotoUpload();
                    $id    = $this->studentModel->create(array_merge($_POST, [
                        'student_id' => $generatedStudentId,
                        'photo' => $photo,
                    ]));

                    // Handle face image uploads
                    if (!empty($_FILES['face_images']['name'][0])) {
                        $this->saveFaceImages((int)$id);
                    }
                    $this->saveWebcamImages((int)$id, $_POST['webcam_images'] ?? '');

                    $this->setFlash('success', 'Student added successfully.');
                    $this->redirect('students');
                }
            }
        }

        include BASE_PATH . '/views/students/create.php';
    }

    // ── Edit ──────────────────────────────────────────────

    public function edit(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $id      = (int)($_GET['id'] ?? 0);
        $student = $this->studentModel->findById($id);
        if (!$student) { $this->notFound(); return; }

        $faceImages = $this->faceModel->getByStudentId($id);
        $errors     = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                // Student ID is system-generated and immutable once created.
                $_POST['student_id'] = (string)$student['student_id'];
                $errors = $this->validateStudentInput($_POST, $id);
                if (empty($errors)) {
                    $this->studentModel->update($id, $_POST);

                    // Optional new profile photo
                    $photo = $this->handlePhotoUpload();
                    if ($photo) $this->studentModel->updatePhoto($id, $photo);

                    // Additional face images
                    if (!empty($_FILES['face_images']['name'][0])) {
                        $this->saveFaceImages($id);
                    }
                    $this->saveWebcamImages($id, $_POST['webcam_images'] ?? '');

                    // Re-enroll on Python side after update
                    $this->triggerEnrollment($id);

                    $this->setFlash('success', 'Student updated successfully.');
                    $this->redirect('students');
                }
            }
        }

        include BASE_PATH . '/views/students/edit.php';
    }

    // ── Delete face image ─────────────────────────────────

    public function delete_face(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $faceId    = (int)($_GET['face_id'] ?? 0);
        $studentId = (int)($_GET['student_id'] ?? 0);

        $this->faceModel->delete($faceId);
        $this->triggerEnrollment($studentId);

        $this->setFlash('success', 'Face image removed.');
        $this->redirect('students', 'edit', ['id' => $studentId]);
    }

    // ── Soft delete student ───────────────────────────────

    public function delete(): void
    {
        $this->requireAuth(['admin']);
        $id      = (int)($_GET['id'] ?? 0);
        $student = $this->studentModel->findById($id);
        $this->studentModel->delete($id);
        if ($student && !empty($student['student_id'])) {
            $this->triggerUnenrollment((string)$student['student_id']);
        }
        $this->setFlash('success', 'Student removed.');
        $this->redirect('students');
    }

    // ── Enroll faces to Python API ────────────────────────

    public function enroll(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $id      = (int)($_GET['id'] ?? 0);
        $student = $this->studentModel->findById($id);
        if (!$student) { echo json_encode(['error' => 'Student not found']); return; }

        $result = $this->triggerEnrollment($id);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // ── Private helpers ───────────────────────────────────

    private function validateStudentInput(array $post, int $excludeId = 0, bool $requireStudentId = true): array
    {
        $errors = [];
        if ($requireStudentId && empty($post['student_id'])) $errors[] = 'Student ID is required.';
        if (empty($post['name']))       $errors[] = 'Name is required.';

        // Duplicate student_id check
        if (!empty($post['student_id'])) {
            $existing = $this->studentModel->findByStudentId(trim($post['student_id']));
            if ($existing && (int)$existing['id'] !== $excludeId) {
                $errors[] = 'Student ID already exists.';
            }
        }
        return $errors;
    }

    private function handlePhotoUpload(): ?string
    {
        if (empty($_FILES['photo']['name'])) return null;

        $file     = $_FILES['photo'];
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) return null;
        if ($file['size'] > MAX_UPLOAD_SIZE) return null;

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'photo_' . uniqid() . '.' . $ext;
        $dest     = UPLOAD_PATH . $filename;

        if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return $filename;
        }
        return null;
    }

    private function saveFaceImages(int $studentId): void
    {
        $existing = $this->faceModel->countByStudentId($studentId);
        $files    = $_FILES['face_images'];

        foreach ($files['tmp_name'] as $idx => $tmp) {
            if ($existing >= MAX_FACE_IMAGES) break;
            if ($files['error'][$idx] !== UPLOAD_ERR_OK) continue;

            $mimeType = mime_content_type($tmp);
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) continue;
            if ($files['size'][$idx] > MAX_UPLOAD_SIZE) continue;

            $ext      = pathinfo($files['name'][$idx], PATHINFO_EXTENSION);
            $filename = 'face_' . $studentId . '_' . uniqid() . '.' . $ext;
            $dest     = UPLOAD_PATH . $filename;

            if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
            if (move_uploaded_file($tmp, $dest)) {
                $this->faceModel->create($studentId, $filename);
                $existing++;
            }
        }
    }

    private function saveWebcamImages(int $studentId, string $rawImages): void
    {
        if (trim($rawImages) === '') {
            return;
        }

        $images = json_decode($rawImages, true);
        if (!is_array($images)) {
            return;
        }

        $existing = $this->faceModel->countByStudentId($studentId);
        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        foreach ($images as $image) {
            if ($existing >= MAX_FACE_IMAGES || !is_string($image)) {
                break;
            }

            if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $image, $matches)) {
                continue;
            }

            $mimeType = strtolower($matches[1]);
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
                continue;
            }

            $binary = base64_decode($matches[2], true);
            if ($binary === false || strlen($binary) > MAX_UPLOAD_SIZE) {
                continue;
            }

            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => null,
            };

            if ($extension === null) {
                continue;
            }

            $filename = 'face_' . $studentId . '_' . uniqid() . '.' . $extension;
            $dest     = UPLOAD_PATH . $filename;

            if (file_put_contents($dest, $binary) !== false) {
                $detectedMime = mime_content_type($dest) ?: '';
                if (in_array($detectedMime, ALLOWED_IMAGE_TYPES, true)) {
                    $this->faceModel->create($studentId, $filename);
                    $existing++;
                    continue;
                }
                @unlink($dest);
            }
        }
    }

    private function triggerEnrollment(int $studentId): array
    {
        $student    = $this->studentModel->findById($studentId);
        $faceImages = $this->faceModel->getByStudentId($studentId);
        if (!$student) {
            return ['error' => 'No face images to enroll'];
        }

        if (empty($faceImages)) {
            return $this->triggerUnenrollment((string)$student['student_id']);
        }

        $base64Images = [];
        foreach ($faceImages as $fd) {
            $path = UPLOAD_PATH . $fd['image_path'];
            if (file_exists($path)) {
                $mime          = mime_content_type($path);
                $base64Images[] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        if (empty($base64Images)) return ['error' => 'Image files not found'];

        $payload = json_encode([
            'student_id' => (string)$student['student_id'],
            'name'       => $student['name'],
            'images'     => $base64Images,
        ]);

        $ch = curl_init(PYTHON_API_URL . '/enroll');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => PYTHON_API_TIMEOUT,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? (json_decode($response, true) ?? ['raw' => $response]) : ['error' => 'Python API unreachable'];
    }

    private function triggerUnenrollment(string $studentId): array
    {
        $payload = json_encode([
            'student_id' => $studentId,
        ]);

        $ch = curl_init(PYTHON_API_URL . '/unenroll');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => PYTHON_API_TIMEOUT,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? (json_decode($response, true) ?? ['raw' => $response]) : ['error' => 'Python API unreachable'];
    }

    private function requireAuth(array $roles): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/?page=auth&action=login');
            exit;
        }
        if (!in_array($_SESSION['user_role'] ?? '', $roles)) {
            http_response_code(403);
            include BASE_PATH . '/views/403.php';
            exit;
        }
    }

    private function verifyCsrf(): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
    }

    private function setFlash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }

    private function redirect(string $page, string $action = 'index', array $extra = []): void
    {
        $qs = http_build_query(array_merge(['page' => $page, 'action' => $action], $extra));
        header('Location: ' . BASE_URL . '/?' . $qs);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        include BASE_PATH . '/views/404.php';
    }
}
