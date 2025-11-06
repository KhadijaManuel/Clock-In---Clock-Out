<?php
/**
 * Database Connection Class
 * Handles MySQLi connection with prepared statement helpers
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the MySQLi connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute a prepared SELECT query
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @param string $types Parameter types (e.g., 'ssi' for string, string, int)
     * @return array Result set as associative array
     */
    public function query($query, $params = [], $types = '') {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detect types if not provided
                $types = $this->detectTypes($params);
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result === false) {
            $stmt->close();
            return [];
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }

    /**
     * Execute a prepared INSERT/UPDATE/DELETE query
     * @param string $query SQL query with ? placeholders
     * @param array $params Parameters to bind
     * @param string $types Parameter types
     * @return bool|int True on success, insert ID for INSERT, false on failure
     */
    public function execute($query, $params = [], $types = '') {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        if (!empty($params)) {
            if (empty($types)) {
                $types = $this->detectTypes($params);
            }
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        
        if (!$success) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Execute failed: " . $error);
        }

        // Return insert ID for INSERT queries
        if (stripos(trim($query), 'INSERT') === 0) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return $insertId;
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows > 0;
    }

    /**
     * Auto-detect parameter types
     */
    private function detectTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob
            }
        }
        return $types;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->connection->rollback();
    }

    /**
     * Escape string for SQL
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function for quick access
function db() {
    return Database::getInstance();
}