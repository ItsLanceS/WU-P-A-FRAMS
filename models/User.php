<?php
class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        );
    }

    public function findAnyByEmail(string $email): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [$email]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetch(
            'SELECT id, name, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public function getAll(): array
    {
        return $this->db->fetchAll('SELECT id, name, email, role, is_active, created_at FROM users ORDER BY name');
    }

    public function getByRole(string $role): array
    {
        return $this->db->fetchAll(
            'SELECT id, name, email, role, is_active, created_at FROM users WHERE role = ? ORDER BY name',
            [$role]
        );
    }

    public function create(array $data): string
    {
        return $this->db->insert(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['role'],
            ]
        );
    }

    public function update(int $id, string $name, string $email): int
    {
        return $this->db->execute(
            'UPDATE users SET name = ?, email = ? WHERE id = ?',
            [$name, $email, $id]
        );
    }

    public function isEmailTaken(string $email, int $excludeId = 0): bool
    {
        return (bool)$this->db->fetch(
            'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1',
            [$email, $excludeId]
        );
    }

    public function updatePassword(int $id, string $newPassword): int
    {
        return $this->db->execute(
            'UPDATE users SET password = ? WHERE id = ?',
            [password_hash($newPassword, PASSWORD_BCRYPT), $id]
        );
    }

    public function toggleActive(int $id): int
    {
        return $this->db->execute(
            'UPDATE users SET is_active = NOT is_active WHERE id = ?',
            [$id]
        );
    }
}
