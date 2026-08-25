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


<!-- Main Product Section -->
<section class="py-5 bg-white">
    <div class="container py-3">
        <div class="row g-5">
            
            <!-- Left Column: Product Visuals -->
            <div class="col-lg-6">
                <div class="card p-3 rounded-4 border-0 shadow-sm bg-cream mb-3">
                    <?php
                    $mainImg = image_url($product['main_image'] ?? null, 'products', 'placeholder-product.jpg');
                    ?>
                    <div class="position-relative rounded-4 overflow-hidden" style="height: 400px; background-color: var(--color-forest-dark);">
                        <img src="<?= e($mainImg); ?>" alt="<?= e($product['name']); ?>" id="mainProductImg" class="w-100 h-100 object-fit-cover d-block" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-product.jpg';">
                        <?php if ($hasDiscount): ?>
                            <span class="position-absolute top-0 start-0 m-3 badge bg-danger text-white fs-6">
                                SAVE <?= format_inr($saveAmount); ?>
                            </span>
                        <?php endif; ?>
                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                            SKU: <?= e($product['sku']); ?>
                        </span>
                    </div>

                    <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="d-flex gap-2 mt-3 overflow-auto pb-1">
                        <?php foreach ($product['images'] as $imgItem): 
                            $thumbUrl = image_url($imgItem['image_path'], 'products', 'placeholder-product.jpg');
                        ?>
                        <button type="button" class="btn p-0 border rounded-3 overflow-hidden flex-shrink-0" style="width: 70px; height: 70px;" onclick="document.getElementById('mainProductImg').src='<?= e($thumbUrl); ?>'">
                            <img src="<?= e($thumbUrl); ?>" class="w-100 h-100 object-fit-cover" alt="Thumbnail">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
                
                <!-- Stock, Unit & Order Placed Status -->
                <?php
                $detailOrderCount = (int)($product['total_orders_count'] ?? 0);
                $detailOrderedQty = (int)($product['total_ordered_qty'] ?? 0);
                $detailHasOrders = $detailOrderCount > 0;
                ?>
                <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-3 small">
                    <span class="badge <?= $inStock ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-1">
                        <?= $inStock ? '<i class="bi bi-check-circle-fill me-1"></i> In Stock (' . $product['stock_quantity'] . ' Units Available)' : 'Out of Stock'; ?>
                    </span>
                    
                    <?php if ($detailHasOrders): ?>
                        <span class="badge bg-gold text-forest-dark rounded-pill px-3 py-1 fw-bold border border-warning border-opacity-50">
                            <i class="bi bi-bag-check-fill me-1"></i> <?= $detailOrderCount; ?> <?= $detailOrderCount === 1 ? 'Order Placed' : 'Orders Placed'; ?> (<?= $detailOrderedQty; ?> <?= e($product['unit']); ?> Sold)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted rounded-pill px-3 py-1 border">
                            <i class="bi bi-tag me-1"></i> Ready for 1st Order
                        </span>
                    <?php endif; ?>

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

                <!-- Quantity Stepper & Actions -->
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div class="input-group" style="width: 130px;">
                        <button class="btn btn-outline-forest" type="button" id="btnQtyMinus"><i class="bi bi-dash"></i></button>
                        <input type="number" id="inputProductQty" class="form-control text-center fw-bold" value="1" min="1" max="<?= max(1, (int)$product['stock_quantity']); ?>">
                        <button class="btn btn-outline-forest" type="button" id="btnQtyPlus"><i class="bi bi-plus"></i></button>
                    </div>

                    <button type="button" class="btn btn-gold btn-lg rounded-pill px-4 flex-grow-1 shadow-gold fw-bold" id="btnBuyNowMain" <?= !$inStock ? 'disabled' : ''; ?>>
                        <i class="bi bi-bag-check-fill me-2"></i> Buy Now
                    </button>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-forest rounded-pill py-2 flex-grow-1" id="btnAddToCartMain" <?= !$inStock ? 'disabled' : ''; ?>>
                        <i class="bi bi-cart-plus me-1"></i> Add to Shopping Cart
                    </button>
                    <a href="<?= BASE_URL; ?>/cart.php" class="btn btn-outline-secondary rounded-pill px-3 py-2" title="View Shopping Cart">
                        <i class="bi bi-cart3"></i>
                    </a>
                </div>

                <!-- Direct Order via WhatsApp Button with Preset Message -->
                <?php
                    $detailWaPhone = !empty($product['whatsapp_number']) ? $product['whatsapp_number'] : get_primary_whatsapp_number();
                    $cleanDetailWaPhone = preg_replace('/\D/', '', $detailWaPhone);
                    $detailPriceText = format_inr($effectivePrice);
                    $detailUrl = BASE_URL . '/product-details.php?slug=' . urlencode($product['slug']);
                    
                    if (!empty($product['whatsapp_message'])) {
                        $detailWaMsg = $product['whatsapp_message'];
                    } else {
                        $detailWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                      "I would like to order:\n" .
                                      "🌿 *Product:* " . $product['name'] . "\n" .
                                      "📦 *Unit:* " . $product['unit'] . "\n" .
                                      "💰 *Price:* " . $detailPriceText . "\n" .
                                      "🔗 *Product Link:* " . $detailUrl . "\n\n" .
                                      "Please confirm stock availability and share payment/delivery instructions.";
                    }
                    $detailWaUrl = "https://wa.me/" . $cleanDetailWaPhone . "?text=" . rawurlencode($detailWaMsg);
                ?>
                <div class="mb-4">
                    <a href="<?= e($detailWaUrl); ?>" id="btnWhatsAppOrderSingle" target="_blank" rel="noopener" class="btn btn-success btn-lg rounded-pill w-100 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-whatsapp fs-5"></i> Order via WhatsApp Direct
                    </a>
                    <small class="text-muted text-center d-block mt-1 extra-small">
                        <i class="bi bi-shield-check me-1"></i>Connects to sanctuary order desk: <strong><?= e($detailWaPhone); ?></strong>
                    </small>
                </div>

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

    const btnWaSingle = document.getElementById('btnWhatsAppOrderSingle');
    const baseWaUrl = 'https://wa.me/<?= $cleanDetailWaPhone; ?>';
    const prodName = <?= json_encode($product['name']); ?>;
    const prodUnit = <?= json_encode($product['unit']); ?>;
    const prodPrice = <?= json_encode($effectivePrice); ?>;
    const prodUrl = <?= json_encode($detailUrl); ?>;
    const customMsgTemplate = <?= json_encode($product['whatsapp_message'] ?? ''); ?>;

    function syncWaLink() {
        if (!btnWaSingle) return;
        const qty = parseInt(qtyInput.value, 10) || 1;
        const totalPrice = (prodPrice * qty).toLocaleString('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });
        
        let msg = '';
        if (customMsgTemplate && customMsgTemplate.trim().length > 0) {
            msg = customMsgTemplate + (qty > 1 ? `\n📦 *Order Quantity:* ${qty} Units (Total: ${totalPrice})` : '');
        } else {
            msg = `🙏 *Namaste Kamadenu Goushala!*\n\nI would like to order:\n🌿 *Product:* ${prodName}\n📦 *Quantity:* ${qty} × ${prodUnit}\n💰 *Total Price:* ${totalPrice}\n🔗 *Product Link:* ${prodUrl}\n\nPlease confirm availability and share payment/delivery instructions.`;
        }
        btnWaSingle.href = baseWaUrl + '?text=' + encodeURIComponent(msg);
    }

    btnMinus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value, 10);
        if (val > 1) {
            qtyInput.value = val - 1;
            syncWaLink();
        }
    });

    btnPlus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value, 10);
        let max = parseInt(qtyInput.getAttribute('max') || '99', 10);
        if (val < max) {
            qtyInput.value = val + 1;
            syncWaLink();
        }
    });

    qtyInput.addEventListener('input', syncWaLink);
    syncWaLink();

    const btnBuy = document.getElementById('btnBuyNowMain');
    if (btnBuy) {
        btnBuy.addEventListener('click', async function() {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
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
                    window.location.href = '<?= BASE_URL; ?>/checkout.php';
                } else {
                    showToast(data.message || 'Could not process order.', 'danger');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            } catch (err) {
                console.error(err);
                showToast('Failed to connect to checkout service.', 'danger');
                this.innerHTML = originalHtml;
                this.disabled = false;
            }
        });
    }

    if (btnAdd) {
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
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
