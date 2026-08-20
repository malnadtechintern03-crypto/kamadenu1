<?php
/**
 * Kamadenu Goushala Platform - Gau Seva Programs Hub
 */

declare(strict_types=1);

$pageTitle = 'Gau Seva Programs – Auspicious Vedic Seva';
$metaDescription = 'Participate in sacred Gau Seva programs at Kamadenu Goushala: Feed a Cow (Grāsa Dāna), Medical Care, Cow Rescue, Adoption, and Senior Cow Sponsorship. 80G Tax Exempted.';

require_once __DIR__ . '/includes/header.php';

$sevaPrograms = Database::fetchAll("SELECT * FROM seva_programs WHERE is_active = 1 ORDER BY display_order ASC");
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-flower1 me-1"></i> Sarva Deva Mayi Gauḥ (All Divinities Reside in Gomata)
        </span>
        <h1 class="page-hero-title">Sacred Gau Seva Programs</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            In Vedic Sanatana Dharma, serving a mother cow dissolves obstacles and brings immense peace, health, and prosperity to one's entire lineage.
        </p>
    </div>
</section>

<!-- Dedicated Seva Highlights Grid -->
<section class="py-5 bg-cream-soft">
    <div class="container py-4">
        
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-heart-pulse-fill"></i> Sacred Opportunities</span>
            <h2 class="section-title">Choose Your Seva Offering</h2>
            <p class="section-subtitle mx-auto">Every seva initiative directly nurtures, heals, and protects our rescued resident cows.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($sevaPrograms as $prog): ?>
            <div class="col-md-6 col-lg-4">
                <div class="heritage-card h-100 d-flex flex-column">
                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon-wrapper" style="width:56px;height:56px;font-size:1.6rem;">
                                <i class="bi <?= e($prog['icon_class']); ?>"></i>
                            </div>
                            <div>
                                <h3 class="h5 font-serif text-forest-dark mb-0"><?= e($prog['title']); ?></h3>
                                <small class="text-gold-dark fw-bold"><?= format_inr($prog['suggested_amount']); ?> suggested</small>
                            </div>
                        </div>

                        <?php if (!empty($prog['subtitle'])): ?>
                            <p class="text-forest fw-semibold small mb-2"><?= e($prog['subtitle']); ?></p>
                        <?php endif; ?>

                        <p class="small text-muted mb-4 flex-grow-1">
                            <?= e($prog['description']); ?>
                        </p>

                        <div class="d-grid gap-2 pt-3 border-top mt-auto">
                            <a href="<?= BASE_URL; ?>/seva-details.php?slug=<?= e($prog['slug']); ?>" class="btn btn-forest rounded-pill py-2">
                                Seva Details & Giving <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <a href="<?= BASE_URL; ?>/donate.php?seva_id=<?= $prog['id']; ?>&amount=<?= (int)$prog['suggested_amount']; ?>&purpose=<?= urlencode($prog['title']); ?>" class="btn btn-outline-forest btn-sm rounded-pill">
                                Quick Donate <?= format_inr($prog['suggested_amount']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- Auspicious Occasion Giving Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-tag"><i class="bi bi-calendar-event"></i> Auspicious Milestones</span>
                <h2 class="section-title">Perform Seva on Special Days</h2>
                <p class="text-muted">
                    Commemorate your family's most sacred moments by feeding and sponsoring holy cows. We perform special morning Vedic prayers (Sankalpam) in your name and family gotra.
                </p>
                <div class="d-flex flex-column gap-2 mb-4">
                    <div class="d-flex align-items-center gap-2 small text-forest-dark">
                        <i class="bi bi-check-circle-fill text-gold"></i>
                        <span><strong>Birthdays & Anniversaries:</strong> Offer gratitude and seek blessings for longevity and health.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small text-forest-dark">
                        <i class="bi bi-check-circle-fill text-gold"></i>
                        <span><strong>Pitru Paksha & Shraadh:</strong> Perform sacred Godana & Grāsa Dāna in memory of departed ancestors.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 small text-forest-dark">
                        <i class="bi bi-check-circle-fill text-gold"></i>
                        <span><strong>Festivals & Sankranti:</strong> Celebrate Gopashtami, Makar Sankranti, and Diwali with Gomata puja.</span>
                    </div>
                </div>
                <a href="<?= BASE_URL; ?>/feed.php" class="btn btn-gold rounded-pill px-4 py-2">
                    <i class="bi bi-calendar-check me-1"></i> Book Auspicious Day Seva
                </a>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-4 bg-cream border border-warning border-opacity-50">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-stars text-gold me-2"></i> Specialized Gau Seva Links</h3>
                    <div class="list-group list-group-flush bg-transparent">
                        <a href="<?= BASE_URL; ?>/feed.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                            <div>
                                <strong class="text-forest-dark d-block">Feed a Cow (Grāsa Dāna) Calculator</strong>
                                <small class="text-muted">Calculate fresh grass and husk feed for 1 to 50+ cows</small>
                            </div>
                            <i class="bi bi-chevron-right text-gold"></i>
                        </a>
                        <a href="<?= BASE_URL; ?>/adopt.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                            <div>
                                <strong class="text-forest-dark d-block">Adopt a Cow (Monthly Guardian)</strong>
                                <small class="text-muted">Receive digital adoption certificate & monthly photo updates</small>
                            </div>
                            <i class="bi bi-chevron-right text-gold"></i>
                        </a>
                        <a href="<?= BASE_URL; ?>/sponsor.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                            <div>
                                <strong class="text-forest-dark d-block">Senior & Hospice Cow Sponsorship</strong>
                                <small class="text-muted">Lifelong dignity for blind, injured, and elderly cows</small>
                            </div>
                            <i class="bi bi-chevron-right text-gold"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
