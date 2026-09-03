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

/**
 * Detect if the current request is served over HTTPS
 * (Handles direct SSL, Cloudflare, InfinityFree reverse proxies, load balancers)
 */
function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }
    if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        $visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
        if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
            return true;
        }
    }
    return false;
}

/**
 * Detect relative application base path
 * E.g., '' when installed directly in htdocs root (InfinityFree),
 * or '/kamadenu1' when installed in a subfolder (localhost/kamadenu1).
 */
function detect_base_path(): string {
    if (php_sapi_name() === 'cli') {
        return '/kamadenu1';
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    $dir = dirname($scriptName);
    
    // If inside /admin or /ajax subfolder, strip to reach app root dir
    $dir = preg_replace('#/(admin|ajax)(/.*)?$#i', '', $dir);
    return ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/\\');
}

/**
 * Detect full Base URL dynamically
 */
function detect_base_url(): string {
    if (php_sapi_name() === 'cli') {
        return 'http://localhost/kamadenu1';
    }

    $protocol = is_https() ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $basePath = detect_base_path();
    
    return $protocol . $host . $basePath;
}

defined('BASE_PATH') || define('BASE_PATH', detect_base_path());
defined('BASE_URL') || define('BASE_URL', detect_base_url());
defined('ASSETS_URL') || define('ASSETS_URL', (BASE_PATH !== '' ? BASE_PATH : '') . '/assets');
defined('UPLOADS_URL') || define('UPLOADS_URL', (BASE_PATH !== '' ? BASE_PATH : '') . '/uploads');
defined('ADMIN_URL') || define('ADMIN_URL', BASE_URL . '/admin');

// Include Database configuration
require_once KAMADENU_ROOT . '/config/database.php';

// Class & Service Autoloader with Case-Insensitive Fallback for Linux Servers
spl_autoload_register(function ($class) {
    $cleanClass = str_replace('\\', '/', $class);
    $baseDirs = [
        KAMADENU_ROOT . '/classes/',
        KAMADENU_ROOT . '/Classes/',
        dirname(__DIR__) . '/classes/',
        KAMADENU_ROOT . '/services/',
        KAMADENU_ROOT . '/Services/',
        dirname(__DIR__) . '/services/'
    ];

    $variations = [
        $cleanClass . '.php',
        strtolower($cleanClass) . '.php',
        ucfirst($cleanClass) . '.php'
    ];

    foreach ($baseDirs as $dir) {
        foreach ($variations as $file) {
            $path = $dir . $file;
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
});

// Include Core Functions & Security Guards
require_once KAMADENU_ROOT . '/includes/functions.php';
require_once KAMADENU_ROOT . '/includes/csrf.php';
require_once KAMADENU_ROOT . '/includes/auth.php';
