<?php
class DashboardController
{
    private Student    $studentModel;
    private Attendance $attendanceModel;

    public function __construct()
    {
        $this->studentModel    = new Student();
        $this->attendanceModel = new Attendance();
    }

    public function index(): void
    {
        $this->requireAuth();

        $today      = date('Y-m-d');
        $month      = (int)date('m');
        $year       = (int)date('Y');

        $totalStudents = $this->studentModel->count();
        $todayStats    = $this->attendanceModel->getTodayStats($today);
        $weeklyStats   = $this->attendanceModel->getWeeklyStats();
        $recentMarks   = $this->attendanceModel->getRecentToday($today, 10);

        // Absent = total students – those who have an attendance record today
        $markedToday         = (int)($todayStats['total']   ?? 0);
        $presentToday        = (int)($todayStats['present'] ?? 0);
        $lateToday           = (int)($todayStats['late']    ?? 0);
        $excusedToday        = (int)($todayStats['excused'] ?? 0);
        $absentToday         = max(0, $totalStudents - $markedToday);
        $todayStats['absent'] = $absentToday;

        include BASE_PATH . '/views/dashboard/index.php';
    }

    // ── Helpers ───────────────────────────────────────────

    private function requireAuth(string $role = ''): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/?page=auth&action=login');
            exit;
        }
        if ($role && $_SESSION['user_role'] !== $role) {
            http_response_code(403);
            include BASE_PATH . '/views/403.php';
            exit;
        }
    }
}
