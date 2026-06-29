<?php
// ============================================================
// Database – Singleton PDO wrapper
// ============================================================
class Database
{
    private static ?Database $instance = null;
    private PDO $conn;

    // ── Edit these credentials if needed ──────────────────
    private string $host     = 'localhost';
    private string $dbname   = 'frams_db';
    private string $username = 'root';
    private string $password = '';
    // ──────────────────────────────────────────────────────

    private function __construct()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    /** Run a prepared statement and return the PDOStatement. */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /** Execute INSERT and return last insert ID. */
    public function insert(string $sql, array $params = []): string
    {
        $this->query($sql, $params);
        return $this->conn->lastInsertId();
    }

    /** Execute UPDATE/DELETE and return affected rows. */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }
}
