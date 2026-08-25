<?php
/**
 * Kamadenu Goushala Platform - Senior & Differently-Abled Cow Sponsorship
 */

declare(strict_types=1);

$pageTitle = 'Sponsor a Cow – Lifelong Care for Senior & Special-Needs Cows';
$metaDescription = 'Sponsor elderly, blind, and special-needs rescued cows at Kamadenu Goushala. Provide continuous hospice care, warm bedding, and specialized medicine.';

require_once __DIR__ . '/includes/header.php';

// Fetch senior & special-needs resident cows
$seniorCows = Database::fetchAll("
    SELECT c.*, b.name AS breed_name 
    FROM cows c 
    JOIN cow_breeds b ON c.breed_id = b.id 
    WHERE c.health_status IN ('elderly_care', 'recovering', 'under_treatment') AND c.status != 'deceased'
    ORDER BY c.id ASC
");
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-shield-heart me-1"></i> Compassionate Hospice Seva
        </span>
        <h1 class="page-hero-title">Sponsor a Senior or Special-Needs Cow</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Non-milking, elderly, and differently-abled cows often face the greatest risk of neglect. At Kamadenu, we ensure they live in sacred comfort until their natural last breath.
        </p>
    </div>
</section>

<!-- Main Sponsorship Information & Grid -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <div class="row g-5">
            
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="section-tag"><i class="bi bi-heart-fill text-danger"></i> Souls Needing Special Love</span>
                        <h2 class="section-title mb-0">Senior & Hospice Residents</h2>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach ($seniorCows as $cow): 
                        $healthClass = match($cow['health_status']) {
                            'under_treatment' => 'badge-health-treatment',
                            'elderly_care'   => 'badge-health-elderly',
                            default          => 'badge-health-recovering'
                        };
                    ?>
                    <?php
                        $cowImage = image_url($cow['main_image'] ?? null, 'cows', 'placeholder-cow.jpg');
                    ?>
                    <div class="col-md-6">
                        <div class="heritage-card h-100 d-flex flex-column">
                            <div class="position-relative" style="height: 180px; overflow: hidden;">
                                <img
                                    src="<?= e($cowImage); ?>"
                                    alt="<?= e($cow['name']); ?>"
                                    class="w-100 h-100 object-fit-cover d-block"
                                    loading="lazy"
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
                                    <?= e(mb_strimwidth($cow['rescue_story'] ?? $cow['description'] ?? '', 0, 100, '...')); ?>
                                </p>
                                <div class="pt-3 border-top mt-auto">
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL; ?>/donate.php?cow_id=<?= $cow['id']; ?>&purpose=Senior+Sponsorship+for+<?= urlencode($cow['name']); ?>&amount=2501" class="btn btn-gold btn-sm rounded-pill flex-grow-1">
                                            <i class="bi bi-shield-heart me-1"></i> Sponsor (₹ 2,501/mo)
                                        </a>
                                        <?php
                                            $spWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                                            $cleanSpWaPhone = preg_replace('/\D/', '', $spWaPhone);
                                            $spWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                                       "I would like to inquire about sponsoring Senior / Hospice Gau Mata:\n" .
                                                       "🐄 *Name:* " . $cow['name'] . " (" . $cow['cow_code'] . ")\n" .
                                                       "🩺 *Health Status:* " . ucfirst(str_replace('_', ' ', $cow['health_status'])) . "\n\n" .
                                                       "Please share monthly medical & hospice care sponsorship details.";
                                            $spWaUrl = "https://wa.me/" . $cleanSpWaPhone . "?text=" . rawurlencode($spWaMsg);
                                        ?>
                                        <a href="<?= e($spWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm rounded-pill px-2" title="Inquire on WhatsApp about sponsoring <?= e($cow['name']); ?>">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Sponsorship Perks & Details -->
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-patch-check-fill text-gold me-2"></i> How Sponsorship Helps</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 small text-muted mb-0">
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Digestible Warm Mash Diet:</strong>
                                <p class="mb-0">Elderly cows with worn teeth receive boiled grain mash, wheat bran, and mineral liquid supplements.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Cushioned Rubber Matted Sheds:</strong>
                                <p class="mb-0">Joint protection and orthopedic bedding to keep arthritis-prone cattle comfortable during winter and monsoons.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Geriatric Pain Relief & Vet Care:</strong>
                                <p class="mb-0">Anti-inflammatory herbal oils, daily limb massage, and liver tonic administration.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-md">
                    <h4 class="h6 font-serif text-gold mb-2">Instant 80G Tax Benefit</h4>
                    <p class="small text-cream opacity-85 mb-3">
                        All senior sponsorship contributions qualify for a 50% income tax deduction under Section 80G of the Indian Income Tax Act.
                    </p>
                    <a href="<?= BASE_URL; ?>/donate.php?purpose=General+Senior+Cow+Hospice+Fund&amount=5001" class="btn btn-outline-gold btn-sm rounded-pill w-100">
                        Sponsor Full Hospice Shed (₹ 5,001)
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
