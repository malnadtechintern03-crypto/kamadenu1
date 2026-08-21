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
                <a href="<?= BASE_URL; ?>/admin/cows.php" class="btn btn-outline-forest btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Back to Directory
                </a>
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

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
