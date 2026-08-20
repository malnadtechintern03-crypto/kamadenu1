<?php
/**
 * Kamadenu Goushala Platform - Admin Sidebar Navigation
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function(string $path) use ($currentUri): bool {
    return str_contains($currentUri, $path);
};
?>
<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <!-- Brand Logo -->
    <div class="p-3 px-4 border-bottom border-forest d-flex align-items-center gap-2">
        <div class="navbar-brand-logo" style="width:36px;height:36px;font-size:1.2rem;">
            <i class="bi bi-shield-lock-fill text-gold"></i>
        </div>
        <div>
            <span class="font-serif fw-bold text-cream d-block fs-6 leading-tight">Kamadenu</span>
            <small class="text-gold-light opacity-75 extra-small">Sanctuary Admin</small>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="py-3 flex-grow-1 overflow-y-auto">
        <div class="px-4 py-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Overview</div>
        <a href="<?= BASE_URL; ?>/admin/index.php" class="admin-nav-link <?= $isActive('index.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Bovine Sanctuary</div>
        <a href="<?= BASE_URL; ?>/admin/cows.php" class="admin-nav-link <?= $isActive('cows.php') || $isActive('cow-edit.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i> Cows Directory
        </a>
        <a href="<?= BASE_URL; ?>/admin/breeds.php" class="admin-nav-link <?= $isActive('breeds.php') ? 'active' : ''; ?>">
            <i class="bi bi-patch-check"></i> Indigenous Breeds
        </a>
        <a href="<?= BASE_URL; ?>/admin/medical.php" class="admin-nav-link <?= $isActive('medical.php') ? 'active' : ''; ?>">
            <i class="bi bi-heart-pulse"></i> Medical Records
        </a>
        <a href="<?= BASE_URL; ?>/admin/vaccinations.php" class="admin-nav-link <?= $isActive('vaccinations.php') ? 'active' : ''; ?>">
            <i class="bi bi-shield-plus"></i> Vaccinations
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Media & Stories</div>
        <a href="<?= BASE_URL; ?>/admin/gallery.php" class="admin-nav-link <?= $isActive('gallery.php') ? 'active' : ''; ?>">
            <i class="bi bi-images"></i> Events & Gallery
        </a>
        <a href="<?= BASE_URL; ?>/admin/blog.php" class="admin-nav-link <?= $isActive('blog.php') ? 'active' : ''; ?>">
            <i class="bi bi-newspaper"></i> Rescue Stories / Blog
        </a>
        <a href="<?= BASE_URL; ?>/admin/videos.php" class="admin-nav-link <?= $isActive('videos.php') ? 'active' : ''; ?>">
            <i class="bi bi-camera-reels"></i> Video Documentaries
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Philanthropy</div>
        <a href="<?= BASE_URL; ?>/admin/donations.php" class="admin-nav-link <?= $isActive('donations.php') ? 'active' : ''; ?>">
            <i class="bi bi-cash-stack"></i> 80G Donations
        </a>
        <a href="<?= BASE_URL; ?>/admin/adoptions.php" class="admin-nav-link <?= $isActive('adoptions.php') ? 'active' : ''; ?>">
            <i class="bi bi-suit-heart"></i> Cow Adoptions
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">E-Commerce</div>
        <a href="<?= BASE_URL; ?>/admin/products.php" class="admin-nav-link <?= $isActive('products.php') ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i> Products Catalog
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="admin-nav-link <?= $isActive('orders.php') || $isActive('order-details.php') ? 'active' : ''; ?>">
            <i class="bi bi-bag-check"></i> Customer Orders
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Finance & Inquiries</div>
        <a href="<?= BASE_URL; ?>/admin/expenses.php" class="admin-nav-link <?= $isActive('expenses.php') ? 'active' : ''; ?>">
            <i class="bi bi-receipt"></i> Verified Expenses
        </a>
        <a href="<?= BASE_URL; ?>/admin/messages.php" class="admin-nav-link <?= $isActive('messages.php') ? 'active' : ''; ?>">
            <i class="bi bi-envelope"></i> Devotee Messages
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold">Administration</div>
        <a href="<?= BASE_URL; ?>/admin/settings.php" class="admin-nav-link <?= $isActive('settings.php') ? 'active' : ''; ?>">
            <i class="bi bi-sliders"></i> Sanctuary Settings
        </a>
    </div>

    <!-- Sign Out Footer Link -->
    <div class="p-3 border-top border-forest mt-auto">
        <a href="<?= BASE_URL; ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
    </div>
</aside>
