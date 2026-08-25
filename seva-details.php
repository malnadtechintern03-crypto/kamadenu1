<?php
/**
 * Kamadenu Goushala Platform - Individual Seva Program Details
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/seva.php');
    exit;
}

$program = Database::fetchOne("SELECT * FROM seva_programs WHERE slug = ?", [$slug]);
if (!$program) {
    http_response_code(404);
    $pageTitle = 'Seva Program Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Seva Program Not Found</h1>
            <p class="text-muted">The seva program you are looking for does not exist in our catalog.</p>
            <a href="' . BASE_URL . '/seva.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Seva Programs</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $program['title'] . ' – Sacred Gau Seva';
$metaDescription = $program['subtitle'] ?? ('Support ' . $program['title'] . ' at Kamadenu Goushala with 80G tax-exempt contributions.');

require_once __DIR__ . '/includes/header.php';
?>


<!-- Main Seva Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row g-5 align-items-start">
            
            <!-- Left Column: Seva Description & Impact -->
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon-wrapper" style="width:60px;height:60px;font-size:1.8rem;">
                        <i class="bi <?= e($program['icon_class']); ?>"></i>
                    </div>
                    <div>
                        <span class="badge bg-gold-subtle text-gold-dark fw-bold small">Auspicious Vedic Seva</span>
                        <h1 class="h2 font-serif text-forest-dark mb-0"><?= e($program['title']); ?></h1>
                    </div>
                </div>

                <?php if (!empty($program['subtitle'])): ?>
                    <p class="fs-5 text-forest fw-semibold mb-4"><?= e($program['subtitle']); ?></p>
                <?php endif; ?>

                <div class="lead text-muted mb-4">
                    <?= nl2br(e($program['description'])); ?>
                </div>

                <!-- Impact Highlights -->
                <div class="card p-4 rounded-4 bg-cream border border-warning border-opacity-50 mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-patch-check-fill text-gold me-2"></i> How Your Contribution Transforms Lives</h3>
                    <ul class="small text-muted d-flex flex-column gap-2 mb-0">
                        <li><i class="bi bi-check-circle-fill text-forest me-2"></i><strong>100% Direct Allocation:</strong> Funds are exclusively utilized for fodder, medical kits, ambulance fuel, or shed maintenance.</li>
                        <li><i class="bi bi-check-circle-fill text-forest me-2"></i><strong>Instant 80G Tax Exemption:</strong> Official tax deduction certificate delivered immediately via email & download.</li>
                        <li><i class="bi bi-check-circle-fill text-forest me-2"></i><strong>Morning Prayer Sankalpam:</strong> Special Vedic prayers performed in your family's name.</li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Quick Participation Form -->
            <div class="col-lg-5">
                <div class="card p-4 rounded-4 bg-cream-soft border-0 shadow-md sticky-top" style="top: 100px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 font-serif text-forest-dark mb-0"><i class="bi bi-heart-fill text-gold me-2"></i> Offer This Seva</h3>
                        <span class="badge bg-forest text-white">80G Benefit</span>
                    </div>

                    <form method="GET" action="<?= BASE_URL; ?>/donate.php">
                        <input type="hidden" name="seva_id" value="<?= $program['id']; ?>">
                        <input type="hidden" name="purpose" value="<?= e($program['title']); ?>">

                        <label class="form-label small fw-bold text-forest-dark mb-2">Select Contribution Tier</label>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                    <input type="radio" name="amount" value="<?= (int)$program['suggested_amount']; ?>" checked class="d-none">
                                    <span class="fw-bold fs-6"><?= format_inr($program['suggested_amount']); ?></span>
                                    <small class="d-block text-muted">Standard Seva</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                    <input type="radio" name="amount" value="<?= (int)($program['suggested_amount'] * 2); ?>" class="d-none">
                                    <span class="fw-bold fs-6"><?= format_inr($program['suggested_amount'] * 2); ?></span>
                                    <small class="d-block text-muted">Double Seva</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                    <input type="radio" name="amount" value="5001" class="d-none">
                                    <span class="fw-bold fs-6">₹ 5,001</span>
                                    <small class="d-block text-muted">Maha Seva</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                    <input type="radio" name="amount" value="10001" class="d-none">
                                    <span class="fw-bold fs-6">₹ 10,001</span>
                                    <small class="d-block text-muted">Sanctuary Patron</small>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold">
                                Proceed to Seva Offering <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>

                        <?php
                            $sevaWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                            $cleanSevaWaPhone = preg_replace('/\D/', '', $sevaWaPhone);
                            $sevaWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                         "I would like to offer / inquire about the *" . $program['title'] . "* Seva Program.\n" .
                                         "✨ *Suggested Offering:* " . format_inr($program['suggested_amount']) . "\n\n" .
                                         "Please share family prayer sankalpam & booking details.";
                            $sevaWaUrl = "https://wa.me/" . $cleanSevaWaPhone . "?text=" . rawurlencode($sevaWaMsg);
                        ?>
                        <div class="d-grid mt-2">
                            <a href="<?= e($sevaWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm rounded-pill py-2 fw-semibold">
                                <i class="bi bi-whatsapp me-1"></i> Book via WhatsApp
                            </a>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-shield-check text-forest me-1"></i> Safe & Encrypted 80G Certified Portal
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
