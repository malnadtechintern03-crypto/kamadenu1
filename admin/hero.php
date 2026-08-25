<?php
/**
 * Kamadenu Goushala Platform - Admin Homepage Hero Section & Banners Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor', 'staff']);

$currentUser = get_logged_in_user();

// Auto-Migration Guard: ensure table exists seamlessly
try {
    Database::execute("
        CREATE TABLE IF NOT EXISTS `hero_slides` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `eyebrow` VARCHAR(100) DEFAULT 'KAMADENU GOUSHALA',
            `title` VARCHAR(255) NOT NULL,
            `subtitle` TEXT DEFAULT NULL,
            `image_path` VARCHAR(255) DEFAULT 'assets/images/hero-cow.jpg',
            `btn_primary_text` VARCHAR(80) DEFAULT 'Support a Cow',
            `btn_primary_url` VARCHAR(255) DEFAULT '/donate.php',
            `btn_primary_icon` VARCHAR(50) DEFAULT 'bi-heart-fill',
            `btn_secondary_text` VARCHAR(80) DEFAULT 'Explore Our Goushala',
            `btn_secondary_url` VARCHAR(255) DEFAULT '/about.php',
            `btn_secondary_icon` VARCHAR(50) DEFAULT 'bi-compass',
            `badge_text` VARCHAR(100) DEFAULT NULL,
            `display_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Throwable $t) {
    error_log("Hero table check: " . $t->getMessage());
}

// -----------------------------------------------------------------------------
// POST Request Handling
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';

    // 1. ADD SLIDE
    if ($action === 'add_slide') {
        $eyebrow = sanitize_input($_POST['eyebrow'] ?? 'KAMADENU GOUSHALA');
        $title = sanitize_input($_POST['title'] ?? '');
        $subtitle = sanitize_input($_POST['subtitle'] ?? '');
        $badgeText = sanitize_input($_POST['badge_text'] ?? '');
        $btnPrimaryText = sanitize_input($_POST['btn_primary_text'] ?? 'Support a Cow');
        $btnPrimaryUrl = sanitize_input($_POST['btn_primary_url'] ?? '/donate.php');
        $btnPrimaryIcon = sanitize_input($_POST['btn_primary_icon'] ?? 'bi-heart-fill');
        $btnSecondaryText = sanitize_input($_POST['btn_secondary_text'] ?? 'Explore Our Goushala');
        $btnSecondaryUrl = sanitize_input($_POST['btn_secondary_url'] ?? '/about.php');
        $btnSecondaryIcon = sanitize_input($_POST['btn_secondary_icon'] ?? 'bi-compass');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            set_flash('danger', 'Hero headline / title is required.');
        } else {
            $imagePath = 'assets/images/hero-cow.jpg'; // Default fallback

            if (!empty($_FILES['image']['name'])) {
                try {
                    $uploaded = UploadService::upload($_FILES['image'], 'hero');
                    $imagePath = 'uploads/hero/' . $uploaded;
                } catch (Exception $e) {
                    set_flash('danger', 'Image upload failed: ' . $e->getMessage());
                    header('Location: ' . BASE_URL . '/admin/hero.php');
                    exit;
                }
            } elseif (!empty($_POST['preset_image'])) {
                $imagePath = sanitize_input($_POST['preset_image']);
            }

            Database::insert("
                INSERT INTO hero_slides (
                    eyebrow, title, subtitle, image_path,
                    btn_primary_text, btn_primary_url, btn_primary_icon,
                    btn_secondary_text, btn_secondary_url, btn_secondary_icon,
                    badge_text, display_order, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $eyebrow, $title, $subtitle, $imagePath,
                $btnPrimaryText, $btnPrimaryUrl, $btnPrimaryIcon,
                $btnSecondaryText, $btnSecondaryUrl, $btnSecondaryIcon,
                $badgeText, $displayOrder, $isActive
            ]);

            log_activity((int)$currentUser['id'], 'create_hero_slide', 'hero_slides', null, "Created hero slide: {$title}");
            set_flash('success', "Hero slide '{$title}' added successfully.");
        }
        header('Location: ' . BASE_URL . '/admin/hero.php');
        exit;
    }

    // 2. EDIT SLIDE
    if ($action === 'edit_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $slide = Database::fetchOne("SELECT * FROM hero_slides WHERE id = ?", [$slideId]);

        if (!$slide) {
            set_flash('danger', 'Hero slide not found.');
        } else {
            $eyebrow = sanitize_input($_POST['eyebrow'] ?? 'KAMADENU GOUSHALA');
            $title = sanitize_input($_POST['title'] ?? '');
            $subtitle = sanitize_input($_POST['subtitle'] ?? '');
            $badgeText = sanitize_input($_POST['badge_text'] ?? '');
            $btnPrimaryText = sanitize_input($_POST['btn_primary_text'] ?? 'Support a Cow');
            $btnPrimaryUrl = sanitize_input($_POST['btn_primary_url'] ?? '/donate.php');
            $btnPrimaryIcon = sanitize_input($_POST['btn_primary_icon'] ?? 'bi-heart-fill');
            $btnSecondaryText = sanitize_input($_POST['btn_secondary_text'] ?? 'Explore Our Goushala');
            $btnSecondaryUrl = sanitize_input($_POST['btn_secondary_url'] ?? '/about.php');
            $btnSecondaryIcon = sanitize_input($_POST['btn_secondary_icon'] ?? 'bi-compass');
            $displayOrder = (int)($_POST['display_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title)) {
                set_flash('danger', 'Hero headline / title is required.');
            } else {
                $imagePath = $slide['image_path'];

                if (!empty($_FILES['image']['name'])) {
                    try {
                        $uploaded = UploadService::upload($_FILES['image'], 'hero');
                        // Delete old image if in uploads/hero
                        if (!empty($slide['image_path']) && str_starts_with($slide['image_path'], 'uploads/hero/')) {
                            $oldFile = str_replace('uploads/hero/', '', $slide['image_path']);
                            UploadService::delete($oldFile, 'hero');
                        }
                        $imagePath = 'uploads/hero/' . $uploaded;
                    } catch (Exception $e) {
                        set_flash('danger', 'Image upload failed: ' . $e->getMessage());
                        header('Location: ' . BASE_URL . '/admin/hero.php');
                        exit;
                    }
                } elseif (!empty($_POST['preset_image'])) {
                    $imagePath = sanitize_input($_POST['preset_image']);
                }

                Database::execute("
                    UPDATE hero_slides SET
                        eyebrow = ?, title = ?, subtitle = ?, image_path = ?,
                        btn_primary_text = ?, btn_primary_url = ?, btn_primary_icon = ?,
                        btn_secondary_text = ?, btn_secondary_url = ?, btn_secondary_icon = ?,
                        badge_text = ?, display_order = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ", [
                    $eyebrow, $title, $subtitle, $imagePath,
                    $btnPrimaryText, $btnPrimaryUrl, $btnPrimaryIcon,
                    $btnSecondaryText, $btnSecondaryUrl, $btnSecondaryIcon,
                    $badgeText, $displayOrder, $isActive, $slideId
                ]);

                log_activity((int)$currentUser['id'], 'update_hero_slide', 'hero_slides', $slideId, "Updated hero slide: {$title}");
                set_flash('success', "Hero slide '{$title}' updated successfully.");
            }
        }
        header('Location: ' . BASE_URL . '/admin/hero.php');
        exit;
    }

    // 3. TOGGLE STATUS (Active/Inactive)
    if ($action === 'toggle_status') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $slide = Database::fetchOne("SELECT * FROM hero_slides WHERE id = ?", [$slideId]);

        if ($slide) {
            $newStatus = $slide['is_active'] ? 0 : 1;
            Database::execute("UPDATE hero_slides SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $slideId]);
            log_activity((int)$currentUser['id'], 'toggle_hero_slide', 'hero_slides', $slideId, "Toggled slide ID {$slideId} to status {$newStatus}");
            
            // Check if AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'is_active' => $newStatus]);
                exit;
            }
            set_flash('success', 'Slide status toggled successfully.');
        } else {
            set_flash('danger', 'Slide not found.');
        }
        header('Location: ' . BASE_URL . '/admin/hero.php');
        exit;
    }

    // 4. DUPLICATE SLIDE
    if ($action === 'duplicate_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $slide = Database::fetchOne("SELECT * FROM hero_slides WHERE id = ?", [$slideId]);

        if ($slide) {
            Database::insert("
                INSERT INTO hero_slides (
                    eyebrow, title, subtitle, image_path,
                    btn_primary_text, btn_primary_url, btn_primary_icon,
                    btn_secondary_text, btn_secondary_url, btn_secondary_icon,
                    badge_text, display_order, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ", [
                $slide['eyebrow'],
                $slide['title'] . ' (Copy)',
                $slide['subtitle'],
                $slide['image_path'],
                $slide['btn_primary_text'],
                $slide['btn_primary_url'],
                $slide['btn_primary_icon'],
                $slide['btn_secondary_text'],
                $slide['btn_secondary_url'],
                $slide['btn_secondary_icon'],
                $slide['badge_text'],
                ((int)$slide['display_order']) + 1
            ]);

            log_activity((int)$currentUser['id'], 'duplicate_hero_slide', 'hero_slides', $slideId, "Duplicated hero slide ID {$slideId}");
            set_flash('success', 'Hero slide duplicated as draft.');
        }
        header('Location: ' . BASE_URL . '/admin/hero.php');
        exit;
    }

    // 5. DELETE SLIDE
    if ($action === 'delete_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $slide = Database::fetchOne("SELECT * FROM hero_slides WHERE id = ?", [$slideId]);

        if ($slide) {
            if (!empty($slide['image_path']) && str_starts_with($slide['image_path'], 'uploads/hero/')) {
                $filename = str_replace('uploads/hero/', '', $slide['image_path']);
                UploadService::delete($filename, 'hero');
            }
            Database::execute("DELETE FROM hero_slides WHERE id = ?", [$slideId]);
            log_activity((int)$currentUser['id'], 'delete_hero_slide', 'hero_slides', $slideId, "Deleted hero slide ID {$slideId}");
            set_flash('success', 'Hero slide removed successfully.');
        } else {
            set_flash('danger', 'Slide not found.');
        }
        header('Location: ' . BASE_URL . '/admin/hero.php');
        exit;
    }
}

// Fetch all slides ordered by sort order
$slides = Database::fetchAll("SELECT * FROM hero_slides ORDER BY display_order ASC, id ASC");
$activeCount = count(array_filter($slides, fn($s) => (int)$s['is_active'] === 1));
$inactiveCount = count($slides) - $activeCount;

// Helper to resolve slide image URL
function get_hero_slide_image_url(string $imagePath): string {
    if (empty($imagePath)) {
        return BASE_URL . '/assets/images/hero-cow.jpg';
    }
    if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
        return $imagePath;
    }
    if (str_starts_with($imagePath, 'assets/') || str_starts_with($imagePath, 'uploads/')) {
        return BASE_URL . '/' . ltrim($imagePath, '/');
    }
    return BASE_URL . '/uploads/hero/' . rawurlencode($imagePath);
}

$pageTitle = 'Homepage Hero Section & Slides Manager';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ==============================================================================
     HEADER ACTIONS BAR
     ============================================================================== -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h3 font-serif text-forest-dark mb-0">Homepage Hero Banners & Slider</h1>
            <span class="badge bg-gold text-forest-dark fw-bold px-2 py-1 rounded-pill">
                <i class="bi bi-broadcast me-1"></i> Live Homepage Section
            </span>
        </div>
        <p class="text-muted small mb-0">Manage visual storytelling banners, Sanskrit eyebrows, call-to-action buttons, and background photography.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL; ?>/index.php" target="_blank" class="btn btn-outline-forest rounded-pill px-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> View Live Site
        </a>
        <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addSlideModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Add Hero Slide
        </button>
    </div>
</div>

<!-- ==============================================================================
     STATISTICS & QUICK STATUS STRIP
     ============================================================================== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100 admin-action-card admin-card-animate" style="animation-delay: 0.1s;">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-forest-subtle text-forest-dark">
                    <i class="bi bi-layers-fill fs-4"></i>
                </div>
                <div>
                    <h3 class="h4 font-serif text-forest-dark mb-0 admin-counter-value" data-target="<?= count($slides); ?>"><?= count($slides); ?></h3>
                    <small class="text-muted">Total Slides</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100 admin-action-card admin-card-animate" style="animation-delay: 0.2s;">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-success-subtle text-success">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div>
                    <h3 class="h4 font-serif text-success mb-0 admin-counter-value" data-target="<?= $activeCount; ?>"><?= $activeCount; ?></h3>
                    <small class="text-muted">Active in Rotation</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100 admin-action-card admin-card-animate" style="animation-delay: 0.3s;">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-warning-subtle text-warning">
                    <i class="bi bi-pause-circle-fill fs-4"></i>
                </div>
                <div>
                    <h3 class="h4 font-serif text-warning mb-0 admin-counter-value" data-target="<?= $inactiveCount; ?>"><?= $inactiveCount; ?></h3>
                    <small class="text-muted">Inactive / Drafts</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100 admin-action-card admin-card-animate" style="animation-delay: 0.4s;">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-3 bg-info-subtle text-info">
                    <i class="bi bi-sliders fs-4"></i>
                </div>
                <div>
                    <h4 class="h6 font-serif text-forest-dark mb-0">
                        <?= $activeCount > 1 ? 'Dynamic Slider' : ($activeCount === 1 ? 'Single Hero' : 'Static Fallback'); ?>
                    </h4>
                    <small class="text-muted">Homepage Mode</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($slides)): ?>
<!-- ==============================================================================
     LIVE VISUAL PREVIEW SIMULATOR
     ============================================================================== -->
<?php $previewSlide = !empty($slides) ? (array_values(array_filter($slides, fn($s) => (int)$s['is_active'] === 1))[0] ?? $slides[0]) : null; ?>
<?php if ($previewSlide): ?>
<div class="card rounded-4 border-0 shadow-sm overflow-hidden mb-4 bg-white">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-eye-fill text-gold fs-5"></i>
            <h2 class="h6 font-serif text-forest-dark mb-0">Live Frontend Visual Preview (Active Hero Banner)</h2>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-forest-dark text-white rounded-pill px-3 py-1 extra-small">
                Order #<?= (int)$previewSlide['display_order']; ?> • ID: #<?= (int)$previewSlide['id']; ?>
            </span>
        </div>
    </div>
    
    <!-- Hero Preview Canvas -->
    <div class="position-relative overflow-hidden" style="min-height: 380px; max-height: 480px; background: linear-gradient(135deg, #102F32 0%, #1F5257 100%);">
        <!-- Background Image -->
        <img 
            src="<?= e(get_hero_slide_image_url($previewSlide['image_path'])); ?>" 
            alt="<?= e($previewSlide['title']); ?>"
            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-60"
            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/hero-cow.jpg';"
        >
        <!-- Overlay Gradient -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(90deg, rgba(16, 47, 50, 0.95) 0%, rgba(31, 82, 87, 0.70) 55%, rgba(31, 82, 87, 0.25) 100%);"></div>

        <!-- Content Overlay -->
        <div class="position-relative z-2 p-4 p-md-5 d-flex flex-column justify-content-center h-100" style="min-height: 380px; max-width: 820px;">
            <?php if (!empty($previewSlide['badge_text'])): ?>
                <div class="mb-2">
                    <span class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-50 px-3 py-1 rounded-pill small">
                        <i class="bi bi-award-fill me-1"></i> <?= e($previewSlide['badge_text']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <span class="text-gold fw-bold text-uppercase tracking-wider small mb-2 d-inline-block" style="font-family: var(--font-heading, 'Outfit', sans-serif); letter-spacing: 0.18em;">
                <?= e($previewSlide['eyebrow'] ?: 'KAMADENU GOUSHALA'); ?>
            </span>

            <h1 class="text-white font-serif display-6 fw-bold mb-3" style="text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                <?= e($previewSlide['title']); ?>
            </h1>

            <p class="text-light text-opacity-90 fs-6 mb-4 max-w-700">
                <?= e($previewSlide['subtitle'] ?: 'Protecting, healing and nurturing rescued cows with compassion, seva and dignity.'); ?>
            </p>

            <div class="d-flex flex-wrap gap-2">
                <?php if (!empty($previewSlide['btn_primary_text'])): ?>
                    <a href="<?= e(str_starts_with($previewSlide['btn_primary_url'], 'http') ? $previewSlide['btn_primary_url'] : BASE_URL . '/' . ltrim($previewSlide['btn_primary_url'], '/')); ?>" 
                       class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi <?= e($previewSlide['btn_primary_icon'] ?: 'bi-heart-fill'); ?> me-1"></i> 
                        <?= e($previewSlide['btn_primary_text']); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($previewSlide['btn_secondary_text'])): ?>
                    <a href="<?= e(str_starts_with($previewSlide['btn_secondary_url'], 'http') ? $previewSlide['btn_secondary_url'] : BASE_URL . '/' . ltrim($previewSlide['btn_secondary_url'], '/')); ?>" 
                       class="btn btn-outline-light rounded-pill px-4">
                        <i class="bi <?= e($previewSlide['btn_secondary_icon'] ?: 'bi-compass'); ?> me-1"></i> 
                        <?= e($previewSlide['btn_secondary_text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ==============================================================================
     HERO SLIDES LIST / CARDS
     ============================================================================== -->
<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Configured Hero Slides</h2>
            <small class="text-muted">Slides ordered by display order. Active slides are displayed in the homepage hero area.</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Total: <strong><?= count($slides); ?></strong></span>
        </div>
    </div>

    <?php if (empty($slides)): ?>
        <div class="text-center py-5">
            <div class="p-4 rounded-circle bg-cream-soft text-gold d-inline-flex mb-3">
                <i class="bi bi-images fs-1"></i>
            </div>
            <h3 class="h5 font-serif text-forest-dark">No Hero Slides Configured Yet</h3>
            <p class="text-muted small mb-3">Create your first hero banner to showcase sacred cows, donation campaigns, or sanctuary darshan.</p>
            <button type="button" class="btn btn-gold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Create First Hero Slide
            </button>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($slides as $index => $slide): 
                $imgUrl = get_hero_slide_image_url($slide['image_path']);
            ?>
                <div class="col-lg-6">
                    <div class="card h-100 rounded-4 border overflow-hidden shadow-sm position-relative transition-all <?= $slide['is_active'] ? 'border-gold' : 'border-light-subtle opacity-75'; ?>">
                        <!-- Image Container with Overlay -->
                        <div class="position-relative" style="height: 180px; background: #102F32;">
                            <img 
                                src="<?= e($imgUrl); ?>" 
                                alt="<?= e($slide['title']); ?>"
                                class="w-100 h-100 object-fit-cover"
                                onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/hero-cow.jpg';"
                            >
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(16,47,50,0.3) 0%, rgba(16,47,50,0.85) 100%);"></div>

                            <!-- Badges Header -->
                            <div class="position-absolute top-0 start-0 end-0 p-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-forest-dark bg-opacity-90 text-white rounded-pill px-3 py-1 shadow-sm">
                                    <i class="bi bi-sort-numeric-down me-1"></i> Order: <?= (int)$slide['display_order']; ?>
                                </span>

                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($slide['is_active']): ?>
                                        <span class="badge bg-success rounded-pill px-3 py-1 shadow-sm">
                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-1 shadow-sm">
                                            <i class="bi bi-pause-circle me-1"></i> Draft / Hidden
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Eyebrow & Title inside preview -->
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 text-white">
                                <span class="text-warning extra-small fw-bold text-uppercase tracking-wider d-block">
                                    <?= e($slide['eyebrow'] ?: 'KAMADENU GOUSHALA'); ?>
                                </span>
                                <h3 class="h5 font-serif text-white mb-0 text-truncate">
                                    <?= e($slide['title']); ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted small mb-3 line-clamp-2" style="min-height: 40px;">
                                    <?= e($slide['subtitle'] ?: 'No description provided.'); ?>
                                </p>

                                <!-- Buttons Info -->
                                <div class="p-3 rounded-3 bg-cream-soft border mb-3">
                                    <div class="row g-2 small">
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block extra-small">Primary CTA:</span>
                                            <span class="fw-semibold text-forest-dark">
                                                <i class="bi <?= e($slide['btn_primary_icon'] ?: 'bi-heart-fill'); ?> text-danger me-1"></i>
                                                <?= e($slide['btn_primary_text'] ?: 'None'); ?>
                                            </span>
                                            <span class="text-muted d-block text-truncate extra-small"><?= e($slide['btn_primary_url'] ?: '-'); ?></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block extra-small">Secondary CTA:</span>
                                            <span class="fw-semibold text-forest-dark">
                                                <i class="bi <?= e($slide['btn_secondary_icon'] ?: 'bi-compass'); ?> text-primary me-1"></i>
                                                <?= e($slide['btn_secondary_text'] ?: 'None'); ?>
                                            </span>
                                            <span class="text-muted d-block text-truncate extra-small"><?= e($slide['btn_secondary_url'] ?: '-'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Row -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top">
                                <!-- Status Toggle Form -->
                                <form method="POST" action="<?= BASE_URL; ?>/admin/hero.php" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="slide_id" value="<?= (int)$slide['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?= $slide['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success'; ?> rounded-pill px-3" title="Toggle Visibility">
                                        <i class="bi <?= $slide['is_active'] ? 'bi-eye-slash' : 'bi-eye'; ?> me-1"></i>
                                        <?= $slide['is_active'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>

                                <div class="d-flex align-items-center gap-1">
                                    <!-- Duplicate Button -->
                                    <form method="POST" action="<?= BASE_URL; ?>/admin/hero.php" class="d-inline">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="duplicate_slide">
                                        <input type="hidden" name="slide_id" value="<?= (int)$slide['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-light border rounded-pill" title="Duplicate Slide">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <button type="button" 
                                            class="btn btn-sm btn-gold rounded-pill px-3 edit-slide-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editSlideModal"
                                            data-slide='<?= htmlspecialchars(json_encode($slide), ENT_QUOTES, 'UTF-8'); ?>'
                                            data-imgurl="<?= e($imgUrl); ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger rounded-pill px-2 delete-slide-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteSlideModal"
                                            data-id="<?= (int)$slide['id']; ?>"
                                            data-title="<?= e($slide['title']); ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ==============================================================================
     MODAL: ADD NEW HERO SLIDE
     ============================================================================== -->
<div class="modal fade" id="addSlideModal" tabindex="-1" aria-labelledby="addSlideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-forest-dark text-white py-3 px-4 border-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-gold text-forest-dark">
                        <i class="bi bi-plus-lg fs-6"></i>
                    </div>
                    <h2 class="modal-title h5 font-serif mb-0 text-white" id="addSlideModalLabel">Add New Hero Banner Slide</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= BASE_URL; ?>/admin/hero.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_slide">

                <div class="modal-body p-4">
                    <!-- Section 1: Headline & Content -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-fonts text-gold me-2"></i> Headline & Storytelling Copy
                    </h3>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Eyebrow Tagline / Sanskrit Label</label>
                            <input type="text" name="eyebrow" class="form-control" placeholder="e.g. KAMADENU GOUSHALA or SACRED GAU SEVA" value="KAMADENU GOUSHALA">
                            <small class="text-muted extra-small">Appears in small golden capital letters above the main title.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Top Badge (Optional)</label>
                            <input type="text" name="badge_text" class="form-control" placeholder="e.g. 80G Tax Exemption Available">
                            <small class="text-muted extra-small">Optional pill badge above the eyebrow.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Main Headline / Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg font-serif" placeholder="e.g. Every Life Deserves Care." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Subheading / Description Paragraph</label>
                            <textarea name="subtitle" class="form-control" rows="3" placeholder="Protecting, healing and nurturing rescued cows with compassion, seva and dignity."></textarea>
                        </div>
                    </div>

                    <!-- Section 2: Photography & Banner Image -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-camera-fill text-gold me-2"></i> Hero Background Image
                    </h3>

                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Upload Custom High-Res Image (JPG, PNG, WEBP)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" id="addHeroImageInput">
                            <small class="text-muted extra-small">Recommended resolution: 1920x1080px (Max 5MB).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Or Select Preset Sanctuary Asset</label>
                            <select name="preset_image" class="form-select">
                                <option value="">-- Or Choose Built-in Photo --</option>
                                <option value="assets/images/hero-cow.jpg">Default Sacred Cow (hero-cow.jpg)</option>
                                <option value="assets/images/breeds/gir.jpg">Gir Breed Sanctuary (breeds/gir.jpg)</option>
                                <option value="assets/images/breeds/sahiwal.jpg">Sahiwal Indigenous Cow (breeds/sahiwal.jpg)</option>
                                <option value="assets/images/breeds/hallikar.jpg">Hallikar Heritage Draught (breeds/hallikar.jpg)</option>
                                <option value="assets/images/breeds/tharparkar.jpg">Tharparkar Divine Cow (breeds/tharparkar.jpg)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Section 3: Call-to-Action Buttons -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-link-45deg text-gold me-2"></i> Call-to-Action (CTA) Buttons
                    </h3>

                    <div class="p-3 bg-cream-soft rounded-4 mb-3 border">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Primary Button Label</label>
                                <input type="text" name="btn_primary_text" class="form-control" value="Support a Cow">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Primary Button Link</label>
                                <input type="text" name="btn_primary_url" class="form-control" value="/donate.php" placeholder="/donate.php or full URL">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Primary Icon</label>
                                <select name="btn_primary_icon" class="form-select">
                                    <option value="bi-heart-fill" selected>Heart (bi-heart-fill)</option>
                                    <option value="bi-suit-heart-fill">Suit Heart (bi-suit-heart-fill)</option>
                                    <option value="bi-gift-fill">Gift (bi-gift-fill)</option>
                                    <option value="bi-shield-check">Shield Check (bi-shield-check)</option>
                                    <option value="bi-currency-rupee">Rupee (bi-currency-rupee)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-cream-soft rounded-4 mb-4 border">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Secondary Button Label</label>
                                <input type="text" name="btn_secondary_text" class="form-control" value="Explore Our Goushala">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Secondary Button Link</label>
                                <input type="text" name="btn_secondary_url" class="form-control" value="/about.php" placeholder="/about.php or /cows.php">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Secondary Icon</label>
                                <select name="btn_secondary_icon" class="form-select">
                                    <option value="bi-compass" selected>Compass (bi-compass)</option>
                                    <option value="bi-person-badge">Cow Badge (bi-person-badge)</option>
                                    <option value="bi-camera-reels">Video Reels (bi-camera-reels)</option>
                                    <option value="bi-info-circle">Info (bi-info-circle)</option>
                                    <option value="bi-arrow-right">Arrow (bi-arrow-right)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Display Order & Status -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Display Order / Sequence</label>
                            <input type="number" name="display_order" class="form-control" value="<?= count($slides) + 1; ?>" min="0">
                            <small class="text-muted extra-small">Lower numbers display first (e.g. 1, 2, 3).</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="addIsActive" checked>
                                <label class="form-check-label fs-6 fw-bold ms-2" for="addIsActive">Active & Visible on Homepage</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi bi-check-lg me-1"></i> Save Hero Slide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==============================================================================
     MODAL: EDIT HERO SLIDE
     ============================================================================== -->
<div class="modal fade" id="editSlideModal" tabindex="-1" aria-labelledby="editSlideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-forest-dark text-white py-3 px-4 border-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle bg-gold text-forest-dark">
                        <i class="bi bi-pencil-fill fs-6"></i>
                    </div>
                    <h2 class="modal-title h5 font-serif mb-0 text-white" id="editSlideModalLabel">Edit Hero Banner Slide</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="<?= BASE_URL; ?>/admin/hero.php" enctype="multipart/form-data" id="editSlideForm">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_slide">
                <input type="hidden" name="slide_id" id="editSlideId">

                <div class="modal-body p-4">
                    <!-- Section 1: Headline & Content -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-fonts text-gold me-2"></i> Headline & Storytelling Copy
                    </h3>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Eyebrow Tagline / Sanskrit Label</label>
                            <input type="text" name="eyebrow" id="editEyebrow" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Top Badge (Optional)</label>
                            <input type="text" name="badge_text" id="editBadgeText" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Main Headline / Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="editTitle" class="form-control form-control-lg font-serif" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Subheading / Description Paragraph</label>
                            <textarea name="subtitle" id="editSubtitle" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Section 2: Photography & Banner Image -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-camera-fill text-gold me-2"></i> Hero Background Image
                    </h3>

                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-md-4">
                            <div class="rounded-3 border overflow-hidden position-relative" style="height: 100px;">
                                <img id="editImagePreview" src="" alt="Current Banner" class="w-100 h-100 object-fit-cover">
                            </div>
                            <small class="text-muted extra-small d-block mt-1">Current Active Image</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Replace Banner Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted extra-small">Leave empty to keep existing image.</small>
                        </div>
                    </div>

                    <!-- Section 3: Call-to-Action Buttons -->
                    <h3 class="h6 font-serif text-forest-dark mb-3 border-bottom pb-2">
                        <i class="bi bi-link-45deg text-gold me-2"></i> Call-to-Action (CTA) Buttons
                    </h3>

                    <div class="p-3 bg-cream-soft rounded-4 mb-3 border">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Primary Button Label</label>
                                <input type="text" name="btn_primary_text" id="editBtnPrimaryText" class="form-control">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Primary Button Link</label>
                                <input type="text" name="btn_primary_url" id="editBtnPrimaryUrl" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Primary Icon</label>
                                <select name="btn_primary_icon" id="editBtnPrimaryIcon" class="form-select">
                                    <option value="bi-heart-fill">Heart (bi-heart-fill)</option>
                                    <option value="bi-suit-heart-fill">Suit Heart (bi-suit-heart-fill)</option>
                                    <option value="bi-gift-fill">Gift (bi-gift-fill)</option>
                                    <option value="bi-shield-check">Shield Check (bi-shield-check)</option>
                                    <option value="bi-currency-rupee">Rupee (bi-currency-rupee)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-cream-soft rounded-4 mb-4 border">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Secondary Button Label</label>
                                <input type="text" name="btn_secondary_text" id="editBtnSecondaryText" class="form-control">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Secondary Button Link</label>
                                <input type="text" name="btn_secondary_url" id="editBtnSecondaryUrl" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Secondary Icon</label>
                                <select name="btn_secondary_icon" id="editBtnSecondaryIcon" class="form-select">
                                    <option value="bi-compass">Compass (bi-compass)</option>
                                    <option value="bi-person-badge">Cow Badge (bi-person-badge)</option>
                                    <option value="bi-camera-reels">Video Reels (bi-camera-reels)</option>
                                    <option value="bi-info-circle">Info (bi-info-circle)</option>
                                    <option value="bi-arrow-right">Arrow (bi-arrow-right)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Display Order & Status -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Display Order / Sequence</label>
                            <input type="number" name="display_order" id="editDisplayOrder" class="form-control" min="0">
                        </div>
                        <div class="col-md-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="editIsActive">
                                <label class="form-check-label fs-6 fw-bold ms-2" for="editIsActive">Active & Visible on Homepage</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi bi-check-lg me-1"></i> Update Hero Slide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==============================================================================
     MODAL: DELETE HERO SLIDE CONFIRMATION
     ============================================================================== -->
<div class="modal fade" id="deleteSlideModal" tabindex="-1" aria-labelledby="deleteSlideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white py-3 px-4 border-0">
                <h2 class="modal-title h5 font-serif mb-0 text-white" id="deleteSlideModalLabel">Confirm Hero Slide Removal</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/hero.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete_slide">
                <input type="hidden" name="slide_id" id="deleteSlideId">

                <div class="modal-body p-4 text-center">
                    <div class="p-3 rounded-circle bg-danger-subtle text-danger d-inline-flex mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-2"></i>
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-2">Delete Slide "<span id="deleteSlideTitle" class="fw-bold"></span>"?</h3>
                    <p class="text-muted small mb-0">Are you sure you want to permanently delete this hero slide banner? This action cannot be undone.</p>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="bi bi-trash-fill me-1"></i> Delete Slide
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript to populate Edit and Delete modals dynamically -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal Binding
    const editBtns = document.querySelectorAll('.edit-slide-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-slide'));
            const imgUrl = this.getAttribute('data-imgurl');

            document.getElementById('editSlideId').value = data.id || '';
            document.getElementById('editEyebrow').value = data.eyebrow || 'KAMADENU GOUSHALA';
            document.getElementById('editTitle').value = data.title || '';
            document.getElementById('editSubtitle').value = data.subtitle || '';
            document.getElementById('editBadgeText').value = data.badge_text || '';
            document.getElementById('editBtnPrimaryText').value = data.btn_primary_text || 'Support a Cow';
            document.getElementById('editBtnPrimaryUrl').value = data.btn_primary_url || '/donate.php';
            document.getElementById('editBtnPrimaryIcon').value = data.btn_primary_icon || 'bi-heart-fill';
            document.getElementById('editBtnSecondaryText').value = data.btn_secondary_text || 'Explore Our Goushala';
            document.getElementById('editBtnSecondaryUrl').value = data.btn_secondary_url || '/about.php';
            document.getElementById('editBtnSecondaryIcon').value = data.btn_secondary_icon || 'bi-compass';
            document.getElementById('editDisplayOrder').value = data.display_order ?? 0;
            document.getElementById('editIsActive').checked = parseInt(data.is_active, 10) === 1;

            const previewImg = document.getElementById('editImagePreview');
            if (previewImg && imgUrl) {
                previewImg.src = imgUrl;
            }
        });
    });

    // Delete Modal Binding
    const deleteBtns = document.querySelectorAll('.delete-slide-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');

            document.getElementById('deleteSlideId').value = id;
            document.getElementById('deleteSlideTitle').textContent = title;
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
