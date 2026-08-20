<?php
/**
 * Kamadenu Goushala Platform - Database Configuration
 * PDO Connection Singleton with UTF8mb4 & Secure Options
 */

declare(strict_types=1);

if (!defined('KAMADENU_ROOT')) {
    define('KAMADENU_ROOT', dirname(__DIR__));
}

// Database Credentials
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', 'kamadenu');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_PORT') || define('DB_PORT', '3306');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/**
 * Returns the singleton PDO database connection instance.
 *
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+05:30'"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            // In development or when requested, provide clean message
            throw new RuntimeException('Unable to establish database connection. Please ensure MySQL service is running.');
        }
    }

    return $pdo;
}
