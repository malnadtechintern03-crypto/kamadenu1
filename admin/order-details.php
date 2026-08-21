<?php
/**
 * Kamadenu Goushala Platform - Admin Order Details View
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role(['super_admin', 'admin', 'manager']);

$currentUser = get_logged_in_user();

$orderId = (int)($_GET['id'] ?? 0);
$order = Database::fetchOne("
    SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.landmark,
           p.gateway, p.transaction_id
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    LEFT JOIN addresses a ON o.shipping_address_id = a.id 
    LEFT JOIN payments p ON p.reference_type = 'order' AND p.reference_id = o.id 
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    set_flash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

$pageTitle = 'Order ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';

$items = Database::fetchAll("
    SELECT oi.*, p.sku, p.unit 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
", [$orderId]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 font-serif text-forest-dark mb-0">Order Reference: <?= e($order['order_number']); ?></h2>
        <small class="text-muted">Placed on <?= format_date($order['created_at']); ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
        <a href="<?= BASE_URL; ?>/order-confirmation.php?order=<?= e($order['order_number']); ?>" target="_blank" class="btn btn-gold btn-sm rounded-pill px-3">
            <i class="bi bi-printer me-1"></i> Printable Invoice
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Customer Info -->
    <div class="col-md-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
            <h3 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-person-check text-gold me-2"></i> Customer Details</h3>
            <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
                <li><strong>Full Name:</strong> <span class="text-forest-dark"><?= e($order['customer_name']); ?></span></li>
                <li><strong>Email:</strong> <?= e($order['customer_email']); ?></li>
                <li><strong>Phone:</strong> <?= e($order['customer_phone']); ?></li>
                <?php if (!empty($order['customer_notes'])): ?>
                    <li class="p-2 bg-cream-soft rounded border mt-2"><strong>Notes:</strong> <?= e($order['customer_notes']); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Shipping Address -->
    <div class="col-md-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
            <h3 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-geo-alt-fill text-gold me-2"></i> Courier Delivery Address</h3>
            <div class="small text-muted">
                <div><?= e($order['address_line1']); ?></div>
                <?php if (!empty($order['address_line2'])): ?><div><?= e($order['address_line2']); ?></div><?php endif; ?>
                <div><?= e($order['city']); ?>, <?= e($order['state']); ?> - <strong><?= e($order['pincode']); ?></strong></div>
                <?php if (!empty($order['landmark'])): ?><div class="text-muted extra-small">Landmark: <?= e($order['landmark']); ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <h3 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-box-seam text-gold me-2"></i> Ordered Products</h3>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 small">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Item Description</th>
                    <th>SKU</th>
                    <th>Unit</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td><strong><?= e($it['product_name']); ?></strong></td>
                    <td class="font-monospace text-muted"><?= e($it['sku'] ?? 'N/A'); ?></td>
                    <td><?= e($it['unit'] ?? 'Unit'); ?></td>
                    <td class="text-center fw-bold"><?= $it['quantity']; ?></td>
                    <td class="text-end"><?= format_inr($it['unit_price'], true); ?></td>
                    <td class="text-end font-serif fw-bold text-forest-dark"><?= format_inr($it['total_price'], true); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end fw-semibold">Subtotal:</td>
                    <td class="text-end font-serif fw-bold"><?= format_inr($order['subtotal'], true); ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-semibold">Courier Shipping:</td>
                    <td class="text-end font-serif fw-bold"><?= format_inr($order['shipping_charge'], true); ?></td>
                </tr>
                <tr class="bg-cream fw-bold">
                    <td colspan="5" class="text-end text-forest-dark fs-6">Grand Total Amount Paid:</td>
                    <td class="text-end font-serif text-forest-dark fs-5 fw-bold"><?= format_inr($order['total_amount'], true); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
