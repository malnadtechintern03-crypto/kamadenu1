<?php
/**
 * Kamadenu Goushala Platform - Authentication & Role Authorization Guard
 */

declare(strict_types=1);

/**
 * Check if an admin/staff user is currently authenticated.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_role']);
}

/**
 * Get currently authenticated user data.
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['admin_user_id'],
        'name'     => $_SESSION['admin_user_name'] ?? 'Staff',
        'email'    => $_SESSION['admin_user_email'] ?? '',
        'role'     => $_SESSION['admin_user_role'],
        'role_name'=> $_SESSION['admin_user_role_name'] ?? ucfirst(str_replace('_', ' ', $_SESSION['admin_user_role'])),
        'avatar'   => $_SESSION['admin_user_avatar'] ?? null
    ];
}

/**
 * Alias for current_user().
 */
function get_logged_in_user(): ?array {
    return current_user();
}

/**
 * Check if the authenticated user has a specific role or belongs to allowed roles.
 */
function has_role(string|array $roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    
    $userRole = $_SESSION['admin_user_role'];
    
    // Super admin has universal access
    if ($userRole === 'super_admin' || $userRole === 'admin') {
        return true;
    }
    
    if (is_string($roles)) {
        return $userRole === $roles;
    }
    
    return in_array($userRole, $roles, true);
}

/**
 * Check if user is super admin or admin.
 */
function is_admin(): bool {
    return has_role(['super_admin', 'admin']);
}

/**
 * Check if user is staff (manager, editor, etc.).
 */
function is_staff(): bool {
    return has_role(['super_admin', 'admin', 'manager', 'editor', 'staff']);
}

/**
 * Guard page: Require authentication or redirect to login.
 */
function require_login(?string $redirectUrl = null): void {
    if (!is_logged_in()) {
        $loginUrl = $redirectUrl ?? (ADMIN_URL . '/login.php');
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? ADMIN_URL;
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Guard page: Require specific role or show 403 Forbidden.
 */
function require_role(string|array $roles, ?string $redirectUrl = null): void {
    require_login($redirectUrl);
    
    if (!has_role($roles)) {
        http_response_code(403);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>403 Access Denied - Kamadenu Goushala</title>
            <link rel="stylesheet" href="' . ASSETS_URL . '/css/style.css">
        </head>
        <body class="bg-cream d-flex align-items-center justify-content-center min-vh-100 p-4">
            <div class="card p-5 shadow-lg border-0 rounded-4 text-center max-w-500">
                <div class="display-1 text-danger mb-3"><i class="bi bi-shield-lock-fill"></i></div>
                <h2 class="font-serif text-forest-dark fw-bold mb-2">Access Restricted</h2>
                <p class="text-muted">You do not have the required administrative permissions (' . (is_array($roles) ? implode(', ', $roles) : $roles) . ') to access this module.</p>
                <div class="mt-4">
                    <a href="' . ADMIN_URL . '/index.php" class="btn btn-forest rounded-pill px-4">Return to Dashboard</a>
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
}

/**
 * Set user session on successful login with session fixation protection.
 */
function login_user(array $user, array $role): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Prevent session fixation attacks
    session_regenerate_id(true);
    
    $_SESSION['admin_user_id']        = (int)$user['id'];
    $_SESSION['admin_user_name']      = $user['name'];
    $_SESSION['admin_user_email']     = $user['email'];
    $_SESSION['admin_user_role']      = $role['slug'];
    $_SESSION['admin_user_role_name'] = $role['name'];
    $_SESSION['admin_user_avatar']    = $user['avatar'] ?? null;
    $_SESSION['admin_logged_in_at']   = time();
    
    // Update last login in database
    try {
        Database::execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        log_activity((int)$user['id'], 'login', 'users', (int)$user['id'], 'User logged in successfully');
    } catch (Throwable $t) {
        error_log('Failed to update last login: ' . $t->getMessage());
    }
}

/**
 * Authenticate user credentials by username, email or phone and start session.
 */
function login(string $identifier, string $password): bool {
    try {
        $identifier = trim($identifier);
        $user = Database::fetchOne("
            SELECT u.*, r.slug AS role_slug, r.name AS role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = ? OR u.email = ? OR u.phone = ?) AND u.status = 'active'
            LIMIT 1
        ", [$identifier, $identifier, $identifier]);

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            login_user($user, [
                'slug' => $user['role_slug'],
                'name' => $user['role_name']
            ]);
            return true;
        }
    } catch (Throwable $t) {
        error_log('Login error: ' . $t->getMessage());
    }
    return false;
}

/**
 * Destroy admin session and log out.
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['admin_user_id'] ?? null;
    if ($userId) {
        log_activity((int)$userId, 'logout', 'users', (int)$userId, 'User logged out');
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Logout helper alias.
 */
function logout(): void {
    logout_user();
}
