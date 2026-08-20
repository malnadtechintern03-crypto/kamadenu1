<?php
/**
 * Kamadenu Goushala Platform - Admin Logout
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logout();
set_flash('success', 'You have been safely signed out from the admin portal.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
