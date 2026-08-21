<?php
/**
 * Kamadenu Goushala Platform - Admin Blog & Rescue Stories Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor']);

$currentUser = get_logged_in_user();

$categories = Database::fetchAll("SELECT * FROM blog_categories ORDER BY name ASC");

// Handle Add Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($title);
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $authorName = sanitize_input($_POST['author_name'] ?? $currentUser['name'] ?? 'Kamadenu Team');
    $status = sanitize_input($_POST['status'] ?? 'published');

    $uploadedFilename = null;
    if (!empty($_FILES['featured_image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['featured_image'], 'blog');
        } catch (Exception $e) {
            set_flash('danger', 'Featured image upload failed: ' . $e->getMessage());
        }
    }

    if (!empty($title) && !empty($content)) {
        $finalImage = $uploadedFilename ?: 'placeholder-blog.jpg';

        Database::insert("
            INSERT INTO blog_posts (
                category_id, title, slug, excerpt, content, featured_image,
                author_name, status, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ", [$categoryId, $title, $slug, $excerpt, $content, $finalImage, $authorName, $status]);

        log_activity((int)$currentUser['id'], 'create_blog_post', 'blog_posts', null, "Created story: {$title}");
        set_flash('success', "Story '{$title}' published successfully!");
        header('Location: ' . BASE_URL . '/admin/blog.php');
        exit;
    } else {
        set_flash('danger', 'Please provide both title and content for the story.');
    }
}

// Handle Delete Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    verify_csrf_or_die();
    $postId = (int)($_POST['post_id'] ?? 0);
    $post = Database::fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
    if ($post) {
        UploadService::delete($post['featured_image'], 'blog');
        Database::execute("DELETE FROM blog_posts WHERE id = ?", [$postId]);
        log_activity((int)$currentUser['id'], 'delete_blog_post', 'blog_posts', $postId, "Deleted story ID {$postId}");
        set_flash('success', 'Story deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

$posts = Database::fetchAll("
    SELECT bp.*, bc.name AS category_name
    FROM blog_posts bp
    JOIN blog_categories bc ON bp.category_id = bc.id
    ORDER BY bp.id DESC
");

$pageTitle = 'Rescue Stories & Blog Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1">Rescue Stories & Vedic Blog Articles</h1>
        <p class="text-muted small mb-0">Publish cow rehabilitation narratives, Vedic discourses, and sanctuary updates.</p>
    </div>
    <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold" data-bs-toggle="modal" data-bs-target="#addPostModal">
        <i class="bi bi-pencil-square me-1"></i> Write New Story
    </button>
</div>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Article / Story</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No stories published yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): 
                        $blogImg = image_url($post['featured_image'] ?? null, 'blog', 'placeholder-blog.jpg');
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 54px; height: 54px; background: var(--color-forest-dark);">
                                    <img src="<?= e($blogImg); ?>" alt="<?= e($post['title']); ?>" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div>
                                    <div class="fw-bold text-forest-dark"><?= e($post['title']); ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 320px;"><?= e($post['excerpt'] ?? ''); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-cream text-forest border"><?= e($post['category_name']); ?></span></td>
                        <td><small class="text-forest-dark fw-semibold"><?= e($post['author_name']); ?></small></td>
                        <td><small class="text-muted"><?= date('d M Y', strtotime($post['created_at'])); ?></small></td>
                        <td>
                            <span class="badge <?= $post['status'] === 'published' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?= ucfirst($post['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this story?');" class="d-inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" title="Delete Story">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Story Modal -->
<div class="modal fade" id="addPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-pencil-square text-gold me-2"></i> Write New Story / Article</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_post">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Story Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Rescuing Nandini: A Story of Hope and Divine Healing" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= e($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Author Name</label>
                            <input type="text" name="author_name" class="form-control" value="<?= e($currentUser['name'] ?? 'Kamadenu Editorial Team'); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Featured Header Photo (JPG, PNG, WEBP max 5MB)</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Short Excerpt Summary</label>
                            <textarea name="excerpt" class="form-control" rows="2" placeholder="Brief 1-2 sentence preview for search engines and cards..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Full Narrative Content *</label>
                            <textarea name="content" class="form-control font-monospace" rows="8" placeholder="Write the complete story narrative here..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                        <i class="bi bi-send-fill me-1"></i> Publish Story
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
