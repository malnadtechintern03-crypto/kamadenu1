<?php
/**
 * Kamadenu Goushala Platform - Admin Login
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// If already logged in as admin/staff, redirect to dashboard
if (is_logged_in() && (is_admin() || is_staff())) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $usernameOrEmail = sanitize_input($_POST['username'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Please enter both your username/email and password.';
    } else {
        if (login($usernameOrEmail, $password)) {
            if (is_admin() || is_staff()) {
                set_flash('success', 'Welcome back to Kamadenu Sanctuary Admin Portal!');
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit;
            } else {
                logout();
                $error = 'Access denied. Administrator privileges required.';
            }
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – Kamadenu Goushala</title>
    <meta name="csrf-token" content="<?= csrf_token(); ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL; ?>/css/style.css">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #102F32 0%, #1F5257 100%);">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            
            <div class="card p-4 p-md-5 rounded-4 shadow-lg border bg-white position-relative" style="border-color: var(--color-border) !important; box-shadow: 0 8px 30px rgba(16, 47, 50, 0.20) !important;">
                
                <!-- Brand Emblem -->
                <div class="text-center mb-4">
                    <div class="navbar-brand-logo mx-auto mb-2" style="width:58px;height:58px;font-size:1.8rem;background:rgba(233,120,58,0.12);border-radius:50%;">
                        <i class="bi bi-shield-lock-fill text-saffron"></i>
                    </div>
                    <h1 class="h4 font-serif text-royal-teal mb-1">Sanctuary Admin</h1>
                    <p class="text-muted small mb-0">Kamadenu Goushala Operations Portal</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small py-2 d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-exclamation-circle-fill fs-6 flex-shrink-0"></i>
                        <div><?= e($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL; ?>/admin/login.php">
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-cream border-end-0"><i class="bi bi-person-fill text-forest"></i></span>
                            <input type="text" name="username" class="form-control border-start-0" placeholder="admin" required value="<?= e($_POST['username'] ?? 'admin'); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-forest-dark">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-cream border-end-0"><i class="bi bi-lock-fill text-forest"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required value="admin123">
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gold btn-lg rounded-pill fw-bold shadow-gold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                        </button>
                    </div>

                    <div class="p-3 bg-cream-soft rounded-3 border text-center small text-muted">
                        <div class="text-forest-dark fw-bold mb-1"><i class="bi bi-key-fill text-gold me-1"></i> Demo Credentials:</div>
                        <div>Username: <strong>admin</strong> | Password: <strong>admin123</strong></div>
                        <div class="text-muted opacity-75 mt-1" style="font-size: 0.75rem;">(or email: admin@kamadenu.org)</div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="<?= BASE_URL; ?>/index.php" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Return to Main Website
                    </a>
                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>
