<?php
/**
 * Kamadenu Goushala Platform - Shopping Cart Page
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$cart = Order::getCart();

$pageTitle = 'Shopping Cart – ' . $cart['count'] . ' Items';
$metaDescription = 'Review your organic A2 Goushala cart items, update quantities, and proceed to secure checkout.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-bag-check-fill me-1"></i> Non-Profit Vedic Store
        </span>
        <h1 class="page-hero-title">Your Shopping Cart</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Every purchase supports the nourishment and medical treatments of rescued non-milking cows.
        </p>
    </div>
</section>

<!-- Cart Items Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <?php if (empty($cart['items'])): ?>
            <!-- Empty Cart State -->
            <div class="card p-5 text-center rounded-4 border-0 bg-white shadow-sm mx-auto" style="max-width: 600px;">
                <div class="display-1 text-muted mb-3"><i class="bi bi-cart-x"></i></div>
                <h2 class="font-serif text-forest-dark mb-2">Your Shopping Cart is Empty</h2>
                <p class="text-muted mb-4">You have not added any organic A2 Goushala products to your cart yet.</p>
                <div>
                    <a href="<?= BASE_URL; ?>/products.php" class="btn btn-gold rounded-pill px-5 py-3 fw-bold shadow-gold">
                        <i class="bi bi-shop me-1"></i> Explore Pure A2 Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-5">
                
                <!-- Items Table Column -->
                <div class="col-lg-8">
                    <div class="card p-4 rounded-4 border-0 shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 font-serif text-forest-dark mb-0"><i class="bi bi-cart3 text-gold me-2"></i> Cart Items (<?= $cart['count']; ?>)</h2>
                            <a href="<?= BASE_URL; ?>/products.php" class="small text-forest fw-semibold"><i class="bi bi-arrow-left me-1"></i> Add More Items</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="bg-cream-soft">
                                    <tr class="small text-muted">
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th style="width: 140px;">Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart['items'] as $item): ?>
                                    <tr id="cartRow-<?= $item['product_id']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-3 bg-forest-dark text-gold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 54px; height: 54px; font-size: 1.3rem;">
                                                    <i class="bi bi-flower1"></i>
                                                </div>
                                                <div>
                                                    <a href="<?= BASE_URL; ?>/product-details.php?slug=<?= e($item['slug']); ?>" class="fw-bold text-forest-dark text-decoration-none">
                                                        <?= e($item['name']); ?>
                                                    </a>
                                                    <div class="small text-muted">Unit: <?= e($item['unit']); ?> &bull; SKU: <?= e($item['sku']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold"><?= format_inr($item['effective_price']); ?></span>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <button class="btn btn-outline-secondary btn-cart-minus" type="button" data-id="<?= $item['product_id']; ?>">-</button>
                                                <input type="number" class="form-control text-center cart-qty-input" value="<?= $item['quantity']; ?>" min="1" max="<?= max(1, $item['stock_quantity']); ?>" data-id="<?= $item['product_id']; ?>" readonly>
                                                <button class="btn btn-outline-secondary btn-cart-plus" type="button" data-id="<?= $item['product_id']; ?>">+</button>
                                            </div>
                                        </td>
                                        <td class="text-end font-serif text-forest-dark fw-bold" id="itemSubtotal-<?= $item['product_id']; ?>">
                                            <?= format_inr($item['line_total']); ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-item" data-id="<?= $item['product_id']; ?>" title="Remove item">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- Order Summary Column -->
                <div class="col-lg-4">
                    <div class="card p-4 rounded-4 border-0 shadow-md bg-white sticky-top" style="top: 100px;">
                        <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-receipt text-gold me-2"></i> Order Summary</h3>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Items Subtotal:</span>
                            <span class="fw-bold" id="cartSummarySubtotal"><?= format_inr($cart['subtotal']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Estimated Shipping:</span>
                            <span class="fw-bold <?= $cart['shipping'] == 0 ? 'text-success' : ''; ?>" id="cartSummaryShipping">
                                <?= $cart['shipping'] == 0 ? 'FREE' : format_inr($cart['shipping']); ?>
                            </span>
                        </div>

                        <?php if ($cart['shipping'] > 0): ?>
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i> Add ₹ <?= (999 - $cart['subtotal']); ?> more for <strong>FREE Delivery</strong>!
                            </div>
                        <?php endif; ?>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-5 text-forest-dark fw-bold">Grand Total:</span>
                            <span class="display-6 font-serif text-forest-dark fw-bold" id="cartSummaryGrandTotal"><?= format_inr($cart['grand_total']); ?></span>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL; ?>/checkout.php" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold">
                                Proceed to Checkout <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline-forest btn-sm rounded-pill py-2">
                                Continue Shopping
                            </a>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-shield-check text-forest me-1"></i> Safe & Secure Indian Courier Packaging
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cart AJAX Update Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    async function updateCart(productId, qty) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', qty);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            const res = await fetch('<?= BASE_URL; ?>/ajax/products.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message, 'danger');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function removeItem(productId) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            const res = await fetch('<?= BASE_URL; ?>/ajax/products.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        } catch (e) {
            console.error(e);
        }
    }

    document.querySelectorAll('.btn-cart-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const input = document.querySelector(`.cart-qty-input[data-id="${id}"]`);
            let val = parseInt(input.value, 10);
            if (val > 1) {
                updateCart(id, val - 1);
            } else {
                if (confirm('Remove this item from your cart?')) {
                    removeItem(id);
                }
            }
        });
    });

    document.querySelectorAll('.btn-cart-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const input = document.querySelector(`.cart-qty-input[data-id="${id}"]`);
            let val = parseInt(input.value, 10);
            let max = parseInt(input.getAttribute('max') || '99', 10);
            if (val < max) {
                updateCart(id, val + 1);
            }
        });
    });

    document.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm('Remove this product from your cart?')) {
                removeItem(id);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
