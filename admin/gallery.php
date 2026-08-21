<?php
/**
 * Kamadenu Goushala Platform - Events & Sanctuary Photo Gallery Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor']);

$currentUser = get_logged_in_user();

$categories = Database::fetchAll("SELECT * FROM gallery_categories ORDER BY name ASC");

// Handle Add / Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_photo') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $caption = sanitize_input($_POST['caption'] ?? '');
    $sortOrder = (int)($_POST['display_order'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title)) {
        set_flash('danger', 'Photo or Event title is required.');
    } elseif (empty($_FILES['image']['name'])) {
        set_flash('danger', 'Please choose an image file to upload.');
    } else {
        try {
            $imageFilename = UploadService::upload($_FILES['image'], 'gallery');

            Database::insert(
                "INSERT INTO gallery (category_id, title, caption, image_path, display_order, is_featured) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$categoryId, $title, $caption, $imageFilename, $sortOrder, $isFeatured]
            );

            log_activity((int)$currentUser['id'], 'upload_gallery_image', 'gallery', null, "Uploaded event photo: {$title}");
            set_flash('success', "Event photo '{$title}' uploaded successfully!");
            header('Location: ' . BASE_URL . '/admin/gallery.php');
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Upload failed: ' . $e->getMessage());
        }
    }
}

// Handle Edit Photo / Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_photo') {
    verify_csrf_or_die();

    $photoId = (int)($_POST['photo_id'] ?? 0);
    $title = sanitize_input($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $caption = sanitize_input($_POST['caption'] ?? '');
    $sortOrder = (int)($_POST['display_order'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    $photo = Database::fetchOne("SELECT * FROM gallery WHERE id = ?", [$photoId]);

    if (!$photo) {
        set_flash('danger', 'Photo or event record not found.');
    } elseif (empty($title)) {
        set_flash('danger', 'Photo or Event title is required.');
    } else {
        try {
            $imageFilename = $photo['image_path'];

            // If a replacement image is provided
            if (!empty($_FILES['image']['name'])) {
                $newImageFilename = UploadService::upload($_FILES['image'], 'gallery');
                // Safely delete old image if it's an uploaded file
                if (!empty($photo['image_path']) && !str_starts_with($photo['image_path'], 'assets/')) {
                    UploadService::delete($photo['image_path'], 'gallery');
                }
                $imageFilename = $newImageFilename;
            }

            Database::execute(
                "UPDATE gallery 
                 SET category_id = ?, title = ?, caption = ?, image_path = ?, display_order = ?, is_featured = ? 
                 WHERE id = ?",
                [$categoryId, $title, $caption, $imageFilename, $sortOrder, $isFeatured, $photoId]
            );

            log_activity((int)$currentUser['id'], 'update_gallery_image', 'gallery', $photoId, "Updated event photo: {$title}");
            set_flash('success', "Event photo '{$title}' updated successfully!");
            header('Location: ' . BASE_URL . '/admin/gallery.php');
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Update failed: ' . $e->getMessage());
        }
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    verify_csrf_or_die();

    $photoId = (int)($_POST['photo_id'] ?? 0);
    $photo = Database::fetchOne("SELECT * FROM gallery WHERE id = ?", [$photoId]);

    if ($photo) {
        if (!empty($photo['image_path']) && !str_starts_with($photo['image_path'], 'assets/')) {
            UploadService::delete($photo['image_path'], 'gallery');
        }
        Database::execute("DELETE FROM gallery WHERE id = ?", [$photoId]);
        log_activity((int)$currentUser['id'], 'delete_gallery_image', 'gallery', $photoId, "Deleted photo ID {$photoId}");
        set_flash('success', 'Photo deleted successfully.');
    } else {
        set_flash('danger', 'Photo not found.');
    }
    header('Location: ' . BASE_URL . '/admin/gallery.php');
    exit;
}

// Filter query
$categoryFilter = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : 0;
$whereSql = $categoryFilter > 0 ? "WHERE g.category_id = {$categoryFilter}" : "";

$photos = Database::fetchAll("
    SELECT g.*, gc.name AS category_name
    FROM gallery g
    JOIN gallery_categories gc ON g.category_id = gc.id
    {$whereSql}
    ORDER BY g.display_order ASC, g.id DESC
");

$pageTitle = 'Gallery & Events Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1">Sanctuary Gallery & Event Photos</h1>
        <p class="text-muted small mb-0">Upload, edit, organize, and manage photographic archives for public events and daily seva.</p>
    </div>
    <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload New Photo
    </button>
</div>

<!-- Category Filter Pills -->
<div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="small fw-bold text-forest-dark me-2"><i class="bi bi-funnel me-1"></i> Categories:</span>
        <a href="<?= BASE_URL; ?>/admin/gallery.php" class="btn btn-sm rounded-pill <?= $categoryFilter === 0 ? 'btn-forest' : 'btn-outline-forest'; ?>">
            All (<?= count($photos); ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL; ?>/admin/gallery.php?cat=<?= $cat['id']; ?>" class="btn btn-sm rounded-pill <?= $categoryFilter === (int)$cat['id'] ? 'btn-forest' : 'btn-outline-forest'; ?>">
                <?= e($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Photos Gallery Grid -->
<?php if (empty($photos)): ?>
    <div class="card p-5 text-center rounded-4 border-0 shadow-sm bg-white">
        <i class="bi bi-images fs-1 text-muted mb-2"></i>
        <h3 class="font-serif text-forest-dark">No Photos Found</h3>
        <p class="text-muted small mb-3">No event or sanctuary photos have been uploaded for this category yet.</p>
        <div>
            <button type="button" class="btn btn-forest rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                <i class="bi bi-plus-circle me-1"></i> Upload First Image
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($photos as $item): 
            $photoImgUrl = !empty($item['image_path'])
                ? (str_starts_with($item['image_path'], 'assets/') ? BASE_URL . '/' . ltrim($item['image_path'], '/') : image_url($item['image_path'], 'gallery', 'placeholder-gallery.jpg'))
                : BASE_URL . '/assets/images/placeholder-gallery.jpg';
        ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 rounded-4 border-0 shadow-sm overflow-hidden bg-white">
                <div class="position-relative" style="height: 200px; background-color: var(--color-forest-dark);">
                    <img 
                        src="<?= e($photoImgUrl); ?>" 
                        alt="<?= e($item['title']); ?>" 
                        class="w-100 h-100 object-fit-cover d-block"
                        onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-gallery.jpg';"
                    >
                    <span class="position-absolute top-0 start-0 m-2 badge bg-gold text-forest-dark fw-bold small">
                        <?= e($item['category_name']); ?>
                    </span>
                    <?php if (!empty($item['is_featured'])): ?>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-forest text-white small">
                            <i class="bi bi-star-fill text-gold me-1"></i> Featured
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    <h3 class="h6 font-serif text-forest-dark mb-1 text-truncate" title="<?= e($item['title']); ?>"><?= e($item['title']); ?></h3>
                    <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.8rem; max-height: 40px; overflow: hidden;" title="<?= e($item['caption'] ?? ''); ?>">
                        <?= e($item['caption'] ?? 'No description provided.'); ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y', strtotime($item['created_at'])); ?>
                        </small>
                        <div class="d-flex gap-1">
                            <button type="button" 
                                    class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0 edit-photo-btn" 
                                    title="Edit Event / Photo"
                                    data-id="<?= $item['id']; ?>"
                                    data-title="<?= e($item['title']); ?>"
                                    data-category-id="<?= $item['category_id']; ?>"
                                    data-caption="<?= e($item['caption'] ?? ''); ?>"
                                    data-display-order="<?= (int)$item['display_order']; ?>"
                                    data-is-featured="<?= !empty($item['is_featured']) ? '1' : '0'; ?>"
                                    data-image-url="<?= e($photoImgUrl); ?>"
                            >
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <form method="POST" action="<?= BASE_URL; ?>/admin/gallery.php" onsubmit="return confirm('Are you sure you want to permanently delete this photo?');" class="d-inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?= $item['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" title="Delete Photo">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= BASE_URL; ?>/admin/gallery.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_photo">

                <div class="modal-header bg-forest-dark text-white rounded-top-4 p-4">
                    <h5 class="modal-title font-serif" id="uploadPhotoModalLabel">
                        <i class="bi bi-cloud-arrow-up text-gold me-2"></i> Upload Event / Sanctuary Photo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Photo / Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Gopashtami Mahotsav 2026 Celebration" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= e($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Choose Image File (JPG, PNG, WEBP max 5MB) <span class="text-danger">*</span></label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required onchange="previewUploadImage(this, 'photoPreviewBox', 'photoPreviewImg')">
                        <div class="mt-2 text-center d-none" id="photoPreviewBox">
                            <img src="" id="photoPreviewImg" class="rounded-3 border shadow-xs" style="max-height: 140px; max-width: 100%;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Caption / Event Details</label>
                        <textarea name="caption" class="form-control" rows="2" placeholder="Brief note about the celebration or rescue activity..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-forest-dark">Sort Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check pb-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="modalIsFeatured" value="1">
                                <label class="form-check-label small fw-semibold text-forest-dark" for="modalIsFeatured">
                                    Feature on Home
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-cream-soft rounded-bottom-4 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi bi-check-circle-fill me-1"></i> Upload Image
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Photo / Event Modal -->
<div class="modal fade" id="editPhotoModal" tabindex="-1" aria-labelledby="editPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="POST" action="<?= BASE_URL; ?>/admin/gallery.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit_photo">
                <input type="hidden" name="photo_id" id="editPhotoId" value="">

                <div class="modal-header bg-forest-dark text-white rounded-top-4 p-4">
                    <h5 class="modal-title font-serif" id="editPhotoModalLabel">
                        <i class="bi bi-pencil-square text-gold me-2"></i> Edit Event / Sanctuary Photo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Photo / Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="editTitle" class="form-control" placeholder="e.g., Gopashtami Mahotsav 2026 Celebration" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="editCategoryId" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= e($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Current Image Preview -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Current Image</label>
                        <div class="d-flex align-items-center gap-3 p-2 bg-cream-soft rounded-3 border">
                            <img src="" id="editCurrentImg" class="rounded-2 border object-fit-cover" style="width: 80px; height: 60px;" alt="Current Image">
                            <div>
                                <span class="badge bg-forest text-gold mb-1">Active Photo</span>
                                <p class="small text-muted mb-0" style="font-size: 0.75rem;">To keep this image, leave the replacement field below empty.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Replace Image (Optional - JPG, PNG, WEBP max 5MB)</label>
                        <input type="file" name="image" id="editImageInput" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewUploadImage(this, 'editPhotoPreviewBox', 'editPhotoPreviewImg')">
                        <div class="mt-2 text-center d-none" id="editPhotoPreviewBox">
                            <div class="small text-muted mb-1">New Image Preview:</div>
                            <img src="" id="editPhotoPreviewImg" class="rounded-3 border shadow-xs" style="max-height: 140px; max-width: 100%;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-forest-dark">Caption / Event Details</label>
                        <textarea name="caption" id="editCaption" class="form-control" rows="2" placeholder="Brief note about the celebration or rescue activity..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-forest-dark">Sort Order</label>
                            <input type="number" name="display_order" id="editDisplayOrder" class="form-control" value="0">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check pb-2">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="editIsFeatured" value="1">
                                <label class="form-check-label small fw-semibold text-forest-dark" for="editIsFeatured">
                                    Feature on Home
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-cream-soft rounded-bottom-4 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi bi-check-circle-fill me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewUploadImage(input, previewBoxId, previewImgId) {
    previewImgId = previewImgId || 'photoPreviewImg';
    const box = document.getElementById(previewBoxId);
    const img = document.getElementById(previewImgId);
    if (!box || !img) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            box.classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        box.classList.add('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editModalEl = document.getElementById('editPhotoModal');
    if (!editModalEl) return;
    const editModal = new bootstrap.Modal(editModalEl);

    document.querySelectorAll('.edit-photo-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id') || '';
            const title = this.getAttribute('data-title') || '';
            const categoryId = this.getAttribute('data-category-id') || '1';
            const caption = this.getAttribute('data-caption') || '';
            const displayOrder = this.getAttribute('data-display-order') || '0';
            const isFeatured = this.getAttribute('data-is-featured') === '1';
            const imageUrl = this.getAttribute('data-image-url') || '';

            document.getElementById('editPhotoId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editCategoryId').value = categoryId;
            document.getElementById('editCaption').value = caption;
            document.getElementById('editDisplayOrder').value = displayOrder;
            document.getElementById('editIsFeatured').checked = isFeatured;

            const currentImg = document.getElementById('editCurrentImg');
            if (currentImg) {
                currentImg.src = imageUrl;
            }

            // Reset replacement file input and preview
            const fileInput = document.getElementById('editImageInput');
            if (fileInput) fileInput.value = '';
            const previewBox = document.getElementById('editPhotoPreviewBox');
            if (previewBox) previewBox.classList.add('d-none');

            editModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

