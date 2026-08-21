<?php
/**
 * Kamadenu Goushala Platform - Core Application Configuration
 */

declare(strict_types=1);

// Enable Output Buffering
if (!ob_get_level()) {
    ob_start();
}

// Application Constants
defined('KAMADENU_ROOT') || define('KAMADENU_ROOT', dirname(__DIR__));
defined('APP_NAME') || define('APP_NAME', 'Kamadenu Goushala');
defined('APP_TAGLINE') || define('APP_TAGLINE', 'Preserving Sacred Indigenous Cows with Vedic Care & Love');
defined('APP_VERSION') || define('APP_VERSION', '1.0.0');
defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'Asia/Kolkata');

// Set default timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting configuration
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    // In production with HTTPS:
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    
    session_start();
}

// Detect Base URL dynamically
function detect_base_url(): string {
    if (php_sapi_name() === 'cli') {
        return 'http://localhost/kamadenu1';
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Normalize script directory path
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = dirname($scriptName);
    
    // If inside /admin or /ajax subfolder, get root dir
    $dir = preg_replace('#/(admin|ajax)(/.*)?$#i', '', $dir);
    $basePath = ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/\\');
    
    return $protocol . $host . $basePath;
}

defined('BASE_URL') || define('BASE_URL', detect_base_url());
defined('ASSETS_URL') || define('ASSETS_URL', BASE_URL . '/assets');
defined('UPLOADS_URL') || define('UPLOADS_URL', BASE_URL . '/uploads');
defined('ADMIN_URL') || define('ADMIN_URL', BASE_URL . '/admin');

// Include Database configuration
require_once KAMADENU_ROOT . '/config/database.php';

// Class Autoloader
spl_autoload_register(function ($class) {
    $classFile = KAMADENU_ROOT . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return;
    }
    
    $serviceFile = KAMADENU_ROOT . '/services/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($serviceFile)) {
        require_once $serviceFile;
        return;
    }
});

// Include Core Functions & Security Guards
require_once KAMADENU_ROOT . '/includes/functions.php';
require_once KAMADENU_ROOT . '/includes/csrf.php';
require_once KAMADENU_ROOT . '/includes/auth.php';
