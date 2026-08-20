<?php
/**
 * Kamadenu Goushala Platform - Cross-Site Request Forgery (CSRF) Protection
 */

declare(strict_types=1);

/**
 * Generate or get existing CSRF token for the active session.
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden HTML input tag containing CSRF token.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate incoming CSRF token with constant-time string comparison.
 */
function validate_csrf_token(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF token on POST/PUT/DELETE requests or terminate execution with 403.
 */
function verify_csrf_or_die(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!validate_csrf_token($token)) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'CSRF verification failed. Please refresh the page.']);
            } else {
                echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>403 Forbidden</h2><p>Security validation failed (Invalid CSRF Token). Please return and refresh the form.</p><p><a href="javascript:history.back()">Go Back</a></p></body></html>';
            }
            exit;
        }
    }
}
