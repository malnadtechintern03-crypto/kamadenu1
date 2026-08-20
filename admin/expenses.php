<?php
/**
 * Kamadenu Goushala Platform - Admin Financial Expenses Module
 */

declare(strict_types=1);

$pageTitle = 'Operational Expenses & Procurement Log';

require_once __DIR__ . '/includes/header.php';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $catId = (int)($_POST['category_id'] ?? 0);
    $title = sanitize_input($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date = sanitize_input($_POST['expense_date'] ?? date('Y-m-d'));
    $vendor = sanitize_input($_POST['vendor_name'] ?? '');
    $desc = sanitize_input($_POST['description'] ?? '');

    if ($catId > 0 && !empty($title) && $amount > 0) {
        $currentUser = get_logged_in_user();
        Database::insert("
            INSERT INTO expenses (category_id, title, amount, expense_date, vendor_name, description, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ", [$catId, $title, $amount, $date, $vendor, $desc, $currentUser['id'] ?? null]);

        set_flash('success', 'Operational expense successfully recorded.');
        header('Location: ' . BASE_URL . '/admin/expenses.php');
        exit;
    } else {
        set_flash('error', 'Please fill in all mandatory expense fields.');
    }
}

$expenses = Database::fetchAll("
    SELECT e.*, ec.name AS category_name, ec.icon_class 
    FROM expenses e 
    JOIN expense_categories ec ON e.category_id = ec.id 
    ORDER BY e.expense_date DESC, e.id DESC
");

$categories = Database::fetchAll("SELECT * FROM expense_categories ORDER BY name ASC");
$totalExp = array_sum(array_column($expenses, 'amount'));
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Sanctuary Expenses Ledger (<?= count($expenses); ?>)</h2>
            <small class="text-muted">Total Recorded Operational Procurement: <strong><?= format_inr($totalExp, true); ?></strong></small>
        </div>
        <button type="button" class="btn btn-forest rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="bi bi-plus-circle me-1"></i> Log Verified Expense
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Expense Title / Purpose</th>
                    <th>Vendor / Supplier</th>
                    <th class="text-end">Amount (INR)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No expense records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i> <?= format_date($e['expense_date']); ?></td>
                        <td>
                            <span class="badge bg-cream text-forest-dark border">
                                <i class="bi <?= e($e['icon_class']); ?> me-1"></i> <?= e($e['category_name']); ?>
                            </span>
                        </td>
                        <td>
                            <strong class="text-forest-dark"><?= e($e['title']); ?></strong>
                            <?php if (!empty($e['description'])): ?>
                                <small class="text-muted d-block"><?= e($e['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($e['vendor_name'] ?? 'Direct Sanctuary Procurement'); ?></td>
                        <td class="text-end font-serif text-forest-dark fw-bold fs-6"><?= format_inr($e['amount'], true); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest text-white">
                <h5 class="modal-title font-serif"><i class="bi bi-receipt me-2"></i> Log Operational Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/expenses.php">
                <?= csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expense Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id']; ?>"><?= e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Amount in INR *</label>
                            <input type="number" name="amount" class="form-control" placeholder="15000" step="0.01" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Expense Title / Item Purpose *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Green Fodder Procurement - 10 Tons" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expense Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vendor / Supplier Name</label>
                            <input type="text" name="vendor_name" class="form-control" placeholder="e.g. Green Valley Agro Traders">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Invoice Description / Notes</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Invoice reference number, payment mode..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-forest rounded-pill px-4">Record Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
