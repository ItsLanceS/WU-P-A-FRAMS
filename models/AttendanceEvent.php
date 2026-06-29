<?php
class AttendanceEvent
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
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
    }

    public function create(array $data): string
    {
        return $this->db->insert(
            'INSERT INTO attendance_events (title, event_date, time_in_start, time_out_end, late_time, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['event_date'],
                $data['time_in_start'],
                $data['time_out_end'],
                $data['late_time'],
                !empty($data['is_active']) ? 1 : 0,
                $data['created_by'] ?? null,
            ]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch('SELECT * FROM attendance_events WHERE id = ? LIMIT 1', [$id]);
    }

    public function getByDate(string $date): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM attendance_events
             WHERE event_date = ?
             ORDER BY time_in_start ASC, id ASC',
            [$date]
        );
    }

    public function getRecent(int $limit = 50): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM attendance_events
             ORDER BY event_date DESC, time_in_start DESC
             LIMIT ?',
            [$limit]
        );
    }
}
