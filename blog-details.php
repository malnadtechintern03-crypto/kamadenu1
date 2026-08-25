<?php
/**
 * Kamadenu Goushala Platform - Blog & Rescue Story Details
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/blog.php');
    exit;
}

$sql = "
    SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, u.name AS author_name 
    FROM blog_posts bp 
    JOIN blog_categories bc ON bp.category_id = bc.id 
    LEFT JOIN users u ON bp.author_id = u.id 
    WHERE bp.slug = ? AND bp.is_published = 1
";
$post = Database::fetchOne($sql, [$slug]);
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Article Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-journal-x fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Article Not Found</h1>
            <p class="text-muted">The story you are looking for is unavailable or has been archived.</p>
            <a href="' . BASE_URL . '/blog.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Blog Hub</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Increment view count
Database::execute("UPDATE blog_posts SET views_count = views_count + 1 WHERE id = ?", [$post['id']]);

// Fetch related articles
$relatedPosts = Database::fetchAll("
    SELECT bp.*, bc.name AS category_name 
    FROM blog_posts bp 
    JOIN blog_categories bc ON bp.category_id = bc.id 
    WHERE bp.category_id = ? AND bp.id != ? AND bp.is_published = 1 
    ORDER BY bp.published_at DESC 
    LIMIT 3
", [$post['category_id'], $post['id']]);

$pageTitle = $post['title'] . ' – Kamadenu Stories';
$metaDescription = $post['excerpt'] ?? $post['title'];

require_once __DIR__ . '/includes/header.php';
?>


<!-- Article Header Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="mx-auto max-w-800 text-center">
            <span class="badge bg-gold text-forest-dark fw-bold px-3 py-1 rounded-pill mb-3">
                <?= e($post['category_name']); ?>
            </span>
            <h1 class="display-5 font-serif text-forest-dark fw-bold mb-4"><?= e($post['title']); ?></h1>
            
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 text-muted small">
                <div><i class="bi bi-person-circle text-gold me-1"></i> By <strong><?= e($post['author_name'] ?? 'Kamadenu Editorial'); ?></strong></div>
                <div><i class="bi bi-calendar3 text-gold me-1"></i> <?= format_date($post['published_at']); ?></div>
                <div><i class="bi bi-eye text-gold me-1"></i> <?= $post['views_count'] + 1; ?> Views</div>
            </div>
        </div>
    </div>
</section>

<!-- Article Content Body -->
<section class="py-5 bg-cream-soft">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="card p-4 p-md-5 rounded-4 border-0 shadow-sm bg-white mb-5">
                    <!-- Lead Paragraph -->
                    <?php if (!empty($post['excerpt'])): ?>
                        <p class="fs-5 text-forest fw-semibold lh-base mb-4 pb-3 border-bottom fst-italic">
                            <?= e($post['excerpt']); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Rich Article Content -->
                    <div class="article-content text-muted lh-lg fs-6">
                        <?= nl2br(e($post['content'])); ?>
                    </div>

                    <!-- Social Sharing Links -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-4 mt-5 border-top gap-3">
                        <strong class="text-forest-dark"><i class="bi bi-share-fill text-gold me-2"></i> Share this inspiring story:</strong>
                        <div class="d-flex gap-2">
                            <a href="https://api.whatsapp.com/send?text=<?= urlencode($post['title'] . ' ' . BASE_URL . '/blog-details.php?slug=' . $post['slug']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']); ?>&url=<?= urlencode(BASE_URL . '/blog-details.php?slug=' . $post['slug']); ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                <i class="bi bi-twitter-x me-1"></i> X
                            </a>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Share Bar -->
                <?php
                    $blogUrl = BASE_URL . '/blog-details.php?slug=' . urlencode($post['slug']);
                    $blogShareMsg = "📖 *" . $post['title'] . "*\n\n" .
                                    ($post['excerpt'] ? ($post['excerpt'] . "\n\n") : '') .
                                    "Read this inspiring story from Kamadenu Goushala:\n" .
                                    $blogUrl;
                    $waShareUrl = "https://api.whatsapp.com/send?text=" . rawurlencode($blogShareMsg);
                ?>
                <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center mb-4 shadow-xs">
                    <span class="small fw-bold text-forest-dark"><i class="bi bi-share-fill text-gold me-2"></i> Share this inspiring story:</span>
                    <a href="<?= e($waShareUrl); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold">
                        <i class="bi bi-whatsapp me-1"></i> Share on WhatsApp
                    </a>
                </div>

                <!-- Call to Action Banner -->
                <div class="p-4 p-md-5 rounded-4 bg-forest-dark text-white text-center mb-5">
                    <span class="badge bg-gold text-forest-dark px-3 py-1 rounded-pill mb-2 fw-bold">Support Rescue Care</span>
                    <h3 class="font-serif text-cream mb-2">Help Us Save More Innocent Lives</h3>
                    <p class="text-cream opacity-80 mx-auto max-w-600 small mb-4">
                        Every rescue mission and recovery is made possible by your kind 80G tax-deductible contributions.
                    </p>
                    <a href="<?= BASE_URL; ?>/donate.php" class="btn btn-gold btn-lg rounded-pill px-4 shadow-gold">
                        <i class="bi bi-heart-fill me-1"></i> Make an 80G Seva Contribution
                    </a>
                </div>

                <!-- Related Stories Grid -->
                <?php if (!empty($relatedPosts)): ?>
                <div class="mb-4">
                    <h3 class="h4 font-serif text-forest-dark mb-3"><i class="bi bi-journal-bookmark text-gold me-2"></i> Related Stories</h3>
                    <div class="row g-3">
                        <?php foreach ($relatedPosts as $rel): ?>
                        <div class="col-md-4">
                            <div class="heritage-card h-100 p-3">
                                <span class="badge bg-gold-subtle text-gold-dark small mb-2"><?= e($rel['category_name']); ?></span>
                                <h4 class="h6 font-serif text-forest-dark mb-2"><?= e($rel['title']); ?></h4>
                                <a href="<?= BASE_URL; ?>/blog-details.php?slug=<?= e($rel['slug']); ?>" class="small text-forest fw-semibold">Read Story <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
