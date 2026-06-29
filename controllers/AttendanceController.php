<?php
class AttendanceController
{
    private Attendance $attendanceModel;
    private Student    $studentModel;
    private AttendanceEvent $eventModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->studentModel    = new Student();
        $this->eventModel      = new AttendanceEvent();
    }

    // ── Live attendance page (webcam) ─────────────────────

    public function index(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $today           = date('Y-m-d');
        $selectedEventId = (int)($_GET['event_id'] ?? 0);
        $eventsToday     = $this->eventModel->getByDate($today);
        $recentLogs      = $this->attendanceModel->getRecentToday($today, 20, $selectedEventId ?: null);
        include BASE_PATH . '/views/attendance/index.php';
    }

    public function log_partial(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $selectedEventId = (int)($_GET['event_id'] ?? 0);
        $recentLogs = $this->attendanceModel->getRecentToday(date('Y-m-d'), 20, $selectedEventId ?: null);
        include BASE_PATH . '/views/attendance/_log_rows.php';
    }

    public function events(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $title       = trim((string)($_POST['title'] ?? ''));
                $eventDate   = (string)($_POST['event_date'] ?? date('Y-m-d'));
                $timeInStart = (string)($_POST['time_in_start'] ?? '08:00');
                $timeOutEnd  = (string)($_POST['time_out_end'] ?? '17:00');
                $lateTime    = (string)($_POST['late_time'] ?? '08:30');
                $isActive    = !empty($_POST['is_active']);

                if ($title === '') {
                    $errors[] = 'Event title is required.';
                }

                if (strtotime($timeInStart) === false || strtotime($timeOutEnd) === false || strtotime($lateTime) === false) {
                    $errors[] = 'Invalid event time values.';
                } elseif (strtotime($lateTime) < strtotime($timeInStart) || strtotime($lateTime) > strtotime($timeOutEnd)) {
                    $errors[] = 'Late cutoff must be within the event time window.';
                }

                if (empty($errors)) {
                    $this->eventModel->create([
                        'title'         => $title,
                        'event_date'    => $eventDate,
                        'time_in_start' => $timeInStart . ':00',
                        'time_out_end'  => $timeOutEnd . ':00',
                        'late_time'     => $lateTime . ':00',
                        'is_active'     => $isActive,
                        'created_by'    => (int)($_SESSION['user_id'] ?? 0),
                    ]);
                    $this->setFlash('success', 'Attendance event created.');
                    $this->redirect('attendance', 'events', ['date' => $eventDate]);
                }
            }
        }

        $filterDate = (string)($_GET['date'] ?? date('Y-m-d'));
        $events = $this->eventModel->getByDate($filterDate);
        include BASE_PATH . '/views/attendance/events.php';
    }

    // ── Manual attendance page ────────────────────────────

    public function manual(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $students = $this->studentModel->getAll();
        $errors   = [];
        $entryDate = (string)($_POST['date'] ?? date('Y-m-d'));
        $eventsForEntry = $this->eventModel->getByDate($entryDate);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $studentId = (int)($_POST['student_id'] ?? 0);
                $date      = $_POST['date']   ?? date('Y-m-d');
                $eventId   = (int)($_POST['event_id'] ?? 0);
                $status    = $_POST['status'] ?? 'present';
                $timeIn    = !empty($_POST['time_in'])  ? $_POST['time_in']  : null;
                $timeOut   = !empty($_POST['time_out']) ? $_POST['time_out'] : null;
                $notes     = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
                $lateCutoff = LATE_TIME;

                if ($eventId > 0) {
                    $event = $this->eventModel->findById($eventId);
                    if (!$event || $event['event_date'] !== $date) {
                        $errors[] = 'Selected event is invalid for the chosen date.';
                    } else {
                        $lateCutoff = (string)$event['late_time'];
                    }
                }

                if (!$studentId) {
                    $errors[] = 'Please select a student.';
                } elseif (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
                    $errors[] = 'Invalid status.';
                } elseif (empty($errors)) {
                    $existing = $this->attendanceModel->getByStudentAndDate($studentId, $date, $eventId ?: null);
                    if ($existing) {
                        $this->attendanceModel->manualUpdate($existing['id'], [
                            'event_id'  => $eventId ?: null,
                            'time_in'  => $timeIn,
                            'time_out' => $timeOut,
                            'status'   => $status,
                            'late_cutoff_time' => $lateCutoff,
                            'notes'    => $notes,
                        ]);
                        $this->setFlash('success', 'Attendance record updated.');
                    } else {
                        $this->attendanceModel->manualCreate($studentId, [
                            'date'     => $date,
                            'event_id' => $eventId ?: null,
                            'time_in'  => $timeIn,
                            'time_out' => $timeOut,
                            'status'   => $status,
                            'late_cutoff_time' => $lateCutoff,
                            'notes'    => $notes,
                        ]);
                        $this->setFlash('success', 'Attendance record created.');
                    }
                    $this->redirect('attendance', 'manual');
                }
            }
        }

        $filterDate = (string)($_GET['filter_date'] ?? date('Y-m-d'));
        $filterEventId = (int)($_GET['filter_event_id'] ?? 0);
        $eventsForFilter = $this->eventModel->getByDate($filterDate);

        include BASE_PATH . '/views/attendance/manual.php';
    }

    // ── Edit existing record ──────────────────────────────

    public function edit(): void
    {
        $this->requireAuth(['admin', 'teacher']);
        $id     = (int)($_GET['id'] ?? 0);
        $record = $this->attendanceModel->findById($id);
        if (!$record) { $this->notFound(); return; }

        $students = $this->studentModel->getAll();
        $errors   = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf()) {
                $errors[] = 'Invalid CSRF token.';
            } else {
                $eventId = (int)($_POST['event_id'] ?? (int)($record['event_id'] ?? 0));
                $lateCutoff = LATE_TIME;
                if ($eventId > 0) {
                    $event = $this->eventModel->findById($eventId);
                    if ($event && $event['event_date'] === $record['date']) {
                        $lateCutoff = (string)$event['late_time'];
                    }
                }

                $this->attendanceModel->manualUpdate($id, [
                    'event_id' => $eventId ?: null,
                    'time_in'  => !empty($_POST['time_in'])  ? $_POST['time_in']  : null,
                    'time_out' => !empty($_POST['time_out']) ? $_POST['time_out'] : null,
                    'status'   => $_POST['status'] ?? 'present',
                    'late_cutoff_time' => $lateCutoff,
                    'notes'    => htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                ]);
                $this->setFlash('success', 'Attendance updated.');
                $this->redirect('attendance', 'manual');
            }
        }

        $eventsForDate = $this->eventModel->getByDate((string)$record['date']);

        include BASE_PATH . '/views/attendance/edit.php';
    }

    // ── Delete record ─────────────────────────────────────

    public function delete(): void
    {
        $this->requireAuth(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        $this->attendanceModel->delete($id);
        $this->setFlash('success', 'Record deleted.');
        $this->redirect('attendance', 'manual');
    }

    // ── AJAX: mark attendance via facial recognition ──────

    public function mark(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Unauthorized']); return;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $studentSid = $input['student_id']  ?? '';  // string student_id from Python
        $confidence = (float)($input['confidence'] ?? 0);
        $action     = $input['action']       ?? 'time_in'; // time_in | time_out
        $eventId    = (int)($input['event_id'] ?? 0);

        if (empty($studentSid) || $confidence < RECOGNITION_MIN_CONF) {
            echo json_encode(['error' => 'Confidence too low or no student identified']);
            return;
        }

        $student = (new Student())->findByStudentId($studentSid);
        if (!$student) {
            echo json_encode(['error' => 'Student not found in database']);
            return;
        }

        $today  = date('Y-m-d');
        $time   = date('H:i:s');
        $lateCutoff = LATE_TIME;
        if ($eventId > 0) {
            $event = $this->eventModel->findById($eventId);
            if (!$event || $event['event_date'] !== $today || !(int)$event['is_active']) {
                echo json_encode(['error' => 'Selected event is invalid or inactive for today']);
                return;
            }
            $lateCutoff = (string)$event['late_time'];
        }

        $record = $this->attendanceModel->getByStudentAndDate((int)$student['id'], $today, $eventId ?: null);

        if ($action === 'time_in') {
            if ($record) {
                echo json_encode(['warning' => 'Already marked time-in today', 'student' => $student['name']]);
                return;
            }
            $this->attendanceModel->markTimeIn((int)$student['id'], $today, $time, $confidence, 'facial_recognition', $eventId ?: null, $lateCutoff);
            echo json_encode(['success' => true, 'message' => 'Time-in marked', 'student' => $student['name'], 'time' => $time]);
        } else {
            if (!$record) {
                echo json_encode(['warning' => 'No time-in record found for today', 'student' => $student['name']]);
                return;
            }
            if ($record['time_out']) {
                echo json_encode(['warning' => 'Time-out already recorded', 'student' => $student['name']]);
                return;
            }
            $this->attendanceModel->markTimeOut((int)$record['id'], $time);
            echo json_encode(['success' => true, 'message' => 'Time-out marked', 'student' => $student['name'], 'time' => $time]);
        }
    }

    // ── Helpers ───────────────────────────────────────────

    private function requireAuth(array $roles): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/?page=auth&action=login'); exit;
        }
        if (!in_array($_SESSION['user_role'] ?? '', $roles)) {
            http_response_code(403); include BASE_PATH . '/views/403.php'; exit;
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
        header('Location: ' . BASE_URL . '/?' . $qs); exit;
    }

    private function notFound(): void
    {
        http_response_code(404); include BASE_PATH . '/views/404.php';
    }
}
