<?php
/**
 * Kamadenu Goushala Platform - Responsive Navigation Bar
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!-- Main Sticky Navigation -->
<nav class="navbar navbar-expand-lg heritage-navbar sticky-top" id="mainNavbar">
    <div class="container">
        <!-- Logo and Brand -->
        <a class="navbar-brand navbar-brand-wrapper" href="<?= BASE_URL; ?>/index.php">
            <div class="navbar-brand-logo">
                <i class="bi bi-flower1"></i>
            </div>
            <div>
                <h1 class="navbar-brand-title"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></h1>
                <p class="navbar-brand-subtitle">Vedic Cow Sanctuary & Research</p>
            </div>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuDrawer" aria-controls="mobileMenuDrawer" aria-label="Toggle navigation">
            <i class="bi bi-list fs-1 text-forest-dark"></i>
        </button>

        <!-- Desktop Navigation Items -->
        <div class="collapse navbar-collapse d-none d-lg-block" id="desktopNavbarContent">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= ($currentUri === '' || str_ends_with($currentUri, 'index.php') || $currentUri === '/') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'about.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'cows.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/cows.php">Our Cows</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'breeds.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/breeds.php">Breeds</a>
                </li>
                
                <!-- Gau Seva Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Gau Seva
                    </a>
                    <ul class="dropdown-menu dropdown-menu-heritage shadow-lg">
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/seva.php"><i class="bi bi-heart-pulse me-2 text-forest"></i>All Seva Programs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/feed.php"><i class="bi bi-flower1 me-2 text-forest"></i>Feed a Cow (Grāsa)</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/adopt.php"><i class="bi bi-suit-heart-fill me-2 text-forest"></i>Adopt a Rescued Cow</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/sponsor.php"><i class="bi bi-shield-check me-2 text-forest"></i>Sponsor Senior Cow</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'products.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/products.php">A2 Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'gallery.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/gallery.php">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= str_contains($currentUri, 'blog.php') ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/blog.php">Stories</a>
                </li>

                <!-- Primary CTA -->
                <li class="nav-item ms-lg-2">
                    <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold px-4">
                        <i class="bi bi-heart-fill"></i> Donate
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Offcanvas Menu Drawer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenuDrawer" aria-labelledby="mobileMenuDrawerLabel">
    <div class="offcanvas-header bg-forest-dark text-white p-3">
        <div class="d-flex align-items-center gap-2">
            <div class="navbar-brand-logo" style="width:38px;height:38px;font-size:1.2rem;">
                <i class="bi bi-flower1"></i>
            </div>
            <div>
                <h5 class="offcanvas-title font-serif text-cream mb-0" id="mobileMenuDrawerLabel"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></h5>
                <small class="text-gold-light">Vedic Sanctuary</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between p-4 bg-cream-soft">
        <ul class="nav flex-column gap-2">
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/index.php"><i class="bi bi-house-door me-2 text-forest"></i>Home</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/about.php"><i class="bi bi-info-circle me-2 text-forest"></i>About Us</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/cows.php"><i class="bi bi-emoji-smile me-2 text-forest"></i>Our Cows</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/breeds.php"><i class="bi bi-bookmarks me-2 text-forest"></i>Indigenous Breeds</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/seva.php"><i class="bi bi-heart me-2 text-forest"></i>Gau Seva Programs</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/products.php"><i class="bi bi-shop me-2 text-forest"></i>Vedic A2 Products</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/gallery.php"><i class="bi bi-images me-2 text-forest"></i>Photo & Video Gallery</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/blog.php"><i class="bi bi-journal-text me-2 text-forest"></i>Rescue Stories & Blog</a></li>
            <li class="nav-item"><a class="nav-link nav-link-custom fs-5" href="<?= BASE_URL; ?>/contact.php"><i class="bi bi-telephone me-2 text-forest"></i>Contact Us</a></li>
        </ul>

        <div class="mt-4 pt-3 border-top">
            <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold w-100 py-3 fs-5 mb-3 shadow-lg">
                <i class="bi bi-heart-fill"></i> Donate Now
            </a>
            <div class="text-center small text-muted">
                <p class="mb-1"><i class="bi bi-telephone me-1"></i> <?= e(get_setting('site_phone', '+91 98450 12345')); ?></p>
                <p class="mb-0"><i class="bi bi-shield-check text-forest me-1"></i> 80G Tax Exemption Available</p>
            </div>
        </div>
    </div>
</div>
