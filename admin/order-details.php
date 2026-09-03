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

// 1. Handle Order Confirmation Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_order') {
    verify_csrf_or_die();
    Database::execute("UPDATE orders SET order_status = 'confirmed', updated_at = NOW() WHERE id = ?", [$orderId]);
    set_flash('success', 'Order has been confirmed successfully.');
    header('Location: ' . BASE_URL . '/admin/order-details.php?id=' . $orderId);
    exit;
}

// 2. Handle Mark Payment Paid Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    verify_csrf_or_die();
    Database::execute("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?", [$orderId]);
    Database::execute("UPDATE payments SET status = 'captured' WHERE reference_type = 'order' AND reference_id = ?", [$orderId]);
    set_flash('success', 'Payment status marked as Paid.');
    header('Location: ' . BASE_URL . '/admin/order-details.php?id=' . $orderId);
    exit;
}

// 3. Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verify_csrf_or_die();
    $newStatus = sanitize_input($_POST['order_status'] ?? 'placed');
    Database::execute("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);
    set_flash('success', 'Order status updated to ' . ucfirst($newStatus) . '.');
    header('Location: ' . BASE_URL . '/admin/order-details.php?id=' . $orderId);
    exit;
}

// 4. Handle Courier Tracking Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tracking') {
    verify_csrf_or_die();
    $courierName = sanitize_input($_POST['courier_name'] ?? '');
    $trackingNumber = sanitize_input($_POST['tracking_number'] ?? '');
    Database::execute("UPDATE orders SET courier_name = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?", [$courierName, $trackingNumber, $orderId]);
    set_flash('success', 'Courier tracking information saved.');
    header('Location: ' . BASE_URL . '/admin/order-details.php?id=' . $orderId);
    exit;
}

$order = Database::fetchOne("
    SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.landmark,
           p.gateway, p.transaction_id, p.status AS payment_txn_status
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
    SELECT oi.*, p.sku, p.unit, p.main_image, p.slug 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
", [$orderId]);

$cleanCustomerPhone = preg_replace('/\D/', '', $order['customer_phone'] ?? '');
$gw = strtolower($order['gateway'] ?? 'upi');
$isCod = in_array($gw, ['cash', 'cod'], true);
$isPaid = ($order['payment_status'] === 'paid');

$statusClass = match($order['order_status']) {
    'confirmed'  => 'bg-success text-white',
    'delivered'  => 'bg-success text-white',
    'dispatched' => 'bg-info text-dark',
    'processing' => 'bg-primary text-white',
    'cancelled'  => 'bg-danger text-white',
    default      => 'bg-warning text-dark'
};
?>

<!-- Header Bar -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h4 font-serif text-forest-dark mb-0">Order: <?= e($order['order_number']); ?></h2>
            <span class="badge <?= $statusClass; ?> rounded-pill px-3 py-1 fw-bold"><?= ucfirst($order['order_status']); ?></span>
        </div>
        <small class="text-muted">Placed on <?= format_date($order['created_at']); ?> &bull; <?= count($items); ?> Items</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
        <a href="<?= BASE_URL; ?>/order-confirmation.php?order=<?= e($order['order_number']); ?>" target="_blank" class="btn btn-gold btn-sm rounded-pill px-3 shadow-xs">
            <i class="bi bi-printer me-1"></i> Printable Invoice
        </a>
    </div>
</div>

<!-- Order Confirmation Action Callout (If Awaiting Confirmation) -->
<?php if ($order['order_status'] === 'placed'): ?>
<div class="card p-4 rounded-4 border-warning border-opacity-50 bg-white shadow-sm mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon stat-icon-gold" style="width: 48px; height: 48px; font-size: 1.4rem;">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div>
                <h5 class="font-serif text-forest-dark mb-1 fw-bold">This Order Needs Administrator Confirmation</h5>
                <p class="text-muted small mb-0">Review the person and product details below and confirm order fulfillment.</p>
            </div>
        </div>
        <form method="POST" action="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $order['id']; ?>">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="confirm_order">
            <button type="submit" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-check2-circle me-1 fs-6"></i> Confirm Order Now
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Customer / Person Who Placed Order Info -->
    <div class="col-md-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 font-serif text-forest-dark mb-0">
                    <i class="bi bi-person-check-fill text-gold me-2"></i> Person Details (Customer)
                </h3>
            </div>
            <ul class="list-unstyled small text-muted mb-0 d-flex flex-column gap-2">
                <li>
                    <strong class="text-forest-dark">Full Name:</strong> 
                    <span class="fs-6 fw-bold text-forest-dark ms-1"><?= e($order['customer_name']); ?></span>
                </li>
                <li class="d-flex align-items-center gap-2">
                    <strong class="text-forest-dark">Mobile Phone:</strong> 
                    <a href="tel:<?= e($cleanCustomerPhone); ?>" class="text-forest fw-bold"><?= e($order['customer_phone']); ?></a>
                    <?php if (!empty($cleanCustomerPhone)): ?>
                        <a href="https://wa.me/<?= e($cleanCustomerPhone); ?>?text=<?= rawurlencode("Namaste " . $order['customer_name'] . ", regarding your order #" . $order['order_number'] . " from Kamadenu Goushala."); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm py-0 px-2 rounded-pill extra-small" title="Chat on WhatsApp">
                            <i class="bi bi-whatsapp me-1"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </li>
                <li>
                    <strong class="text-forest-dark">Email Address:</strong> 
                    <a href="mailto:<?= e($order['customer_email']); ?>" class="text-muted"><?= e($order['customer_email']); ?></a>
                </li>
                <li class="pt-2 border-top">
                    <strong class="text-forest-dark d-block mb-1"><i class="bi bi-geo-alt-fill text-gold me-1"></i> Delivery Address:</strong>
                    <div class="text-forest-dark">
                        <?= e($order['address_line1']); ?><br>
                        <?php if (!empty($order['address_line2'])): ?><?= e($order['address_line2']); ?><br><?php endif; ?>
                        <?= e($order['city']); ?>, <?= e($order['state']); ?> - <strong><?= e($order['pincode']); ?></strong>
                        <?php if (!empty($order['landmark'])): ?><div class="text-muted extra-small mt-1">Landmark: <?= e($order['landmark']); ?></div><?php endif; ?>
                    </div>
                </li>
                <?php if (!empty($order['customer_notes'])): ?>
                    <li class="p-2 bg-cream-soft rounded-3 border mt-2">
                        <strong class="text-forest-dark d-block extra-small">Customer Instructions:</strong> 
                        <span class="text-forest-dark"><?= e($order['customer_notes']); ?></span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Payment & Fulfillment Management -->
    <div class="col-md-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100 d-flex flex-column">
            <h3 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-credit-card-2-front text-gold me-2"></i> Payment & Order Fulfillment</h3>
            
            <div class="p-3 bg-cream-soft rounded-3 border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">Payment Mode:</span>
                    <?php if ($isCod): ?>
                        <span class="badge bg-warning text-dark border border-warning rounded-pill px-2 py-1"><i class="bi bi-truck me-1"></i> Cash on Delivery (COD)</span>
                    <?php elseif ($gw === 'upi'): ?>
                        <span class="badge bg-success text-white rounded-pill px-2 py-1"><i class="bi bi-qr-code-scan me-1"></i> Direct UPI</span>
                    <?php else: ?>
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1"><i class="bi bi-credit-card me-1"></i> Online Gateway</span>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small text-muted">Payment Status:</span>
                    <div>
                        <?php if ($isPaid): ?>
                            <span class="badge bg-success rounded-pill px-2 py-1"><i class="bi bi-check2"></i> Paid</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill px-2 py-1"><i class="bi bi-clock"></i> Pending (Collect on Delivery)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($order['transaction_id'])): ?>
                    <div class="d-flex justify-content-between align-items-center extra-small text-muted">
                        <span>Transaction / UTR:</span>
                        <span class="font-monospace fw-bold text-forest"><?= e($order['transaction_id']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!$isPaid): ?>
                    <form method="POST" action="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $order['id']; ?>" class="mt-2 text-end">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="mark_paid">
                        <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3 extra-small">
                            <i class="bi bi-check-lg me-1"></i> Mark Payment as Paid
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Status Changer -->
            <form method="POST" action="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $order['id']; ?>" class="mb-3">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update_status">
                <label class="form-label small fw-bold text-forest-dark mb-1">Update Order Status:</label>
                <div class="input-group">
                    <select name="order_status" class="form-select form-select-sm">
                        <option value="placed" <?= $order['order_status'] === 'placed' ? 'selected' : ''; ?>>Placed (Awaiting Confirmation)</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing & Packaging</option>
                        <option value="dispatched" <?= $order['order_status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched / In Transit</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-forest btn-sm px-3">Update</button>
                </div>
            </form>

            <!-- Courier Tracking Form -->
            <form method="POST" action="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $order['id']; ?>" class="mt-auto">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="update_tracking">
                <label class="form-label small fw-bold text-forest-dark mb-1">Courier Tracking Details:</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" name="courier_name" class="form-control form-control-sm" placeholder="e.g. DTDC, BlueDart" value="<?= e($order['courier_name'] ?? ''); ?>">
                    </div>
                    <div class="col-6">
                        <input type="text" name="tracking_number" class="form-control form-control-sm" placeholder="AWB / Tracking #" value="<?= e($order['tracking_number'] ?? ''); ?>">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-outline-forest btn-sm rounded-pill px-3 extra-small">Save Tracking</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ordered Products Details Table -->
<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h6 font-serif text-forest-dark mb-0">
            <i class="bi bi-box-seam text-gold me-2"></i> Ordered Products (<?= count($items); ?>)
        </h3>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 small">
            <thead class="bg-cream-soft text-forest-dark">
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Item Description</th>
                    <th>SKU</th>
                    <th>Packaging Unit</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): 
                    $itImg = image_url($it['main_image'] ?? null, 'products', 'placeholder-product.jpg');
                ?>
                <tr>
                    <td class="text-center">
                        <img src="<?= e($itImg); ?>" alt="<?= e($it['product_name']); ?>" class="rounded-2 object-fit-cover" style="width: 44px; height: 44px;">
                    </td>
                    <td>
                        <strong class="text-forest-dark"><?= e($it['product_name']); ?></strong>
                    </td>
                    <td class="font-monospace text-muted"><?= e($it['sku'] ?? 'N/A'); ?></td>
                    <td><?= e($it['unit'] ?? 'Unit'); ?></td>
                    <td class="text-center fw-bold fs-6 text-forest"><?= $it['quantity']; ?></td>
                    <td class="text-end"><?= format_inr($it['unit_price'], true); ?></td>
                    <td class="text-end font-serif fw-bold text-forest-dark"><?= format_inr($it['total_price'], true); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end fw-semibold">Subtotal:</td>
                    <td class="text-end font-serif fw-bold"><?= format_inr($order['subtotal'], true); ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-semibold">Courier Shipping:</td>
                    <td class="text-end font-serif fw-bold <?= $order['shipping_charge'] == 0 ? 'text-success' : ''; ?>">
                        <?= $order['shipping_charge'] == 0 ? 'FREE' : format_inr($order['shipping_charge'], true); ?>
                    </td>
                </tr>
                <tr class="bg-cream fw-bold">
                    <td colspan="6" class="text-end text-forest-dark fs-6">Grand Total:</td>
                    <td class="text-end font-serif text-forest-dark fs-5 fw-bold"><?= format_inr($order['total_amount'], true); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

