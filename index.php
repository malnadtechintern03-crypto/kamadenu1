<?php
/**
 * Kamadenu Goushala Platform - Master Homepage
 * Storytelling Flow with 11 Dynamic Sections Connected to MySQL
 */

declare(strict_types=1);

$pageTitle = 'Preserving Sacred Indigenous Cows with Vedic Care & Love';
$metaDescription = 'Welcome to Kamadenu Goushala. We rescue, shelter, and provide lifelong holistic care for indigenous Indian cows. 80G Tax-exempt donations.';
$hideTopbar = true;

require_once __DIR__ . '/includes/header.php';

// Fetch Live Database Data for Homepage
try {
    // 1. Live Impact Numbers
    $totalCows = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE status != 'deceased'");
    $rescuedCows = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE rescue_date IS NOT NULL");
    $activeSponsors = (int)Database::fetchColumn("SELECT COUNT(*) FROM sponsors WHERE status = 'active'");
    $yearsOfSeva = (int)get_setting('years_of_seva', '14');

    // 2. Initial Featured Cows
    $featuredCows = Database::fetchAll("
        SELECT c.*, b.name AS breed_name, b.slug AS breed_slug
        FROM cows c
        JOIN cow_breeds b ON c.breed_id = b.id
        WHERE c.status != 'deceased'
        ORDER BY c.is_featured DESC, c.id ASC
        LIMIT 8
    ");

    // 3. Cow Breeds for Filter Tabs
    $breeds = Database::fetchAll("SELECT id, name, slug FROM cow_breeds ORDER BY name ASC");

    // 4. Seva Programs
    $sevaPrograms = Database::fetchAll("SELECT * FROM seva_programs WHERE is_active = 1 ORDER BY display_order ASC LIMIT 6");

    // 5. Financial Transparency Data
    $totalDonations = (float)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'success'");
    $totalExpenses = (float)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM expenses");
    $expenseCategories = Database::fetchAll("
        SELECT ec.name, ec.icon_class, COALESCE(SUM(e.amount), 0) AS cat_total
        FROM expense_categories ec
        LEFT JOIN expenses e ON ec.id = e.category_id
        GROUP BY ec.id, ec.name, ec.icon_class
        ORDER BY cat_total DESC
    ");

    // 6. Rescue Stories / Blog Posts
    $recentStories = Database::fetchAll("
        SELECT bp.*, bc.name AS category_name, u.name AS author_name
        FROM blog_posts bp
        JOIN blog_categories bc ON bp.category_id = bc.id
        LEFT JOIN users u ON bp.author_id = u.id
        WHERE bp.is_published = 1
        ORDER BY bp.published_at DESC
        LIMIT 3
    ");

    // 7. Gallery Highlights
    $galleryItems = Database::fetchAll("
        SELECT g.*, gc.name AS category_name
        FROM gallery g
        JOIN gallery_categories gc ON g.category_id = gc.id
        ORDER BY g.display_order ASC, g.id DESC
        LIMIT 6
    ");

    // 8. Testimonials
    $testimonials = Database::fetchAll("
        SELECT * FROM testimonials
        WHERE is_featured = 1
        ORDER BY id ASC
        LIMIT 3
    ");

    // 0. Live Hero Slides
    try {
        $heroSlides = Database::fetchAll("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    } catch (Throwable $th) {
        $heroSlides = [];
    }

} catch (Throwable $t) {
    error_log('Homepage Data Load Error: ' . $t->getMessage());
    $heroSlides = [];
    $totalCows = 0; $rescuedCows = 0; $activeSponsors = 0; $yearsOfSeva = 14;
    $featuredCows = []; $breeds = []; $sevaPrograms = [];
    $totalDonations = 0; $totalExpenses = 0; $expenseCategories = [];
    $recentStories = []; $galleryItems = []; $testimonials = [];
}

$resolveHeroUrl = function(string $url): string {
    if (empty($url)) return '#';
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }
    return BASE_URL . '/' . ltrim($url, '/');
};

$resolveHeroImg = function(string $img): string {
    if (empty($img)) return BASE_URL . '/assets/images/hero-cow.jpg';
    if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) return $img;
    if (str_starts_with($img, 'assets/') || str_starts_with($img, 'uploads/')) return BASE_URL . '/' . ltrim($img, '/');
    return BASE_URL . '/uploads/hero/' . rawurlencode($img);
};
?>

<!-- ==============================================================================
     1. DYNAMIC FULL-SCREEN HERO SECTION / SLIDER (Admin Managed)
     ============================================================================== -->
<?php if (count($heroSlides) > 1): ?>
    <section class="hero-section hero-slider-section position-relative p-0 overflow-hidden">
        <!-- Grand Golden Particle Sparks Layer -->
        <canvas id="heroParticlesCanvas" class="hero-particles-layer"></canvas>
        <div class="hero-ambient-orb hero-ambient-orb-1"></div>
        <div class="hero-ambient-orb hero-ambient-orb-2"></div>

        <div id="homepageHeroCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="hover">
            <!-- Carousel Slide Progress Countdown Bar -->
            <div class="carousel-slide-progress">
                <div class="carousel-progress-fill" id="heroCarouselProgress"></div>
            </div>

            <!-- Carousel Indicators -->
            <div class="carousel-indicators mb-4">
                <?php foreach ($heroSlides as $idx => $slide): ?>
                    <button type="button" 
                            data-bs-target="#homepageHeroCarousel" 
                            data-bs-slide-to="<?= $idx; ?>" 
                            class="<?= $idx === 0 ? 'active' : ''; ?>" 
                            aria-current="<?= $idx === 0 ? 'true' : 'false'; ?>" 
                            aria-label="Slide <?= $idx + 1; ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Slides -->
            <div class="carousel-inner h-100">
                <?php foreach ($heroSlides as $idx => $slide): 
                    $slideBg = $resolveHeroImg($slide['image_path'] ?? '');
                ?>
                    <div class="carousel-item <?= $idx === 0 ? 'active' : ''; ?> position-relative">
                        <div class="hero-image">
                            <img src="<?= e($slideBg); ?>" alt="<?= e($slide['title']); ?>" class="hero-bg-image" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/hero-cow.jpg';">
                        </div>
                        <div class="hero-overlay"></div>
                        <div class="container hero-content">
                            <?php if (!empty($slide['badge_text'])): ?>
                                <div class="mb-2">
                                    <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill small border border-warning border-opacity-50 animate-pulse-glow">
                                        <i class="bi bi-award-fill me-1"></i> <?= e($slide['badge_text']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <span class="hero-eyebrow">
                                <?= e($slide['eyebrow'] ?: 'KAMADENU GOUSHALA'); ?>
                            </span>

                            <h1>
                                <?= e($slide['title']); ?>
                            </h1>

                            <?php if (!empty($slide['subtitle'])): ?>
                                <p class="fs-5 mb-4 text-light text-opacity-90 max-w-700">
                                    <?= e($slide['subtitle']); ?>
                                </p>
                            <?php endif; ?>

                            <div class="hero-buttons">
                                <?php if (!empty($slide['btn_primary_text'])): ?>
                                    <a href="<?= e($resolveHeroUrl($slide['btn_primary_url'] ?? '/donate.php')); ?>" class="btn btn-gold shadow-gold">
                                        <i class="bi <?= e($slide['btn_primary_icon'] ?: 'bi-heart-fill'); ?> me-1"></i> 
                                        <?= e($slide['btn_primary_text']); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($slide['btn_secondary_text'])): ?>
                                    <a href="<?= e($resolveHeroUrl($slide['btn_secondary_url'] ?? '/about.php')); ?>" class="btn btn-outline-light">
                                        <i class="bi <?= e($slide['btn_secondary_icon'] ?: 'bi-compass'); ?> me-1"></i> 
                                        <?= e($slide['btn_secondary_text']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="prev" aria-label="Previous Slide">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homepageHeroCarousel" data-bs-slide="next" aria-label="Next Slide">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <!-- Scroll Down Indicator -->
            <a href="#meet-cows" class="hero-scroll-indicator" aria-label="Scroll down to meet our cows">
                <i class="bi bi-mouse"></i>
                <i class="bi bi-chevron-compact-down fs-5"></i>
            </a>
        </div>
    </section>

<?php elseif (count($heroSlides) === 1): 
    $singleSlide = $heroSlides[0];
    $singleBg = $resolveHeroImg($singleSlide['image_path'] ?? '');
?>
    <section class="hero-section position-relative overflow-hidden">
        <canvas id="heroParticlesCanvas" class="hero-particles-layer"></canvas>
        <div class="hero-ambient-orb hero-ambient-orb-1"></div>
        <div class="hero-ambient-orb hero-ambient-orb-2"></div>
        <div class="hero-image">
            <img src="<?= e($singleBg); ?>" alt="<?= e($singleSlide['title']); ?>" class="hero-bg-image" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/hero-cow.jpg';">
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content position-relative" style="z-index: 4;">
            <?php if (!empty($singleSlide['badge_text'])): ?>
                <div class="mb-2">
                    <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill small border border-warning border-opacity-50 animate-pulse-glow">
                        <i class="bi bi-award-fill me-1"></i> <?= e($singleSlide['badge_text']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <span class="hero-eyebrow">
                <?= e($singleSlide['eyebrow'] ?: 'KAMADENU GOUSHALA'); ?>
            </span>

            <h1>
                <?= e($singleSlide['title']); ?>
            </h1>

            <?php if (!empty($singleSlide['subtitle'])): ?>
                <p class="fs-5 mb-4 text-light text-opacity-90 max-w-700">
                    <?= e($singleSlide['subtitle']); ?>
                </p>
            <?php endif; ?>

            <div class="hero-buttons">
                <?php if (!empty($singleSlide['btn_primary_text'])): ?>
                    <a href="<?= e($resolveHeroUrl($singleSlide['btn_primary_url'] ?? '/donate.php')); ?>" class="btn btn-gold shadow-gold">
                        <i class="bi <?= e($singleSlide['btn_primary_icon'] ?: 'bi-heart-fill'); ?> me-1"></i> 
                        <?= e($singleSlide['btn_primary_text']); ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($singleSlide['btn_secondary_text'])): ?>
                    <a href="<?= e($resolveHeroUrl($singleSlide['btn_secondary_url'] ?? '/about.php')); ?>" class="btn btn-outline-light">
                        <i class="bi <?= e($singleSlide['btn_secondary_icon'] ?: 'bi-compass'); ?> me-1"></i> 
                        <?= e($singleSlide['btn_secondary_text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Scroll Down Indicator -->
        <a href="#meet-cows" class="hero-scroll-indicator" aria-label="Scroll down to meet our cows">
            <i class="bi bi-mouse"></i>
            <i class="bi bi-chevron-compact-down fs-5"></i>
        </a>
    </section>

<?php else: ?>
    <!-- Static Fallback Hero Section -->
    <section class="hero-section position-relative overflow-hidden">
        <canvas id="heroParticlesCanvas" class="hero-particles-layer"></canvas>
        <div class="hero-ambient-orb hero-ambient-orb-1"></div>
        <div class="hero-ambient-orb hero-ambient-orb-2"></div>
        <div class="hero-image">
            <img src="<?= BASE_URL; ?>/assets/images/hero-cow.jpg" alt="Rescued cow at Kamadenu Goushala" class="hero-bg-image">
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content position-relative" style="z-index: 4;">
            <span class="hero-eyebrow">KAMADENU GOUSHALA</span>
            <h1>Every Life Deserves Care.</h1>
            <p class="fs-5 mb-4 text-light text-opacity-90">Protecting, healing and nurturing rescued cows with compassion, seva and dignity.</p>
            <div class="hero-buttons">
                <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold shadow-gold">
                    <i class="bi bi-heart-fill me-1"></i> Support a Cow
                </a>
                <a href="<?= BASE_URL; ?>/about.php" class="btn btn-outline-light">
                    <i class="bi bi-compass me-1"></i> Explore Our Goushala
                </a>
            </div>
        </div>
        <!-- Scroll Down Indicator -->
        <a href="#meet-cows" class="hero-scroll-indicator" aria-label="Scroll down to meet our cows">
            <i class="bi bi-mouse"></i>
            <i class="bi bi-chevron-compact-down fs-5"></i>
        </a>
    </section>
<?php endif; ?>

<!-- ==============================================================================
     2. LIVE IMPACT DASHBOARD (Direct MySQL Queries)
     ============================================================================== -->
<section class="impact-dashboard-section" data-animate="fade-up">
    <div class="container">
        <div class="row g-4 justify-content-center">
            
            <!-- Card 1: Total Cows Protected -->
            <div class="col-12 col-sm-6 col-lg-3" data-animate="zoom-in" data-delay="100">
                <div class="stat-card h-100">
                    <div class="stat-icon">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number counter-value" data-target="<?= $totalCows; ?>"><?= $totalCows; ?></div>
                        <div class="stat-label">TOTAL COWS PROTECTED</div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Cows Rescued -->
            <div class="col-12 col-sm-6 col-lg-3" data-animate="zoom-in" data-delay="200">
                <div class="stat-card h-100">
                    <div class="stat-icon stat-icon-gold">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number counter-value" data-target="<?= $rescuedCows; ?>"><?= $rescuedCows; ?></div>
                        <div class="stat-label">COWS RESCUED</div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Active Devotee Sponsors -->
            <div class="col-12 col-sm-6 col-lg-3" data-animate="zoom-in" data-delay="300">
                <div class="stat-card h-100">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number counter-value" data-target="<?= $activeSponsors; ?>"><?= $activeSponsors; ?></div>
                        <div class="stat-label">ACTIVE DEVOTEE SPONSORS</div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Years of Sacred Seva -->
            <div class="col-12 col-sm-6 col-lg-3" data-animate="zoom-in" data-delay="400">
                <div class="stat-card h-100">
                    <div class="stat-icon stat-icon-gold">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number counter-value" data-target="<?= $yearsOfSeva; ?>"><?= $yearsOfSeva; ?></div>
                        <div class="stat-label">YEARS OF SACRED SEVA</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ==============================================================================
     3. MEET THE COWS (AJAX Filterable Showcase)
     ============================================================================== -->
<section class="py-5 mt-4" id="meet-cows">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3" data-animate="fade-up">
            <div>
                <span class="section-tag"><i class="bi bi-stars"></i> Protected Souls</span>
                <h2 class="section-title">Meet Our Rescued Residents</h2>
                <p class="section-subtitle mb-0">Every cow carries a profound journey from distress to safety and divine grace.</p>
            </div>
            <a href="<?= BASE_URL; ?>/cows.php" class="btn btn-outline-forest">
                View All Cows <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="d-flex flex-wrap gap-2 mb-4 pb-2" data-animate="fade-up" data-delay="150">
            <button class="btn btn-forest btn-sm rounded-pill px-3 active" data-cow-filter="all">All Breeds</button>
            <button class="btn btn-outline-forest btn-sm rounded-pill px-3" data-cow-filter="featured">Featured</button>
            <button class="btn btn-outline-forest btn-sm rounded-pill px-3" data-cow-filter="adoptable">Adoptable</button>
            <?php foreach ($breeds as $b): ?>
                <button class="btn btn-outline-forest btn-sm rounded-pill px-3" data-cow-breed="<?= e($b['slug']); ?>" data-cow-filter="breed"><?= e($b['name']); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Dynamic Cows Grid Container -->
        <div class="row g-4" id="cowsShowcaseContainer">
            <?php foreach ($featuredCows as $cow): 
                $healthClass = match($cow['health_status']) {
                    'under_treatment' => 'badge-health-treatment',
                    'elderly_care'   => 'badge-health-elderly',
                    'recovering'     => 'badge-health-recovering',
                    default          => 'badge-health-healthy'
                };
                $cowImage = image_url(
                    $cow['main_image'] ?? null,
                    'cows',
                    'placeholder-cow.jpg'
                );
            ?>
            <div class="col-md-6 col-lg-3" data-animate="fade-up">
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
                    </div>

                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h3 class="h5 font-serif text-forest-dark mb-0"><?= e($cow['name']); ?></h3>
                            <span class="badge bg-gold-subtle text-gold-dark small"><?= e($cow['breed_name']); ?></span>
                        </div>
                        <p class="small text-muted mb-3 flex-grow-1">
                            <?= e(mb_strimwidth($cow['rescue_story'] ?? $cow['description'] ?? '', 0, 95, '...')); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                            <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Rescued: <?= format_date($cow['rescue_date']); ?></small>
                            <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($cow['slug']); ?>" class="btn btn-sm btn-outline-forest rounded-pill px-3">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     4. A COW'S JOURNEY (Interactive 6-Stage Rescue Timeline)
     ============================================================================== -->
<section class="py-5 bg-cream position-relative overflow-hidden" data-animate="fade-up">
    <!-- Auspicious Sacred Mandala Accent -->
    <div class="mandala-watermark mandala-watermark-right">
        <svg viewBox="0 0 100 100" fill="currentColor">
            <circle cx="50" cy="50" r="46" stroke="currentColor" stroke-width="1.5" fill="none"/>
            <circle cx="50" cy="50" r="32" stroke="currentColor" stroke-width="1" fill="none"/>
            <path d="M50 4 Q58 35 50 50 Q42 35 50 4 Z M50 96 Q58 65 50 50 Q42 65 50 96 Z M4 50 Q35 58 50 50 Q35 42 4 50 Z M96 50 Q65 58 50 50 Q65 42 96 50 Z" fill="currentColor"/>
        </svg>
    </div>
    <div class="container py-4 text-center position-relative" style="z-index: 2;">
        <span class="section-tag justify-content-center"><i class="bi bi-arrow-repeat"></i> Holistic Lifecycle</span>
        <h2 class="section-title">A Cow's Journey to Sanctuary</h2>
        <p class="section-subtitle mx-auto mb-5">Our 6-step compassionate protocol ensures every rescued soul transitions from trauma to lifelong peace.</p>

        <div class="journey-timeline-wrapper">
            <div class="journey-step-card" data-animate="fade-up" data-delay="100">
                <div class="journey-step-num">1</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Emergency Rescue</h3>
                <p class="small text-muted mb-0">24x7 ambulance response to distress calls, accidents & transit abuse.</p>
            </div>
            <div class="journey-step-card" data-animate="fade-up" data-delay="200">
                <div class="journey-step-num">2</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Veterinary Care</h3>
                <p class="small text-muted mb-0">Surgeries, wound debridement, hydration IVs, and quarantine check.</p>
            </div>
            <div class="journey-step-card" data-animate="fade-up" data-delay="300">
                <div class="journey-step-num">3</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Holistic Recovery</h3>
                <p class="small text-muted mb-0">Ayurvedic herbs, trauma healing, and loving caretaker companionship.</p>
            </div>
            <div class="journey-step-card" data-animate="fade-up" data-delay="400">
                <div class="journey-step-num">4</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Pure Nutrition</h3>
                <p class="small text-muted mb-0">Fresh green organic fodder, jowar dry husk, and clean natural water.</p>
            </div>
            <div class="journey-step-card" data-animate="fade-up" data-delay="500">
                <div class="journey-step-num">5</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Safe Open Pasture</h3>
                <p class="small text-muted mb-0">15 acres of pesticide-free natural roaming grounds with herd harmony.</p>
            </div>
            <div class="journey-step-card" data-animate="fade-up" data-delay="600">
                <div class="journey-step-num">6</div>
                <h3 class="h6 font-heading text-forest-dark mb-2">Lifelong Seva</h3>
                <p class="small text-muted mb-0">Unconditional dignity and Vedic care until their natural last breath.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     5. WHY YOUR SUPPORT MATTERS (Interactive Preset Giving)
     ============================================================================== -->
<section class="py-5 bg-white" data-animate="fade-up">
    <div class="container py-4 text-center">
        <span class="section-tag justify-content-center"><i class="bi bi-heart-pulse-fill"></i> Sacred Giving</span>
        <h2 class="section-title">Why Your Support Matters</h2>
        <p class="section-subtitle mx-auto mb-5">Every single rupee directly feeds, treats, and houses a rescued cow with 100% financial transparency.</p>

        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-3" data-animate="zoom-in" data-delay="100">
                <div class="preset-giving-card h-100">
                    <div class="badge bg-gold text-forest-dark mb-2">Green Fodder</div>
                    <div class="preset-amount">₹ 101</div>
                    <p class="small text-muted mb-3">Feeds fresh grass and nutritious dry fodder to 1 rescued cow for a full day.</p>
                    <a href="<?= BASE_URL; ?>/donate.php?amount=101" class="btn btn-outline-forest btn-sm w-100 rounded-pill">Select ₹ 101</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-animate="zoom-in" data-delay="200">
                <div class="preset-giving-card active h-100">
                    <div class="badge bg-forest text-white mb-2">Most Popular</div>
                    <div class="preset-amount">₹ 501</div>
                    <p class="small text-muted mb-3">Provides full day nutrition, protein mash & fresh clean water for 5 cows.</p>
                    <a href="<?= BASE_URL; ?>/donate.php?amount=501" class="btn btn-gold btn-sm w-100 rounded-pill">Select ₹ 501</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-animate="zoom-in" data-delay="300">
                <div class="preset-giving-card h-100">
                    <div class="badge bg-gold text-forest-dark mb-2">Medicine & Vet</div>
                    <div class="preset-amount">₹ 1,001</div>
                    <p class="small text-muted mb-3">Sponsors essential emergency antibiotics, wound dressing & vet checkup.</p>
                    <a href="<?= BASE_URL; ?>/donate.php?amount=1001" class="btn btn-outline-forest btn-sm w-100 rounded-pill">Select ₹ 1,001</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3" data-animate="zoom-in" data-delay="400">
                <div class="preset-giving-card h-100">
                    <div class="badge bg-gold text-forest-dark mb-2">Monthly Parent</div>
                    <div class="preset-amount">₹ 2,501</div>
                    <p class="small text-muted mb-3">Complete guardian sponsorship including shelter, vaccines & grooming.</p>
                    <a href="<?= BASE_URL; ?>/donate.php?amount=2501" class="btn btn-outline-forest btn-sm w-100 rounded-pill">Select ₹ 2,501</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     6. GAU SEVA PROGRAMS
     ============================================================================== -->
<section class="py-5 bg-cream-soft" id="seva" data-animate="fade-up">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-hands"></i> Auspicious Seva</span>
            <h2 class="section-title">Our Gau Seva Initiatives</h2>
            <p class="section-subtitle mx-auto">Participate in dedicated Vedic seva programs designed to uphold Sanatana Dharma and cow welfare.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($sevaPrograms as $i => $prog): ?>
            <div class="col-md-6 col-lg-4" data-animate="fade-up" data-delay="<?= ($i % 3) * 150 + 100; ?>">
                <div class="heritage-card seva-program-card h-100 d-flex flex-column">
                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon-wrapper" style="width:50px;height:50px;font-size:1.4rem;">
                                <i class="bi <?= e($prog['icon_class']); ?>"></i>
                            </div>
                            <div>
                                <h3 class="h5 font-serif text-forest-dark mb-0"><?= e($prog['title']); ?></h3>
                                <small class="text-gold-dark fw-semibold"><?= format_inr($prog['suggested_amount']); ?> suggested</small>
                            </div>
                        </div>
                        <p class="small text-muted mb-4 flex-grow-1">
                            <?= e($prog['subtitle'] ?? mb_strimwidth($prog['description'], 0, 110, '...')); ?>
                        </p>
                        <a href="<?= BASE_URL; ?>/seva-details.php?slug=<?= e($prog['slug']); ?>" class="btn btn-outline-forest rounded-pill w-100 mt-auto">
                            Participate in Seva <i class="bi bi-heart-fill ms-1 text-gold"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     7. TRANSPARENCY & ACCOUNTABILITY (Charts & Real MySQL Data)
     ============================================================================== -->
<section class="py-5 bg-white border-top border-bottom" data-animate="fade-up">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-animate="fade-right">
                <span class="section-tag"><i class="bi bi-shield-check"></i> Pure Transparency</span>
                <h2 class="section-title">Every Rupee Accounted For</h2>
                <p class="text-muted mb-4">
                    We maintain absolute financial integrity. Donors receive official 80G tax certificates, and all operational expenses are audited and published openly.
                </p>

                <div class="p-3 rounded-4 bg-cream border border-warning border-opacity-50 mb-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-forest-dark fw-bold">Total Seva Donations Received</span>
                        <span class="font-serif text-forest-dark fw-bold"><?= format_inr($totalDonations, true); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Total Verified Operational Expenses</span>
                        <span class="font-serif text-muted small"><?= format_inr($totalExpenses, true); ?></span>
                    </div>
                    <div class="transparency-bar">
                        <div class="transparency-fill" style="width: 82%; transition: width 1.8s cubic-bezier(0.16, 1, 0.3, 1);"></div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4">
                    <div class="badge-heritage badge-heritage-gold px-3 py-2 animate-pulse-glow">
                        <i class="bi bi-patch-check-fill me-1"></i> Section 80G Certified
                    </div>
                    <div class="badge-heritage badge-heritage-forest px-3 py-2">
                        <i class="bi bi-bank2 me-1"></i> Audited Trust
                    </div>
                </div>
            </div>

            <div class="col-lg-7" data-animate="fade-left">
                <div class="card p-4 rounded-4 shadow-sm border-0 bg-cream-soft transparency-card">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-pie-chart-fill text-gold me-2"></i> Monthly Expense Allocation</h3>
                    <div class="row g-3">
                        <?php foreach ($expenseCategories as $k => $cat): ?>
                        <div class="col-sm-6" data-animate="zoom-in" data-delay="<?= ($k % 4) * 80 + 50; ?>">
                            <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-xs transition-all hover-translate">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= e($cat['icon_class']); ?> text-forest fs-5"></i>
                                    <span class="small fw-semibold text-forest-dark"><?= e($cat['name']); ?></span>
                                </div>
                                <span class="small fw-bold text-gold-dark"><?= format_inr($cat['cat_total']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================================
     8. RESCUE STORIES & ARTICLES
     ============================================================================== -->
<section class="py-5 bg-cream-soft" id="stories" data-animate="fade-up">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="section-tag"><i class="bi bi-journal-richtext"></i> Chronicles of Care</span>
                <h2 class="section-title">Rescue Stories & Vedic Wisdom</h2>
                <p class="section-subtitle mb-0">Heartwarming journeys of transformation, ayurvedic insights, and updates from the sanctuary.</p>
            </div>
            <a href="<?= BASE_URL; ?>/blog.php" class="btn btn-outline-forest">
                View All Stories <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($recentStories as $idx => $story):
                $storyImg = image_url($story['featured_image'] ?? null, 'blog', 'placeholder-blog.jpg');
            ?>
            <div class="col-md-6 col-lg-4" data-animate="fade-up" data-delay="<?= $idx * 150 + 100; ?>">
                <div class="heritage-card h-100 d-flex flex-column">
                    <div class="blog-card-img overflow-hidden">
                        <img
                            src="<?= e($storyImg); ?>"
                            alt="<?= e($story['title']); ?>"
                            class="w-100 h-100 object-fit-cover d-block transition-all"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-blog.jpg';"
                        >
                    </div>
                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-gold-subtle text-gold-dark small"><?= e($story['category_name']); ?></span>
                            <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?= format_date($story['published_at']); ?></small>
                        </div>
                        <h3 class="h5 font-serif text-forest-dark mb-2"><?= e($story['title']); ?></h3>
                        <p class="small text-muted mb-4 flex-grow-1">
                            <?= e($story['excerpt']); ?>
                        </p>
                        <a href="<?= BASE_URL; ?>/blog-details.php?slug=<?= e($story['slug']); ?>" class="btn btn-sm btn-outline-forest rounded-pill mt-auto align-self-start">
                            Read Full Story <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     9. PHOTO GALLERY HIGHLIGHTS
     ============================================================================== -->
<section class="py-5 bg-white" id="gallery" data-animate="fade-up">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="section-tag"><i class="bi bi-camera-fill"></i> Moments of Peace</span>
                <h2 class="section-title">Sanctuary Gallery</h2>
                <p class="section-subtitle mb-0">Glimpses of joyful free grazing, morning aartis, and compassionate veterinary care.</p>
            </div>
            <a href="<?= BASE_URL; ?>/gallery.php" class="btn btn-outline-forest">
                Explore Full Gallery <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($galleryItems as $gidx => $item):
                $galleryImg = !empty($item['image_path'])
                    ? (str_starts_with($item['image_path'], 'assets/') ? BASE_URL . '/' . ltrim($item['image_path'], '/') : image_url($item['image_path'], 'gallery', 'placeholder-gallery.jpg'))
                    : BASE_URL . '/assets/images/placeholder-gallery.jpg';
            ?>
            <div class="col-sm-6 col-lg-4" data-animate="zoom-in" data-delay="<?= ($gidx % 3) * 120 + 80; ?>">
                <div class="gallery-item">
                    <img
                        src="<?= e($galleryImg); ?>"
                        alt="<?= e($item['title']); ?>"
                        class="w-100 h-100 object-fit-cover d-block"
                        loading="lazy"
                        onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-gallery.jpg';"
                    >
                    <div class="gallery-overlay">
                        <span class="badge bg-gold text-forest-dark mb-1 align-self-start small"><?= e($item['category_name']); ?></span>
                        <h4 class="h6 text-white mb-0"><?= e($item['title']); ?></h4>
                        <small class="text-cream opacity-75"><?= e($item['caption'] ?? ''); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     10. TESTIMONIALS & COMMUNITY VOICES
     ============================================================================== -->
<section class="py-5 bg-cream" data-animate="fade-up">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-chat-heart"></i> Devotee & Donor Words</span>
            <h2 class="section-title">Voices of Our Seva Community</h2>
            <p class="section-subtitle mx-auto">Hear from pilgrims, regular adopters, and volunteers whose lives have been touched by Gomata.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($testimonials as $tidx => $t): ?>
            <div class="col-md-6 col-lg-4" data-animate="fade-up" data-delay="<?= $tidx * 150 + 100; ?>">
                <div class="testimonial-card h-100 d-flex flex-column justify-content-between">
                    <i class="bi bi-quote testimonial-quote-icon"></i>
                    <div>
                        <div class="text-warning mb-3">
                            <?php for($i=0; $i<$t['rating']; $i++): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-muted fst-italic mb-4">
                            "<?= e($t['content']); ?>"
                        </p>
                    </div>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <div class="testimonial-avatar">
                            <?= mb_substr($t['name'], 0, 1); ?>
                        </div>
                        <div>
                            <h4 class="h6 font-serif text-forest-dark mb-0"><?= e($t['name']); ?></h4>
                            <small class="text-muted"><?= e($t['designation'] ?? 'Devotee'); ?>, <?= e($t['location'] ?? ''); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================================
     11. FINAL HIGH-IMPACT DONATION CALL TO ACTION
     ============================================================================== -->
<section class="py-5 bg-forest-dark text-white text-center position-relative overflow-hidden" data-animate="zoom-in">
    <div class="hero-ambient-orb hero-ambient-orb-1" style="opacity: 0.35;"></div>
    <div class="hero-ambient-orb hero-ambient-orb-2" style="opacity: 0.25;"></div>
    <div class="container py-5 position-relative" style="z-index: 2;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <span class="badge bg-gold-subtle text-gold px-3 py-2 rounded-pill mb-3 border border-warning border-opacity-50 animate-pulse-glow">
                    <i class="bi bi-flower1 me-1"></i> Punyakoti Mahā Dāna
                </span>
                <h2 class="font-serif display-5 text-cream fw-bold mb-3">
                    Protect a Sacred Life with Your Compassion
                </h2>
                <p class="text-cream opacity-90 fs-5 mb-4">
                    Your contribution ensures nutritional green fodder, 24x7 veterinary medicine, and lifelong sanctuary for cows who have nowhere else to go.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                    <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold btn-lg px-5 py-3 fs-5 shadow-gold">
                        <i class="bi bi-heart-fill"></i> Donate Online (80G Tax Benefit)
                    </a>
                    <a href="<?= BASE_URL; ?>/contact.php" class="btn btn-outline-gold btn-lg px-4 py-3">
                        <i class="bi bi-geo-alt-fill"></i> Visit Sanctuary
                    </a>
                </div>
                <p class="small text-gold-light mb-0">
                    <i class="bi bi-shield-check me-1"></i> 100% Tax Exemption Eligible under Section 80G | Instant Digital Receipt
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Include Cows AJAX script for filtering -->
<script src="<?= ASSETS_URL; ?>/js/cows.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

