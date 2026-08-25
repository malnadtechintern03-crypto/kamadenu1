<?php
/**
 * Kamadenu Goushala Platform - Admin Add/Edit Cow with Secure Image Upload
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager']);

$currentUser = get_logged_in_user();
$cowId = (int)($_GET['id'] ?? 0);
$isEditing = $cowId > 0;
$pageTitle = $isEditing ? 'Edit Cow Record' : 'Register New Rescued Cow';

$cow = null;
if ($isEditing) {
    $cow = Database::fetchOne("SELECT * FROM cows WHERE id = ?", [$cowId]);
    if (!$cow) {
        set_flash('error', 'Cow record not found.');
        header('Location: ' . BASE_URL . '/admin/cows.php');
        exit;
    }
}

$breeds = Breed::getAllWithCount();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $code = sanitize_input($_POST['cow_code'] ?? '');
    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($name);
    $breedId = (int)($_POST['breed_id'] ?? 0);
    $gender = sanitize_input($_POST['gender'] ?? 'female');
    $dob = sanitize_input($_POST['date_of_birth'] ?? '') ?: null;
    $rescueDate = sanitize_input($_POST['rescue_date'] ?? date('Y-m-d'));
    $healthStatus = sanitize_input($_POST['health_status'] ?? 'healthy');
    $status = sanitize_input($_POST['status'] ?? 'active');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $rescueStory = sanitize_input($_POST['rescue_story'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    if (empty($code)) $errors[] = 'Cow Identification Code is required.';
    if (empty($name)) $errors[] = 'Cow Name is required.';
    if ($breedId <= 0) $errors[] = 'Please select a valid breed.';

    // Process Image Upload if provided
    $uploadedFilename = null;
    if (!empty($_FILES['main_image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['main_image'], 'cows');
        } catch (Exception $e) {
            $errors[] = 'Image upload failed: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            if ($isEditing) {
                $finalImage = $uploadedFilename ?: ($cow['main_image'] ?? null);

                $sql = "
                    UPDATE cows SET 
                        cow_code = ?, name = ?, slug = ?, breed_id = ?, gender = ?,
                        date_of_birth = ?, rescue_date = ?, health_status = ?, status = ?,
                        is_featured = ?, rescue_story = ?, description = ?, main_image = ?, updated_at = NOW()
                    WHERE id = ?
                ";
                Database::execute($sql, [
                    $code, $name, $slug, $breedId, $gender, $dob, $rescueDate,
                    $healthStatus, $status, $isFeatured, $rescueStory, $description, $finalImage, $cowId
                ]);
                log_activity((int)$currentUser['id'], 'update_cow', 'cows', $cowId, "Updated cow details: {$name} ({$code})");
                set_flash('success', "Cow '{$name}' updated successfully.");
            } else {
                $finalImage = $uploadedFilename ?: 'placeholder-cow.jpg';

                $sql = "
                    INSERT INTO cows (
                        cow_code, name, slug, breed_id, gender, date_of_birth,
                        rescue_date, health_status, status, is_featured, rescue_story,
                        description, main_image, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ";
                $newId = Database::insert($sql, [
                    $code, $name, $slug, $breedId, $gender, $dob,
                    $rescueDate, $healthStatus, $status, $isFeatured, $rescueStory, $description, $finalImage
                ]);
                log_activity((int)$currentUser['id'], 'create_cow', 'cows', (int)$newId, "Registered new cow: {$name} ({$code})");
                set_flash('success', "Cow '{$name}' successfully registered with Code {$code}.");
            }
            header('Location: ' . BASE_URL . '/admin/cows.php');
            exit;
        } catch (Throwable $t) {
            error_log('Cow save error: ' . $t->getMessage());
            $errors[] = 'Failed to save cow details. Please verify that Identification Code and Slug are unique.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card p-4 p-md-5 rounded-4 border-0 shadow-sm bg-white mb-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 font-serif text-forest-dark mb-0"><?= $isEditing ? 'Update Cow Details' : 'Register New Cow'; ?></h2>
                    <small class="text-muted">Manage sanctuary records, pedigree lineage, and veterinary photography.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($isEditing): 
                        $cowShareUrl = BASE_URL . '/cow-details.php?slug=' . urlencode($cow['slug']);
                        $breedName = 'Indigenous';
                        foreach ($breeds as $b) {
                            if ($b['id'] == $cow['breed_id']) {
                                $breedName = $b['name'];
                                break;
                            }
                        }
                        $cowAge = calculate_cow_age($cow['date_of_birth']);
                        $cowHealth = ucfirst(str_replace('_', ' ', $cow['health_status']));
                        $cowStatusText = ($cow['status'] === 'adopted') ? 'Currently Adopted by Devotee' : 'Available for Monthly Adoption (₹ 3,000/mo)';
                        $sitePhone = get_setting('site_phone', '+91 98450 12345');

                        $defaultMsg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" .
                                      "Meet our sacred resident cow:\n" .
                                      "🐮 *Name:* " . $cow['name'] . " (" . $cow['cow_code'] . ")\n" .
                                      "🌾 *Breed:* " . $breedName . " (" . ucfirst($cow['gender']) . ")\n" .
                                      "⏳ *Age:* " . $cowAge . "\n" .
                                      "🏥 *Health Status:* " . $cowHealth . "\n" .
                                      "❤️ *Adoption Status:* " . $cowStatusText . "\n\n" .
                                      "✨ *About Her:* " . mb_strimwidth($cow['description'] ?: ($cow['rescue_story'] ?: 'Nurtured with loving care at our Nandi Hills sanctuary.'), 0, 140, '...') . "\n\n" .
                                      "🔗 *View Sacred Profile & Adopt:* " . $cowShareUrl . "\n\n" .
                                      "📞 *Helpline / Seva Desk:* " . $sitePhone . "\n" .
                                      "🙏 *Jai Gau Mata!*";
                    ?>
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#whatsappCowModal" title="WhatsApp Message Editor & Share">
                        <i class="bi bi-whatsapp me-1"></i> WhatsApp Message
                    </button>
                    <a href="<?= $cowShareUrl; ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="View Public Live Profile">
                        <i class="bi bi-eye me-1"></i> View Live
                    </a>
                    <?php if (has_role(['super_admin', 'admin', 'manager'])): ?>
                    <form method="POST" action="<?= BASE_URL; ?>/admin/cows.php" onsubmit="return confirm('Are you sure you want to permanently delete cow record \'<?= e(addslashes($cow['name'])); ?>\' (<?= e($cow['cow_code']); ?>)? This action cannot be undone.');" class="d-inline">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="delete_cow">
                        <input type="hidden" name="id" value="<?= $cow['id']; ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Delete Cow">
                            <i class="bi bi-trash me-1"></i> Delete Cow
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?= BASE_URL; ?>/admin/cows.php" class="btn btn-outline-forest btn-sm rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-3 mb-4">
                    <ul class="mb-0 small">
                        <?php foreach ($errors as $e): ?>
                            <li><?= e($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrf_field(); ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Identification Code *</label>
                        <input type="text" name="cow_code" class="form-control font-monospace" placeholder="KG-2024-09" required value="<?= e($_POST['cow_code'] ?? $cow['cow_code'] ?? 'KG-' . date('Y') . '-' . rand(10, 99)); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Cow Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Radharani" required value="<?= e($_POST['name'] ?? $cow['name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">URL Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="radharani" value="<?= e($_POST['slug'] ?? $cow['slug'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Indigenous Breed *</label>
                        <select name="breed_id" class="form-select" required>
                            <option value="">-- Select Breed --</option>
                            <?php foreach ($breeds as $b): ?>
                                <option value="<?= $b['id']; ?>" <?= (($_POST['breed_id'] ?? $cow['breed_id'] ?? 0) == $b['id']) ? 'selected' : ''; ?>><?= e($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Gender / Category</label>
                        <select name="gender" class="form-select">
                            <option value="female" <?= (($_POST['gender'] ?? $cow['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Mother Cow (Female)</option>
                            <option value="male" <?= (($_POST['gender'] ?? $cow['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Sacred Bull / Nandi (Male)</option>
                            <option value="calf_female" <?= (($_POST['gender'] ?? $cow['gender'] ?? '') === 'calf_female') ? 'selected' : ''; ?>>Female Calf (Young)</option>
                            <option value="calf_male" <?= (($_POST['gender'] ?? $cow['gender'] ?? '') === 'calf_male') ? 'selected' : ''; ?>>Male Calf (Young)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Date of Birth (Estimated)</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= e($_POST['date_of_birth'] ?? $cow['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Rescue Date</label>
                        <input type="date" name="rescue_date" class="form-control" value="<?= e($_POST['rescue_date'] ?? $cow['rescue_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Health Status</label>
                        <select name="health_status" class="form-select">
                            <option value="healthy" <?= (($_POST['health_status'] ?? $cow['health_status'] ?? '') === 'healthy') ? 'selected' : ''; ?>>Healthy & Active</option>
                            <option value="recovering" <?= (($_POST['health_status'] ?? $cow['health_status'] ?? '') === 'recovering') ? 'selected' : ''; ?>>Recovering from Injury</option>
                            <option value="under_treatment" <?= (($_POST['health_status'] ?? $cow['health_status'] ?? '') === 'under_treatment') ? 'selected' : ''; ?>>Under Active Medical Treatment</option>
                            <option value="elderly_care" <?= (($_POST['health_status'] ?? $cow['health_status'] ?? '') === 'elderly_care') ? 'selected' : ''; ?>>Elderly / Hospice Care</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-forest-dark">Resident Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= (($_POST['status'] ?? $cow['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active Sanctuary Resident</option>
                            <option value="adopted" <?= (($_POST['status'] ?? $cow['status'] ?? '') === 'adopted') ? 'selected' : ''; ?>>Currently Adopted</option>
                            <option value="transferred" <?= (($_POST['status'] ?? $cow['status'] ?? '') === 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                            <option value="deceased" <?= (($_POST['status'] ?? $cow['status'] ?? '') === 'deceased') ? 'selected' : ''; ?>>Deceased (Passed in Peace)</option>
                        </select>
                    </div>

                    <!-- Cow Main Photograph Upload & Preview -->
                    <div class="col-12">
                        <div class="p-3 rounded-4 bg-cream-soft border border-warning border-opacity-50">
                            <label class="form-label small fw-bold text-forest-dark d-block">
                                <i class="bi bi-camera-fill text-gold me-1"></i> Main Cow Photograph (JPG, PNG, WEBP max 5MB)
                            </label>
                            <div class="row align-items-center g-3">
                                <?php if ($isEditing && !empty($cow['main_image'])): 
                                    $currentImg = image_url($cow['main_image'], 'cows', 'placeholder-cow.jpg');
                                ?>
                                <div class="col-auto">
                                    <div class="rounded-3 border overflow-hidden shadow-xs" style="width: 120px; height: 90px; background: var(--color-forest-dark);">
                                        <img src="<?= e($currentImg); ?>" alt="<?= e($cow['name']); ?>" class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <small class="d-block text-muted text-center mt-1">Current Photo</small>
                                </div>
                                <?php endif; ?>
                                <div class="col">
                                    <input type="file" name="main_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">Choose a new file to replace or assign the primary photograph.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedCheck" <?= (($_POST['is_featured'] ?? $cow['is_featured'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label small fw-bold text-forest-dark" for="isFeaturedCheck">Feature on Homepage</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-forest-dark">Rescue & Rehabilitation Story</label>
                        <textarea name="rescue_story" class="form-control" rows="3" placeholder="Where was the cow rescued from? What injuries or condition was observed?"><?= e($_POST['rescue_story'] ?? $cow['rescue_story'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-forest-dark">General Description & Caretaker Observations</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Temperament, favorite fodder, grazing companions..."><?= e($_POST['description'] ?? $cow['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL; ?>/admin/cows.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-gold rounded-pill px-5 shadow-gold">
                        <i class="bi bi-save me-1"></i> <?= $isEditing ? 'Save Changes' : 'Register Cow'; ?>
                    </button>
                </div>
            </form>

            <?php if ($isEditing): ?>
            <!-- Embedded WhatsApp Seva Outreach Slab on Cow Edit Page -->
            <div class="p-4 rounded-4 bg-cream-soft border mt-5">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                    <h3 class="h6 font-serif text-forest-dark mb-0">
                        <i class="bi bi-whatsapp text-success me-2 fs-5"></i> WhatsApp Devotee Outreach & Custom Message
                    </h3>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 bg-white" onclick="copyWaText()">
                            <i class="bi bi-clipboard me-1"></i> Copy Message
                        </button>
                        <a id="waSendBtnPage" href="https://api.whatsapp.com/send?text=<?= rawurlencode($defaultMsg); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm rounded-pill px-3 shadow-xs fw-bold">
                            <i class="bi bi-whatsapp me-1"></i> Open in WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Recipient & Preset Controls -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-forest-dark">Recipient WhatsApp Phone (Optional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-success"><i class="bi bi-person-fill"></i></span>
                            <input type="text" id="waRecipientPhone" class="form-control font-monospace" placeholder="e.g. 919845012345 (Leave blank to choose chat)" oninput="updateWaLink()">
                        </div>
                        <small class="text-muted extra-small">Country code without + (e.g. 919845012345).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-forest-dark">Message Template Presets</label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('profile')">General Profile</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('adopt')">Adoption Appeal</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('medical')">Medical Seva</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('blessing')">Birthday Seva</button>
                        </div>
                    </div>
                </div>

                <!-- Editable Message Textarea -->
                <div class="mb-2">
                    <label class="form-label small fw-bold text-forest-dark">Live Customizable Message Body</label>
                    <textarea id="waMessageText" class="form-control font-monospace small bg-white" rows="8" oninput="updateWaLink()"><?= e($defaultMsg); ?></textarea>
                    <small class="text-muted extra-small">You can edit the message above and it will sync directly with the WhatsApp send link.</small>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php if ($isEditing): ?>
<!-- Modal for WhatsApp Message Editor -->
<div class="modal fade" id="whatsappCowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif">
                    <i class="bi bi-whatsapp text-success me-2 fs-5"></i> WhatsApp Message Editor: <?= e($cow['name']); ?> (<?= e($cow['cow_code']); ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Cow Info Preview Strip -->
                <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border mb-3">
                    <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 55px; height: 55px; background: var(--color-forest-dark);">
                        <img src="<?= e(image_url($cow['main_image'], 'cows', 'placeholder-cow.jpg')); ?>" alt="<?= e($cow['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="font-serif text-forest-dark mb-0 fw-bold"><?= e($cow['name']); ?> (<?= e($cow['cow_code']); ?>)</h6>
                                <small class="text-muted"><?= e($breedName); ?> &bull; <?= $cowAge; ?> &bull; <?= ucfirst($cow['gender']); ?></small>
                            </div>
                            <div>
                                <span class="badge bg-gold text-forest-dark fw-bold"><?= ucfirst($cow['status']); ?></span>
                                <span class="badge bg-success-subtle text-success border ms-1"><?= $cowHealth; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recipient & Preset Controls -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-forest-dark">Recipient WhatsApp Number (Optional)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                            <input type="text" id="waRecipientPhoneModal" class="form-control font-monospace" placeholder="e.g. 919845012345 (Optional)" oninput="updateWaLink()">
                        </div>
                        <small class="text-muted extra-small">Leave empty to choose recipient/group inside WhatsApp.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-forest-dark">Message Templates / Presets</label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('profile')">General Profile</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('adopt')">Adoption Appeal</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('medical')">Medical Seva</button>
                            <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset('blessing')">Birthday Seva</button>
                        </div>
                    </div>
                </div>

                <!-- Editable WhatsApp Message Body -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-forest-dark mb-0">Customizable WhatsApp Message Text</label>
                        <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0 fw-semibold" onclick="copyWaText()">
                            <i class="bi bi-clipboard me-1"></i> Copy to Clipboard
                        </button>
                    </div>
                    <textarea id="waMessageTextModal" class="form-control font-monospace small" rows="9" oninput="syncModalToPage()"><?= e($defaultMsg); ?></textarea>
                    <small class="text-muted extra-small">You can freely edit, add personal donor greetings, or customize the text above before sending.</small>
                </div>

            </div>
            <div class="modal-footer bg-cream-soft border-0 p-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success rounded-pill px-3" onclick="copyWaText()">
                        <i class="bi bi-clipboard me-1"></i> Copy Text
                    </button>
                    <a id="waSendBtnModal" href="https://api.whatsapp.com/send?text=<?= rawurlencode($defaultMsg); ?>" target="_blank" rel="noopener" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                        <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic WhatsApp Link & Message Editor -->
<script>
const cowData = {
    name: <?= json_encode($cow['name']); ?>,
    code: <?= json_encode($cow['cow_code']); ?>,
    breed: <?= json_encode($breedName); ?>,
    age: <?= json_encode($cowAge); ?>,
    health: <?= json_encode($cowHealth); ?>,
    statusText: <?= json_encode($cowStatusText); ?>,
    url: <?= json_encode($cowShareUrl); ?>,
    phone: <?= json_encode($sitePhone); ?>
};

function updateWaLink() {
    const textPage = document.getElementById('waMessageText');
    const textModal = document.getElementById('waMessageTextModal');
    const phonePage = document.getElementById('waRecipientPhone');
    const phoneModal = document.getElementById('waRecipientPhoneModal');
    const sendBtnPage = document.getElementById('waSendBtnPage');
    const sendBtnModal = document.getElementById('waSendBtnModal');
    
    const textVal = textPage ? textPage.value : (textModal ? textModal.value : '');
    const phoneVal = (phonePage && phonePage.value.trim().length > 0) 
        ? phonePage.value.replace(/\D/g, '') 
        : (phoneModal ? phoneModal.value.replace(/\D/g, '') : '');
    
    const messageEncoded = encodeURIComponent(textVal);
    const targetUrl = phoneVal.length > 0 
        ? ('https://wa.me/' + phoneVal + '?text=' + messageEncoded)
        : ('https://api.whatsapp.com/send?text=' + messageEncoded);
        
    if (sendBtnPage) sendBtnPage.href = targetUrl;
    if (sendBtnModal) sendBtnModal.href = targetUrl;
}

function syncModalToPage() {
    const textPage = document.getElementById('waMessageText');
    const textModal = document.getElementById('waMessageTextModal');
    if (textPage && textModal) {
        textPage.value = textModal.value;
    }
    updateWaLink();
}

function copyWaText() {
    const textPage = document.getElementById('waMessageText');
    const textModal = document.getElementById('waMessageTextModal');
    const textToCopy = textModal && textModal.value ? textModal.value : (textPage ? textPage.value : '');
    
    if (!textToCopy) return;
    navigator.clipboard.writeText(textToCopy).then(() => {
        showToast('WhatsApp message copied to clipboard!', 'success');
    }).catch(() => {
        if (textPage) { textPage.select(); document.execCommand('copy'); }
        showToast('WhatsApp message copied to clipboard!', 'success');
    });
}

function applyWaPreset(preset) {
    const textPage = document.getElementById('waMessageText');
    const textModal = document.getElementById('waMessageTextModal');
    
    let msg = '';
    if (preset === 'adopt') {
        msg = "🙏 *Gau Seva Adoption Appeal - " + cowData.name + "*\n\n" +
              "Kamadenu Goushala invites compassionate devotees to support:\n" +
              "🐮 *Cow:* " + cowData.name + " (" + cowData.code + ")\n" +
              "🌾 *Breed:* " + cowData.breed + " | *Age:* " + cowData.age + "\n" +
              "🏥 *Health:* " + cowData.health + "\n\n" +
              "By adopting " + cowData.name + " for ₹3,000/month, you ensure her lifelong green fodder, nutritious Ayurvedic mash, and 24x7 veterinary care.\n\n" +
              "📜 *Tax Benefit:* 50% Tax Exemption under Section 80G.\n" +
              "🔗 *Adopt Online:* " + cowData.url + "\n" +
              "📞 *WhatsApp / Call Helpline:* " + cowData.phone + "\n\n" +
              "🙏 *Jai Gau Mata!*";
    } else if (preset === 'medical') {
        msg = "🏥 *Emergency Veterinary Seva Appeal for " + cowData.name + " (" + cowData.code + ")*\n\n" +
              "Namaste! Our rescued cow " + cowData.name + " is under specialized care.\n" +
              "Condition: " + cowData.health + "\n\n" +
              "Help fund her clinical medications, dressing bandages, and probiotic recovery feeds:\n" +
              "🔗 *Support Medical Care:* " + cowData.url + "\n" +
              "📞 *Helpline Desk:* " + cowData.phone + "\n\n" +
              "Every small contribution heals a sacred life. 🙏";
    } else if (preset === 'blessing') {
        msg = "🌸 *Celebrate Your Birthday or Anniversary with Gau Seva!*\n\n" +
              "Dedicate a day of sacred Grāsa Dāna (fresh green grass, jaggery & fruits) for " + cowData.name + " (" + cowData.code + ") at Nandi Hills Sanctuary.\n\n" +
              "🌾 *Sponsor Fodder on Your Special Day:*\n" +
              "🔗 " + cowData.url + "\n" +
              "📞 *Contact Seva Desk:* " + cowData.phone + "\n\n" +
              "Receive Vedic prayers & cow darshan video. 🙏✨";
    } else {
        msg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" +
              "Meet our sacred resident cow:\n" +
              "🐮 *Name:* " + cowData.name + " (" + cowData.code + ")\n" +
              "🌾 *Breed:* " + cowData.breed + "\n" +
              "⏳ *Age:* " + cowData.age + "\n" +
              "🏥 *Health Status:* " + cowData.health + "\n" +
              "❤️ *Adoption Status:* " + cowData.statusText + "\n\n" +
              "🔗 *View Sacred Profile & Adopt:* " + cowData.url + "\n\n" +
              "📞 *Helpline / Seva Desk:* " + cowData.phone + "\n\n" +
              "🙏 *Jai Gau Mata!*";
    }
    
    if (textPage) textPage.value = msg;
    if (textModal) textModal.value = msg;
    updateWaLink();
    showToast('Template preset applied. You can edit the text before sending.', 'info');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
