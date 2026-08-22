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
$authors = Database::fetchAll("SELECT id, name, username FROM users ORDER BY name ASC");

// Handle Add Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($title);
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : (int)($currentUser['id'] ?? 1);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;

    $uploadedFilename = null;
    if (!empty($_FILES['featured_image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['featured_image'], 'blog');
        } catch (Exception $e) {
            set_flash('error', 'Featured image upload failed: ' . $e->getMessage());
        }
    }

    if (!empty($title) && !empty($content)) {
        $finalImage = $uploadedFilename ?: 'placeholder-blog.jpg';

        Database::insert("
            INSERT INTO blog_posts (
                category_id, author_id, title, slug, excerpt, content, featured_image,
                is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ", [$categoryId, $authorId, $title, $slug, $excerpt, $content, $finalImage, $isPublished]);

        log_activity((int)$currentUser['id'], 'create_blog_post', 'blog_posts', null, "Created story: {$title}");
        set_flash('success', "Story '{$title}' published successfully!");
        header('Location: ' . BASE_URL . '/admin/blog.php');
        exit;
    } else {
        set_flash('error', 'Please provide both title and content for the story.');
    }
}

// Handle Edit Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
    verify_csrf_or_die();

    $postId = (int)($_POST['post_id'] ?? 0);
    $title = sanitize_input($_POST['title'] ?? '');
    $slug = sanitize_input($_POST['slug'] ?? '') ?: slugify($title);
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : (int)($currentUser['id'] ?? 1);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;

    $post = Database::fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
    if ($post && !empty($title) && !empty($content)) {
        $finalImage = $post['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            try {
                $newImage = UploadService::upload($_FILES['featured_image'], 'blog');
                if ($newImage) {
                    UploadService::delete($post['featured_image'], 'blog');
                    $finalImage = $newImage;
                }
            } catch (Exception $e) {
                set_flash('error', 'Featured image upload failed: ' . $e->getMessage());
            }
        }

        Database::execute("
            UPDATE blog_posts 
            SET category_id = ?, author_id = ?, title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ", [$categoryId, $authorId, $title, $slug, $excerpt, $content, $finalImage, $isPublished, $postId]);

        log_activity((int)$currentUser['id'], 'update_blog_post', 'blog_posts', $postId, "Updated story ID {$postId}: {$title}");
        set_flash('success', "Story '{$title}' updated successfully!");
    } else {
        set_flash('error', 'Please provide both title and content for the story.');
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
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
    SELECT bp.*, bc.name AS category_name, COALESCE(u.name, 'Kamadenu Editorial Team') AS author_name
    FROM blog_posts bp
    JOIN blog_categories bc ON bp.category_id = bc.id
    LEFT JOIN users u ON bp.author_id = u.id
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
                                    <img src="<?= e($blogImg); ?>" alt="<?= e($post['title']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-blog.jpg';">
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
                            <span class="badge <?= !empty($post['is_published']) ? 'bg-success' : 'bg-secondary'; ?>">
                                <?= !empty($post['is_published']) ? 'Published' : 'Draft'; ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="<?= BASE_URL; ?>/blog-details.php?slug=<?= e($post['slug']); ?>" target="_blank" class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0" title="View Public Post">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#editPostModal<?= $post['id']; ?>" title="Edit Story">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this story?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" title="Delete Story">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Story Modal -->
                    <div class="modal fade" id="editPostModal<?= $post['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow-lg">
                                <div class="modal-header bg-forest-dark text-white p-4">
                                    <h5 class="modal-title font-serif"><i class="bi bi-pencil-square text-gold me-2"></i> Edit Story / Article</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" enctype="multipart/form-data">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="edit_post">
                                    <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                                    <div class="modal-body p-4">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Story Title *</label>
                                                <input type="text" name="title" class="form-control" value="<?= e($post['title']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Category *</label>
                                                <select name="category_id" class="form-select" required>
                                                    <?php foreach ($categories as $c): ?>
                                                        <option value="<?= $c['id']; ?>" <?= ($post['category_id'] == $c['id']) ? 'selected' : ''; ?>><?= e($c['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Author</label>
                                                <select name="author_id" class="form-select">
                                                    <?php foreach ($authors as $a): ?>
                                                        <option value="<?= $a['id']; ?>" <?= ($post['author_id'] == $a['id']) ? 'selected' : ''; ?>><?= e($a['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small fw-bold">Status</label>
                                                <select name="is_published" class="form-select">
                                                    <option value="1" <?= !empty($post['is_published']) ? 'selected' : ''; ?>>Published</option>
                                                    <option value="0" <?= empty($post['is_published']) ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Replace Featured Header Photo (Optional)</label>
                                                <input type="file" name="featured_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                                <?php if (!empty($post['featured_image'])): ?>
                                                    <div class="form-text small">Current: <?= e($post['featured_image']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Short Excerpt Summary</label>
                                                <textarea name="excerpt" class="form-control" rows="2" placeholder="Brief 1-2 sentence preview..."><?= e($post['excerpt'] ?? ''); ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Full Narrative Content *</label>
                                                <textarea name="content" class="form-control font-monospace" rows="8" required><?= e($post['content'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-cream-soft border-0 p-3">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-gold rounded-pill px-4 shadow-gold">
                                            <i class="bi bi-check-circle-fill me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Author</label>
                            <select name="author_id" class="form-select">
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= $a['id']; ?>" <?= (($currentUser['id'] ?? 1) == $a['id']) ? 'selected' : ''; ?>><?= e($a['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="is_published" class="form-select">
                                <option value="1" selected>Published</option>
                                <option value="0">Draft</option>
                            </select>
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
