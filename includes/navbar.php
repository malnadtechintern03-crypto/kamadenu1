<?php
/**
 * Kamadenu Goushala Platform - Responsive Navigation Bar (Single Row Horizontal Flow)
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
$isDonate   = str_contains($currentUri, 'donate.php');
?>
<!-- Main Sticky Navigation -->
<nav class="navbar heritage-navbar mobile-header sticky-top" id="mainNavbar">
    <div class="container-fluid container-xl d-flex align-items-center flex-nowrap w-100 px-2 px-md-3">
        <!-- Fixed Logo on Far Left -->
        <div class="mobile-logo flex-shrink-0">
            <a class="navbar-brand navbar-brand-wrapper" href="<?= BASE_URL; ?>/index.php">
                <div class="navbar-brand-logo">
                    <img src="<?= BASE_URL; ?>/assets/images/logo.png?v=3" alt="Kamadenu Goushala Logo" class="w-100 h-100 object-fit-contain" onerror="this.onerror=null;this.outerHTML='<i class=\'bi bi-flower1\'></i>';">
                </div>
                <div class="navbar-brand-text d-flex flex-column">
                    <h1 class="navbar-brand-title"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></h1>
                    <p class="navbar-brand-subtitle">Vedic Cow Sanctuary & Research</p>
                </div>
            </a>
        </div>

        <!-- Horizontally Scrollable Navigation Links (Single Row beside Logo) -->
        <div class="mobile-nav-scroll flex-grow-1 ms-2 ms-md-3 ms-lg-4" id="mainNavbarNav">
            <ul class="navbar-nav d-flex flex-row align-items-center flex-nowrap ms-auto gap-1 gap-md-2">
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isHome ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isAbout ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/about.php">About Us</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isCows ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/cows.php">Our Cows</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isBreeds ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/breeds.php">Breeds</a>
                </li>
                
                <!-- Gau Seva Dropdown -->
                <li class="nav-item dropdown flex-shrink-0">
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

                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isProducts ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/products.php">A2 Products</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isGallery ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/gallery.php">Gallery</a>
                </li>
                <li class="nav-item flex-shrink-0">
                    <a class="nav-link nav-link-custom <?= $isBlog ? 'active' : ''; ?>" href="<?= BASE_URL; ?>/blog.php">Stories</a>
                </li>

                <!-- Primary CTA -->
                <li class="nav-item flex-shrink-0 ms-1 ms-lg-2">
                    <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold px-3 px-lg-4 shadow-sm text-nowrap">
                        <i class="bi bi-heart-fill"></i> Donate
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

