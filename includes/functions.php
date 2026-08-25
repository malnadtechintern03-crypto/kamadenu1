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
    // Trim whitespace and ensure we have a string
    $filename = trim((string)$filename);

    // If no filename supplied, return fallback placeholder from assets
    if ($filename === '') {
        return BASE_URL . '/assets/images/' . $fallback;
    }

    // If the stored filename already includes a path prefix (uploads/ or assets/),
    // we can use it directly – this covers cases where the DB stores the full
    // relative path instead of just the bare filename.
    if (str_starts_with($filename, 'uploads/') || str_starts_with($filename, 'assets/')) {
        $fullPath = dirname(__DIR__) . '/' . ltrim($filename, '/');
        if (is_file($fullPath)) {
            return BASE_URL . '/' . ltrim($filename, '/');
        }
    }

    // At this point we only have a plain filename; construct a safe filename.
    $safeFilename = basename($filename);

    // 1️⃣ Try the expected upload folder: /uploads/{folder}/{filename}
    $uploadPath = dirname(__DIR__) . '/uploads/' . $folder . '/' . $safeFilename;
    if (is_file($uploadPath)) {
        return BASE_URL . '/uploads/' . $folder . '/' . rawurlencode($safeFilename);
    }

    // 2️⃣ Fallback to assets sub‑folder: /assets/images/{folder}/{filename}
    $assetSubfolderPath = dirname(__DIR__) . '/assets/images/' . $folder . '/' . $safeFilename;
    if (is_file($assetSubfolderPath)) {
        return BASE_URL . '/assets/images/' . $folder . '/' . rawurlencode($safeFilename);
    }

    // 3️⃣ If everything fails, serve the generic placeholder image.
    return BASE_URL . '/assets/images/' . $fallback;
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

/**
 * Retrieve list of configured WhatsApp department lines from settings.
 * Returns array of [ ['id' => ..., 'label' => ..., 'phone' => ..., 'is_default' => 0|1] ]
 */
function get_whatsapp_numbers(): array {
    $raw = get_setting('whatsapp_numbers_list', '');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    $primary = get_setting('site_whatsapp', get_setting('whatsapp_number', '+91 98450 12345'));
    return [
        ['id' => 'wa_1', 'label' => 'Primary Seva Helpline', 'phone' => $primary, 'is_default' => 1],
        ['id' => 'wa_2', 'label' => 'Organic Store & Orders Desk', 'phone' => '+91 98450 67890', 'is_default' => 0],
        ['id' => 'wa_3', 'label' => 'Cow Adoption & Sponsorships', 'phone' => '+91 98450 11223', 'is_default' => 0],
    ];
}

/**
 * Retrieve the active primary WhatsApp number for the platform.
 */
function get_primary_whatsapp_number(): string {
    $numbers = get_whatsapp_numbers();
    foreach ($numbers as $num) {
        if (!empty($num['is_default']) && !empty($num['phone'])) {
            return $num['phone'];
        }
    }
    return !empty($numbers[0]['phone']) ? $numbers[0]['phone'] : get_setting('site_whatsapp', '+91 98450 12345');
}

/**
 * Retrieve configured Goushala visiting and opening/closing hours.
 */
function get_goushala_timings(): array {
    $morning = get_setting('visiting_hours_morning', '06:30 AM - 12:30 PM');
    $evening = get_setting('visiting_hours_evening', '04:00 PM - 07:30 PM');
    $days = get_setting('visiting_days', 'Open All 7 Days • Monday to Sunday');
    $aarti = get_setting('aarti_timings', 'Morning Gomata Aarti: 06:30 AM | Sandhya Deepa Aarti: 06:45 PM');
    $note = get_setting('goushala_timings_note', 'Devotees and families are warmly welcome for sacred Gomata Darshan, fresh grass feeding, and sanctuary parikrama.');
    $override = get_setting('visiting_status_override', 'auto');

    // Calculate current live status based on local time
    $isOpen = true;
    if ($override === 'closed') {
        $isOpen = false;
        $statusText = 'Closed for the Day';
    } elseif ($override === 'open') {
        $isOpen = true;
        $statusText = 'Open for Darshan Now';
    } elseif ($override === 'festival_special') {
        $isOpen = true;
        $statusText = 'Festival Special Darshan Open';
    } else {
        // Auto calculation: check slots (06:30 to 12:30 & 16:00 to 19:30)
        $nowH = (int)date('H');
        $nowM = (int)date('i');
        $nowMinutes = ($nowH * 60) + $nowM;
        
        $mStart = (6 * 60) + 30; // 06:30 AM
        $mEnd = (12 * 60) + 30;  // 12:30 PM
        $eStart = (16 * 60) + 0; // 04:00 PM
        $eEnd = (19 * 60) + 30;  // 07:30 PM

        $isMorning = ($nowMinutes >= $mStart && $nowMinutes <= $mEnd);
        $isEvening = ($nowMinutes >= $eStart && $nowMinutes <= $eEnd);
        $isOpen = ($isMorning || $isEvening);

        if ($isOpen) {
            $statusText = $isMorning ? 'Open Now (Morning Slot)' : 'Open Now (Evening Slot)';
        } else {
            if ($nowMinutes < $mStart) {
                $statusText = 'Opens at 06:30 AM';
            } elseif ($nowMinutes > $mEnd && $nowMinutes < $eStart) {
                $statusText = 'Afternoon Rest (Opens 04:00 PM)';
            } else {
                $statusText = 'Closed for Night (Opens 06:30 AM)';
            }
        }
    }

    return [
        'morning' => $morning,
        'evening' => $evening,
        'days'    => $days,
        'aarti'   => $aarti,
        'note'    => $note,
        'override'=> $override,
        'is_open' => $isOpen,
        'status_text' => $statusText,
        'full_display' => "{$morning} & {$evening}"
    ];
}


