<?php
/**
 * Kamadenu Goushala Platform - Rescue Stories & Vedic Blog Hub
 */

declare(strict_types=1);

$pageTitle = 'Rescue Stories & Vedic Wisdom – Kamadenu Blog';
$metaDescription = 'Read inspiring cow rescue stories, ancient Vedic cattle science, and Ayurveda health guides published by Kamadenu Goushala.';

require_once __DIR__ . '/includes/header.php';

$categorySlug = sanitize_input($_GET['category'] ?? '');
$search = sanitize_input($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;

$where = ["bp.is_published = 1"];
$params = [];

if (!empty($categorySlug)) {
    $where[] = "bc.slug = ?";
    $params[] = $categorySlug;
}

if (!empty($search)) {
    $where[] = "(bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereClause = implode(' AND ', $where);

// Count
$countSql = "
    SELECT COUNT(*) 
    FROM blog_posts bp 
    JOIN blog_categories bc ON bp.category_id = bc.id 
    WHERE {$whereClause}
";
$totalPosts = (int)Database::fetchColumn($countSql, $params);
$totalPages = (int)ceil($totalPosts / $perPage);

// Fetch
$offset = max(0, ($page - 1) * $perPage);
$sql = "
    SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, u.name AS author_name 
    FROM blog_posts bp 
    JOIN blog_categories bc ON bp.category_id = bc.id 
    LEFT JOIN users u ON bp.author_id = u.id 
    WHERE {$whereClause} 
    ORDER BY bp.published_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
";
$posts = Database::fetchAll($sql, $params);

$categories = Database::fetchAll("
    SELECT bc.*, COUNT(bp.id) AS post_count 
    FROM blog_categories bc 
    LEFT JOIN blog_posts bp ON bc.id = bp.category_id AND bp.is_published = 1 
    GROUP BY bc.id, bc.name, bc.slug 
    ORDER BY bc.name ASC
");
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-journal-richtext me-1"></i> Chronicles of Compassion
        </span>
        <h1 class="page-hero-title">Rescue Stories & Vedic Blog</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Explore heartwarming journeys of cow recovery, ancient Vedic wisdom on Bos Indicus cattle, and holistic Ayurvedic science.
        </p>
    </div>
</section>

<!-- Blog Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        
        <!-- Category Filter Tabs & Search Bar -->
        <div class="row g-3 align-items-center justify-content-between mb-5">
            <div class="col-lg-8">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL; ?>/blog.php" class="btn btn-sm rounded-pill px-3 <?= empty($categorySlug) ? 'btn-forest' : 'btn-outline-forest'; ?>">
                        All Articles (<?= array_sum(array_column($categories, 'post_count')); ?>)
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= BASE_URL; ?>/blog.php?category=<?= e($cat['slug']); ?>" class="btn btn-sm rounded-pill px-3 <?= ($categorySlug === $cat['slug']) ? 'btn-forest' : 'btn-outline-forest'; ?>">
                            <?= e($cat['name']); ?> (<?= $cat['post_count']; ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <form method="GET" action="<?= BASE_URL; ?>/blog.php" class="d-flex gap-2">
                    <?php if (!empty($categorySlug)): ?>
                        <input type="hidden" name="category" value="<?= e($categorySlug); ?>">
                    <?php endif; ?>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search stories..." value="<?= e($search); ?>">
                    <button type="submit" class="btn btn-forest btn-sm px-3"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>

        <!-- Blog Posts Grid -->
        <?php if (empty($posts)): ?>
            <div class="card p-5 text-center rounded-4 border-0 bg-white shadow-sm">
                <i class="bi bi-journal-x fs-1 text-muted mb-2"></i>
                <h3 class="font-serif text-forest-dark">No Articles Found</h3>
                <p class="text-muted small mb-3">Try choosing another category or search with a different keyword.</p>
                <div>
                    <a href="<?= BASE_URL; ?>/blog.php" class="btn btn-forest rounded-pill px-4">View All Articles</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($posts as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="heritage-card h-100 d-flex flex-column">
                        <div class="blog-card-img">
                            <i class="bi bi-journal-text"></i>
                        </div>

                        <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-gold-subtle text-gold-dark small"><?= e($p['category_name']); ?></span>
                                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?= format_date($p['published_at']); ?></small>
                            </div>

                            <h3 class="h5 font-serif text-forest-dark mb-2"><?= e($p['title']); ?></h3>

                            <p class="small text-muted mb-4 flex-grow-1">
                                <?= e(mb_strimwidth($p['excerpt'], 0, 120, '...')); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                                <small class="text-muted"><i class="bi bi-person me-1"></i> <?= e($p['author_name'] ?? 'Sanctuary Team'); ?></small>
                                <a href="<?= BASE_URL; ?>/blog-details.php?slug=<?= e($p['slug']); ?>" class="btn btn-sm btn-outline-forest rounded-pill px-3">
                                    Read Story <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Blog Pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link text-forest-dark" href="?<?= http_build_query(array_filter(['category' => $categorySlug, 'q' => $search, 'page' => $page - 1])); ?>">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link <?= $i === $page ? 'bg-forest border-forest text-white' : 'text-forest-dark'; ?>" href="?<?= http_build_query(array_filter(['category' => $categorySlug, 'q' => $search, 'page' => $i])); ?>">
                                <?= $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link text-forest-dark" href="?<?= http_build_query(array_filter(['category' => $categorySlug, 'q' => $search, 'page' => $page + 1])); ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
