<?php
/**
 * Kamadenu Goushala Platform - Cows Directory
 */

declare(strict_types=1);

$pageTitle = 'Our Cows – Sacred Rescued Residents';
$metaDescription = 'Explore our directory of rescued indigenous cows at Kamadenu Goushala. Search by breed, gender, health status, and sponsor or adopt a cow today.';

require_once __DIR__ . '/includes/header.php';

// Parse query filters
$filters = [
    'search'        => sanitize_input($_GET['q'] ?? ''),
    'breed'         => sanitize_input($_GET['breed'] ?? ''),
    'gender'        => sanitize_input($_GET['gender'] ?? ''),
    'health_status' => sanitize_input($_GET['health'] ?? ''),
    'status'        => sanitize_input($_GET['status'] ?? ''),
    'sort'          => sanitize_input($_GET['sort'] ?? 'featured')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

// Fetch cow data and breeds
$cowData = Cow::getAll($filters, $page, $perPage);
$cows = $cowData['items'];
$totalCows = $cowData['total'];
$totalPages = $cowData['total_pages'];

$breedsList = Breed::getAllWithCount();

// Check if any filter is active
$hasActiveFilters = !empty($filters['search']) || !empty($filters['breed']) || !empty($filters['gender']) || !empty($filters['health_status']) || !empty($filters['status']);
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-heart-pulse-fill me-1"></i> Rescued & Protected Souls
        </span>
        <h1 class="page-hero-title">Meet Our Rescued Cows</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Every cow in our sanctuary has a story of transformation. Find a blessed soul to visit, sponsor, or adopt as your spiritual companion.
        </p>
    </div>
</section>

<!-- Main Cows Catalog & Filter Layout -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <div class="row g-4">
            
            <!-- Left Filter Sidebar -->
            <div class="col-lg-3">
                <div class="card p-4 rounded-4 shadow-sm border-0 bg-white sticky-top" style="top: 100px; z-index: 10;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 font-serif text-forest-dark mb-0"><i class="bi bi-funnel-fill text-gold me-2"></i> Filters</h2>
                        <?php if ($hasActiveFilters): ?>
                            <a href="<?= BASE_URL; ?>/cows.php" class="small text-danger fw-semibold">Reset All</a>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="<?= BASE_URL; ?>/cows.php" id="cowFilterForm">
                        
                        <!-- Search Box -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">Search Cow</label>
                            <div class="input-group">
                                <input type="text" name="q" class="form-control form-control-sm" placeholder="Name, code or story..." value="<?= e($filters['search']); ?>">
                                <button class="btn btn-forest btn-sm" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                        </div>

                        <!-- Breed Filter -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">Indigenous Breed</label>
                            <select name="breed" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Breeds (<?= array_sum(array_column($breedsList, 'cow_count')); ?>)</option>
                                <?php foreach ($breedsList as $b): ?>
                                    <option value="<?= e($b['slug']); ?>" <?= $filters['breed'] === $b['slug'] ? 'selected' : ''; ?>>
                                        <?= e($b['name']); ?> (<?= $b['cow_count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Gender Filter -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">Gender & Category</label>
                            <select name="gender" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <option value="female" <?= $filters['gender'] === 'female' ? 'selected' : ''; ?>>Mother Cow (Female)</option>
                                <option value="male" <?= $filters['gender'] === 'male' ? 'selected' : ''; ?>>Sacred Bull / Nandi (Male)</option>
                                <option value="calf_female" <?= $filters['gender'] === 'calf_female' ? 'selected' : ''; ?>>Female Calf (Vatsa)</option>
                                <option value="calf_male" <?= $filters['gender'] === 'calf_male' ? 'selected' : ''; ?>>Male Calf (Vatsa)</option>
                            </select>
                        </div>

                        <!-- Health Status Filter -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">Health Status</label>
                            <select name="health" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Health Statuses</option>
                                <option value="healthy" <?= $filters['health_status'] === 'healthy' ? 'selected' : ''; ?>>Healthy & Active</option>
                                <option value="recovering" <?= $filters['health_status'] === 'recovering' ? 'selected' : ''; ?>>Recovering from Injury</option>
                                <option value="under_treatment" <?= $filters['health_status'] === 'under_treatment' ? 'selected' : ''; ?>>Under Vet Treatment</option>
                                <option value="elderly_care" <?= $filters['health_status'] === 'elderly_care' ? 'selected' : ''; ?>>Senior / Hospice Care</option>
                            </select>
                        </div>

                        <!-- Adoption Status -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">Adoption Availability</label>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Cows</option>
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : ''; ?>>Awaiting Guardian (Active)</option>
                                <option value="adopted" <?= $filters['status'] === 'adopted' ? 'selected' : ''; ?>>Currently Adopted</option>
                            </select>
                        </div>

                        <!-- Hidden Sort Parameter if present -->
                        <?php if (!empty($filters['sort'])): ?>
                            <input type="hidden" name="sort" value="<?= e($filters['sort']); ?>">
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-sm rounded-pill">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Cows Catalog Grid -->
            <div class="col-lg-9">
                
                <!-- Catalog Header & Sorting -->
                <div class="card p-3 rounded-4 shadow-sm border-0 bg-white mb-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <span class="text-forest-dark fw-bold"><?= $totalCows; ?> <?= $totalCows === 1 ? 'Cow' : 'Cows'; ?></span>
                            <span class="text-muted small">found in sanctuary</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted text-nowrap">Sort By:</label>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="location = this.value;">
                                <?php
                                $buildSortUrl = function($sortKey) use ($filters) {
                                    $q = $filters;
                                    $q['sort'] = $sortKey;
                                    return BASE_URL . '/cows.php?' . http_build_query(array_filter($q));
                                };
                                ?>
                                <option value="<?= $buildSortUrl('featured'); ?>" <?= $filters['sort'] === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                                <option value="<?= $buildSortUrl('newest'); ?>" <?= $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest Rescued</option>
                                <option value="<?= $buildSortUrl('oldest'); ?>" <?= $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>Oldest Rescued</option>
                                <option value="<?= $buildSortUrl('name_asc'); ?>" <?= $filters['sort'] === 'name_asc' ? 'selected' : ''; ?>>Name (A to Z)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cows Cards Grid -->
                <?php if (empty($cows)): ?>
                    <div class="card p-5 text-center rounded-4 border-0 shadow-sm bg-white">
                        <i class="bi bi-search fs-1 text-muted mb-2"></i>
                        <h3 class="font-serif text-forest-dark">No Cows Matched Your Criteria</h3>
                        <p class="text-muted small mb-4">Try clearing one or more filters or searching with a different term.</p>
                        <div>
                            <a href="<?= BASE_URL; ?>/cows.php" class="btn btn-forest rounded-pill px-4">Reset All Filters</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($cows as $cow): 
                            $healthClass = match($cow['health_status']) {
                                'under_treatment' => 'badge-health-treatment',
                                'elderly_care'   => 'badge-health-elderly',
                                'recovering'     => 'badge-health-recovering',
                                default          => 'badge-health-healthy'
                            };
                            $ageStr = calculate_cow_age($cow['date_of_birth']);
                            $cowImage = image_url(
                                $cow['main_image'] ?? null,
                                'cows',
                                'placeholder-cow.jpg'
                            );
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="heritage-card cow-card h-100 d-flex flex-column">
                                <div class="cow-image-wrapper">
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
                                    <?php if ($cow['status'] === 'adopted'): ?>
                                        <span class="position-absolute top-0 start-0 m-3 badge bg-gold text-forest-dark fw-bold small">
                                            <i class="bi bi-suit-heart-fill me-1"></i> Adopted
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h3 class="h5 font-serif text-forest-dark mb-0"><?= e($cow['name']); ?></h3>
                                        <span class="badge bg-gold-subtle text-gold-dark small"><?= e($cow['breed_name']); ?></span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center gap-2 small text-muted mb-2">
                                        <span><i class="bi bi-hourglass me-1"></i> <?= $ageStr; ?></span>
                                        <span>&bull;</span>
                                        <span><?= ucfirst(str_replace('_', ' ', $cow['gender'])); ?></span>
                                    </div>

                                    <p class="small text-muted mb-3 flex-grow-1">
                                        <?= e(mb_strimwidth($cow['rescue_story'] ?? $cow['description'] ?? '', 0, 110, '...')); ?>
                                    </p>

                                    <div class="pt-3 border-top mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Rescued: <?= format_date($cow['rescue_date']); ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($cow['slug']); ?>" class="btn btn-outline-forest btn-sm rounded-pill flex-grow-1">
                                                View Profile
                                            </a>
                                            <a href="<?= BASE_URL; ?>/adopt.php?cow_id=<?= $cow['id']; ?>" class="btn btn-gold btn-sm rounded-pill px-3" title="Adopt <?= e($cow['name']); ?>">
                                                <i class="bi bi-heart-fill"></i>
                                            </a>
                                            <?php
                                                $cowWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                                                $cleanCowWaPhone = preg_replace('/\D/', '', $cowWaPhone);
                                                $cowProfileUrl = BASE_URL . '/cow-details.php?slug=' . urlencode($cow['slug']);
                                                $cowWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                                            "I would like to inquire about adopting / sponsoring:\n" .
                                                            "🐄 *Gau Mata:* " . $cow['name'] . " (" . $cow['cow_code'] . ")\n" .
                                                            "🏷️ *Breed:* " . $cow['breed_name'] . "\n" .
                                                            "🔗 *Profile Link:* " . $cowProfileUrl . "\n\n" .
                                                            "Please share adoption seva details.";
                                                $cowWaUrl = "https://wa.me/" . $cleanCowWaPhone . "?text=" . rawurlencode($cowWaMsg);
                                            ?>
                                            <a href="<?= e($cowWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm rounded-pill px-2" title="Inquire on WhatsApp about <?= e($cow['name']); ?>">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Cow Directory Pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link text-forest-dark" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])); ?>">
                                        <i class="bi bi-chevron-left"></i> Prev
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link <?= $i === $page ? 'bg-forest border-forest text-white' : 'text-forest-dark'; ?>" href="?<?= http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                        <?= $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link text-forest-dark" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])); ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
