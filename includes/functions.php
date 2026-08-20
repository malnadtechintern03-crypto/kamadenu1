<?php
/**
 * Kamadenu Goushala Platform - Global Helper Functions & Security Utilities
 */

declare(strict_types=1);

/**
 * Escape HTML output for XSS prevention.
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize user input recursively.
 */
function sanitize_input(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    if (is_string($data)) {
        return trim(strip_tags($data));
    }
    return $data;
}

/**
 * Format currency to Indian Rupees (INR) with symbol.
 * Example: 25000 -> ₹ 25,000
 */
function format_inr(float|int|string $amount, bool $includeDecimals = false): string {
    $num = (float)$amount;
    if ($includeDecimals) {
        return '₹ ' . number_format($num, 2, '.', ',');
    }
    return '₹ ' . number_format($num, 0, '.', ',');
}

/**
 * Generate full URL for uploaded image or fallback placeholder.
 */
function image_url(
    ?string $filename,
    string $folder,
    string $fallback = 'placeholder.jpg'
): string {
    $filename = trim((string)$filename);

    if ($filename === '') {
        return BASE_URL . '/assets/images/' . $fallback;
    }

    $safeFilename = basename($filename);

    $filePath = dirname(__DIR__) . '/uploads/' . $folder . '/' . $safeFilename;

    if (!is_file($filePath)) {
        return BASE_URL . '/assets/images/' . $fallback;
    }

    return BASE_URL . '/uploads/' . $folder . '/' . rawurlencode($safeFilename);
}

/**
 * Retrieve a setting from database settings table with static cache.
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settingsCache[$row['setting_key']] = $row['setting_value'] ?? '';
            }
        } catch (Throwable $t) {
            error_log('Failed to load settings: ' . $t->getMessage());
        }
    }

    return $settingsCache[$key] ?? $default;
}

/**
 * Format date in friendly Indian format (e.g., "15 Jan 2024").
 */
function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception) {
        return $date;
    }
}

/**
 * Calculate cow's approximate age from date of birth.
 */
function calculate_cow_age(?string $dob): string {
    if (empty($dob) || $dob === '0000-00-00') {
        return 'Age Unknown';
    }
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $diff = $birthDate->diff($today);

        if ($diff->y > 0) {
            $yearStr = $diff->y . ' ' . ($diff->y === 1 ? 'Year' : 'Years');
            if ($diff->m > 0) {
                return $yearStr . ' ' . $diff->m . ' ' . ($diff->m === 1 ? 'Month' : 'Months');
            }
            return $yearStr;
        }

        if ($diff->m > 0) {
            return $diff->m . ' ' . ($diff->m === 1 ? 'Month' : 'Months');
        }

        return $diff->d . ' ' . ($diff->d === 1 ? 'Day' : 'Days');
    } catch (Exception) {
        return 'N/A';
    }
}

/**
 * Generate clean URL slug from string.
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Send clean JSON response with HTTP status code.
 */
function json_response(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Set flash message in session.
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash_messages'][] = [
            'type'    => $type, // success, danger, warning, info
            'message' => $message
        ];
    }
}

/**
 * Get and clear a specific flash message by type.
 */
function get_flash(string $type): ?string {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $i => $msg) {
            if ($msg['type'] === $type) {
                $text = $msg['message'];
                unset($_SESSION['flash_messages'][$i]);
                $_SESSION['flash_messages'] = array_values($_SESSION['flash_messages']);
                return $text;
            }
        }
    }
    return null;
}

/**
 * Get and clear all flash messages from session.
 */
function get_flash_messages(): array {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

/**
 * Render flash messages HTML alert.
 */
function render_flash_messages(): string {
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="flash-messages-container mb-4">';
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        $icon = match($type) {
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default   => 'bi-info-circle-fill'
        };

        $html .= sprintf(
            '<div class="alert alert-%s alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi %s me-2 fs-5"></i>
                <div class="flex-grow-1">%s</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>',
            $type,
            $icon,
            $text
        );
    }
    $html .= '</div>';
    return $html;
}

/**
 * Log activity in activity_logs table.
 */
function log_activity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        Database::insert($sql, [$userId, $action, $entityType, $entityId, $details, $ip, $ua]);
    } catch (Throwable $t) {
        error_log('Failed to log activity: ' . $t->getMessage());
    }
}
