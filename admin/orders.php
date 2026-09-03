<?php
/**
 * Kamadenu Goushala Platform - Admin Orders Management
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role(['super_admin', 'admin', 'manager']);

$currentUser = get_logged_in_user();

// 1. Handle Confirm Order Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_order') {
    verify_csrf_or_die();
    $orderId = (int)($_POST['order_id'] ?? 0);
    Database::execute("UPDATE orders SET order_status = 'confirmed', updated_at = NOW() WHERE id = ?", [$orderId]);
    set_flash('success', 'Order has been confirmed successfully.');
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// 2. Handle Mark Payment as Paid Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    verify_csrf_or_die();
    $orderId = (int)($_POST['order_id'] ?? 0);
    Database::execute("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE id = ?", [$orderId]);
    Database::execute("UPDATE payments SET status = 'captured' WHERE reference_type = 'order' AND reference_id = ?", [$orderId]);
    set_flash('success', 'Payment status marked as Paid.');
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// 3. Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verify_csrf_or_die();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize_input($_POST['order_status'] ?? 'placed');
    Database::execute("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);
    set_flash('success', 'Order status updated to ' . ucfirst($newStatus) . '.');
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

$status = sanitize_input($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];

if (!empty($status)) {
    $where[] = "o.order_status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// Fetch orders with customer details, shipping address, and payment information
$orders = Database::fetchAll("
    SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           a.address_line1, a.address_line2, a.city, a.state, a.pincode, a.landmark,
           p.gateway AS payment_gateway, p.transaction_id, p.status AS payment_txn_status,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS items_count
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    LEFT JOIN payments p ON p.reference_type = 'order' AND p.reference_id = o.id
    WHERE {$whereClause} 
    ORDER BY o.created_at DESC
", $params);

// Fetch items for all retrieved orders
$orderIds = array_column($orders, 'id');
$orderItemsMap = [];
if (!empty($orderIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $items = Database::fetchAll("
        SELECT oi.*, p.main_image, p.unit, p.slug, pc.name AS category_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE oi.order_id IN ({$inPlaceholders})
    ", $orderIds);
    foreach ($items as $it) {
        $orderItemsMap[$it['order_id']][] = $it;
    }
}

// Summary statistics
$totalOrdersCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders");
$placedOrdersCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE order_status = 'placed'");
$confirmedOrdersCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE order_status = 'confirmed'");
$totalRevenue = (float)Database::fetchColumn("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled'");

$pageTitle = 'Manage Customer Store Orders';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Orders Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted extra-small fw-bold text-uppercase">Total Orders</span>
                    <h3 class="h4 font-serif text-forest-dark mb-0 fw-bold"><?= $totalOrdersCount; ?></h3>
                </div>
                <div class="stat-icon stat-icon-forest" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-bag-check"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100 <?= $placedOrdersCount > 0 ? 'border border-warning' : ''; ?>">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted extra-small fw-bold text-uppercase">Awaiting Confirmation</span>
                    <h3 class="h4 font-serif text-warning mb-0 fw-bold"><?= $placedOrdersCount; ?></h3>
                </div>
                <div class="stat-icon stat-icon-gold" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted extra-small fw-bold text-uppercase">Confirmed & Active</span>
                    <h3 class="h4 font-serif text-success mb-0 fw-bold"><?= $confirmedOrdersCount; ?></h3>
                </div>
                <div class="stat-icon stat-icon-forest" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted extra-small fw-bold text-uppercase">Total Sales Revenue</span>
                    <h3 class="h4 font-serif text-forest-dark mb-0 fw-bold"><?= format_inr($totalRevenue); ?></h3>
                </div>
                <div class="stat-icon stat-icon-gold" style="width: 44px; height: 44px; font-size: 1.25rem;">
                    <i class="bi bi-currency-rupee"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Customer Store Orders (<?= count($orders); ?>)</h2>
            <small class="text-muted">Review person contact details, inspect ordered products, and confirm incoming customer orders.</small>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="btn btn-sm rounded-pill px-3 <?= empty($status) ? 'btn-forest' : 'btn-outline-forest'; ?>">
            All Orders (<?= $totalOrdersCount; ?>)
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=placed" class="btn btn-sm rounded-pill px-3 <?= $status === 'placed' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark'; ?>">
            <i class="bi bi-exclamation-circle me-1"></i> Placed (Needs Confirmation) (<?= $placedOrdersCount; ?>)
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=confirmed" class="btn btn-sm rounded-pill px-3 <?= $status === 'confirmed' ? 'btn-forest' : 'btn-outline-forest'; ?>">
            Confirmed (<?= $confirmedOrdersCount; ?>)
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=processing" class="btn btn-sm rounded-pill px-3 <?= $status === 'processing' ? 'btn-forest' : 'btn-outline-forest'; ?>">
            Processing
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=dispatched" class="btn btn-sm rounded-pill px-3 <?= $status === 'dispatched' ? 'btn-forest' : 'btn-outline-forest'; ?>">
            Dispatched / Shipped
        </a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=delivered" class="btn btn-sm rounded-pill px-3 <?= $status === 'delivered' ? 'btn-forest' : 'btn-outline-forest'; ?>">
            Delivered
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Order Reference</th>
                    <th>Customer / Person Details</th>
                    <th>Ordered Product Details</th>
                    <th>Payment</th>
                    <th>Total (INR)</th>
                    <th>Status & Confirmation</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No orders found matching this criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $statusClass = match($o['order_status']) {
                            'confirmed'  => 'bg-success text-white',
                            'delivered'  => 'bg-success text-white',
                            'dispatched' => 'bg-info text-dark',
                            'processing' => 'bg-primary text-white',
                            'cancelled'  => 'bg-danger text-white',
                            default      => 'bg-warning text-dark'
                        };
                        $gw = strtolower($o['payment_gateway'] ?? 'upi');
                        $isCod = in_array($gw, ['cash', 'cod'], true);
                        $isPaid = ($o['payment_status'] === 'paid');
                        $itemsList = $orderItemsMap[$o['id']] ?? [];
                        $cleanCustomerPhone = preg_replace('/\D/', '', $o['customer_phone'] ?? '');
                    ?>
                    <tr>
                        <!-- 1. Order Ref & Date -->
                        <td style="min-width: 140px;">
                            <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="fw-bold font-monospace text-forest text-decoration-none d-block">
                                <?= e($o['order_number']); ?>
                            </a>
                            <span class="text-muted extra-small d-block"><?= format_date($o['created_at']); ?></span>
                            <button type="button" class="btn btn-link p-0 text-gold extra-small text-decoration-none fw-bold mt-1" data-bs-toggle="modal" data-bs-target="#orderModal_<?= $o['id']; ?>">
                                <i class="bi bi-eye"></i> Quick View
                            </button>
                        </td>

                        <!-- 2. Person Who Placed Order Details -->
                        <td style="min-width: 220px;">
                            <div class="fw-bold text-forest-dark fs-6 mb-1"><?= e($o['customer_name']); ?></div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <a href="tel:<?= e($cleanCustomerPhone); ?>" class="text-decoration-none text-forest fw-semibold extra-small" title="Call Customer">
                                    <i class="bi bi-telephone-fill text-gold me-1"></i><?= e($o['customer_phone']); ?>
                                </a>
                                <?php if (!empty($cleanCustomerPhone)): ?>
                                    <a href="https://wa.me/<?= e($cleanCustomerPhone); ?>?text=<?= rawurlencode("Namaste " . $o['customer_name'] . ", regarding your order #" . $o['order_number'] . " from Kamadenu Goushala."); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm py-0 px-1 extra-small rounded-pill" title="WhatsApp Customer">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted extra-small mb-1 text-truncate" title="<?= e($o['customer_email']); ?>">
                                <i class="bi bi-envelope me-1"></i><?= e($o['customer_email']); ?>
                            </div>
                            <div class="text-muted extra-small">
                                <i class="bi bi-geo-alt me-1 text-gold"></i>
                                <?= e($o['address_line1'] ?? ''); ?>, <?= e($o['city'] ?? ''); ?> (<?= e($o['pincode'] ?? ''); ?>)
                            </div>
                        </td>

                        <!-- 3. Ordered Product Details -->
                        <td style="min-width: 260px;">
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($itemsList as $it): 
                                    $itImg = image_url($it['main_image'] ?? null, 'products', 'placeholder-product.jpg');
                                ?>
                                    <div class="d-flex align-items-center gap-2 p-1 rounded-2 bg-cream-soft border border-secondary border-opacity-10">
                                        <img src="<?= e($itImg); ?>" alt="<?= e($it['product_name']); ?>" class="rounded-2 object-fit-cover flex-shrink-0" style="width: 36px; height: 36px;">
                                        <div class="overflow-hidden flex-grow-1">
                                            <strong class="text-forest-dark text-truncate d-block extra-small mb-0"><?= e($it['product_name']); ?></strong>
                                            <div class="extra-small text-muted">
                                                <span class="fw-bold text-forest"><?= $it['quantity']; ?> &times;</span> <?= format_inr($it['unit_price']); ?> (<?= e($it['unit']); ?>)
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <!-- 4. Payment Details -->
                        <td style="min-width: 140px;">
                            <div class="mb-1">
                                <?php if ($isCod): ?>
                                    <span class="badge bg-warning text-dark border border-warning rounded-pill extra-small"><i class="bi bi-truck me-1"></i> COD</span>
                                <?php elseif ($gw === 'upi'): ?>
                                    <span class="badge bg-success text-white rounded-pill extra-small"><i class="bi bi-qr-code-scan me-1"></i> UPI</span>
                                <?php else: ?>
                                    <span class="badge bg-primary text-white rounded-pill extra-small"><i class="bi bi-credit-card me-1"></i> Online</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($isPaid): ?>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill extra-small"><i class="bi bi-check2"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-danger border border-danger border-opacity-25 rounded-pill extra-small"><i class="bi bi-clock"></i> Pending (COD)</span>
                                    <form method="POST" action="<?= BASE_URL; ?>/admin/orders.php" class="d-inline mt-1">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="action" value="mark_paid">
                                        <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                        <button type="submit" class="btn btn-outline-success btn-sm py-0 px-1 extra-small rounded-pill d-block mt-1" title="Mark as Paid">
                                            Mark Paid
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 5. Total Amount -->
                        <td class="font-serif text-forest-dark fw-bold fs-6" style="min-width: 110px;">
                            <?= format_inr($o['total_amount'], true); ?>
                        </td>

                        <!-- 6. Order Status & 1-Click Confirmation -->
                        <td style="min-width: 170px;">
                            <div class="mb-2">
                                <span class="badge <?= $statusClass; ?> rounded-pill px-2 py-1 extra-small fw-bold">
                                    <?php if ($o['order_status'] === 'placed'): ?>
                                        <i class="bi bi-clock me-1"></i> Placed (Awaiting Conf.)
                                    <?php elseif ($o['order_status'] === 'confirmed'): ?>
                                        <i class="bi bi-check-circle-fill me-1"></i> Confirmed
                                    <?php else: ?>
                                        <?= ucfirst($o['order_status']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <!-- 1-Click Admin Order Confirmation Button -->
                            <?php if ($o['order_status'] === 'placed'): ?>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/orders.php" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="confirm_order">
                                    <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm rounded-pill py-1 px-3 fw-bold shadow-xs extra-small" title="Confirm this customer order">
                                        <i class="bi bi-check2-circle me-1"></i> Confirm Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>

                        <!-- 7. Actions -->
                        <td class="text-end" style="min-width: 180px;">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                <!-- Status Changer Form -->
                                <form method="POST" action="<?= BASE_URL; ?>/admin/orders.php" class="d-inline-flex gap-1">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                    <select name="order_status" class="form-select form-select-sm extra-small py-0" style="width: auto;">
                                        <option value="placed" <?= $o['order_status'] === 'placed' ? 'selected' : ''; ?>>Placed</option>
                                        <option value="confirmed" <?= $o['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="processing" <?= $o['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="dispatched" <?= $o['order_status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                        <option value="delivered" <?= $o['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?= $o['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-forest btn-sm py-0 px-2 extra-small" title="Update Status">Save</button>
                                </form>

                                <!-- View Full Invoice Profile -->
                                <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="btn btn-gold btn-sm py-0 px-2 extra-small rounded-pill" title="View Full Order Invoice & Tracking">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal: Full Person & Ordered Product Inspection Modal -->
                    <div class="modal fade" id="orderModal_<?= $o['id']; ?>" tabindex="-1" aria-labelledby="orderModalLabel_<?= $o['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                            <div class="modal-content rounded-4 border-0 shadow-lg">
                                <div class="modal-header bg-forest text-white px-4 py-3 border-bottom border-warning border-opacity-25">
                                    <div>
                                        <h5 class="modal-title font-serif text-white mb-0" id="orderModalLabel_<?= $o['id']; ?>">
                                            Order Reference: <?= e($o['order_number']); ?>
                                        </h5>
                                        <small class="text-cream opacity-90 extra-small">Placed on <?= format_date($o['created_at']); ?></small>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4 bg-cream-soft">
                                    
                                    <!-- Person / Buyer Contact Details Card -->
                                    <div class="card p-3 rounded-4 bg-white border-0 shadow-xs mb-3">
                                        <h6 class="font-serif text-forest-dark fw-bold mb-2"><i class="bi bi-person-check-fill text-gold me-2"></i> Person Details (Customer)</h6>
                                        <div class="row g-2 small">
                                            <div class="col-sm-6">
                                                <span class="text-muted extra-small d-block">Full Name:</span>
                                                <strong class="text-forest-dark fs-6"><?= e($o['customer_name']); ?></strong>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted extra-small d-block">Mobile Phone:</span>
                                                <a href="tel:<?= e($cleanCustomerPhone); ?>" class="text-forest fw-bold"><?= e($o['customer_phone']); ?></a>
                                                <?php if (!empty($cleanCustomerPhone)): ?>
                                                    <a href="https://wa.me/<?= e($cleanCustomerPhone); ?>" target="_blank" class="btn btn-outline-success btn-sm py-0 px-2 extra-small rounded-pill ms-2"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted extra-small d-block">Email Address:</span>
                                                <span class="text-forest-dark"><?= e($o['customer_email']); ?></span>
                                            </div>
                                            <div class="col-sm-6">
                                                <span class="text-muted extra-small d-block">Delivery Address:</span>
                                                <span class="text-forest-dark"><?= e($o['address_line1']); ?>, <?= e($o['address_line2'] ?? ''); ?> <?= e($o['city']); ?>, <?= e($o['state']); ?> - <strong><?= e($o['pincode']); ?></strong></span>
                                            </div>
                                            <?php if (!empty($o['customer_notes'])): ?>
                                                <div class="col-12 mt-2 p-2 bg-cream rounded border">
                                                    <span class="text-muted extra-small fw-bold">Customer Notes:</span>
                                                    <div class="text-forest-dark"><?= e($o['customer_notes']); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Ordered Products Details Table -->
                                    <div class="card p-3 rounded-4 bg-white border-0 shadow-xs mb-3">
                                        <h6 class="font-serif text-forest-dark fw-bold mb-2"><i class="bi bi-box-seam-fill text-gold me-2"></i> Ordered Products</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle mb-0 extra-small">
                                                <thead class="bg-cream-soft">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Packaging</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Unit Price</th>
                                                        <th class="text-end">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($itemsList as $it): ?>
                                                    <tr>
                                                        <td><strong><?= e($it['product_name']); ?></strong></td>
                                                        <td><?= e($it['unit'] ?? 'Unit'); ?></td>
                                                        <td class="text-center fw-bold"><?= $it['quantity']; ?></td>
                                                        <td class="text-end"><?= format_inr($it['unit_price']); ?></td>
                                                        <td class="text-end font-serif fw-bold text-forest-dark"><?= format_inr($it['total_price']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-end fw-semibold">Subtotal:</td>
                                                        <td class="text-end fw-bold"><?= format_inr($o['subtotal']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-end fw-semibold">Courier Shipping:</td>
                                                        <td class="text-end fw-bold <?= $o['shipping_charge'] == 0 ? 'text-success' : ''; ?>">
                                                            <?= $o['shipping_charge'] == 0 ? 'FREE' : format_inr($o['shipping_charge']); ?>
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-cream fw-bold">
                                                        <td colspan="4" class="text-end text-forest-dark">Grand Total:</td>
                                                        <td class="text-end font-serif text-forest-dark fs-6"><?= format_inr($o['total_amount']); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer bg-white px-4 py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($o['order_status'] === 'placed'): ?>
                                            <form method="POST" action="<?= BASE_URL; ?>/admin/orders.php" class="d-inline">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="confirm_order">
                                                <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold shadow-xs">
                                                    <i class="bi bi-check2-circle me-1"></i> Confirm Order Now
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge <?= $statusClass; ?> rounded-pill px-3 py-1 fw-bold">Status: <?= ucfirst($o['order_status']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="btn btn-gold btn-sm rounded-pill px-3">
                                            <i class="bi bi-file-earmark-text me-1"></i> Full Invoice
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

