<?php
/**
 * Kamadenu Goushala Platform - Indigenous Cow Breeds Catalog
 */

declare(strict_types=1);

$pageTitle = 'Indigenous Cow Breeds (Bos Indicus) – Sacred Heritage';
$metaDescription = 'Explore the divine indigenous cow breeds of India conserved at Kamadenu Goushala: Gir, Sahiwal, Hallikar, Tharparkar, Rathi, and Malnad Gidda.';

require_once __DIR__ . '/includes/header.php';

$breeds = Breed::getAllWithCount();
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-flower1 me-1"></i> Bos Indicus Conservation
        </span>
        <h1 class="page-hero-title">Sacred Indigenous Cow Breeds</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            India is blessed with extraordinary native cattle breeds celebrated in ancient Vedic scriptures for their celestial energy, A2 beta-casein nutrition, and immense motherly grace.
        </p>
    </div>
</section>

<!-- Indigenous Breeds Grid -->
<section class="py-5 bg-cream-soft">
    <div class="container py-4">
        
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-shield-check"></i> Divine Biodiversity</span>
            <h2 class="section-title">Breeds Under Our Sanctuary Care</h2>
            <p class="section-subtitle mx-auto">We actively conserve, shelter, and rehabilitate endangered indigenous cattle lines.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($breeds as $b): 
                $breedImage = image_url($b['image'] ?? null, 'breeds', 'placeholder-breed.jpg');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="heritage-card cow-card h-100 d-flex flex-column">
                    <div class="cow-image-wrapper" style="height: 220px;">
                        <img
                            src="<?= e($breedImage); ?>"
                            alt="<?= e($b['name']); ?>"
                            class="cow-card-image"
                            loading="lazy"
                            width="600"
                            height="450"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-breed.jpg';"
                        >
                        <span class="position-absolute top-0 end-0 m-3 badge bg-gold text-forest-dark fw-bold badge-heritage">
                            <?= $b['cow_count']; ?> <?= $b['cow_count'] === 1 ? 'Cow' : 'Cows'; ?> in Sanctuary
                        </span>
                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                            <i class="bi bi-geo-alt-fill text-gold me-1"></i> <?= e($b['origin_region']); ?>
                        </span>
                    </div>

                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <h3 class="h4 font-serif text-forest-dark mb-2"><?= e($b['name']); ?></h3>
                        
                        <div class="p-2 rounded bg-cream-soft border border-warning border-opacity-25 mb-3 small">
                            <strong class="text-forest-dark"><i class="bi bi-stars text-gold me-1"></i> Key Traits:</strong>
                            <span class="text-muted"><?= e(mb_strimwidth($b['characteristics'] ?? '', 0, 90, '...')); ?></span>
                        </div>

                        <p class="small text-muted mb-4 flex-grow-1">
                            <?= e(mb_strimwidth($b['description'] ?? '', 0, 130, '...')); ?>
                        </p>

                        <div class="d-flex gap-2 pt-3 border-top mt-auto">
                            <a href="<?= BASE_URL; ?>/breed-details.php?slug=<?= e($b['slug']); ?>" class="btn btn-outline-forest btn-sm rounded-pill flex-grow-1">
                                Breed Details & Cows <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <a href="<?= BASE_URL; ?>/cows.php?breed=<?= e($b['slug']); ?>" class="btn btn-gold btn-sm rounded-pill px-3" title="View Cows of this Breed">
                                <i class="bi bi-search"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- Indigenous Science Info Banner -->
<section class="py-5 bg-white border-top">
    <div class="container py-3">
        <div class="p-4 p-md-5 rounded-4 bg-forest-dark text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-gold text-forest-dark fw-bold px-3 py-1 rounded-pill mb-2">Vedic Science</span>
                    <h3 class="display-6 font-serif text-cream mb-2">The Suryaketu Nadi & Pure A2 Milk</h3>
                    <p class="text-cream opacity-75 mb-0">
                        Indigenous Indian cows possess a distinct rounded hump containing the Suryaketu Nadi. When touched by morning solar rays, it naturally enriches their milk with carotene, pure A2 beta-casein, and sacred bioactive elements.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold btn-lg px-4 py-3 shadow-gold">
                        <i class="bi bi-heart-fill"></i> Sponsor Indigenous Care
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
