<?php
/**
 * Kamadenu Goushala Platform - Dual-Mode Responsive Navigation Bar & Mobile Offcanvas Drawer
 */

declare(strict_types=1);

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isHome     = ($currentUri === '' || str_ends_with($currentUri, 'index.php') || $currentUri === '/' || $currentUri === '/kamadenu1/' || $currentUri === '/kamadenu1');
$isAbout    = str_contains($currentUri, 'about.php');
$isCows     = str_contains($currentUri, 'cows.php') || str_contains($currentUri, 'cow-details.php');
$isBreeds   = str_contains($currentUri, 'breeds.php') || str_contains($currentUri, 'breed-details.php');
$isSeva     = str_contains($currentUri, 'seva') || str_contains($currentUri, 'feed.php') || str_contains($currentUri, 'adopt.php') || str_contains($currentUri, 'sponsor.php');
$isProducts = str_contains($currentUri, 'products.php') || str_contains($currentUri, 'product-details.php') || str_contains($currentUri, 'cart.php') || str_contains($currentUri, 'checkout.php');
$isGallery  = str_contains($currentUri, 'gallery.php') || str_contains($currentUri, 'videos.php');
$isBlog     = str_contains($currentUri, 'blog.php') || str_contains($currentUri, 'blog-details.php');
$isContact  = str_contains($currentUri, 'contact.php');
$isDonate   = str_contains($currentUri, 'donate.php');

$footTimings = get_goushala_timings();
$primaryWa = preg_replace('/\D/', '', get_primary_whatsapp_number());
?>
<!-- Main Sticky Navigation Bar -->
<nav class="navbar heritage-navbar sticky-top" id="mainNavbar">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between w-100 px-2 px-sm-3 px-md-4">
        
        <!-- Brand Logo & Identity -->
        <div class="mobile-logo flex-shrink-0">
            <a class="navbar-brand navbar-brand-wrapper" href="<?= BASE_URL; ?>/index.php">
                <div class="navbar-brand-logo">
                    <img src="<?= BASE_URL; ?>/assets/images/logo.png?v=3" alt="Kamadenu Goushala Logo" class="w-100 h-100 object-fit-contain" onerror="this.onerror=null;this.outerHTML='<i class=\'bi bi-flower1\'></i>';">
                </div>
                <div class="navbar-brand-text d-flex flex-column">
                    <span class="navbar-brand-title"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></span>
                    <span class="navbar-brand-subtitle">Vedic Cow Sanctuary & Research</span>
                </div>
            </a>
        </div>

        <!-- Desktop Navigation Links (Visible on Large Screens >= 992px) -->
        <div class="d-none d-lg-flex align-items-center gap-1 gap-xl-2 ms-auto me-3">
            <ul class="navbar-nav d-flex flex-row align-items-center gap-1 mb-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isHome ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isAbout ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isCows ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/cows.php">Our Cows</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isBreeds ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/breeds.php">Breeds</a>
                </li>
                
                <!-- Gau Seva Desktop Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle <?= $isSeva ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Gau Seva
                    </a>
                    <ul class="dropdown-menu dropdown-menu-heritage shadow-lg">
                        <li><a class="dropdown-item <?= str_contains($currentUri, 'seva.php') ? 'active fw-bold text-forest' : ''; ?>" href="<?= BASE_URL; ?>/seva.php"><i class="bi bi-heart-pulse me-2 text-forest"></i>All Seva Programs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item <?= str_contains($currentUri, 'feed.php') ? 'active fw-bold text-forest' : ''; ?>" href="<?= BASE_URL; ?>/feed.php"><i class="bi bi-flower1 me-2 text-forest"></i>Feed a Cow (Grāsa)</a></li>
                        <li><a class="dropdown-item <?= str_contains($currentUri, 'adopt.php') ? 'active fw-bold text-forest' : ''; ?>" href="<?= BASE_URL; ?>/adopt.php"><i class="bi bi-suit-heart-fill me-2 text-forest"></i>Adopt a Rescued Cow</a></li>
                        <li><a class="dropdown-item <?= str_contains($currentUri, 'sponsor.php') ? 'active fw-bold text-forest' : ''; ?>" href="<?= BASE_URL; ?>/sponsor.php"><i class="bi bi-shield-check me-2 text-forest"></i>Sponsor Senior Cow</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isProducts ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/products.php">A2 Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isGallery ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/gallery.php">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isBlog ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/blog.php">Stories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?= $isContact ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/contact.php">Contact</a>
                </li>
            </ul>
        </div>

        <!-- Right Side CTA Actions & Mobile Toggle Button -->
        <div class="navbar-cta-wrapper d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Pinned Donate CTA Button -->
            <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold navbar-donate-btn <?= $isDonate ? 'btn-donate-active' : ''; ?> px-3 px-md-4 py-2 rounded-pill shadow-gold d-inline-flex align-items-center gap-1 gap-sm-2 text-nowrap" title="Support Sacred Gau Seva">
                <i class="bi bi-heart-fill"></i> <span class="fw-bold">Donate</span>
            </a>

            <!-- Mobile Hamburger Toggle Button (Visible on Screens < 992px) -->
            <button class="navbar-toggler-heritage d-inline-flex d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuDrawer" aria-controls="mobileMenuDrawer" aria-label="Toggle mobile navigation menu">
                <i class="bi bi-list"></i>
            </button>
        </div>

    </div>
</nav>

<!-- Mobile Navigation Offcanvas Drawer (Full Touch-Optimized Menu) -->
<div class="offcanvas offcanvas-end offcanvas-heritage" tabindex="-1" id="mobileMenuDrawer" aria-labelledby="mobileMenuDrawerLabel">
    <!-- Offcanvas Header -->
    <div class="offcanvas-header bg-forest-dark text-white border-bottom border-secondary border-opacity-25 py-3 px-3">
        <div class="d-flex align-items-center gap-2" id="mobileMenuDrawerLabel">
            <div class="navbar-brand-logo" style="width: 44px; height: 44px; min-width: 44px;">
                <img src="<?= BASE_URL; ?>/assets/images/logo.png?v=3" alt="Logo" class="w-100 h-100 object-fit-contain" onerror="this.onerror=null;this.outerHTML='<i class=\'bi bi-flower1 text-gold\'></i>';">
            </div>
            <div>
                <h6 class="font-serif text-cream mb-0 fs-6"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></h6>
                <small class="text-gold-light extra-small text-uppercase tracking-wider">Sanctuary Menu</small>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- Offcanvas Body Nav Links -->
    <div class="offcanvas-body p-3 d-flex flex-column justify-content-between">
        <div class="d-flex flex-column gap-1">
            
            <a class="nav-link-custom <?= $isHome ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/index.php">
                <i class="bi bi-house me-2 text-gold"></i> Home
            </a>
            
            <a class="nav-link-custom <?= $isAbout ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/about.php">
                <i class="bi bi-flower1 me-2 text-gold"></i> About Sanctuary
            </a>
            
            <a class="nav-link-custom <?= $isCows ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/cows.php">
                <i class="bi bi-heart-pulse me-2 text-gold"></i> Meet Our Cows
            </a>
            
            <a class="nav-link-custom <?= $isBreeds ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/breeds.php">
                <i class="bi bi-award me-2 text-gold"></i> Indigenous Breeds
            </a>

            <!-- Mobile Gau Seva Collapsible Sub-Menu -->
            <div class="mobile-nav-group my-1">
                <a class="nav-link-custom d-flex align-items-center justify-content-between <?= $isSeva ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobileSevaCollapse" role="button" aria-expanded="<?= $isSeva ? 'true' : 'false'; ?>" aria-controls="mobileSevaCollapse">
                    <span><i class="bi bi-heart me-2 text-gold"></i> Gau Seva Programs</span>
                    <i class="bi bi-chevron-down small transition-transform"></i>
                </a>
                <div class="collapse <?= $isSeva ? 'show' : ''; ?> ps-3 mt-1 d-flex flex-column gap-1 border-start border-warning border-opacity-25 ms-3" id="mobileSevaCollapse">
                    <a class="mobile-sublink <?= str_contains($currentUri, 'seva.php') ? 'text-forest fw-bold' : ''; ?>" href="<?= BASE_URL; ?>/seva.php">
                        <i class="bi bi-check-circle me-1 text-gold"></i> All Seva Programs
                    </a>
                    <a class="mobile-sublink <?= str_contains($currentUri, 'feed.php') ? 'text-forest fw-bold' : ''; ?>" href="<?= BASE_URL; ?>/feed.php">
                        <i class="bi bi-flower1 me-1 text-gold"></i> Feed a Cow (Grāsa)
                    </a>
                    <a class="mobile-sublink <?= str_contains($currentUri, 'adopt.php') ? 'text-forest fw-bold' : ''; ?>" href="<?= BASE_URL; ?>/adopt.php">
                        <i class="bi bi-suit-heart-fill me-1 text-gold"></i> Adopt a Cow
                    </a>
                    <a class="mobile-sublink <?= str_contains($currentUri, 'sponsor.php') ? 'text-forest fw-bold' : ''; ?>" href="<?= BASE_URL; ?>/sponsor.php">
                        <i class="bi bi-shield-check me-1 text-gold"></i> Sponsor Senior Cow
                    </a>
                </div>
            </div>

            <a class="nav-link-custom <?= $isProducts ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/products.php">
                <i class="bi bi-shop me-2 text-gold"></i> Vedic A2 Store
            </a>

            <a class="nav-link-custom <?= $isGallery ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/gallery.php">
                <i class="bi bi-camera me-2 text-gold"></i> Photo Gallery
            </a>

            <a class="nav-link-custom <?= $isBlog ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/blog.php">
                <i class="bi bi-journal-text me-2 text-gold"></i> Rescue Stories
            </a>

            <a class="nav-link-custom <?= $isContact ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/contact.php">
                <i class="bi bi-geo-alt me-2 text-gold"></i> Contact Us
            </a>

        </div>

        <!-- Mobile Drawer Footer Info & Direct Actions -->
        <div class="pt-3 border-top mt-3">
            <div class="p-2 rounded-3 bg-forest-subtle border border-warning border-opacity-25 mb-3 text-center">
                <small class="text-forest-dark fw-semibold d-block">
                    <i class="bi bi-shield-fill-check text-gold me-1"></i> Section 80G 50% Tax Exempted
                </small>
                <small class="text-muted extra-small">
                    Darshan: <?= e($footTimings['morning']); ?> &bull; <?= e($footTimings['evening']); ?>
                </small>
            </div>

            <div class="d-grid gap-2">
                <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold rounded-pill py-2 fw-bold shadow-gold d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-heart-fill"></i> Donate for Gau Seva
                </a>
                <?php if (!empty($primaryWa)): ?>
                <a href="https://wa.me/<?= e($primaryWa); ?>" target="_blank" rel="noopener" class="btn btn-outline-forest btn-sm rounded-pill py-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-whatsapp text-success"></i> WhatsApp Helpline
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

