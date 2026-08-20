<?php
/**
 * Kamadenu Goushala Platform - Order Confirmation & Invoice
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$orderNumber = sanitize_input($_GET['order'] ?? '');
if (empty($orderNumber)) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

$order = Order::findByOrderNumber($orderNumber);
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-bag-x fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Order Not Found</h1>
            <p class="text-muted">The order reference number you entered could not be found in our records.</p>
            <a href="' . BASE_URL . '/products.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Products Store</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Order Confirmed – ' . $order['order_number'];
$metaDescription = 'Thank you for your order at Kamadenu Goushala. Your purchase supports sacred indigenous cow care.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-soft py-5">
    <div class="container text-center mb-4 d-print-none">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success text-white mb-2 shadow-xs">
            <i class="bi bi-check-circle-fill"></i>
            <span class="small fw-bold">Order Successfully Placed</span>
        </div>
        <h1 class="h3 font-serif text-forest-dark">Thank You for Your Order!</h1>
        <p class="text-muted small">We have received your order and our sanctuary packaging team is preparing your package.</p>
        <button class="btn btn-gold rounded-pill px-4 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print / Save Invoice PDF
        </button>
    </div>

    <!-- Official Printable Invoice Container -->
    <div class="container">
        <div class="card p-4 p-md-5 rounded-4 shadow-lg border bg-white mx-auto position-relative" style="max-width: 820px;">
            
            <!-- Invoice Header -->
            <div class="row align-items-center pb-3 border-bottom border-2">
                <div class="col-sm-8">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="navbar-brand-logo" style="width:40px;height:40px;font-size:1.2rem;">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <h3 class="font-serif text-forest-dark mb-0 fs-4">Kamadenu Goushala Store</h3>
                    </div>
                    <p class="small text-muted mb-0">
                        <?= e(get_setting('site_address', 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore - 562103')); ?>
                    </p>
                    <p class="small text-muted mb-0">
                        Email: orders@kamadenugoushala.org | Support: <?= e(get_setting('site_phone', '+91 98450 12345')); ?>
                    </p>
                </div>
                <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                    <span class="badge bg-success px-3 py-2 rounded-pill small fw-bold">PAYMENT CONFIRMED</span>
                    <div class="small text-muted mt-1">Invoice / Order Ref:</div>
                    <strong class="font-monospace fs-6 text-forest-dark"><?= e($order['order_number']); ?></strong>
                </div>
            </div>

            <!-- Customer & Shipping Meta -->
            <div class="row py-3 bg-cream-soft rounded-3 my-3 small">
                <div class="col-sm-6">
                    <div class="text-muted">Shipping & Recipient Details:</div>
                    <strong class="text-forest-dark fs-6 d-block"><?= e($order['customer_name']); ?></strong>
                    <div class="text-muted"><?= e($order['address_line1']); ?></div>
                    <?php if (!empty($order['address_line2'])): ?>
                        <div class="text-muted"><?= e($order['address_line2']); ?></div>
                    <?php endif; ?>
                    <div class="text-muted"><?= e($order['city']); ?>, <?= e($order['state']); ?> - <span class="font-monospace fw-bold"><?= e($order['pincode']); ?></span></div>
                    <div class="text-muted">Mobile: <?= e($order['customer_phone']); ?></div>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <div class="text-muted">Order Date:</div>
                    <strong class="text-forest-dark fs-6"><?= format_date($order['created_at']); ?></strong>
                    <div class="text-muted mt-2">Payment Gateway: <?= strtoupper($order['gateway'] ?? 'ONLINE'); ?></div>
                    <div class="text-muted">Transaction ID: <span class="font-monospace"><?= e($order['transaction_id'] ?? 'PAID-ON-ORDER'); ?></span></div>
                </div>
            </div>

            <!-- Order Items Table -->
            <div class="table-responsive my-3">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="bg-forest text-white">
                        <tr>
                            <th>Item Details</th>
                            <th class="text-center" style="width: 80px;">Qty</th>
                            <th class="text-end" style="width: 140px;">Unit Price</th>
                            <th class="text-end" style="width: 160px;">Total (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['product_name']); ?></strong>
                                <div class="small text-muted">SKU: <?= e($item['sku']); ?> &bull; Unit: <?= e($item['unit']); ?></div>
                            </td>
                            <td class="text-center fw-bold"><?= $item['quantity']; ?></td>
                            <td class="text-end"><?= format_inr($item['unit_price'], true); ?></td>
                            <td class="text-end font-serif text-forest-dark fw-bold"><?= format_inr($item['total_price'], true); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-semibold">Subtotal:</td>
                            <td class="text-end font-serif fw-bold"><?= format_inr($order['subtotal'], true); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end fw-semibold">Courier Shipping:</td>
                            <td class="text-end font-serif fw-bold <?= $order['shipping_charge'] == 0 ? 'text-success' : ''; ?>">
                                <?= $order['shipping_charge'] == 0 ? 'FREE' : format_inr($order['shipping_charge'], true); ?>
                            </td>
                        </tr>
                        <tr class="bg-cream fw-bold">
                            <td colspan="3" class="text-end text-forest-dark fs-5">Grand Total Paid:</td>
                            <td class="text-end fs-4 font-serif text-forest-dark fw-bold"><?= format_inr($order['total_amount'], true); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Delivery Progress Tracker -->
            <div class="p-4 bg-cream-soft rounded-4 border my-4 d-print-none">
                <h4 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-truck text-gold me-2"></i> Delivery Progress Tracker</h4>
                <div class="d-flex justify-content-between text-center small position-relative">
                    <div>
                        <div class="badge bg-forest text-white rounded-pill mb-1"><i class="bi bi-check"></i></div>
                        <div class="fw-bold text-forest-dark">Order Placed</div>
                        <span class="text-muted extra-small"><?= format_date($order['created_at']); ?></span>
                    </div>
                    <div>
                        <div class="badge bg-forest text-white rounded-pill mb-1"><i class="bi bi-box-seam"></i></div>
                        <div class="fw-bold text-forest-dark">Quality Packed</div>
                        <span class="text-muted extra-small">Thermal Safe</span>
                    </div>
                    <div>
                        <div class="badge bg-gold text-forest-dark rounded-pill mb-1"><i class="bi bi-truck"></i></div>
                        <div class="fw-bold text-forest-dark">Dispatched</div>
                        <span class="text-muted extra-small">In Transit</span>
                    </div>
                    <div>
                        <div class="badge bg-secondary text-white rounded-pill mb-1"><i class="bi bi-house-door"></i></div>
                        <div class="fw-bold text-muted">Delivered</div>
                        <span class="text-muted extra-small">3-5 Days</span>
                    </div>
                </div>
            </div>

            <!-- Invoice Footer -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top small text-muted">
                <div>
                    Thank you for choosing pure Vedic products from Kamadenu Goushala!
                </div>
                <div class="d-print-none">
                    <a href="<?= BASE_URL; ?>/products.php" class="btn btn-outline-forest btn-sm rounded-pill">
                        <i class="bi bi-arrow-left me-1"></i> Return to Store
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
