<?php
/**
 * Kamadenu Goushala Platform - Admin Sidebar Navigation
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function(string $path) use ($currentUri): bool {
    return str_contains($currentUri, $path);
};

// Retrieve official sanctuary logo from settings or fallback
$siteLogoSetting = get_setting('site_logo', 'assets/images/logo.png');
$siteLogoUrl = image_url($siteLogoSetting, 'logo', 'logo.png');
$siteName = get_setting('site_name', 'Kamadenu Goushala');
?>
<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <!-- Brand Header with Official Logo -->
    <a href="<?= BASE_URL; ?>/admin/index.php" class="admin-brand-header text-decoration-none" title="Kamadenu Sanctuary Admin">
        <div class="admin-logo-container flex-shrink-0">
            <img src="<?= e($siteLogoUrl); ?>" 
                 alt="<?= e($siteName); ?> Logo" 
                 class="admin-logo-img"
                 onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/logo.png';">
        </div>
        <div class="admin-brand-text">
            <span class="admin-brand-title">Kamadenu</span>
            <small class="admin-brand-subtitle">Sanctuary Admin</small>
        </div>
    </a>

    <!-- Navigation Menu -->
    <div class="py-3 flex-grow-1 overflow-y-auto admin-sidebar-menu">
        <div class="px-4 py-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Overview</div>
        <a href="<?= BASE_URL; ?>/admin/index.php" class="admin-nav-link <?= $isActive('index.php') ? 'active' : ''; ?>" title="Dashboard">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Bovine Sanctuary</div>
        <a href="<?= BASE_URL; ?>/admin/cows.php" class="admin-nav-link <?= $isActive('cows.php') || $isActive('cow-edit.php') ? 'active' : ''; ?>" title="Cows Directory">
            <i class="bi bi-person-badge"></i> <span>Cows Directory</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/breeds.php" class="admin-nav-link <?= $isActive('breeds.php') ? 'active' : ''; ?>" title="Indigenous Breeds">
            <i class="bi bi-patch-check"></i> <span>Indigenous Breeds</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/medical.php" class="admin-nav-link <?= $isActive('medical.php') ? 'active' : ''; ?>" title="Medical Records">
            <i class="bi bi-heart-pulse"></i> <span>Medical Records</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/vaccinations.php" class="admin-nav-link <?= $isActive('vaccinations.php') ? 'active' : ''; ?>" title="Vaccinations">
            <i class="bi bi-shield-plus"></i> <span>Vaccinations</span>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Media & Stories</div>
        <a href="<?= BASE_URL; ?>/admin/hero.php" class="admin-nav-link <?= $isActive('hero.php') ? 'active' : ''; ?>" title="Hero Section Banners">
            <i class="bi bi-layout-text-window-reverse"></i> <span>Hero Section</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/gallery.php" class="admin-nav-link <?= $isActive('gallery.php') ? 'active' : ''; ?>" title="Events & Gallery">
            <i class="bi bi-images"></i> <span>Events & Gallery</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/blog.php" class="admin-nav-link <?= $isActive('blog.php') ? 'active' : ''; ?>" title="Rescue Stories / Blog">
            <i class="bi bi-newspaper"></i> <span>Rescue Stories / Blog</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/videos.php" class="admin-nav-link <?= $isActive('videos.php') ? 'active' : ''; ?>" title="Video Documentaries">
            <i class="bi bi-camera-reels"></i> <span>Video Documentaries</span>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Philanthropy</div>
        <a href="<?= BASE_URL; ?>/admin/donations.php" class="admin-nav-link <?= $isActive('donations.php') ? 'active' : ''; ?>" title="80G Donations">
            <i class="bi bi-cash-stack"></i> <span>80G Donations</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/adoptions.php" class="admin-nav-link <?= $isActive('adoptions.php') ? 'active' : ''; ?>" title="Cow Adoptions">
            <i class="bi bi-suit-heart"></i> <span>Cow Adoptions</span>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">E-Commerce</div>
        <a href="<?= BASE_URL; ?>/admin/products.php" class="admin-nav-link <?= $isActive('products.php') ? 'active' : ''; ?>" title="Products Catalog">
            <i class="bi bi-box-seam"></i> <span>Products Catalog</span>
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="admin-nav-link <?= $isActive('orders.php') || $isActive('order-details.php') ? 'active' : ''; ?>" title="Customer Orders">
            <i class="bi bi-bag-check"></i> <span>Customer Orders</span>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Finance & Inquiries</div>
        <a href="<?= BASE_URL; ?>/admin/expenses.php" class="admin-nav-link <?= $isActive('expenses.php') ? 'active' : ''; ?>" title="Verified Expenses">
            <i class="bi bi-receipt"></i> <span>Verified Expenses</span>
        </a>
        <?php
        $sidebarUnreadCount = 0;
        try {
            $sidebarUnreadCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
        } catch (Throwable $e) {}
        ?>
        <a href="<?= BASE_URL; ?>/admin/messages.php" class="admin-nav-link d-flex align-items-center justify-content-between <?= $isActive('messages.php') ? 'active' : ''; ?>" title="Devotee Messages & Darshan Requests">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-envelope"></i> <span>Devotee Messages</span>
            </div>
            <?php if ($sidebarUnreadCount > 0): ?>
                <span class="badge bg-danger rounded-pill px-2 py-0 extra-small"><?= $sidebarUnreadCount; ?></span>
            <?php endif; ?>
        </a>

        <div class="px-4 pt-3 pb-1 small text-uppercase tracking-wider text-muted extra-small fw-bold sidebar-section-title">Administration</div>
        <a href="<?= BASE_URL; ?>/admin/settings.php" class="admin-nav-link <?= $isActive('settings.php') ? 'active' : ''; ?>" title="Sanctuary Settings">
            <i class="bi bi-sliders"></i> <span>Sanctuary Settings</span>
        </a>
    </div>

    <!-- Quick Contact Touchpoint in Sidebar -->
    <?php
    $sbPhone = get_setting('site_phone', '+91 98450 12345');
    $sbWa = get_setting('site_whatsapp', '+91 63635 56712');
    $sbEmail = get_setting('site_email', 'seva@kamadenugoushala.org');
    ?>
    <div class="p-3 mx-2 my-2 rounded-3 bg-forest-dark border border-warning border-opacity-20 admin-sidebar-footer-text" style="font-size: 0.78rem;">
        <div class="d-flex justify-content-between align-items-center mb-1 text-gold fw-bold">
            <span><i class="bi bi-telephone-inbound-fill me-1"></i> Contact Desk</span>
            <a href="<?= BASE_URL; ?>/admin/settings.php" class="text-white-50 hover-gold" title="Edit Contact Info"><i class="bi bi-pencil-square"></i></a>
        </div>
        <div class="text-white opacity-90 text-truncate"><i class="bi bi-telephone text-gold me-1"></i> <?= e($sbPhone); ?></div>
        <div class="text-white opacity-90 text-truncate"><i class="bi bi-whatsapp text-success me-1"></i> <?= e($sbWa); ?></div>
        <div class="text-white opacity-75 text-truncate" title="<?= e($sbEmail); ?>"><i class="bi bi-envelope text-gold me-1"></i> <?= e($sbEmail); ?></div>
    </div>

    <!-- Sign Out Footer Link -->
    <div class="p-3 mt-auto" style="border-top: 1px solid rgba(233, 221, 204, 0.15);">
        <a href="<?= BASE_URL; ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill admin-sidebar-logout" title="Sign Out">
            <i class="bi bi-box-arrow-right me-1"></i> <span class="admin-sidebar-footer-text">Sign Out</span>
        </a>
    </div>
</aside>
