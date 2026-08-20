<?php
/**
 * Kamadenu Goushala Platform - Admin Header Layout
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Guard: Require Admin or Staff authentication
require_role('admin', 'staff');

$currentUser = get_logged_in_user();
$pageTitle = $pageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> – Kamadenu Admin Portal</title>
    <meta name="csrf-token" content="<?= csrf_token(); ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Admin Styles -->
    <link rel="stylesheet" href="<?= ASSETS_URL; ?>/css/style.css">
    <style>
        :root {
            --admin-sidebar-width: 260px;
        }
        body {
            background-color: #F8F9FA;
            font-family: var(--font-body);
        }
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background-color: var(--color-forest-dark);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            color: #FFFFFF;
        }
        .admin-main {
            margin-left: var(--admin-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            color: rgba(246, 240, 223, 0.8);
            text-decoration: none;
            border-radius: 8px;
            margin: 2px 12px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .admin-nav-link:hover, .admin-nav-link.active {
            background-color: rgba(214, 154, 58, 0.2);
            color: var(--color-gold-light);
        }
        .admin-nav-link i {
            font-size: 1.15rem;
        }
        .admin-topbar {
            background-color: #FFFFFF;
            border-bottom: 1px solid #E9ECEF;
            padding: 14px 28px;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar {
                margin-left: calc(-1 * var(--admin-sidebar-width));
                transition: margin 0.3s ease;
            }
            .admin-sidebar.show {
                margin-left: 0;
            }
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Admin Sidebar Include -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="admin-main flex-grow-1">
        <!-- Admin Topbar -->
        <header class="admin-topbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-forest btn-sm d-lg-none" type="button" id="adminSidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="h5 font-serif text-forest-dark mb-0"><?= e($pageTitle); ?></h1>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= BASE_URL; ?>/index.php" target="_blank" class="btn btn-outline-forest btn-sm rounded-pill d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-globe"></i> View Live Site
                </a>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm rounded-pill dropdown-toggle d-flex align-items-center gap-2 border px-3" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-forest text-gold fw-bold d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.8rem;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="small fw-semibold text-forest-dark"><?= e($currentUser['name'] ?? 'Administrator'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold small"><?= e($currentUser['name'] ?? 'Admin'); ?></div>
                            <small class="text-muted"><?= e($currentUser['email'] ?? ''); ?></small>
                        </li>
                        <li><a class="dropdown-item small" href="<?= BASE_URL; ?>/admin/settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item small text-danger" href="<?= BASE_URL; ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Messages Display -->
        <div class="p-4 pb-0">
            <?php
            $flashSuccess = get_flash('success');
            $flashError = get_flash('error');
            ?>
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-xs" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= e($flashSuccess); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($flashError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Injected Here -->
        <main class="p-4 flex-grow-1">
