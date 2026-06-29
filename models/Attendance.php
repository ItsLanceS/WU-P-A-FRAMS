<?php
class Attendance
{
    private Database $db;
    private static bool $schemaChecked = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        $this->db->execute(
            'CREATE TABLE IF NOT EXISTS attendance_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(120) NOT NULL,
                event_date DATE NOT NULL,
                time_in_start TIME NOT NULL,
                time_out_end TIME NOT NULL,
                late_time TIME NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_event_date (event_date),
                INDEX idx_event_active (is_active)
            ) ENGINE=InnoDB'
        );

        $eventColumn = $this->db->fetch("SHOW COLUMNS FROM attendance LIKE 'event_id'");
        if (!$eventColumn) {
            $this->db->execute('ALTER TABLE attendance ADD COLUMN event_id INT NULL AFTER student_id');
            $this->db->execute('ALTER TABLE attendance ADD INDEX idx_attendance_event (event_id)');
        }

        $lateColumn = $this->db->fetch("SHOW COLUMNS FROM attendance LIKE 'late_cutoff_time'");
        if (!$lateColumn) {
            $this->db->execute('ALTER TABLE attendance ADD COLUMN late_cutoff_time TIME NULL AFTER time_out');
        }

        $oldUnique = $this->db->fetch("SHOW INDEX FROM attendance WHERE Key_name = 'unique_attendance'");
        if ($oldUnique) {
            $this->db->execute('ALTER TABLE attendance DROP INDEX unique_attendance');
        }

        $eventUnique = $this->db->fetch("SHOW INDEX FROM attendance WHERE Key_name = 'unique_attendance_event'");
        if (!$eventUnique) {
            $this->db->execute('ALTER TABLE attendance ADD UNIQUE KEY unique_attendance_event (student_id, date, event_id)');
        }

        self::$schemaChecked = true;
    }

    // ── Lookup ────────────────────────────────────────────

    public function getByStudentAndDate(int $studentId, string $date, ?int $eventId = null): array|false
    {
        if (!empty($eventId)) {
            return $this->db->fetch(
                'SELECT * FROM attendance WHERE student_id = ? AND date = ? AND event_id = ? LIMIT 1',
                [$studentId, $date, $eventId]
            );
        }

        return $this->db->fetch(
            'SELECT * FROM attendance WHERE student_id = ? AND date = ? AND event_id IS NULL LIMIT 1',
            [$studentId, $date]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT a.*, s.name AS student_name, s.student_id AS sid, s.course, s.section,
                    e.title AS event_title, e.late_time AS event_late_time
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             LEFT JOIN attendance_events e ON e.id = a.event_id
             WHERE a.id = ? LIMIT 1',
            [$id]
        );
    }

    public function getAll(array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['date'])) {
            $where[]  = 'a.date = ?';
            $params[] = $filters['date'];
        }
        if (!empty($filters['student_id'])) {
            $where[]  = 'a.student_id = ?';
            $params[] = (int)$filters['student_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'a.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'a.date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'a.date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['course'])) {
            $where[]  = 's.course = ?';
            $params[] = $filters['course'];
        }
        if (array_key_exists('event_id', $filters)) {
            if (!empty($filters['event_id'])) {
                $where[]  = 'a.event_id = ?';
                $params[] = (int)$filters['event_id'];
            } else {
                $where[] = 'a.event_id IS NULL';
            }
        }

        $sql = 'SELECT a.*, s.name AS student_name, s.student_id AS sid, s.course, s.section,
                       e.title AS event_title, e.late_time AS event_late_time
                FROM attendance a
                JOIN students s ON s.id = a.student_id
                LEFT JOIN attendance_events e ON e.id = a.event_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.date DESC, a.time_in DESC';

        return $this->db->fetchAll($sql, $params);
    }

    // ── Writing ───────────────────────────────────────────

    /**
     * Time-in: create a new attendance record.
     * Status is 'present' or 'late' based on event late cutoff or LATE_TIME constant.
     */
    public function markTimeIn(
        int $studentId,
        string $date,
        string $time,
        float $confidence = 0.0,
        string $method = 'facial_recognition',
        ?int $eventId = null,
        ?string $lateCutoff = null
    ): string
    {
        $cutoff = $lateCutoff ?: LATE_TIME;
        $status = (strtotime($time) > strtotime($cutoff)) ? 'late' : 'present';
        return $this->db->insert(
            'INSERT INTO attendance (student_id, event_id, date, time_in, status, late_cutoff_time, marked_by, confidence)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$studentId, $eventId, $date, $time, $status, $cutoff, $method, round($confidence, 2)]
        );
    }

    /**
     * Time-out: update time_out on an existing record.
     */
    public function markTimeOut(int $attendanceId, string $time): int
    {
        return $this->db->execute(
            'UPDATE attendance SET time_out = ?, updated_at = NOW() WHERE id = ?',
            [$time, $attendanceId]
        );
    }

    public function manualCreate(int $studentId, array $data): string
    {
        return $this->db->insert(
            'INSERT INTO attendance (student_id, event_id, date, time_in, time_out, status, late_cutoff_time, marked_by, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, "manual", ?)',
            [
                $studentId,
                !empty($data['event_id']) ? (int)$data['event_id'] : null,
                $data['date'],
                $data['time_in']  ?? null,
                $data['time_out'] ?? null,
                $data['status'],
                $data['late_cutoff_time'] ?? LATE_TIME,
                $data['notes']    ?? '',
            ]
        );
    }

    public function manualUpdate(int $id, array $data): int
    {
        return $this->db->execute(
            'UPDATE attendance
             SET event_id = ?, time_in = ?, time_out = ?, status = ?, late_cutoff_time = ?, notes = ?, marked_by = "manual", updated_at = NOW()
             WHERE id = ?',
            [
                !empty($data['event_id']) ? (int)$data['event_id'] : null,
                $data['time_in']  ?? null,
                $data['time_out'] ?? null,
                $data['status'],
                $data['late_cutoff_time'] ?? LATE_TIME,
                $data['notes']    ?? '',
                $id,
            ]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM attendance WHERE id = ?', [$id]);
    }

    // ── Statistics ────────────────────────────────────────

    public function getTodayStats(string $date): array
    {
        return $this->db->fetch(
            'SELECT
                SUM(status = "present") AS present,
                SUM(status = "absent")  AS absent,
                SUM(status = "late")    AS late,
                SUM(status = "excused") AS excused,
                COUNT(*)                AS total
             FROM attendance WHERE date = ?',
            [$date]
        ) ?: ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
    }

    /** Returns 7 rows: one per day for the last 7 days. */
    public function getWeeklyStats(): array
    {
        return $this->db->fetchAll(
            'SELECT date,
                    SUM(status = "present") AS present,
                    SUM(status = "absent")  AS absent,
                    SUM(status = "late")    AS late,
                    SUM(status = "excused") AS excused,
                    COUNT(*)                AS total
             FROM attendance
             WHERE date >= CURDATE() - INTERVAL 6 DAY
             GROUP BY date
             ORDER BY date'
        );
    }

    public function getMonthlyStats(int $month, int $year): array
    {
        return $this->db->fetchAll(
            'SELECT date,
                    SUM(status = "present") AS present,
                    SUM(status = "absent")  AS absent,
                    SUM(status = "late")    AS late,
                    COUNT(*)                AS total
             FROM attendance
             WHERE MONTH(date) = ? AND YEAR(date) = ?
             GROUP BY date
             ORDER BY date',
            [$month, $year]
        );
    }

    public function getStudentAttendance(int $studentId, string $from, string $to): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM attendance
             WHERE student_id = ? AND date BETWEEN ? AND ?
             ORDER BY date DESC',
            [$studentId, $from, $to]
        );
    }

    public function getRecentToday(string $date, int $limit = 10, ?int $eventId = null): array
    {
        $where  = ['a.date = ?'];
        $params = [$date];

        if (!empty($eventId)) {
            $where[]  = 'a.event_id = ?';
            $params[] = $eventId;
        }

        $params[] = $limit;

        return $this->db->fetchAll(
            'SELECT a.*, s.name AS student_name, s.student_id AS sid, e.title AS event_title
             FROM attendance a
             JOIN students s ON s.id = a.student_id
             LEFT JOIN attendance_events e ON e.id = a.event_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY a.created_at DESC
             LIMIT ?',
            $params
        );
    }
}
