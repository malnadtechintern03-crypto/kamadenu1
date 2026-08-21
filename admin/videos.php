<?php
/**
 * Kamadenu Goushala Platform - Admin Video Stories & Documentary Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor']);

$currentUser = get_logged_in_user();

// Helper to extract YouTube video ID
function extract_yt_id(string $url): string {
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        return $match[1];
    }
    return '';
}

// Handle Add Video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_video') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $youtubeUrl = sanitize_input($_POST['youtube_url'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);

    $youtubeVideoId = extract_yt_id($youtubeUrl);

    $uploadedFilename = null;
    if (!empty($_FILES['custom_thumbnail']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['custom_thumbnail'], 'videos');
        } catch (Exception $e) {
            set_flash('danger', 'Thumbnail upload failed: ' . $e->getMessage());
        }
    }

    if (!empty($title) && !empty($youtubeUrl)) {
        $thumbnail = $uploadedFilename ?: ($youtubeVideoId ? "https://img.youtube.com/vi/{$youtubeVideoId}/hqdefault.jpg" : 'assets/images/placeholder-gallery.jpg');

        Database::insert("
            INSERT INTO videos (title, youtube_url, youtube_video_id, thumbnail, description, display_order, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [$title, $youtubeUrl, $youtubeVideoId ?: 'dQw4w9WgXcQ', $thumbnail, $description, $displayOrder]);

        log_activity((int)($currentUser['id'] ?? 0), 'create_video', 'videos', null, "Added video story: {$title}");
        set_flash('success', "Video '{$title}' added successfully.");
        header('Location: ' . BASE_URL . '/admin/videos.php');
        exit;
    } else {
        set_flash('danger', 'Video title and YouTube URL are required.');
    }
}

// Handle Edit Video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_video') {
    verify_csrf_or_die();

    $videoId = (int)($_POST['video_id'] ?? 0);
    $title = sanitize_input($_POST['title'] ?? '');
    $youtubeUrl = sanitize_input($_POST['youtube_url'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);

    $video = Database::fetchOne("SELECT * FROM videos WHERE id = ?", [$videoId]);

    if (!$video) {
        set_flash('danger', 'Video not found.');
        header('Location: ' . BASE_URL . '/admin/videos.php');
        exit;
    }

    $youtubeVideoId = extract_yt_id($youtubeUrl) ?: ($video['youtube_video_id'] ?? 'dQw4w9WgXcQ');

    $thumbnail = $video['thumbnail'];
    if (!empty($_FILES['custom_thumbnail']['name'])) {
        try {
            $newUpload = UploadService::upload($_FILES['custom_thumbnail'], 'videos');
            if ($newUpload) {
                if (!empty($video['thumbnail']) && !str_starts_with($video['thumbnail'], 'http') && !str_starts_with($video['thumbnail'], 'assets/')) {
                    UploadService::delete($video['thumbnail'], 'videos');
                }
                $thumbnail = $newUpload;
            }
        } catch (Exception $e) {
            set_flash('danger', 'Thumbnail upload error: ' . $e->getMessage());
        }
    } elseif (empty($thumbnail) || str_starts_with($thumbnail, 'https://img.youtube.com/')) {
        // Refresh YouTube thumbnail if URL changed
        $thumbnail = "https://img.youtube.com/vi/{$youtubeVideoId}/hqdefault.jpg";
    }

    if (!empty($title) && !empty($youtubeUrl)) {
        Database::execute("
            UPDATE videos SET 
                title = ?, youtube_url = ?, youtube_video_id = ?, thumbnail = ?, description = ?, display_order = ?
            WHERE id = ?
        ", [$title, $youtubeUrl, $youtubeVideoId, $thumbnail, $description, $displayOrder, $videoId]);

        log_activity((int)($currentUser['id'] ?? 0), 'update_video', 'videos', $videoId, "Updated video story: {$title}");
        set_flash('success', "Video '{$title}' updated successfully.");
        header('Location: ' . BASE_URL . '/admin/videos.php');
        exit;
    } else {
        set_flash('danger', 'Video title and YouTube URL are required.');
    }
}

// Handle Delete Video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_video') {
    verify_csrf_or_die();
    $videoId = (int)($_POST['video_id'] ?? 0);
    $video = Database::fetchOne("SELECT * FROM videos WHERE id = ?", [$videoId]);
    if ($video) {
        if (!empty($video['thumbnail']) && !str_starts_with($video['thumbnail'], 'http') && !str_starts_with($video['thumbnail'], 'assets/')) {
            UploadService::delete($video['thumbnail'], 'videos');
        }
        Database::execute("DELETE FROM videos WHERE id = ?", [$videoId]);
        log_activity((int)($currentUser['id'] ?? 0), 'delete_video', 'videos', $videoId, "Deleted video ID {$videoId}");
        set_flash('success', 'Video deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/videos.php');
    exit;
}

$videos = Database::fetchAll("SELECT * FROM videos ORDER BY display_order ASC, id DESC");

$pageTitle = 'Video Documentation Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1">Sanctuary Video Stories & Documentaries (<?= count($videos); ?>)</h1>
        <p class="text-muted small mb-0">Publish rescue missions, daily Gau Seva routines, and veterinary documentary films.</p>
    </div>
    <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addVideoModal">
        <i class="bi bi-camera-reels-fill me-1"></i> Add Video Story
    </button>
</div>

<div class="row g-4">
    <?php if (empty($videos)): ?>
        <div class="col-12">
            <div class="card p-5 text-center rounded-4 border-0 shadow-sm bg-white">
                <i class="bi bi-camera-video fs-1 text-muted mb-2"></i>
                <h3 class="font-serif text-forest-dark">No Videos Cataloged</h3>
                <p class="text-muted small mb-3">Add YouTube links for rescue stories and sanctuary documentaries.</p>
                <div>
                    <button type="button" class="btn btn-forest rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                        <i class="bi bi-plus-circle me-1"></i> Add First Video
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($videos as $v): 
            $thumb = $v['thumbnail'] ?? '';
            if (empty($thumb)) {
                $thumb = !empty($v['youtube_video_id']) 
                    ? "https://img.youtube.com/vi/{$v['youtube_video_id']}/hqdefault.jpg" 
                    : BASE_URL . '/assets/images/placeholder-gallery.jpg';
            } elseif (str_starts_with($thumb, 'assets/')) {
                $thumb = BASE_URL . '/' . ltrim($thumb, '/');
            } elseif (!str_starts_with($thumb, 'http')) {
                $thumb = image_url($thumb, 'videos', 'placeholder-gallery.jpg');
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 rounded-4 border-0 shadow-sm overflow-hidden bg-white">
                <div class="position-relative" style="height: 200px; background: var(--color-forest-dark);">
                    <img src="<?= e($thumb); ?>" alt="<?= e($v['title']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='https://img.youtube.com/vi/<?= e($v['youtube_video_id']); ?>/hqdefault.jpg';">
                    <span class="position-absolute top-0 start-0 m-2 badge bg-forest text-gold small">
                        <i class="bi bi-youtube me-1"></i> Video #<?= $v['id']; ?>
                    </span>
                    <?php if ((int)$v['display_order'] > 0): ?>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white small">
                            Order: <?= (int)$v['display_order']; ?>
                        </span>
                    <?php endif; ?>
                    <a href="<?= e($v['youtube_url']); ?>" target="_blank" class="position-absolute top-50 start-50 translate-middle text-decoration-none">
                        <div class="rounded-circle bg-gold text-forest-dark d-flex align-items-center justify-content-center shadow-lg" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="bi bi-play-fill ms-1"></i>
                        </div>
                    </a>
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    <h3 class="h6 font-serif text-forest-dark mb-1 text-truncate" title="<?= e($v['title']); ?>"><?= e($v['title']); ?></h3>
                    <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.8rem; max-height: 40px; overflow: hidden;" title="<?= e($v['description'] ?? ''); ?>">
                        <?= e($v['description'] ?? 'Rescue and documentary film.'); ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <a href="<?= e($v['youtube_url']); ?>" target="_blank" class="small text-forest fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="bi bi-play-circle-fill text-danger"></i> Watch Video
                        </a>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-outline-forest btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#editVideoModal<?= $v['id']; ?>" title="Edit Video">
                                <i class="bi bi-pencil-square"></i> <span>Edit</span>
                            </button>
                            <form method="POST" action="<?= BASE_URL; ?>/admin/videos.php" onsubmit="return confirm('Are you sure you want to delete this video?');" class="d-inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_video">
                                <input type="hidden" name="video_id" value="<?= $v['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1" title="Delete Video">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-youtube text-danger me-2"></i> Add Sanctuary Video</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/videos.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_video">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Video Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Life at Kamadenu: Dawn to Dusk Gau Seva" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">YouTube Video URL *</label>
                            <input type="url" name="youtube_url" class="form-control font-monospace" placeholder="https://www.youtube.com/watch?v=..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Custom Thumbnail (Optional - defaults to YouTube HQ Thumbnail)</label>
                            <input type="file" name="custom_thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Video Summary & Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief overview of what viewers will experience in this documentary..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold"><i class="bi bi-check2 me-1"></i> Save Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Video Modals -->
<?php if (!empty($videos)): ?>
    <?php foreach ($videos as $v): 
        $thumb = $v['thumbnail'] ?? '';
        if (empty($thumb)) {
            $thumb = !empty($v['youtube_video_id']) 
                ? "https://img.youtube.com/vi/{$v['youtube_video_id']}/hqdefault.jpg" 
                : BASE_URL . '/assets/images/placeholder-gallery.jpg';
        } elseif (str_starts_with($thumb, 'assets/')) {
            $thumb = BASE_URL . '/' . ltrim($thumb, '/');
        } elseif (!str_starts_with($thumb, 'http')) {
            $thumb = image_url($thumb, 'videos', 'placeholder-gallery.jpg');
        }
    ?>
    <div class="modal fade" id="editVideoModal<?= $v['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <h5 class="modal-title font-serif"><i class="bi bi-pencil-square text-gold me-2"></i> Edit Video: <?= e($v['title']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= BASE_URL; ?>/admin/videos.php" enctype="multipart/form-data">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_video">
                    <input type="hidden" name="video_id" value="<?= $v['id']; ?>">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Video Title *</label>
                                <input type="text" name="title" class="form-control" value="<?= e($v['title']); ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">YouTube Video URL *</label>
                                <input type="url" name="youtube_url" class="form-control font-monospace" value="<?= e($v['youtube_url']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Display Order</label>
                                <input type="number" name="display_order" class="form-control" value="<?= (int)($v['display_order'] ?? 0); ?>" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Thumbnail Photograph</label>
                                <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border">
                                    <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 90px; height: 60px; background: var(--color-forest-dark);">
                                        <img src="<?= e($thumb); ?>" alt="<?= e($v['title']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='https://img.youtube.com/vi/<?= e($v['youtube_video_id']); ?>/hqdefault.jpg';">
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="custom_thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <small class="text-muted d-block mt-1">Leave empty to keep current thumbnail.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Video Summary & Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= e($v['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-cream-soft border-0 p-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold"><i class="bi bi-check2 me-1"></i> Update Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
