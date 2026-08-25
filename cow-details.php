<?php
/**
 * Kamadenu Goushala Platform - Cow Profile Details
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/cows.php');
    exit;
}

$cow = Cow::findBySlug($slug);
if (!$cow) {
    http_response_code(404);
    $pageTitle = 'Cow Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Cow Profile Not Found</h1>
            <p class="text-muted">The cow profile you are looking for may have been updated or does not exist.</p>
            <a href="' . BASE_URL . '/cows.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Cow Directory</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $cow['name'] . ' (' . $cow['cow_code'] . ') – ' . $cow['breed_name'];
$metaDescription = 'Read the heartwarming story of ' . $cow['name'] . ', rescued on ' . format_date($cow['rescue_date']) . '. View medical history, vaccination records, and sponsor or adopt ' . $cow['name'] . '.';

require_once __DIR__ . '/includes/header.php';

$healthClass = match($cow['health_status']) {
    'under_treatment' => 'badge-health-treatment',
    'elderly_care'   => 'badge-health-elderly',
    'recovering'     => 'badge-health-recovering',
    default          => 'badge-health-healthy'
};
$ageFormatted = calculate_cow_age($cow['date_of_birth']);
?>


<?php if (is_logged_in() && has_role(['super_admin', 'admin', 'manager', 'editor'])): ?>
<!-- Admin Quick Bar for Logged-in Admins -->
<div class="bg-forest-dark text-white py-2 border-bottom border-warning border-opacity-25 sticky-top shadow-sm" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 small">
            <span class="badge bg-gold text-forest-dark fw-bold"><i class="bi bi-shield-lock-fill me-1"></i> Admin Panel Access</span>
            <span class="text-cream opacity-90">Viewing Cow: <strong><?= e($cow['name']); ?></strong> (<span class="font-monospace text-gold"><?= e($cow['cow_code']); ?></span>)</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= ADMIN_URL; ?>/cow-edit.php?id=<?= $cow['id']; ?>" class="btn btn-gold btn-sm rounded-pill px-3 fw-bold shadow-xs">
                <i class="bi bi-pencil-square me-1"></i> Edit This Cow in Admin Panel
            </a>
            <a href="<?= ADMIN_URL; ?>/cows.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-grid me-1"></i> Cows Directory
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Cow Profile Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row g-5">
            
            <!-- Left Column: Photos & Essential Badges -->
            <div class="col-lg-6">
                <div class="card p-3 rounded-4 border-0 shadow-sm bg-cream mb-4">
                    <?php
                    $cowMainImage = image_url($cow['main_image'] ?? null, 'cows', 'placeholder-cow.jpg');
                    ?>
                    <div class="position-relative rounded-4 overflow-hidden" style="height: 380px; background-color: var(--color-forest-dark);">
                        <img
                            src="<?= e($cowMainImage); ?>"
                            alt="<?= e($cow['name']); ?>"
                            class="w-100 h-100 object-fit-cover d-block"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';"
                        >
                        <span class="position-absolute top-0 end-0 m-3 badge <?= $healthClass; ?> badge-heritage fs-6">
                            <?= ucfirst(str_replace('_', ' ', $cow['health_status'])); ?>
                        </span>
                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white fs-6">
                            ID: <?= e($cow['cow_code']); ?>
                        </span>
                    </div>

                    <!-- Multi-Image Thumbnails if available -->
                    <?php if (!empty($cow['images'])): ?>
                    <div class="d-flex gap-2 mt-3 overflow-x-auto pb-2">
                        <?php foreach ($cow['images'] as $img): ?>
                        <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 80px; height: 60px; background: var(--color-forest-dark);">
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-gold">
                                <i class="bi bi-image small"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Vitals Information Box -->
                <div class="card p-4 rounded-4 border-0 shadow-xs bg-cream-soft">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-info-circle-fill text-gold me-2"></i> Quick Vitals & Heritage</h3>
                    <div class="row g-3 small">
                        <div class="col-6">
                            <div class="text-muted">Indigenous Breed:</div>
                            <strong class="text-forest-dark fs-6"><?= e($cow['breed_name']); ?></strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Gender / Classification:</div>
                            <strong class="text-forest-dark fs-6"><?= ucfirst(str_replace('_', ' ', $cow['gender'])); ?></strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Approximate Age:</div>
                            <strong class="text-forest-dark fs-6"><?= $ageFormatted; ?></strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Rescue Date:</div>
                            <strong class="text-forest-dark fs-6"><?= format_date($cow['rescue_date']); ?></strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Adoption Status:</div>
                            <strong class="<?= $cow['is_adopted'] ? 'text-success' : 'text-gold-dark'; ?> fs-6">
                                <?= $cow['is_adopted'] ? '<i class="bi bi-check-circle-fill me-1"></i> Active Guardian Assigned' : '<i class="bi bi-heart-fill me-1"></i> Awaiting Guardian'; ?>
                            </strong>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Sanctuary Location:</div>
                            <strong class="text-forest-dark fs-6">Nandi Foothills Goushala</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Biography & Adoption Action Box -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-gold-subtle text-gold-dark px-3 py-1 rounded-pill fw-bold">
                            <i class="bi bi-flower1 me-1"></i> <?= e($cow['breed_name']); ?> Cow
                        </span>
                        <?php if ($cow['is_featured']): ?>
                            <span class="badge bg-forest text-white px-2 py-1 rounded-pill small"><i class="bi bi-star-fill me-1"></i> Featured Resident</span>
                        <?php endif; ?>
                    </div>
                    <?php if (is_logged_in() && has_role(['super_admin', 'admin', 'manager', 'editor'])): ?>
                    <a href="<?= ADMIN_URL; ?>/cow-edit.php?id=<?= $cow['id']; ?>" class="btn btn-outline-forest btn-sm rounded-pill px-3 fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> Edit Cow (Admin)
                    </a>
                    <?php endif; ?>
                </div>

                <h1 class="display-5 font-serif text-forest-dark fw-bold mb-3"><?= e($cow['name']); ?></h1>

                <div class="lead text-muted mb-4">
                    <?= e($cow['description'] ?? 'A serene and gentle soul residing happily at Kamadenu Goushala.'); ?>
                </div>

                <!-- Rescue Journey Narrative -->
                <div class="p-4 rounded-4 bg-cream border border-warning border-opacity-50 mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-2"><i class="bi bi-suit-heart-fill text-gold me-2"></i> Rescue & Transformation Story</h3>
                    <p class="small text-muted mb-0 lh-lg">
                        <?= nl2br(e($cow['rescue_story'] ?? 'Found in distress and brought to our sanctuary by our emergency rescue team. Fully rehabilitated with continuous Vedic veterinary love.')); ?>
                    </p>
                </div>

                <!-- Support & Adoption Call-to-Action Card -->
                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-lg mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 font-serif text-cream mb-0"><i class="bi bi-heart-pulse-fill text-gold me-2"></i> Support or Adopt <?= e($cow['name']); ?></h3>
                        <span class="badge bg-gold text-forest-dark fw-bold">80G Tax Deductible</span>
                    </div>
                    <p class="small text-cream opacity-75 mb-4">
                        Your sponsorship provides daily fresh green fodder, mineral mash, regular vaccinations, and lifelong shelter.
                    </p>

                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL; ?>/adopt.php?cow_id=<?= $cow['id']; ?>" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold">
                            <i class="bi bi-suit-heart-fill me-2"></i> Adopt <?= e($cow['name']); ?> (Monthly Guardian)
                        </a>
                        <div class="row g-2 pt-1">
                            <div class="col-6">
                                <a href="<?= BASE_URL; ?>/donate.php?cow_id=<?= $cow['id']; ?>&purpose=Feed+<?= urlencode($cow['name']); ?>&amount=501" class="btn btn-outline-gold btn-sm w-100 rounded-pill py-2">
                                    <i class="bi bi-flower1 me-1"></i> Feed for 1 Day (₹501)
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= BASE_URL; ?>/donate.php?cow_id=<?= $cow['id']; ?>&purpose=Medical+Kit+for+<?= urlencode($cow['name']); ?>&amount=1001" class="btn btn-outline-gold btn-sm w-100 rounded-pill py-2">
                                    <i class="bi bi-bandaid me-1"></i> Medical Kit (₹1,001)
                                </a>
                            </div>
                        </div>

                        <!-- Direct WhatsApp Seva Inquiry -->
                        <?php
                            $cowWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                            $cleanCowWaPhone = preg_replace('/\D/', '', $cowWaPhone);
                            $cowProfileUrl = BASE_URL . '/cow-details.php?slug=' . urlencode($cow['slug']);
                            $cowWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                        "I would like to inquire about adopting / sponsoring:\n" .
                                        "🐄 *Gau Mata:* " . $cow['name'] . " (ID: " . $cow['cow_code'] . ")\n" .
                                        "🏷️ *Breed:* " . $cow['breed_name'] . " (" . ucfirst($cow['gender']) . ")\n" .
                                        "🔗 *Profile Link:* " . $cowProfileUrl . "\n\n" .
                                        "Please share adoption procedure and visit timings.";
                            $cowWaUrl = "https://wa.me/" . $cleanCowWaPhone . "?text=" . rawurlencode($cowWaMsg);
                        ?>
                        <a href="<?= e($cowWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 mt-2">
                            <i class="bi bi-whatsapp fs-6"></i> Inquire / Adopt via WhatsApp
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Medical History & Vaccinations System Tabs -->
<section class="py-5 bg-cream-soft border-top">
    <div class="container py-3">
        <div class="row">
            <div class="col-lg-12">
                <div class="text-center mb-4">
                    <span class="section-tag justify-content-center"><i class="bi bi-file-earmark-medical"></i> Veterinary Ledger</span>
                    <h2 class="section-title">Comprehensive Health & Veterinary Records</h2>
                    <p class="section-subtitle mx-auto">Full transparency on medical treatments, regular vaccinations, and daily caretaker observations.</p>
                </div>

                <!-- Nav Tabs -->
                <ul class="nav nav-pills justify-content-center mb-4 gap-2" id="medicalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill px-4" id="med-records-tab" data-bs-toggle="pill" data-bs-target="#med-records" type="button" role="tab" aria-controls="med-records" aria-selected="true">
                            <i class="bi bi-heart-pulse me-2"></i> Clinical & Medical History (<?= count($cow['medical_records']); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4" id="vaccines-tab" data-bs-toggle="pill" data-bs-target="#vaccines" type="button" role="tab" aria-controls="vaccines" aria-selected="false">
                            <i class="bi bi-capsule me-2"></i> Vaccinations (<?= count($cow['vaccinations']); ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill px-4" id="notes-tab" data-bs-toggle="pill" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="false">
                            <i class="bi bi-journal-text me-2"></i> Caretaker Logs (<?= count($cow['notes']); ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="medicalTabsContent">
                    
                    <!-- 1. Clinical Records -->
                    <div class="tab-pane fade show active" id="med-records" role="tabpanel" aria-labelledby="med-records-tab">
                        <?php if (empty($cow['medical_records'])): ?>
                            <div class="card p-4 rounded-4 text-center border-0 shadow-sm bg-white">
                                <p class="text-muted mb-0"><i class="bi bi-shield-check text-success me-2"></i> No active medical ailments recorded. Patient is in optimal vitality.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($cow['medical_records'] as $record): ?>
                                <div class="col-md-6">
                                    <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-gold-subtle text-gold-dark fw-bold"><?= format_date($record['visit_date']); ?></span>
                                            <small class="text-muted"><i class="bi bi-person-badge me-1"></i> <?= e($record['doctor']); ?></small>
                                        </div>
                                        <h3 class="h6 font-serif text-forest-dark mb-2">Diagnosis: <?= e($record['diagnosis']); ?></h3>
                                        <div class="small text-muted mb-2">
                                            <strong>Treatment:</strong> <?= e($record['treatment']); ?>
                                        </div>
                                        <?php if (!empty($record['medicine'])): ?>
                                        <div class="small text-muted mb-2">
                                            <strong>Prescribed Medicine:</strong> <span class="badge bg-cream text-forest-dark border"><?= e($record['medicine']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($record['notes'])): ?>
                                        <p class="small text-muted fst-italic mb-0 pt-2 border-top">
                                            "<?= e($record['notes']); ?>"
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Vaccinations -->
                    <div class="tab-pane fade" id="vaccines" role="tabpanel" aria-labelledby="vaccines-tab">
                        <?php if (empty($cow['vaccinations'])): ?>
                            <div class="card p-4 rounded-4 text-center border-0 shadow-sm bg-white">
                                <p class="text-muted mb-0">Vaccination schedule is maintained at the sanctuary clinic.</p>
                            </div>
                        <?php else: ?>
                            <div class="card rounded-4 border-0 shadow-sm bg-white overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-forest-dark text-white">
                                            <tr>
                                                <th>Vaccine Name</th>
                                                <th>Date Administered</th>
                                                <th>Next Due Date</th>
                                                <th>Batch No.</th>
                                                <th>Administered By</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cow['vaccinations'] as $vac): ?>
                                            <tr>
                                                <td class="fw-bold text-forest-dark"><?= e($vac['vaccine_name']); ?></td>
                                                <td><?= format_date($vac['vaccination_date']); ?></td>
                                                <td><span class="badge bg-warning text-dark"><?= format_date($vac['next_due_date']); ?></span></td>
                                                <td><code><?= e($vac['batch_number'] ?? 'N/A'); ?></code></td>
                                                <td><?= e($vac['administered_by'] ?? 'Sanctuary Vet'); ?></td>
                                                <td class="small text-muted"><?= e($vac['notes'] ?? 'Routine schedule'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Caretaker Logs -->
                    <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                        <?php if (empty($cow['notes'])): ?>
                            <div class="card p-4 rounded-4 text-center border-0 shadow-sm bg-white">
                                <p class="text-muted mb-0">Daily routine observations are active with no abnormal behavioral alerts.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($cow['notes'] as $n): ?>
                                <div class="col-md-6">
                                    <div class="card p-3 rounded-3 bg-white border shadow-xs">
                                        <div class="d-flex justify-content-between small text-muted mb-2">
                                            <span><i class="bi bi-calendar3 me-1"></i> <?= format_date($n['note_date']); ?></span>
                                            <span><i class="bi bi-person me-1"></i> <?= e($n['recorded_by_name'] ?? 'Caretaker'); ?></span>
                                        </div>
                                        <p class="small text-forest-dark mb-0"><?= nl2br(e($n['content'])); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>

<!-- Related Cows Carousel / Grid -->
<?php if (!empty($cow['related_cows'])): ?>
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 font-serif text-forest-dark mb-0">Other Blessed <?= e($cow['breed_name']); ?> Cows</h2>
            <a href="<?= BASE_URL; ?>/cows.php?breed=<?= e($cow['breed_slug']); ?>" class="small text-forest fw-bold">
                View All <?= e($cow['breed_name']); ?> <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($cow['related_cows'] as $rel): ?>
            <div class="col-md-6 col-lg-3">
                <div class="heritage-card h-100 d-flex flex-column">
                    <div class="position-relative overflow-hidden" style="height: 180px; background-color: var(--color-forest-dark);">
                        <?php $relImage = image_url($rel['main_image'] ?? null, 'cows', 'placeholder-cow.jpg'); ?>
                        <img 
                            src="<?= e($relImage); ?>" 
                            alt="<?= e($rel['name']); ?>" 
                            class="w-100 h-100 object-fit-cover d-block"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';"
                        >
                        <span class="position-absolute bottom-0 start-0 m-2 badge bg-black bg-opacity-75 text-white small">
                            <?= e($rel['cow_code']); ?>
                        </span>
                    </div>
                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <h3 class="h6 font-serif text-forest-dark mb-1"><?= e($rel['name']); ?></h3>
                        <p class="small text-muted mb-3 flex-grow-1">
                            <?= e(mb_strimwidth($rel['rescue_story'] ?? $rel['description'] ?? '', 0, 80, '...')); ?>
                        </p>
                        <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($rel['slug']); ?>" class="btn btn-outline-forest btn-sm rounded-pill w-100 mt-auto">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
