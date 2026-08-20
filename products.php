<?php
/**
 * Kamadenu Goushala Platform - Organic Vedic A2 Products Store
 */

declare(strict_types=1);

$pageTitle = 'Organic Vedic Products – A2 Gir Cow Ghee, Panchagavya & Arka';
$metaDescription = 'Buy pure Vedic A2 Bilona Gir Cow Ghee, classical Panchagavya Ghrita, distilled Gomutra Arka, and organic cow dung dhoop cups. 100% pure & non-profit.';

require_once __DIR__ . '/includes/header.php';

$filters = [
    'category' => sanitize_input($_GET['category'] ?? ''),
    'search'   => sanitize_input($_GET['q'] ?? ''),
    'sort'     => sanitize_input($_GET['sort'] ?? 'featured')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;

$productData = Product::getAll($filters, $page, $perPage);
$products = $productData['items'];
$totalProducts = $productData['total'];
$totalPages = $productData['total_pages'];

$categories = Product::getCategories();
$hasActiveFilters = !empty($filters['category']) || !empty($filters['search']);

$cart = Order::getCart();
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-flower1 me-1"></i> Authentic Vedic Formulations
        </span>
        <h1 class="page-hero-title">Organic Goushala Products</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Handcrafted following ancient Caraka Samhita guidelines. 100% of proceeds support the feed and medical care of rescued non-milking cows.
        </p>
    </div>
</section>

<!-- Products Store Catalog Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        
        <!-- Category Filter Tabs & Cart Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <!-- Filter Pills -->
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL; ?>/products.php" class="btn btn-sm rounded-pill px-3 <?= empty($filters['category']) ? 'btn-forest' : 'btn-outline-forest'; ?>">
                    All Products (<?= array_sum(array_column($categories, 'product_count')); ?>)
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= BASE_URL; ?>/products.php?category=<?= e($cat['slug']); ?>" class="btn btn-sm rounded-pill px-3 <?= ($filters['category'] === $cat['slug']) ? 'btn-forest' : 'btn-outline-forest'; ?>">
                        <?= e($cat['name']); ?> (<?= $cat['product_count']; ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Cart Trigger Link -->
            <a href="<?= BASE_URL; ?>/cart.php" class="btn btn-gold btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-2 shadow-xs">
                <i class="bi bi-cart3 fs-6"></i>
                <span class="fw-bold">My Cart:</span>
                <span class="badge bg-forest text-white rounded-pill" id="cartBadgeCount"><?= $cart['count']; ?></span>
                <span class="small fw-bold"><?= format_inr($cart['subtotal']); ?></span>
            </a>
        </div>

        <!-- Search and Sorting Bar -->
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-4">
            <div class="row g-3 align-items-center justify-content-between">
                <div class="col-md-5">
                    <form method="GET" action="<?= BASE_URL; ?>/products.php" class="d-flex gap-2">
                        <?php if (!empty($filters['category'])): ?>
                            <input type="hidden" name="category" value="<?= e($filters['category']); ?>">
                        <?php endif; ?>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, SKU..." value="<?= e($filters['search']); ?>">
                        <button type="submit" class="btn btn-forest btn-sm px-3"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end gap-2">
                    <span class="small text-muted text-nowrap">Sort By:</span>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="location = this.value;">
                        <?php
                        $buildSortUrl = function($sortKey) use ($filters) {
                            $q = $filters;
                            $q['sort'] = $sortKey;
                            return BASE_URL . '/products.php?' . http_build_query(array_filter($q));
                        };
                        ?>
                        <option value="<?= $buildSortUrl('featured'); ?>" <?= $filters['sort'] === 'featured' ? 'selected' : ''; ?>>Featured First</option>
                        <option value="<?= $buildSortUrl('price_low'); ?>" <?= $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="<?= $buildSortUrl('price_high'); ?>" <?= $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="<?= $buildSortUrl('name_asc'); ?>" <?= $filters['sort'] === 'name_asc' ? 'selected' : ''; ?>>Name (A to Z)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="card p-5 text-center rounded-4 border-0 bg-white shadow-sm">
                <i class="bi bi-shop fs-1 text-muted mb-2"></i>
                <h3 class="font-serif text-forest-dark">No Products Found</h3>
                <p class="text-muted small mb-3">Try choosing another category or clearing your search keywords.</p>
                <div>
                    <a href="<?= BASE_URL; ?>/products.php" class="btn btn-forest rounded-pill px-4">View All Products</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $p): 
                    $hasDiscount = !empty($p['discount_price']) && $p['discount_price'] < $p['price'];
                    $effectivePrice = $hasDiscount ? (float)$p['discount_price'] : (float)$p['price'];
                    $saveAmount = $hasDiscount ? ($p['price'] - $p['discount_price']) : 0;
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="heritage-card h-100 d-flex flex-column">
                        <div class="position-relative" style="height: 200px; background-color: var(--color-forest-dark);">
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-gold fs-1 bg-forest-subtle">
                                <i class="bi bi-flower1"></i>
                            </div>
                            <?php if ($hasDiscount): ?>
                                <span class="position-absolute top-0 start-0 m-3 badge bg-danger text-white small">
                                    SAVE <?= format_inr($saveAmount); ?>
                                </span>
                            <?php endif; ?>
                            <span class="position-absolute top-0 end-0 m-3 badge bg-gold-subtle text-gold-dark small">
                                <?= e($p['unit'] ?? 'Unit'); ?>
                            </span>
                        </div>

                        <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                            <span class="text-gold-dark small text-uppercase tracking-wider fw-bold mb-1"><?= e($p['category_name']); ?></span>
                            <h3 class="h6 font-serif text-forest-dark mb-2 line-clamp-2"><?= e($p['name']); ?></h3>
                            
                            <p class="small text-muted mb-3 flex-grow-1">
                                <?= e(mb_strimwidth($p['short_description'] ?? $p['description'], 0, 85, '...')); ?>
                            </p>

                            <div class="pt-3 border-top mt-auto">
                                <div class="d-flex align-items-baseline gap-2 mb-3">
                                    <span class="fs-5 font-serif text-forest-dark fw-bold"><?= format_inr($effectivePrice); ?></span>
                                    <?php if ($hasDiscount): ?>
                                        <span class="text-decoration-line-through text-muted small"><?= format_inr($p['price']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-gold btn-sm rounded-pill flex-grow-1 btn-add-to-cart" data-product-id="<?= $p['id']; ?>">
                                        <i class="bi bi-cart-plus me-1"></i> Add to Cart
                                    </button>
                                    <a href="<?= BASE_URL; ?>/product-details.php?slug=<?= e($p['slug']); ?>" class="btn btn-outline-forest btn-sm rounded-pill px-3" title="View Product Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Product Directory Pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link text-forest-dark" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])); ?>">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                            <a class="page-link <?= $i === $page ? 'bg-forest border-forest text-white' : 'text-forest-dark'; ?>" href="?<?= http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                <?= $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link text-forest-dark" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])); ?>">
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

<!-- Add To Cart Client Handler Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';
            this.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('product_id', productId);
                formData.append('quantity', '1');
                formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                const res = await fetch('<?= BASE_URL; ?>/ajax/products.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    const badge = document.getElementById('cartBadgeCount');
                    if (badge) {
                        badge.textContent = data.cart.count;
                    }
                } else {
                    showToast(data.message || 'Could not add to cart.', 'danger');
                }
            } catch (err) {
                console.error(err);
                showToast('Failed to connect to cart service.', 'danger');
            } finally {
                this.innerHTML = originalHtml;
                this.disabled = false;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
