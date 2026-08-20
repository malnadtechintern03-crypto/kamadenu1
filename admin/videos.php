<?php
/**
 * Kamadenu Goushala Platform - Admin Video Stories & Documentary Manager
 */

declare(strict_types=1);

$pageTitle = 'Video Documentation Manager';

require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'manager', 'editor']);

// Handle Add Video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_video') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $youtubeUrl = sanitize_input($_POST['youtube_url'] ?? '');
    $category = sanitize_input($_POST['category'] ?? 'Rescue Story');
    $description = sanitize_input($_POST['description'] ?? '');
    $duration = sanitize_input($_POST['duration'] ?? '5:20');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // Extract YouTube ID
    $youtubeId = '';
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtubeUrl, $match)) {
        $youtubeId = $match[1];
    }

    $uploadedFilename = null;
    if (!empty($_FILES['custom_thumbnail']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['custom_thumbnail'], 'videos');
        } catch (Exception $e) {
            set_flash('danger', 'Thumbnail upload failed: ' . $e->getMessage());
        }
    }

    if (!empty($title) && (!empty($youtubeId) || !empty($youtubeUrl))) {
        $thumbnail = $uploadedFilename ?: ($youtubeId ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : 'placeholder-gallery.jpg');

        Database::insert("
            INSERT INTO videos (title, youtube_id, youtube_url, category, description, duration, thumbnail_path, is_featured, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [$title, $youtubeId ?: 'dQw4w9WgXcQ', $youtubeUrl, $category, $description, $duration, $thumbnail, $isFeatured]);

        log_activity((int)$currentUser['id'], 'create_video', 'videos', null, "Added video story: {$title}");
        set_flash('success', "Video '{$title}' added successfully.");
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
        if (!empty($video['thumbnail_path']) && !str_starts_with($video['thumbnail_path'], 'http')) {
            UploadService::delete($video['thumbnail_path'], 'videos');
        }
        Database::execute("DELETE FROM videos WHERE id = ?", [$videoId]);
        log_activity((int)$currentUser['id'], 'delete_video', 'videos', $videoId, "Deleted video ID {$videoId}");
        set_flash('success', 'Video deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/videos.php');
    exit;
}

$videos = Database::fetchAll("SELECT * FROM videos ORDER BY id DESC");
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1">Sanctuary Video Stories & Documentaries</h1>
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
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($videos as $v): 
            $thumb = $v['thumbnail_path'] ?? '';
            if (!str_starts_with($thumb, 'http')) {
                $thumb = image_url($thumb, 'videos', 'placeholder-gallery.jpg');
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 rounded-4 border-0 shadow-sm overflow-hidden bg-white">
                <div class="position-relative" style="height: 190px; background: var(--color-forest-dark);">
                    <img src="<?= e($thumb); ?>" alt="<?= e($v['title']); ?>" class="w-100 h-100 object-fit-cover">
                    <span class="position-absolute top-0 start-0 m-2 badge bg-forest text-gold small">
                        <?= e($v['category']); ?>
                    </span>
                    <span class="position-absolute bottom-0 end-0 m-2 badge bg-black bg-opacity-75 text-white small">
                        <?= e($v['duration'] ?? '5:00'); ?>
                    </span>
                </div>
                <div class="card-body p-3 d-flex flex-column">
                    <h3 class="h6 font-serif text-forest-dark mb-1 text-truncate"><?= e($v['title']); ?></h3>
                    <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.8rem; max-height: 40px; overflow: hidden;">
                        <?= e($v['description'] ?? 'Rescue film documentation.'); ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <a href="<?= e($v['youtube_url'] ?? ('https://www.youtube.com/watch?v=' . ($v['youtube_id'] ?? ''))); ?>" target="_blank" class="small text-forest fw-semibold">
                            <i class="bi bi-play-circle-fill text-danger me-1"></i> Watch Video
                        </a>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this video?');" class="d-inline">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_video">
                            <input type="hidden" name="video_id" value="<?= $v['id']; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-youtube text-danger me-2"></i> Add Sanctuary Video</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/videos.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_video">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Video Title *</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Life at Kamadenu: Dawn to Dusk Gau Seva" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">YouTube Video URL *</label>
                        <input type="url" name="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <option value="Rescue Story">Rescue Story</option>
                                <option value="Daily Seva">Daily Seva</option>
                                <option value="Vedic Knowledge">Vedic Knowledge</option>
                                <option value="Medical Care">Medical Care</option>
                                <option value="Festival Celebration">Festival Celebration</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Duration</label>
                            <input type="text" name="duration" class="form-control" placeholder="e.g. 6:45">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Custom Thumbnail (Optional, defaults to YouTube HQ)</label>
                        <input type="file" name="custom_thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief summary of the video..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">Save Video</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
