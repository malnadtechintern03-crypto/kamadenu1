<?php
/**
 * Kamadenu Goushala Platform - Database Helper Class
 * Provides secure prepared statement execution and transactional safety.
 */

declare(strict_types=1);

class Database {
    private static ?PDO $pdo = null;

    /**
     * Get or initialize PDO connection.
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            self::$pdo = get_db_connection();
        }
        return self::$pdo;
    }

    /**
     * Execute a SELECT query and fetch all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a SELECT query and fetch a single row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Execute a query and fetch a single scalar value.
     */
    public static function fetchColumn(string $sql, array $params = [], int $columnIndex = 0): mixed {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn($columnIndex);
    }

    /**
     * Alias for fetchColumn.
     */
    public static function fetchValue(string $sql, array $params = [], int $columnIndex = 0): mixed {
        return self::fetchColumn($sql, $params, $columnIndex);
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query and return affected rows.
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a record and return the last inserted ID.
     */
    public static function insert(string $sql, array $params = []): int {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit active database transaction.
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    /**
     * Roll back active database transaction.
     */
    public static function rollBack(): bool {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }
}
