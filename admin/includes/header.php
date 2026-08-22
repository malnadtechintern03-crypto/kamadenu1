<?php
/**
 * Kamadenu Goushala Platform - Admin Header Layout
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Guard: Require Admin or Staff authentication
require_role(['admin', 'super_admin', 'manager', 'editor', 'staff']);

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
            --admin-sidebar-collapsed-width: 76px;
        }
        html {
            height: 100%;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--color-border, #E9DDCC) var(--color-background, #FFF9F0);
        }
        body {
            background-color: var(--color-background, #FFF9F0);
            font-family: var(--font-sans);
            color: var(--color-text, #1D2525);
            min-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Custom Scrollbar for Admin Area */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--color-background, #FFF9F0);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--color-border, #E9DDCC);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-accent, #E9783A);
        }

        /* Fixed Sidebar with Internal Scroll */
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background: linear-gradient(180deg, #102F32 0%, #1F5257 100%) !important;
            height: 100vh;
            max-height: 100vh;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            color: #FFFFFF;
            border-right: 1px solid rgba(233, 221, 204, 0.15);
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), margin-left 0.3s ease;
            box-shadow: 2px 0 16px rgba(16, 47, 50, 0.25);
        }

        /* Scrollable Sidebar Nav Container */
        .admin-sidebar-menu,
        .admin-sidebar .overflow-y-auto {
            flex: 1 1 auto;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(233, 120, 58, 0.4) #102F32;
        }
        .admin-sidebar-menu::-webkit-scrollbar,
        .admin-sidebar .overflow-y-auto::-webkit-scrollbar {
            width: 5px;
        }
        .admin-sidebar-menu::-webkit-scrollbar-track,
        .admin-sidebar .overflow-y-auto::-webkit-scrollbar-track {
            background: #102F32;
        }
        .admin-sidebar-menu::-webkit-scrollbar-thumb,
        .admin-sidebar .overflow-y-auto::-webkit-scrollbar-thumb {
            background: rgba(233, 120, 58, 0.45);
            border-radius: 4px;
        }
        .admin-sidebar-menu::-webkit-scrollbar-thumb:hover,
        .admin-sidebar .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: var(--color-accent, #E9783A);
        }

        /* Main Content Container */
        .admin-main {
            margin-left: var(--admin-sidebar-width);
            width: calc(100% - var(--admin-sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--color-background, #FFF9F0);
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1), width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        /* Sidebar Brand & Official Logo Header */
        .admin-brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(233, 221, 204, 0.12);
            text-decoration: none;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
        }
        .admin-brand-header:hover {
            background-color: rgba(255, 255, 255, 0.04);
        }
        .admin-logo-container {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 2px solid var(--color-accent-light, #F4B183);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            padding: 1px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s ease, box-shadow 0.25s ease;
        }
        .admin-brand-header:hover .admin-logo-container {
            transform: scale(1.06);
            border-color: var(--color-accent, #E9783A);
            box-shadow: 0 4px 14px rgba(233, 120, 58, 0.35);
        }
        .admin-logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
            display: block;
        }
        .admin-brand-text {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.2s ease;
        }
        .admin-brand-title {
            font-family: var(--font-serif, 'Playfair Display', serif);
            font-weight: 700;
            color: var(--color-background, #FFF9F0);
            font-size: 1.15rem;
            line-height: 1.15;
            letter-spacing: 0.02em;
            margin: 0;
        }
        .admin-brand-subtitle {
            font-family: var(--font-sans, 'Inter', sans-serif);
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-accent-light, #F4B183);
            opacity: 0.95;
            margin-top: 3px;
            margin-bottom: 0;
        }

        /* Navigation Links */
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            color: rgba(255, 249, 240, 0.88);
            text-decoration: none;
            border-radius: 8px;
            margin: 2px 12px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.25s ease;
            white-space: nowrap;
        }
        .admin-nav-link:hover {
            background-color: rgba(95, 168, 168, 0.15);
            color: #FFFFFF;
        }
        .admin-nav-link.active {
            background-color: rgba(233, 120, 58, 0.15) !important;
            color: #FFFFFF !important;
            border-left: 4px solid #E9783A !important;
            font-weight: 600;
        }
        .admin-nav-link i {
            font-size: 1.15rem;
            flex-shrink: 0;
            color: var(--color-accent-light, #F4B183);
            transition: color 0.2s ease;
        }
        .admin-nav-link:hover i, .admin-nav-link.active i {
            color: var(--color-accent, #E9783A);
        }
        .sidebar-section-title {
            color: rgba(255, 249, 240, 0.45) !important;
        }

        /* Collapsed Sidebar State (Desktop) */
        body.sidebar-collapsed .admin-sidebar,
        .admin-sidebar.collapsed {
            width: var(--admin-sidebar-collapsed-width);
        }
        body.sidebar-collapsed .admin-main,
        .admin-sidebar.collapsed ~ .admin-main {
            margin-left: var(--admin-sidebar-collapsed-width);
            width: calc(100% - var(--admin-sidebar-collapsed-width));
        }
        body.sidebar-collapsed .admin-brand-header,
        .admin-sidebar.collapsed .admin-brand-header {
            justify-content: center;
            padding: 16px 8px;
            gap: 0;
        }
        body.sidebar-collapsed .admin-brand-text,
        .admin-sidebar.collapsed .admin-brand-text,
        body.sidebar-collapsed .admin-nav-link span,
        .admin-sidebar.collapsed .admin-nav-link span,
        body.sidebar-collapsed .sidebar-section-title,
        .admin-sidebar.collapsed .sidebar-section-title,
        body.sidebar-collapsed .admin-sidebar-footer-text,
        .admin-sidebar.collapsed .admin-sidebar-footer-text {
            display: none !important;
        }
        body.sidebar-collapsed .admin-nav-link,
        .admin-sidebar.collapsed .admin-nav-link {
            justify-content: center;
            padding: 10px 0;
            margin: 4px 8px;
        }
        body.sidebar-collapsed .admin-nav-link i,
        .admin-sidebar.collapsed .admin-nav-link i {
            font-size: 1.25rem;
            margin: 0;
        }
        body.sidebar-collapsed .admin-sidebar-logout,
        .admin-sidebar.collapsed .admin-sidebar-logout {
            padding: 8px !important;
            display: flex;
            justify-content: center;
        }

        .admin-topbar {
            background-color: #FFFFFF;
            border-bottom: 1px solid var(--color-border, #E9DDCC);
            padding: 14px 28px;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(16, 47, 50, 0.03);
        }

        /* Admin Cards, Tables & Dashboard Styles */
        .admin-main .card {
            background: #FFFFFF;
            border: 1px solid var(--color-border, #E9DDCC);
            border-radius: var(--radius-md, 14px);
            box-shadow: 0 8px 30px rgba(16, 47, 50, 0.08);
        }
        .admin-main .table thead th,
        .admin-main thead.bg-cream-soft th,
        .admin-main .table thead {
            color: var(--color-background, #FFF9F0) !important;
            background-color: var(--color-primary, #102F32) !important;
            border-bottom: 1px solid var(--color-border, #E9DDCC) !important;
            font-weight: 600;
            font-family: var(--font-heading);
        }
        .admin-main .table tbody tr:hover {
            background-color: rgba(95, 168, 168, 0.08) !important;
        }

        /* Mobile Responsive Sidebar */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                margin-left: calc(-1 * var(--admin-sidebar-width));
                transition: margin 0.3s ease;
                box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            }
            .admin-sidebar.show {
                margin-left: 0;
            }
            .admin-main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            body.sidebar-collapsed .admin-sidebar {
                width: var(--admin-sidebar-width);
                margin-left: calc(-1 * var(--admin-sidebar-width));
            }
            body.sidebar-collapsed .admin-sidebar.show {
                margin-left: 0;
            }
            body.sidebar-collapsed .admin-brand-text,
            body.sidebar-collapsed .admin-nav-link span,
            body.sidebar-collapsed .sidebar-section-title,
            body.sidebar-collapsed .admin-sidebar-footer-text {
                display: block !important;
            }
            body.sidebar-collapsed .admin-nav-link {
                justify-content: flex-start;
                padding: 10px 18px;
                margin: 2px 12px;
            }
            body.sidebar-collapsed .admin-brand-header {
                justify-content: flex-start;
                padding: 16px 20px;
                gap: 12px;
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
                <button class="btn btn-outline-forest btn-sm" type="button" id="adminSidebarToggle" title="Toggle Navigation Sidebar">
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
                        <div class="rounded-circle bg-saffron text-white fw-bold d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:0.8rem;background:var(--color-accent,#E9783A)!important;">
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
