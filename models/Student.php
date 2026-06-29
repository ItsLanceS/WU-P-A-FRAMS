<?php
class Student
{
    private const STUDENT_ID_PREFIX = 'FRAMS';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(string $search = ''): array
    {
        if ($search) {
            return $this->db->fetchAll(
                'SELECT s.*, COUNT(fd.id) AS face_count
                 FROM students s
                 LEFT JOIN face_data fd ON fd.student_id = s.id
                 WHERE s.is_active = 1
                   AND (s.name LIKE ? OR s.student_id LIKE ? OR s.course LIKE ?)
                 GROUP BY s.id
                 ORDER BY s.name',
                ["%$search%", "%$search%", "%$search%"]
            );
        }
        return $this->db->fetchAll(
            'SELECT s.*, COUNT(fd.id) AS face_count
             FROM students s
             LEFT JOIN face_data fd ON fd.student_id = s.id
             WHERE s.is_active = 1
             GROUP BY s.id
             ORDER BY s.name'
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM students WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public function findByStudentId(string $studentId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM students WHERE student_id = ? LIMIT 1',
            [$studentId]
        );
    }

    public function findActiveByStudentId(string $studentId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM students WHERE student_id = ? AND is_active = 1 LIMIT 1',
            [$studentId]
        );
    }

    public function generateStudentId(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $part1 = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $part2 = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $candidate = self::STUDENT_ID_PREFIX . '-' . $part1 . '-' . $part2;
            if (!$this->findByStudentId($candidate)) {
                return $candidate;
            }
        }

        $fallback = self::STUDENT_ID_PREFIX . '-' . date('mdHi') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        if (!$this->findByStudentId($fallback)) {
            return $fallback;
        }

        throw new RuntimeException('Could not generate unique student ID.');
    }

    public function count(): int
    {
        $row = $this->db->fetch('SELECT COUNT(*) AS cnt FROM students WHERE is_active = 1');
        return (int)($row['cnt'] ?? 0);
    }

    public function create(array $data): string
    {
        return $this->db->insert(
            'INSERT INTO students (student_id, name, email, course, year, section, photo)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['student_id'],
                $data['name'],
                $data['email']   ?? '',
                $data['course']  ?? '',
                $data['year']    ?? '',
                $data['section'] ?? '',
                $data['photo']   ?? null,
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return $this->db->execute(
            'UPDATE students
             SET student_id = ?, name = ?, email = ?, course = ?, year = ?, section = ?, updated_at = NOW()
             WHERE id = ?',
            [
                $data['student_id'],
                $data['name'],
                $data['email']   ?? '',
                $data['course']  ?? '',
                $data['year']    ?? '',
                $data['section'] ?? '',
                $id,
            ]
        );
    }

    public function updatePhoto(int $id, string $photo): int
    {
        return $this->db->execute(
            'UPDATE students SET photo = ?, updated_at = NOW() WHERE id = ?',
            [$photo, $id]
        );
    }

    public function delete(int $id): int
    {
        // Hard delete - removes student and all related data via CASCADE
        return $this->db->execute(
            'DELETE FROM students WHERE id = ?',
            [$id]
        );
    }
}
