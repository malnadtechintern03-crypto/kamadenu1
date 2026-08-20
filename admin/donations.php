<?php
/**
 * Kamadenu Goushala Platform - Admin 80G Donations Ledger
 */

declare(strict_types=1);

$pageTitle = 'Master 80G Donations & Seva Ledger';

require_once __DIR__ . '/includes/header.php';

$search = sanitize_input($_GET['q'] ?? '');
$status = sanitize_input($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(d.donation_number LIKE ? OR d.donor_name LIKE ? OR d.donor_email LIKE ? OR d.donor_pan LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (!empty($status)) {
    $where[] = "d.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

$donations = Database::fetchAll("
    SELECT d.*, c.name AS cow_name, r.receipt_number 
    FROM donations d 
    LEFT JOIN cows c ON d.cow_id = c.id 
    LEFT JOIN receipts r ON r.reference_type = 'donation' AND r.reference_id = d.id 
    WHERE {$whereClause} 
    ORDER BY d.created_at DESC
", $params);
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">80G Donation Transactions (<?= count($donations); ?>)</h2>
            <small class="text-muted">Section 80G tax exemption ledger with verified donor PAN details.</small>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="<?= BASE_URL; ?>/admin/donations.php" class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by donor name, PAN, email, or donation ID..." value="<?= e($search); ?>">
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="success" <?= ($status === 'success') ? 'selected' : ''; ?>>Success / Received</option>
                <option value="pending" <?= ($status === 'pending') ? 'selected' : ''; ?>>Pending Verification</option>
                <option value="failed" <?= ($status === 'failed') ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Donation #</th>
                    <th>Date</th>
                    <th>Donor Name & PAN</th>
                    <th>Purpose / Cow</th>
                    <th>Status</th>
                    <th class="text-end">Amount (INR)</th>
                    <th class="text-center">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($donations)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No donation records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($donations as $d): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-forest-dark"><?= e($d['donation_number']); ?></td>
                        <td class="text-nowrap text-muted"><?= format_date($d['created_at']); ?></td>
                        <td>
                            <strong><?= e($d['donor_name']); ?></strong>
                            <div class="small text-muted font-monospace">PAN: <?= e($d['donor_pan'] ?? 'N/A'); ?></div>
                            <small class="text-muted"><?= e($d['donor_email']); ?> &bull; <?= e($d['donor_phone']); ?></small>
                        </td>
                        <td>
                            <?= e($d['purpose']); ?>
                            <?php if (!empty($d['cow_name'])): ?>
                                <span class="badge bg-gold-subtle text-gold-dark d-block mt-1">Dedicated to <?= e($d['cow_name']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($d['status'] === 'success'): ?>
                                <span class="badge bg-success rounded-pill">Success</span>
                            <?php elseif ($d['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill"><?= ucfirst($d['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end font-serif text-forest-dark fw-bold fs-6"><?= format_inr($d['amount'], true); ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL; ?>/receipt.php?num=<?= e($d['donation_number']); ?>" target="_blank" class="btn btn-outline-forest btn-sm rounded-pill px-3 py-1" title="View 80G Tax Receipt">
                                <i class="bi bi-receipt me-1"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
