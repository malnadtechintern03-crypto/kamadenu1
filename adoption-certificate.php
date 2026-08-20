<?php
/**
 * Kamadenu Goushala Platform - Digital Adoption Certificate View & Print
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$certNumber = sanitize_input($_GET['cert'] ?? '');
if (empty($certNumber)) {
    header('Location: ' . BASE_URL . '/adopt.php');
    exit;
}

$adoption = Adoption::findByCertificate($certNumber);
if (!$adoption) {
    http_response_code(404);
    $pageTitle = 'Certificate Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-award fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Certificate Not Found</h1>
            <p class="text-muted">The requested adoption certificate could not be verified in our records.</p>
            <a href="' . BASE_URL . '/cows.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Cows Directory</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Adoption Certificate: ' . $adoption['cow_name'] . ' – ' . $adoption['certificate_number'];
$metaDescription = 'Official Certificate of Sacred Cow Adoption for ' . $adoption['cow_name'] . ' issued by Kamadenu Goushala Charitable Trust.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-soft py-5">
    <div class="container text-center mb-4 d-print-none">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success text-white mb-2 shadow-xs">
            <i class="bi bi-check-circle-fill"></i>
            <span class="small fw-bold">Adoption Successfully Registered</span>
        </div>
        <h1 class="h3 font-serif text-forest-dark">Official Certificate of Sacred Adoption</h1>
        <p class="text-muted small">You may print or save this digital certificate for your devotional archives.</p>
        <button class="btn btn-gold rounded-pill px-4 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print / Save Certificate PDF
        </button>
    </div>

    <!-- Certificate Render Frame -->
    <div class="container">
        <div class="card p-4 p-md-5 rounded-4 shadow-lg border-0 bg-white mx-auto position-relative" style="max-width: 860px; border: 8px double var(--color-gold) !important;">
            
            <!-- Certificate Watermark / Header -->
            <div class="text-center mb-4">
                <div class="navbar-brand-logo mx-auto mb-2" style="width:64px;height:64px;font-size:2rem;">
                    <i class="bi bi-flower1"></i>
                </div>
                <h4 class="text-gold-dark text-uppercase tracking-wider small fw-bold mb-1">Kamadenu Goushala Charitable Trust</h4>
                <h2 class="font-serif display-6 text-forest-dark fw-bold mb-0">Certificate of Sacred Adoption</h2>
                <span class="text-muted fst-italic small">Gau Samrakshana Patram &bull; Section 80G Registered</span>
            </div>

            <hr class="border-warning opacity-50 my-3">

            <!-- Certificate Body Content -->
            <div class="text-center py-3">
                <p class="text-muted mb-2">This is to gratefully certify that</p>
                <h3 class="display-6 font-serif text-forest fw-bold mb-3"><?= e($adoption['adopter_name']); ?></h3>
                
                <p class="lead text-muted mx-auto max-w-650 mb-4 fs-6">
                    has affectionately assumed the sacred guardianship of our beloved rescued indigenous cow
                </p>

                <!-- Adopted Cow Card -->
                <div class="p-3 rounded-4 bg-cream border border-warning border-opacity-50 d-inline-flex align-items-center gap-4 text-start mx-auto mb-4" style="max-width: 500px;">
                    <div class="rounded-3 bg-forest-dark text-gold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <div>
                        <h4 class="font-serif text-forest-dark mb-0 fs-4"><?= e($adoption['cow_name']); ?></h4>
                        <div class="small text-gold-dark fw-bold"><?= e($adoption['breed_name']); ?> Breed &bull; ID: <?= e($adoption['cow_code']); ?></div>
                        <small class="text-muted">Status: <?= ucfirst($adoption['health_status']); ?> &bull; Nandi Hills Sanctuary</small>
                    </div>
                </div>

                <p class="small text-muted mb-4">
                    Adoption Duration: <strong><?= $adoption['duration_months']; ?> <?= $adoption['duration_months'] === 1 ? 'Month' : 'Months'; ?></strong> 
                    (Valid from <strong><?= format_date($adoption['start_date']); ?></strong> to <strong><?= format_date($adoption['end_date']); ?></strong>)
                </p>

                <p class="small text-muted fst-italic">
                    "May the divine blessings of Mother Surabhi bring eternal peace, health, and prosperity to you and your noble family."
                </p>
            </div>

            <hr class="border-warning opacity-50 my-3">

            <!-- Certificate Footer Signatures -->
            <div class="d-flex justify-content-between align-items-end pt-3 text-center">
                <div>
                    <div class="font-serif text-forest-dark fw-bold">Mahant Shri Radheyshyam</div>
                    <small class="text-muted d-block">Spiritual Founder & Trustee</small>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-gold-subtle text-gold-dark border border-warning px-3 py-2 rounded-pill small">
                        <i class="bi bi-patch-check-fill me-1"></i> Verified Authentic
                    </span>
                    <div class="font-monospace small text-muted mt-1"><?= e($adoption['certificate_number']); ?></div>
                </div>
                <div>
                    <div class="font-serif text-forest-dark fw-bold">Dr. H. V. Narayana (MVSc)</div>
                    <small class="text-muted d-block">Chief Veterinary Officer</small>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
