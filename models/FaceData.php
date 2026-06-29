<?php
class FaceData
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByStudentId(int $studentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM face_data WHERE student_id = ? ORDER BY created_at DESC',
            [$studentId]
        );
    }

    public function countByStudentId(int $studentId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS cnt FROM face_data WHERE student_id = ?',
            [$studentId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function create(int $studentId, string $imagePath): string
    {
        return $this->db->insert(
            'INSERT INTO face_data (student_id, image_path) VALUES (?, ?)',
            [$studentId, $imagePath]
        );
    }

    public function updateEncoding(int $id, string $encoding): int
    {
        return $this->db->execute(
            'UPDATE face_data SET encoding = ? WHERE id = ?',
            [$encoding, $id]
        );
    }

    public function delete(int $id): int
    {
        $row = $this->db->fetch('SELECT image_path FROM face_data WHERE id = ?', [$id]);
        if ($row && file_exists(UPLOAD_PATH . $row['image_path'])) {
            @unlink(UPLOAD_PATH . $row['image_path']);
        }
        return $this->db->execute('DELETE FROM face_data WHERE id = ?', [$id]);
    }

    public function deleteByStudentId(int $studentId): int
    {
        $rows = $this->getByStudentId($studentId);
        foreach ($rows as $row) {
            if (file_exists(UPLOAD_PATH . $row['image_path'])) {
                @unlink(UPLOAD_PATH . $row['image_path']);
            }
        }
        return $this->db->execute('DELETE FROM face_data WHERE student_id = ?', [$studentId]);
    }

    /** Return all students that have at least one face image stored. */
    public function getAllEnrolled(): array
    {
        return $this->db->fetchAll(
            'SELECT fd.*, s.name, s.student_id AS sid
             FROM face_data fd
             JOIN students s ON s.id = fd.student_id
             WHERE s.is_active = 1'
        );
    }
}
