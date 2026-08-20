<?php
/**
 * Kamadenu Goushala Platform - Photo Gallery Hub with Lightbox
 */

declare(strict_types=1);

$pageTitle = 'Sanctuary Photo Gallery – Joyful Moments & Seva';
$metaDescription = 'Explore photo moments from Kamadenu Goushala: 24x7 cow rescues, daily Gopashtami rituals, organic green pastures, and clinical veterinary care.';

require_once __DIR__ . '/includes/header.php';

$categories = Database::fetchAll("SELECT * FROM gallery_categories ORDER BY id ASC");
$galleryItems = Database::fetchAll("
    SELECT g.*, gc.name AS category_name, gc.slug AS category_slug 
    FROM gallery g 
    JOIN gallery_categories gc ON g.category_id = gc.id 
    ORDER BY g.display_order ASC, g.id DESC
");
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-camera-fill me-1"></i> Visual Sanctuary Archives
        </span>
        <h1 class="page-hero-title">Goushala Photo Gallery</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Witness the peaceful daily life, morning aarti, emergency rescues, and joyful pasture roaming of our sacred bovine family.
        </p>
    </div>
</section>

<!-- Main Gallery Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        
        <!-- Category Filter Tabs -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
            <button class="btn btn-forest btn-sm rounded-pill px-4 active" data-gallery-filter="all">
                All Photos (<?= count($galleryItems); ?>)
            </button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-outline-forest btn-sm rounded-pill px-3" data-gallery-filter="<?= e($cat['slug']); ?>">
                    <?= e($cat['name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Asymmetric Masonry Gallery Grid -->
        <div class="row g-4" id="galleryGridContainer">
            <?php foreach ($galleryItems as $index => $item): 
                $photoImgUrl = !empty($item['image_path'])
                    ? (str_starts_with($item['image_path'], 'assets/') ? BASE_URL . '/' . ltrim($item['image_path'], '/') : image_url($item['image_path'], 'gallery', 'placeholder-gallery.jpg'))
                    : BASE_URL . '/assets/images/placeholder-gallery.jpg';
            ?>
            <div class="col-sm-6 col-lg-4 gallery-card-col">
                <div class="gallery-item cursor-pointer" onclick="openLightbox(<?= $index; ?>)">
                    <img src="<?= e($photoImgUrl); ?>" alt="<?= e($item['title']); ?>" class="w-100 h-100 object-fit-cover d-block" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-gallery.jpg';">
                    <div class="gallery-overlay">
                        <span class="badge bg-gold text-forest-dark mb-1 align-self-start small"><?= e($item['category_name']); ?></span>
                        <h4 class="h6 text-white mb-0"><?= e($item['title']); ?></h4>
                        <?php if (!empty($item['caption'])): ?>
                            <small class="text-cream opacity-75"><?= e($item['caption']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- Interactive Lightbox Modal -->
<div class="modal fade" id="galleryLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-forest-dark text-white rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <span class="badge bg-gold text-forest-dark" id="lightboxCategory">Sanctuary Gallery</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-3 bg-black bg-opacity-50 d-flex align-items-center justify-content-center mx-auto mb-3 overflow-hidden" style="min-height: 300px; max-height: 480px;">
                    <img src="" id="lightboxImg" class="w-100 h-100 object-fit-contain" alt="">
                </div>
                <h3 class="h4 font-serif text-cream mb-1" id="lightboxTitle">Goushala Moment</h3>
                <p class="small text-cream opacity-75 mb-0" id="lightboxCaption">Description of the moment</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-between">
                <button type="button" class="btn btn-outline-gold btn-sm rounded-pill px-3" onclick="prevLightbox()">
                    <i class="bi bi-chevron-left me-1"></i> Previous
                </button>
                <button type="button" class="btn btn-outline-gold btn-sm rounded-pill px-3" onclick="nextLightbox()">
                    Next <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initial payload for client lightbox
currentGalleryList = <?= json_encode(array_map(function($i) {
    $img = !empty($i['image_path'])
        ? (str_starts_with($i['image_path'], 'assets/') ? BASE_URL . '/' . ltrim($i['image_path'], '/') : image_url($i['image_path'], 'gallery', 'placeholder-gallery.jpg'))
        : BASE_URL . '/assets/images/placeholder-gallery.jpg';
    return [
        'id'            => $i['id'],
        'title'         => $i['title'],
        'image_path'    => $img,
        'caption'       => $i['caption'] ?? '',
        'category_name' => $i['category_name']
    ];
}, $galleryItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<script src="<?= ASSETS_URL; ?>/js/gallery.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
