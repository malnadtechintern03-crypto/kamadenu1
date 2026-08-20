<?php
/**
 * Kamadenu Goushala Platform - Admin Orders Management
 */

declare(strict_types=1);

$pageTitle = 'Manage Customer Store Orders';

require_once __DIR__ . '/includes/header.php';

// Handle Quick Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verify_csrf_or_die();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize_input($_POST['order_status'] ?? 'placed');
    Database::execute("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);
    set_flash('success', 'Order status updated successfully.');
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

$orders = Database::fetchAll("
    SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS items_count
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE {$whereClause} 
    ORDER BY o.created_at DESC
", $params);
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Customer Store Orders (<?= count($orders); ?>)</h2>
            <small class="text-muted">Fulfill packaging, courier dispatch, and delivery status updates.</small>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= BASE_URL; ?>/admin/orders.php" class="btn btn-sm rounded-pill px-3 <?= empty($status) ? 'btn-forest' : 'btn-outline-forest'; ?>">All Orders</a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=placed" class="btn btn-sm rounded-pill px-3 <?= $status === 'placed' ? 'btn-forest' : 'btn-outline-forest'; ?>">Placed</a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=processing" class="btn btn-sm rounded-pill px-3 <?= $status === 'processing' ? 'btn-forest' : 'btn-outline-forest'; ?>">Processing</a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=dispatched" class="btn btn-sm rounded-pill px-3 <?= $status === 'dispatched' ? 'btn-forest' : 'btn-outline-forest'; ?>">Dispatched</a>
        <a href="<?= BASE_URL; ?>/admin/orders.php?status=delivered" class="btn btn-sm rounded-pill px-3 <?= $status === 'delivered' ? 'btn-forest' : 'btn-outline-forest'; ?>">Delivered</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Order Reference</th>
                    <th>Date</th>
                    <th>Customer Name & Contact</th>
                    <th>Items</th>
                    <th>Total (INR)</th>
                    <th>Current Status</th>
                    <th class="text-end">Update / View</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): 
                        $statusClass = match($o['order_status']) {
                            'delivered'  => 'bg-success',
                            'dispatched' => 'bg-info text-dark',
                            'processing' => 'bg-warning text-dark',
                            default      => 'bg-secondary'
                        };
                    ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="fw-bold font-monospace text-forest text-decoration-none">
                                <?= e($o['order_number']); ?>
                            </a>
                        </td>
                        <td class="text-nowrap text-muted"><?= format_date($o['created_at']); ?></td>
                        <td>
                            <strong><?= e($o['customer_name']); ?></strong>
                            <div class="text-muted extra-small"><?= e($o['customer_email']); ?> &bull; <?= e($o['customer_phone']); ?></div>
                        </td>
                        <td><?= $o['items_count']; ?> Products</td>
                        <td class="font-serif text-forest-dark fw-bold"><?= format_inr($o['total_amount'], true); ?></td>
                        <td><span class="badge <?= $statusClass; ?> rounded-pill"><?= ucfirst($o['order_status']); ?></span></td>
                        <td class="text-end">
                            <form method="POST" action="<?= BASE_URL; ?>/admin/orders.php" class="d-inline-flex gap-1">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
                                <select name="order_status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="placed" <?= $o['order_status'] === 'placed' ? 'selected' : ''; ?>>Placed</option>
                                    <option value="processing" <?= $o['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="dispatched" <?= $o['order_status'] === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                    <option value="delivered" <?= $o['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?= $o['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-outline-forest btn-sm py-0 px-2" title="Save Status">Save</button>
                                <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="btn btn-gold btn-sm py-0 px-2" title="View Full Invoice"><i class="bi bi-eye"></i></a>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
