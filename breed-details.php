<?php
/**
 * Kamadenu Goushala Platform - Breed Profile & Resident Cows
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/breeds.php');
    exit;
}

$breed = Breed::findBySlug($slug);
if (!$breed) {
    http_response_code(404);
    $pageTitle = 'Breed Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Breed Information Not Found</h1>
            <p class="text-muted">The cow breed you are looking for does not exist in our catalog.</p>
            <a href="' . BASE_URL . '/breeds.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Breeds Directory</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $breed['name'] . ' Cow Breed – Indigenous Indian Cattle';
$metaDescription = 'Learn all about the ' . $breed['name'] . ' cow breed, originating from ' . $breed['origin_region'] . '. Meet ' . count($breed['cows']) . ' resident cows of this breed at Kamadenu Goushala.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-cream border-bottom py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/index.php" class="text-forest">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/breeds.php" class="text-forest">Breeds</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($breed['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Main Breed Profile Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row g-5 align-items-center">
            
            <div class="col-lg-6">
                <div class="card p-3 rounded-4 border-0 shadow-sm bg-cream">
                    <?php
                    $breedImage = image_url($breed['image'] ?? null, 'breeds', 'placeholder-breed.jpg');
                    ?>
                    <div class="position-relative rounded-4 overflow-hidden" style="height: 380px; background-color: var(--color-forest-dark);">
                        <img
                            src="<?= e($breedImage); ?>"
                            alt="<?= e($breed['name']); ?>"
                            class="w-100 h-100 object-fit-cover d-block"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-breed.jpg';"
                        >
                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white fs-6">
                            <i class="bi bi-geo-alt-fill text-gold me-1"></i> Origin: <?= e($breed['origin_region']); ?>
                        </span>
                        <span class="position-absolute top-0 end-0 m-3 badge bg-gold text-forest-dark fw-bold fs-6">
                            <?= count($breed['cows']); ?> In Sanctuary
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <span class="section-tag"><i class="bi bi-stars"></i> Indigenous Heritage</span>
                <h1 class="display-5 font-serif text-forest-dark fw-bold mb-3"><?= e($breed['name']); ?> Cow Breed</h1>
                
                <div class="p-3 rounded-3 bg-cream-soft border border-warning border-opacity-50 mb-3">
                    <strong class="text-forest-dark d-block mb-1"><i class="bi bi-check2-circle text-gold me-1"></i> Distinguishing Physical Traits:</strong>
                    <p class="small text-muted mb-0"><?= e($breed['characteristics']); ?></p>
                </div>

                <div class="lead text-muted mb-4">
                    <?= nl2br(e($breed['description'])); ?>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a href="#resident-cows" class="btn btn-forest rounded-pill px-4">
                        <i class="bi bi-eye me-1"></i> Meet <?= count($breed['cows']); ?> Resident <?= e($breed['name']); ?> Cows
                    </a>
                    <a href="<?= BASE_URL; ?>/donate.php?purpose=Conservation+of+<?= urlencode($breed['name']); ?>+Breed" class="btn btn-gold rounded-pill px-4">
                        <i class="bi bi-heart-fill me-1"></i> Support Breed Conservation
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Resident Cows of this Breed -->
<section class="py-5 bg-cream-soft border-top" id="resident-cows">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="section-tag"><i class="bi bi-emoji-smile"></i> Sanctuary Herd</span>
                <h2 class="section-title mb-0">Our <?= e($breed['name']); ?> Residents</h2>
            </div>
            <a href="<?= BASE_URL; ?>/cows.php?breed=<?= e($breed['slug']); ?>" class="btn btn-outline-forest btn-sm rounded-pill">
                View in Directory <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (empty($breed['cows'])): ?>
            <div class="card p-5 text-center rounded-4 border-0 bg-white">
                <p class="text-muted mb-0">Currently, no cows of this specific breed are registered in our active sanctuary directory.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($breed['cows'] as $cow): 
                    $healthClass = match($cow['health_status']) {
                        'under_treatment' => 'badge-health-treatment',
                        'elderly_care'   => 'badge-health-elderly',
                        'recovering'     => 'badge-health-recovering',
                        default          => 'badge-health-healthy'
                    };
                    $ageStr = calculate_cow_age($cow['date_of_birth']);
                    $cowImage = image_url($cow['main_image'] ?? null, 'cows', 'placeholder-cow.jpg');
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="heritage-card cow-card h-100 d-flex flex-column">
                        <div class="cow-image-wrapper" style="height: 200px;">
                            <img
                                src="<?= e($cowImage); ?>"
                                alt="<?= e($cow['name']); ?>"
                                class="cow-card-image"
                                loading="lazy"
                                width="600"
                                height="450"
                                onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';"
                            >
                            <span class="position-absolute top-0 end-0 m-3 badge <?= $healthClass; ?> badge-heritage">
                                <?= ucfirst(str_replace('_', ' ', $cow['health_status'])); ?>
                            </span>
                            <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                                <?= e($cow['cow_code']); ?>
                            </span>
                        </div>

                        <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h3 class="h5 font-serif text-forest-dark mb-0"><?= e($cow['name']); ?></h3>
                                <small class="text-muted"><?= $ageStr; ?></small>
                            </div>
                            <p class="small text-muted mb-3 flex-grow-1">
                                <?= e(mb_strimwidth($cow['rescue_story'] ?? $cow['description'] ?? '', 0, 95, '...')); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Rescued: <?= format_date($cow['rescue_date']); ?></small>
                                <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($cow['slug']); ?>" class="btn btn-sm btn-outline-forest rounded-pill px-3">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
