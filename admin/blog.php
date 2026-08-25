<?php
/**
 * Kamadenu Goushala Platform - Admin Blog & Rescue Stories Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor', 'staff']);

$currentUser = get_logged_in_user();
$isPost = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');

// Handle Add Article
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'add_post') {
    verify_csrf_or_die();

    $title = sanitize_input($_POST['title'] ?? '');
    $rawSlug = sanitize_input($_POST['slug'] ?? '') ?: slugify($title);
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : (int)($currentUser['id'] ?? 1);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;

    if (empty($excerpt) && !empty($content)) {
        $excerpt = mb_strimwidth(strip_tags($content), 0, 180, '...');
    }

    if (empty($title) || empty($content)) {
        set_flash('danger', 'Please provide both a title and narrative content for the story.');
        header('Location: ' . BASE_URL . '/admin/blog.php');
        exit;
    }

    // Ensure unique slug
    $slug = $rawSlug;
    $counter = 1;
    while (Database::fetchOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug])) {
        $slug = $rawSlug . '-' . $counter;
        $counter++;
    }

    $uploadedFilename = null;
    if (!empty($_FILES['featured_image']['name'])) {
        try {
            $uploadedFilename = UploadService::upload($_FILES['featured_image'], 'blog');
        } catch (Throwable $e) {
            set_flash('danger', 'Featured image upload failed: ' . $e->getMessage());
        }
    }

    try {
        $finalImage = $uploadedFilename ?: 'placeholder-blog.jpg';

        Database::insert("
            INSERT INTO blog_posts (
                category_id, author_id, title, slug, excerpt, content, featured_image,
                is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ", [$categoryId, $authorId, $title, $slug, $excerpt, $content, $finalImage, $isPublished]);

        log_activity((int)$currentUser['id'], 'create_blog_post', 'blog_posts', null, "Created story: {$title}");
        set_flash('success', "Story '{$title}' published successfully!");
    } catch (Throwable $e) {
        error_log('Error creating blog post: ' . $e->getMessage());
        set_flash('danger', 'Failed to publish story: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Handle Edit Article
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'edit_post') {
    verify_csrf_or_die();

    $postId = (int)($_POST['post_id'] ?? 0);
    $title = sanitize_input($_POST['title'] ?? '');
    $rawSlug = sanitize_input($_POST['slug'] ?? '') ?: slugify($title);
    $categoryId = (int)($_POST['category_id'] ?? 1);
    $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : (int)($currentUser['id'] ?? 1);
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $isPublished = isset($_POST['is_published']) ? (int)$_POST['is_published'] : 1;

    if (empty($excerpt) && !empty($content)) {
        $excerpt = mb_strimwidth(strip_tags($content), 0, 180, '...');
    }

    $post = Database::fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
    if ($post && !empty($title) && !empty($content)) {
        // Ensure unique slug excluding current post
        $slug = $rawSlug;
        $counter = 1;
        while (Database::fetchOne("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $postId])) {
            $slug = $rawSlug . '-' . $counter;
            $counter++;
        }

        $finalImage = $post['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            try {
                $newImage = UploadService::upload($_FILES['featured_image'], 'blog');
                if ($newImage) {
                    if (!empty($post['featured_image']) && !str_starts_with($post['featured_image'], 'assets/')) {
                        UploadService::delete($post['featured_image'], 'blog');
                    }
                    $finalImage = $newImage;
                }
            } catch (Throwable $e) {
                set_flash('danger', 'Featured image upload failed: ' . $e->getMessage());
            }
        }

        try {
            Database::execute("
                UPDATE blog_posts 
                SET category_id = ?, author_id = ?, title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, is_published = ?, updated_at = NOW()
                WHERE id = ?
            ", [$categoryId, $authorId, $title, $slug, $excerpt, $content, $finalImage, $isPublished, $postId]);

            log_activity((int)$currentUser['id'], 'update_blog_post', 'blog_posts', $postId, "Updated story ID {$postId}: {$title}");
            set_flash('success', "Story '{$title}' updated successfully!");
        } catch (Throwable $e) {
            error_log('Error updating blog post: ' . $e->getMessage());
            set_flash('danger', 'Failed to update story: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Please provide both title and content for the story.');
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Handle Quick Toggle Status (Publish / Draft)
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    verify_csrf_or_die();
    $postId = (int)($_POST['post_id'] ?? 0);
    $post = Database::fetchOne("SELECT id, title, is_published FROM blog_posts WHERE id = ?", [$postId]);
    if ($post) {
        $newStatus = empty($post['is_published']) ? 1 : 0;
        Database::execute("UPDATE blog_posts SET is_published = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $postId]);
        $statusText = $newStatus ? 'Published' : 'Draft';
        log_activity((int)$currentUser['id'], 'toggle_blog_status', 'blog_posts', $postId, "Set story #{$postId} to {$statusText}");
        set_flash('success', "Story '{$post['title']}' is now set to {$statusText}.");
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Handle Delete Article
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'delete_post') {
    verify_csrf_or_die();
    $postId = (int)($_POST['post_id'] ?? 0);
    $post = Database::fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$postId]);
    if ($post) {
        if (!empty($post['featured_image']) && !str_starts_with($post['featured_image'], 'assets/')) {
            UploadService::delete($post['featured_image'], 'blog');
        }
        Database::execute("DELETE FROM blog_posts WHERE id = ?", [$postId]);
        log_activity((int)$currentUser['id'], 'delete_blog_post', 'blog_posts', $postId, "Deleted story ID {$postId}: {$post['title']}");
        set_flash('success', 'Story deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Handle Add Category
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    verify_csrf_or_die();
    $catName = sanitize_input($_POST['category_name'] ?? '');
    if (!empty($catName)) {
        $catSlug = slugify($catName);
        try {
            Database::insert("INSERT INTO blog_categories (name, slug, created_at) VALUES (?, ?, NOW())", [$catName, $catSlug]);
            log_activity((int)$currentUser['id'], 'create_blog_category', 'blog_categories', null, "Added blog category: {$catName}");
            set_flash('success', "Category '{$catName}' added successfully!");
        } catch (Throwable $e) {
            set_flash('danger', 'Failed to add category (may already exist).');
        }
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Handle Delete Category
if ($isPost && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    verify_csrf_or_die();
    $catId = (int)($_POST['category_id'] ?? 0);
    $count = (int)Database::fetchValue("SELECT COUNT(*) FROM blog_posts WHERE category_id = ?", [$catId]);
    if ($count > 0) {
        set_flash('danger', "Cannot delete category: {$count} story/articles are currently linked to it. Please reassign them first.");
    } else {
        Database::execute("DELETE FROM blog_categories WHERE id = ?", [$catId]);
        log_activity((int)$currentUser['id'], 'delete_blog_category', 'blog_categories', $catId, "Deleted blog category ID {$catId}");
        set_flash('success', 'Category deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/blog.php');
    exit;
}

// Fetch Categories & Authors
$categories = Database::fetchAll("SELECT bc.*, (SELECT COUNT(*) FROM blog_posts bp WHERE bp.category_id = bc.id) AS post_count FROM blog_categories bc ORDER BY bc.name ASC");
$authors = Database::fetchAll("SELECT id, name, username FROM users ORDER BY name ASC");

// Filter params
$selectedCat = (int)($_GET['category_id'] ?? 0);
$selectedStatus = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$searchQuery = sanitize_input($_GET['q'] ?? '');

$sql = "
    SELECT bp.*, bc.name AS category_name, COALESCE(u.name, 'Kamadenu Editorial Team') AS author_name
    FROM blog_posts bp
    JOIN blog_categories bc ON bp.category_id = bc.id
    LEFT JOIN users u ON bp.author_id = u.id
    WHERE 1=1
";
$params = [];

if ($selectedCat > 0) {
    $sql .= " AND bp.category_id = ?";
    $params[] = $selectedCat;
}
if ($selectedStatus >= 0) {
    $sql .= " AND bp.is_published = ?";
    $params[] = $selectedStatus;
}
if (!empty($searchQuery)) {
    $sql .= " AND (bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)";
    $term = '%' . $searchQuery . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY bp.id DESC";
$posts = Database::fetchAll($sql, $params);

// Statistics
$totalStories = count($posts);
$publishedCount = (int)Database::fetchValue("SELECT COUNT(*) FROM blog_posts WHERE is_published = 1");
$draftCount = (int)Database::fetchValue("SELECT COUNT(*) FROM blog_posts WHERE is_published = 0");
$totalViews = (int)Database::fetchValue("SELECT SUM(views_count) FROM blog_posts");

$primaryWaNumber = get_primary_whatsapp_number();
$sitePhone = get_setting('site_phone', '+91 98450 12345');

$pageTitle = 'Rescue Stories & Blog Manager';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 font-serif text-forest-dark mb-1"><i class="bi bi-journal-text text-gold me-2"></i> Rescue Stories & Vedic Articles</h1>
        <p class="text-muted small mb-0">Publish and broadcast cow rehabilitation narratives, Vedic discourses, and sanctuary updates.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-forest rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
            <i class="bi bi-tags me-1"></i> Manage Categories (<?= count($categories); ?>)
        </button>
        <button type="button" class="btn btn-gold rounded-pill px-4 shadow-gold fw-bold" data-bs-toggle="modal" data-bs-target="#addPostModal">
            <i class="bi bi-pencil-square me-1"></i> Write New Story
        </button>
    </div>
</div>

<!-- Stats Counter Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-xs bg-white text-center">
            <span class="text-muted small fw-bold d-block mb-1">Total Stories</span>
            <span class="display-6 font-serif text-forest-dark fw-bold"><?= $publishedCount + $draftCount; ?></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-xs bg-white text-center">
            <span class="text-muted small fw-bold d-block mb-1">Live Published</span>
            <span class="display-6 font-serif text-success fw-bold"><?= $publishedCount; ?></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-xs bg-white text-center">
            <span class="text-muted small fw-bold d-block mb-1">Drafts</span>
            <span class="display-6 font-serif text-warning fw-bold"><?= $draftCount; ?></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 rounded-4 border-0 shadow-xs bg-white text-center">
            <span class="text-muted small fw-bold d-block mb-1">Total Devotee Reads</span>
            <span class="display-6 font-serif text-gold-dark fw-bold"><?= number_format($totalViews); ?></span>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-4">
    <form method="GET" action="<?= BASE_URL; ?>/admin/blog.php" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search by title, excerpt or story..." value="<?= e($searchQuery); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id']; ?>" <?= ($selectedCat === (int)$c['id']) ? 'selected' : ''; ?>>
                        <?= e($c['name']); ?> (<?= $c['post_count']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="-1" <?= ($selectedStatus === -1) ? 'selected' : ''; ?>>All Statuses</option>
                <option value="1" <?= ($selectedStatus === 1) ? 'selected' : ''; ?>>Published</option>
                <option value="0" <?= ($selectedStatus === 0) ? 'selected' : ''; ?>>Draft</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-forest btn-sm rounded-pill w-100">Filter</button>
            <?php if ($selectedCat > 0 || $selectedStatus >= 0 || !empty($searchQuery)): ?>
                <a href="<?= BASE_URL; ?>/admin/blog.php" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset Filters"><i class="bi bi-x"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Articles Data Table -->
<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Article / Story</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Date</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x fs-1 d-block mb-2 text-muted opacity-50"></i>
                            No articles found matching the current criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): 
                        $blogImg = image_url($post['featured_image'] ?? null, 'blog', 'placeholder-blog.jpg');
                        $postPublicUrl = BASE_URL . '/blog-details.php?slug=' . urlencode($post['slug']);
                        $excerptPreview = $post['excerpt'] ?: mb_strimwidth(strip_tags($post['content']), 0, 140, '...');
                        $defaultWaMsg = "📖 *New Story from Kamadenu Goushala!*\n\n" .
                                        "🐄 *" . $post['title'] . "*\n" .
                                        "🏷️ *Category:* " . $post['category_name'] . "\n\n" .
                                        "📝 " . $excerptPreview . "\n\n" .
                                        "🔗 *Read the full inspiring story here:*\n" .
                                        $postPublicUrl . "\n\n" .
                                        "🙏 *Kamadenu Goushala Sanctuary* | Support: " . $sitePhone;
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 58px; height: 58px; background: var(--color-forest-dark);">
                                    <img src="<?= e($blogImg); ?>" alt="<?= e($post['title']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-blog.jpg';">
                                </div>
                                <div>
                                    <div class="fw-bold text-forest-dark"><?= e($post['title']); ?></div>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 340px;"><?= e($excerptPreview); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-cream text-forest border"><?= e($post['category_name']); ?></span></td>
                        <td><small class="text-forest-dark fw-semibold"><?= e($post['author_name']); ?></small></td>
                        <td><small class="text-muted"><?= date('d M Y', strtotime($post['created_at'])); ?></small></td>
                        <td><span class="badge bg-cream-soft text-muted border"><i class="bi bi-eye me-1"></i><?= number_format((int)$post['views_count']); ?></span></td>
                        <td>
                            <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" class="d-inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                                <button type="submit" class="badge border-0 cursor-pointer <?= !empty($post['is_published']) ? 'bg-success' : 'bg-secondary'; ?>" title="Click to toggle status">
                                    <?= !empty($post['is_published']) ? 'Published' : 'Draft'; ?>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <!-- WhatsApp Broadcast Button -->
                                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#whatsappBlogModal<?= $post['id']; ?>" title="Broadcast / Share on WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <!-- View Public Post -->
                                <a href="<?= e($postPublicUrl); ?>" target="_blank" class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0" title="View Public Post">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Edit Story -->
                                <button type="button" class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#editPostModal<?= $post['id']; ?>" title="Edit Story">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Delete Story -->
                                <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" onsubmit="return confirm('Are you sure you want to permanently delete this story?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" title="Delete Story">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                                </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- All Article WhatsApp & Edit Modals (Clean DOM Outside Table) -->
<?php if (!empty($posts)): ?>
    <?php foreach ($posts as $post): 
        $blogImg = image_url($post['featured_image'] ?? null, 'blog', 'placeholder-blog.jpg');
        $postPublicUrl = BASE_URL . '/blog-details.php?slug=' . urlencode($post['slug']);
        $excerptPreview = $post['excerpt'] ?: mb_strimwidth(strip_tags($post['content']), 0, 140, '...');
        $defaultWaMsg = "📖 *New Story from Kamadenu Goushala!*\n\n" .
                        "🐄 *" . $post['title'] . "*\n" .
                        "🏷️ *Category:* " . $post['category_name'] . "\n\n" .
                        "📝 " . $excerptPreview . "\n\n" .
                        "🔗 *Read the full inspiring story here:*\n" .
                        $postPublicUrl . "\n\n" .
                        "🙏 *Kamadenu Goushala Sanctuary* | Support: " . $sitePhone;
    ?>
    <!-- WhatsApp Broadcast Modal -->
    <div class="modal fade" id="whatsappBlogModal<?= $post['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-whatsapp text-success fs-3"></i>
                        <div>
                            <h5 class="modal-title font-serif text-gold mb-0">Broadcast Story via WhatsApp</h5>
                            <small class="text-cream opacity-75">Send custom narrative bulletin or rescue appeal to devotees & groups</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- Story Preview Banner -->
                    <div class="p-3 bg-cream-soft rounded-3 border mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 50px; height: 50px;">
                                <img src="<?= e($blogImg); ?>" alt="" class="w-100 h-100 object-fit-cover">
                            </div>
                            <div>
                                <h6 class="font-serif text-forest-dark mb-0 fw-bold"><?= e($post['title']); ?></h6>
                                <small class="text-muted"><?= e($post['category_name']); ?> &bull; By <?= e($post['author_name']); ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Recipient & Preset Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Recipient Number (Optional)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="text" id="waBlogPhone<?= $post['id']; ?>" class="form-control font-monospace" placeholder="e.g. 919845012345 (Leave blank for contacts/groups)" oninput="updateWaBlogLink(<?= $post['id']; ?>)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Message Templates / Presets</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaBlogPreset(<?= $post['id']; ?>, 'standard', <?= json_encode($post['title']); ?>, <?= json_encode($post['category_name']); ?>, <?= json_encode($excerptPreview); ?>, <?= json_encode($postPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Standard Story</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaBlogPreset(<?= $post['id']; ?>, 'appeal', <?= json_encode($post['title']); ?>, <?= json_encode($post['category_name']); ?>, <?= json_encode($excerptPreview); ?>, <?= json_encode($postPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Rescue Appeal</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaBlogPreset(<?= $post['id']; ?>, 'wisdom', <?= json_encode($post['title']); ?>, <?= json_encode($post['category_name']); ?>, <?= json_encode($excerptPreview); ?>, <?= json_encode($postPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Vedic Wisdom</button>
                            </div>
                        </div>
                    </div>

                    <!-- Editable WhatsApp Message Body -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-forest-dark mb-0">Customizable WhatsApp Message Text</label>
                            <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0 fw-semibold" onclick="copyWaBlogText(<?= $post['id']; ?>)">
                                <i class="bi bi-clipboard me-1"></i> Copy to Clipboard
                            </button>
                        </div>
                        <textarea id="waBlogText<?= $post['id']; ?>" class="form-control font-monospace small" rows="8" oninput="updateWaBlogLink(<?= $post['id']; ?>)"><?= e($defaultWaMsg); ?></textarea>
                        <small class="text-muted extra-small">Edit or personalize the message above before sending.</small>
                    </div>

                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success rounded-pill px-3" onclick="copyWaBlogText(<?= $post['id']; ?>)">
                            <i class="bi bi-clipboard me-1"></i> Copy Text
                        </button>
                        <a id="waBlogSendBtn<?= $post['id']; ?>" href="https://api.whatsapp.com/send?text=<?= rawurlencode($defaultWaMsg); ?>" target="_blank" rel="noopener" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <label class="form-label small fw-bold">Custom URL Slug</label>
                                <input type="text" name="slug" class="form-control font-monospace" value="<?= e($post['slug']); ?>">
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
                            <input type="text" name="title" id="addStoryTitleInput" class="form-control" placeholder="e.g. Rescuing Nandini: A Story of Hope and Divine Healing" required>
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
                            <label class="form-label small fw-bold">Custom URL Slug (Leave blank to auto-generate)</label>
                            <input type="text" name="slug" id="addStorySlugInput" class="form-control font-monospace" placeholder="e.g. rescuing-nandini-hope-healing">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Featured Header Photo (JPG, PNG, WEBP max 5MB)</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Short Excerpt Summary (Optional - auto extracts from content if blank)</label>
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

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest-dark text-white p-4">
                <h5 class="modal-title font-serif"><i class="bi bi-tags text-gold me-2"></i> Manage Blog Categories</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Add Category Form -->
                <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" class="mb-4">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="add_category">
                    <label class="form-label small fw-bold text-forest-dark">Add New Category</label>
                    <div class="input-group">
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Festival Highlights" required>
                        <button type="submit" class="btn btn-gold fw-bold px-3">Add</button>
                    </div>
                </form>

                <h6 class="font-serif text-forest-dark mb-2">Existing Categories</h6>
                <div class="list-group">
                    <?php foreach ($categories as $cat): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-forest-dark"><?= e($cat['name']); ?></span>
                                <small class="text-muted d-block font-monospace extra-small"><?= e($cat['slug']); ?> &bull; <?= $cat['post_count']; ?> stories</small>
                            </div>
                            <?php if ($cat['post_count'] == 0): ?>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/blog.php" onsubmit="return confirm('Delete this category?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?= $cat['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-xs rounded-pill" title="Delete Category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-cream text-forest border small"><?= $cat['post_count']; ?> active</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
            <div class="modal-footer bg-cream-soft border-0 p-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto Slugify on Title input
const titleInput = document.getElementById('addStoryTitleInput');
const slugInput = document.getElementById('addStorySlugInput');
if (titleInput && slugInput) {
    titleInput.addEventListener('input', () => {
        if (!slugInput.dataset.manual) {
            slugInput.value = titleInput.value.toLowerCase().trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
    slugInput.addEventListener('input', () => {
        slugInput.dataset.manual = 'true';
    });
}

// Update WhatsApp Link for Stories
function updateWaBlogLink(id) {
    const phoneInput = document.getElementById('waBlogPhone' + id);
    const textArea = document.getElementById('waBlogText' + id);
    const sendBtn = document.getElementById('waBlogSendBtn' + id);

    if (!textArea || !sendBtn) return;

    let phone = phoneInput ? phoneInput.value.replace(/\D/g, '') : '';
    let text = encodeURIComponent(textArea.value);

    let url = phone ? `https://wa.me/${phone}?text=${text}` : `https://api.whatsapp.com/send?text=${text}`;
    sendBtn.href = url;
}

// Copy WhatsApp Message to Clipboard
function copyWaBlogText(id) {
    const textArea = document.getElementById('waBlogText' + id);
    if (!textArea) return;

    navigator.clipboard.writeText(textArea.value).then(() => {
        if (typeof showToast === 'function') {
            showToast('WhatsApp story broadcast copied to clipboard!', 'success');
        } else {
            alert('WhatsApp message copied to clipboard!');
        }
    }).catch(err => {
        console.error('Failed to copy text: ', err);
    });
}

// Apply Message Presets
function applyWaBlogPreset(id, type, title, category, excerpt, url, phone) {
    const textArea = document.getElementById('waBlogText' + id);
    if (!textArea) return;

    let msg = '';
    if (type === 'standard') {
        msg = `📖 *New Story from Kamadenu Goushala!*\n\n` +
              `🐄 *${title}*\n` +
              `🏷️ *Category:* ${category}\n\n` +
              `📝 ${excerpt}\n\n` +
              `🔗 *Read the full inspiring story here:*\n` +
              `${url}\n\n` +
              `🙏 *Kamadenu Goushala Sanctuary* | Helpline: ${phone}`;
    } else if (type === 'appeal') {
        msg = `🚨 *Divine Rescue Chronicle & Seva Appeal*\n\n` +
              `🐄 *${title}*\n\n` +
              `Read how divine veterinary rehabilitation and Gau Seva transformed another sacred life:\n` +
              `🔗 *Story Link:* ${url}\n\n` +
              `✨ Support monthly fodder and medical care (80G Tax Deductible).\n` +
              `🙏 *Kamadenu Goushala* | Contact: ${phone}`;
    } else if (type === 'wisdom') {
        msg = `🌿 *Vedic Wisdom & Cow Science from Kamadenu*\n\n` +
              `✨ *${title}*\n\n` +
              `Discover the scientific and spiritual virtues of indigenous cattle:\n` +
              `🔗 *Read Article:* ${url}\n\n` +
              `Share this divine knowledge with your family and devotees! 🙏`;
    }

    textArea.value = msg;
    updateWaBlogLink(id);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
