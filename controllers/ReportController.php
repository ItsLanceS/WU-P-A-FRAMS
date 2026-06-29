<?php
class ReportController
{
    private Attendance $attendanceModel;
    private Student    $studentModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
        $this->studentModel    = new Student();
    }

    public function index(): void
    {
        $this->requireAuth(['admin', 'teacher']);

        $students   = $this->studentModel->getAll();
        $dateFrom   = $_GET['date_from']  ?? date('Y-m-01');
        $dateTo     = $_GET['date_to']    ?? date('Y-m-d');
        $studentId  = (int)($_GET['student_id'] ?? 0);
        $statusF    = $_GET['status']     ?? '';
        $courseF    = $_GET['course']     ?? '';

        $filters = array_filter([
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'student_id' => $studentId ?: null,
            'status'     => $statusF,
            'course'     => $courseF,
        ]);

        $records = $this->attendanceModel->getAll($filters);

        // CSV/PDF export
        if (!empty($_GET['export'])) {
            $format = $_GET['export'];
            if ($format === 'csv') {
                $this->exportCsv($records);
                return;
            }
        }

        // Unique courses for filter dropdown
        $courses = array_unique(array_column($students, 'course'));
        sort($courses);

        include BASE_PATH . '/views/reports/index.php';
    }

    // ── CSV Export ────────────────────────────────────────

    private function exportCsv(array $records): void
    {
        $filename = 'attendance_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['#', 'Student ID', 'Name', 'Course', 'Section', 'Date', 'Time In', 'Time Out', 'Status', 'Method', 'Confidence', 'Notes']);

        $i = 1;
        foreach ($records as $row) {
            fputcsv($out, [
                $i++,
                $row['sid'],
                $row['student_name'],
                $row['course'],
                $row['section'],
                $row['date'],
                $row['time_in']   ?? '',
                $row['time_out']  ?? '',
                strtoupper($row['status']),
                $row['marked_by'],
                $row['confidence'] ? $row['confidence'] . '%' : '',
                $row['notes'] ?? '',
            ]);
        }
        fclose($out);
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
}
