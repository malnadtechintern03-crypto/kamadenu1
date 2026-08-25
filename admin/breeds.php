<?php
/**
 * Kamadenu Goushala Platform - Admin Indigenous Breeds Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager']);

$currentUser = get_logged_in_user();

// Handle Add Breed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_breed') {
    verify_csrf_or_die();

    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($name);
    $origin = sanitize_input($_POST['origin_region'] ?? '');
    $characteristics = sanitize_input($_POST['characteristics'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    $uploadedFilename = null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['image'], 'breeds');
        } catch (Exception $e) {
            set_flash('danger', 'Breed image upload failed: ' . $e->getMessage());
        }
    }

    if (!empty($name)) {
        // Check for duplicate name or slug
        $duplicate = Database::fetchOne("SELECT id FROM cow_breeds WHERE name = ? OR slug = ?", [$name, $slug]);
        if ($duplicate) {
            set_flash('danger', "A breed with name '{$name}' or slug '{$slug}' already exists.");
            header('Location: ' . BASE_URL . '/admin/breeds.php');
            exit;
        }

        $finalImage = $uploadedFilename ?: 'placeholder-breed.jpg';

        Database::insert("
            INSERT INTO cow_breeds (name, slug, origin_region, characteristics, description, image) 
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$name, $slug, $origin, $characteristics, $description, $finalImage]);

        log_activity((int)$currentUser['id'], 'create_breed', 'cow_breeds', null, "Added indigenous breed: {$name}");
        set_flash('success', "Breed '{$name}' registered successfully.");
        header('Location: ' . BASE_URL . '/admin/breeds.php');
        exit;
    } else {
        set_flash('danger', 'Breed name is required.');
    }
}

// Handle Edit Breed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_breed') {
    verify_csrf_or_die();

    $breedId = (int)($_POST['breed_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($name);
    $origin = sanitize_input($_POST['origin_region'] ?? '');
    $characteristics = sanitize_input($_POST['characteristics'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');

    $breed = Database::fetchOne("SELECT * FROM cow_breeds WHERE id = ?", [$breedId]);
    if (!$breed) {
        set_flash('danger', 'Breed not found.');
        header('Location: ' . BASE_URL . '/admin/breeds.php');
        exit;
    }

    if (empty($name)) {
        set_flash('danger', 'Breed name is required.');
        header('Location: ' . BASE_URL . '/admin/breeds.php');
        exit;
    }

    // Check for duplicate name or slug on another breed
    $duplicate = Database::fetchOne("SELECT id FROM cow_breeds WHERE (name = ? OR slug = ?) AND id != ?", [$name, $slug, $breedId]);
    if ($duplicate) {
        set_flash('danger', "Another breed with name '{$name}' or slug '{$slug}' already exists.");
        header('Location: ' . BASE_URL . '/admin/breeds.php');
        exit;
    }

    $imageFilename = $breed['image'];
    if (!empty($_FILES['image']['name'])) {
        try {
            $newUpload = UploadService::upload($_FILES['image'], 'breeds');
            if ($newUpload) {
                if (!empty($breed['image']) && $breed['image'] !== 'placeholder-breed.jpg' && !str_starts_with($breed['image'], 'assets/')) {
                    UploadService::delete($breed['image'], 'breeds');
                }
                $imageFilename = $newUpload;
            }
        } catch (Exception $e) {
            set_flash('danger', 'Breed image upload failed: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/admin/breeds.php');
            exit;
        }
    }

    Database::execute("
        UPDATE cow_breeds 
        SET name = ?, slug = ?, origin_region = ?, characteristics = ?, description = ?, image = ?
        WHERE id = ?
    ", [$name, $slug, $origin, $characteristics, $description, $imageFilename, $breedId]);

    log_activity((int)$currentUser['id'], 'update_breed', 'cow_breeds', $breedId, "Updated breed: {$name}");
    set_flash('success', "Breed '{$name}' updated successfully.");
    header('Location: ' . BASE_URL . '/admin/breeds.php');
    exit;
}

// Handle Delete Breed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_breed') {
    verify_csrf_or_die();
    $breedId = (int)($_POST['breed_id'] ?? 0);
    $count = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE breed_id = ?", [$breedId]);
    if ($count > 0) {
        set_flash('danger', "Cannot delete this breed because {$count} resident cows are associated with it.");
    } else {
        $breed = Database::fetchOne("SELECT * FROM cow_breeds WHERE id = ?", [$breedId]);
        if ($breed) {
            if (!empty($breed['image']) && $breed['image'] !== 'placeholder-breed.jpg' && !str_starts_with($breed['image'], 'assets/')) {
                UploadService::delete($breed['image'], 'breeds');
            }
            Database::execute("DELETE FROM cow_breeds WHERE id = ?", [$breedId]);
            log_activity((int)$currentUser['id'], 'delete_breed', 'cow_breeds', $breedId, "Deleted breed ID {$breedId}");
            set_flash('success', 'Breed removed from catalog.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/breeds.php');
    exit;
}

$breeds = Database::fetchAll("
    SELECT cb.*, COUNT(c.id) AS resident_count
    FROM cow_breeds cb
    LEFT JOIN cows c ON cb.id = c.breed_id AND c.status != 'deceased'
    GROUP BY cb.id
    ORDER BY cb.id ASC
");

$pageTitle = 'Indigenous Breeds Catalog';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1">Indigenous Bos Indicus Breeds (<?= count($breeds); ?>)</h1>
        <p class="text-muted small mb-0">Manage sanctuary lineage profiles, native regions, and descriptive traits.</p>
    </div>
    <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addBreedModal">
        <i class="bi bi-plus-circle-fill me-1"></i> Register Native Breed
    </button>
</div>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Breed Name</th>
                    <th>Native Origin</th>
                    <th>Sanctuary Herd</th>
                    <th>Key Characteristics</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($breeds as $b): 
                    $breedImg = image_url($b['image'] ?? null, 'breeds', 'placeholder-breed.jpg');
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; background: var(--color-forest-dark);">
                                <img src="<?= e($breedImg); ?>" alt="<?= e($b['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-breed.jpg';">
                            </div>
                            <div>
                                <div class="fw-bold text-forest-dark"><?= e($b['name']); ?></div>
                                <code class="small text-muted"><?= e($b['slug']); ?></code>
                            </div>
                        </div>
                    </td>
                    <td><small class="text-forest-dark fw-semibold"><?= e($b['origin_region']); ?></small></td>
                    <td>
                        <span class="badge bg-forest text-gold px-3 py-1 rounded-pill">
                            <?= $b['resident_count']; ?> Resident <?= (int)$b['resident_count'] === 1 ? 'Cow' : 'Cows'; ?>
                        </span>
                    </td>
                    <td>
                        <small class="text-muted text-truncate d-inline-block" style="max-width: 280px;">
                            <?= e($b['characteristics']); ?>
                        </small>
                    </td>
                    <td class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-1">
                            <button type="button" 
                                    class="btn btn-outline-forest btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editBreedModal<?= $b['id']; ?>" 
                                    title="Edit Breed">
                                <i class="bi bi-pencil-square"></i> <span>Edit</span>
                            </button>
                            <form method="POST" action="<?= BASE_URL; ?>/admin/breeds.php" onsubmit="return confirm('Are you sure you want to delete this breed?');" class="d-inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_breed">
                                <input type="hidden" name="breed_id" value="<?= $b['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1" title="Delete Breed">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Breed Modal -->
<div class="modal fade" id="addBreedModal" tabindex="-1" aria-labelledby="addBreedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif" id="addBreedModalLabel"><i class="bi bi-patch-check-fill text-gold me-2"></i> Register Indigenous Breed</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/breeds.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_breed">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Breed Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Kankrej" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">URL Slug (Optional - auto generated)</label>
                            <input type="text" name="slug" class="form-control" placeholder="e.g. kankrej">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Native Origin Region *</label>
                            <input type="text" name="origin_region" class="form-control" placeholder="e.g. Rann of Kutch, Gujarat" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Breed Photograph (JPG, PNG, WEBP max 5MB)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Distinguishing Characteristics</label>
                            <input type="text" name="characteristics" class="form-control" placeholder="Lyre-shaped horns, graceful sawai gait, high hump">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Vedic Heritage & History</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Classical literature references and sanctuary lineage..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold"><i class="bi bi-check2 me-1"></i> Save Breed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Breed Modals -->
<?php if (!empty($breeds)): ?>
    <?php foreach ($breeds as $b): 
        $editImg = image_url($b['image'] ?? null, 'breeds', 'placeholder-breed.jpg');
    ?>
    <div class="modal fade" id="editBreedModal<?= $b['id']; ?>" tabindex="-1" aria-labelledby="editBreedModalLabel<?= $b['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <h5 class="modal-title font-serif" id="editBreedModalLabel<?= $b['id']; ?>">
                        <i class="bi bi-pencil-square text-gold me-2"></i> Edit Breed: <?= e($b['name']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= BASE_URL; ?>/admin/breeds.php" enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_breed">
                    <input type="hidden" name="breed_id" value="<?= $b['id']; ?>">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Breed Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= e($b['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">URL Slug *</label>
                                <input type="text" name="slug" class="form-control" value="<?= e($b['slug']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Native Origin Region *</label>
                                <input type="text" name="origin_region" class="form-control" value="<?= e($b['origin_region']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Breed Photograph</label>
                                <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border">
                                    <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 70px; height: 70px; background: var(--color-forest-dark);">
                                        <img src="<?= e($editImg); ?>" alt="<?= e($b['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-breed.jpg';">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <small class="text-muted d-block mt-1">Select a new image to replace the current photo (JPG, PNG, WEBP max 5MB), or leave empty to keep current photo.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Distinguishing Characteristics</label>
                                <input type="text" name="characteristics" class="form-control" value="<?= e($b['characteristics'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Vedic Heritage & History</label>
                                <textarea name="description" class="form-control" rows="4"><?= e($b['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-cream-soft border-0 p-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                            <i class="bi bi-check2 me-1"></i> Update Breed
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
