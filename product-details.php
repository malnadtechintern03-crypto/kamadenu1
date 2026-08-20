<?php
/**
 * Kamadenu Goushala Platform - Product Details
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$slug = sanitize_input($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

$product = Product::findBySlug($slug);
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-shop fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Product Not Found</h1>
            <p class="text-muted">The organic product you are looking for is currently unavailable in our catalog.</p>
            <a href="' . BASE_URL . '/products.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Products Store</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $product['name'] . ' – ' . $product['category_name'];
$metaDescription = $product['short_description'] ?? ($product['name'] . ' prepared organically at Kamadenu Goushala.');

require_once __DIR__ . '/includes/header.php';

$hasDiscount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
$effectivePrice = $hasDiscount ? (float)$product['discount_price'] : (float)$product['price'];
$saveAmount = $hasDiscount ? ($product['price'] - $product['discount_price']) : 0;
$inStock = (int)$product['stock_quantity'] > 0;
?>

<!-- Breadcrumb -->
<div class="bg-cream border-bottom py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/index.php" class="text-forest">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/products.php" class="text-forest">Organic Products</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>/products.php?category=<?= e($product['category_slug']); ?>" class="text-forest"><?= e($product['category_name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Main Product Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row g-5">
            
            <!-- Left Column: Product Visuals -->
            <div class="col-lg-6">
                <div class="card p-3 rounded-4 border-0 shadow-sm bg-cream mb-3">
                    <div class="position-relative rounded-4 overflow-hidden" style="height: 400px; background-color: var(--color-forest-dark);">
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-gold display-1 bg-forest-subtle">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <?php if ($hasDiscount): ?>
                            <span class="position-absolute top-0 start-0 m-3 badge bg-danger text-white fs-6">
                                SAVE <?= format_inr($saveAmount); ?>
                            </span>
                        <?php endif; ?>
                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                            SKU: <?= e($product['sku']); ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-cream-soft border small text-muted">
                    <i class="bi bi-truck text-forest fs-4 flex-shrink-0"></i>
                    <div>
                        <strong class="text-forest-dark d-block">Free Shipping Across India Above ₹ 999</strong>
                        <span>Packed in eco-friendly tamper-proof thermal glass containers.</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Pricing, Stepper & Add to Cart -->
            <div class="col-lg-6">
                <span class="badge bg-gold-subtle text-gold-dark px-3 py-1 rounded-pill mb-2 fw-bold">
                    <?= e($product['category_name']); ?>
                </span>
                
                <h1 class="h2 font-serif text-forest-dark fw-bold mb-3"><?= e($product['name']); ?></h1>
                
                <!-- Stock & Unit Status -->
                <div class="d-flex align-items-center gap-3 mb-3 small">
                    <span class="badge <?= $inStock ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-1">
                        <?= $inStock ? '<i class="bi bi-check-circle-fill me-1"></i> In Stock (' . $product['stock_quantity'] . ' Units Available)' : 'Out of Stock'; ?>
                    </span>
                    <span class="text-muted"><i class="bi bi-box-seam me-1"></i> Packaging Unit: <strong><?= e($product['unit']); ?></strong></span>
                </div>

                <!-- Price Block -->
                <div class="d-flex align-items-baseline gap-3 p-3 rounded-3 bg-cream-soft border border-warning border-opacity-50 mb-4">
                    <span class="display-6 font-serif text-forest-dark fw-bold"><?= format_inr($effectivePrice); ?></span>
                    <?php if ($hasDiscount): ?>
                        <span class="fs-5 text-decoration-line-through text-muted"><?= format_inr($product['price']); ?></span>
                        <span class="badge bg-danger rounded-pill">Save <?= format_inr($saveAmount); ?></span>
                    <?php endif; ?>
                    <span class="small text-muted ms-auto">Inclusive of all GST taxes</span>
                </div>

                <p class="lead text-muted mb-4 fs-6">
                    <?= e($product['short_description'] ?? $product['description']); ?>
                </p>

                <!-- Quantity Stepper & Add to Cart Action -->
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <div class="input-group" style="width: 140px;">
                        <button class="btn btn-outline-forest" type="button" id="btnQtyMinus"><i class="bi bi-dash"></i></button>
                        <input type="number" id="inputProductQty" class="form-control text-center fw-bold" value="1" min="1" max="<?= max(1, (int)$product['stock_quantity']); ?>">
                        <button class="btn btn-outline-forest" type="button" id="btnQtyPlus"><i class="bi bi-plus"></i></button>
                    </div>

                    <button type="button" class="btn btn-gold btn-lg rounded-pill px-4 flex-grow-1 shadow-gold fw-bold" id="btnAddToCartMain" <?= !$inStock ? 'disabled' : ''; ?>>
                        <i class="bi bi-cart-plus-fill me-2"></i> Add to Shopping Cart
                    </button>
                </div>

                <a href="<?= BASE_URL; ?>/cart.php" class="btn btn-outline-forest w-100 rounded-pill py-2">
                    <i class="bi bi-bag-check me-1"></i> View Cart & Checkout
                </a>

            </div>

        </div>
    </div>
</section>

<!-- Deep Vedic Description & Classical Method -->
<section class="py-5 bg-cream-soft border-top">
    <div class="container py-3">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="card p-4 p-md-5 rounded-4 border-0 shadow-sm bg-white">
                    <h3 class="h4 font-serif text-forest-dark mb-3"><i class="bi bi-stars text-gold me-2"></i> Authentic Vedic Preparation Method</h3>
                    <div class="text-muted lh-lg mb-4">
                        <?= nl2br(e($product['description'])); ?>
                    </div>

                    <div class="p-4 rounded-4 bg-cream border border-warning border-opacity-50">
                        <h4 class="h6 font-serif text-forest-dark mb-2"><i class="bi bi-heart-pulse-fill text-forest me-2"></i> Non-Profit Seva Pledge</h4>
                        <p class="small text-muted mb-0">
                            100% of the proceeds generated from the sale of this product directly fund the green fodder, medical surgeries, and shelter maintenance for our non-milking elderly resident cows.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Vedic Wisdom Sidebar Box -->
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-sm">
                    <h3 class="h5 font-serif text-gold mb-3"><i class="bi bi-flower1 me-2"></i> Classical Attributes</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 small text-cream opacity-90 mb-0">
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0"></i>
                            <div><strong>100% Indigenous A2 Beta-Casein:</strong> Free from A1 inflammatory mutants.</div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0"></i>
                            <div><strong>Clay Pot & Bilona Churned:</strong> Retains high micronutrient bioavailability.</div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0"></i>
                            <div><strong>Zero Preservatives or Chemicals:</strong> Pure, sacred and natural.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add To Cart JS with Quantity Stepper -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qtyInput = document.getElementById('inputProductQty');
    const btnMinus = document.getElementById('btnQtyMinus');
    const btnPlus = document.getElementById('btnQtyPlus');
    const btnAdd = document.getElementById('btnAddToCartMain');

    btnMinus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value, 10);
        if (val > 1) qtyInput.value = val - 1;
    });

    btnPlus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value, 10);
        let max = parseInt(qtyInput.getAttribute('max') || '99', 10);
        if (val < max) qtyInput.value = val + 1;
    });

    btnAdd.addEventListener('click', async function() {
        const originalHtml = this.innerHTML;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';
        this.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', '<?= $product['id']; ?>');
            formData.append('quantity', qtyInput.value);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const res = await fetch('<?= BASE_URL; ?>/ajax/products.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
