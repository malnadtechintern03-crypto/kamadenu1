<?php
/**
 * Kamadenu Goushala Platform - Financial Transparency & Accountability
 */

declare(strict_types=1);

$pageTitle = 'Financial Transparency & Accountability – Open Ledger';
$metaDescription = 'Explore Kamadenu Goushala financial transparency report. View all donations, verified operational expenses, cattle feed procurement, and medical allocations.';

require_once __DIR__ . '/includes/header.php';

try {
    $totalDonations = (float)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'success'");
    $totalExpenses = (float)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM expenses");
    $netBalance = $totalDonations - $totalExpenses;

    $categories = Database::fetchAll("
        SELECT ec.*, COALESCE(SUM(e.amount), 0) AS cat_total, COUNT(e.id) AS expense_count
        FROM expense_categories ec
        LEFT JOIN expenses e ON ec.id = e.category_id
        GROUP BY ec.id, ec.name, ec.slug, ec.icon_class
        ORDER BY cat_total DESC
    ");

    $recentExpenses = Database::fetchAll("
        SELECT e.*, ec.name AS category_name, ec.icon_class
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.id
        ORDER BY e.expense_date DESC, e.id DESC
        LIMIT 10
    ");
} catch (Throwable $t) {
    error_log('Transparency page error: ' . $t->getMessage());
    $totalDonations = 0; $totalExpenses = 0; $netBalance = 0;
    $categories = []; $recentExpenses = [];
}
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-shield-check me-1"></i> Pure Vedic Integrity
        </span>
        <h1 class="page-hero-title">Financial Transparency & Public Ledger</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            We believe that every sacred rupee donated for Gomata must be publicly accounted for. Here is our live audited financial summary and expense breakdown.
        </p>
    </div>
</section>

<!-- Financial KPI Summary Cards -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:rgba(31,96,69,0.15);color:var(--color-forest);">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-number fs-3"><?= format_inr($totalDonations, true); ?></div>
                        <p class="stat-label text-forest">Total Seva Donations Received</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:rgba(214,154,58,0.15);color:var(--color-gold-dark);">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="stat-number fs-3"><?= format_inr($totalExpenses, true); ?></div>
                        <p class="stat-label">Total Verified Expenses</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon-wrapper" style="background:rgba(139,94,60,0.15);color:var(--color-earth);">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div>
                        <div class="stat-number fs-3 <?= $netBalance >= 0 ? 'text-forest-dark' : 'text-danger'; ?>"><?= format_inr($netBalance, true); ?></div>
                        <p class="stat-label">Reserve Care Balance</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown Grid -->
        <div class="card p-4 p-md-5 rounded-4 border-0 shadow-md bg-white mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="section-tag"><i class="bi bi-pie-chart-fill text-gold"></i> Allocation Analysis</span>
                    <h2 class="h3 font-serif text-forest-dark mb-0">Expense Breakdown by Operational Category</h2>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($categories as $cat): 
                    $percent = ($totalExpenses > 0) ? round(($cat['cat_total'] / $totalExpenses) * 100, 1) : 0;
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="p-3 rounded-3 bg-cream-soft border h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= e($cat['icon_class']); ?> text-forest fs-4"></i>
                            <strong class="text-forest-dark small"><?= e($cat['name']); ?></strong>
                        </div>
                        <div>
                            <div class="fs-5 font-serif text-forest-dark fw-bold mb-1"><?= format_inr($cat['cat_total']); ?></div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><?= $cat['expense_count']; ?> Invoices</span>
                                <span><strong><?= $percent; ?>%</strong> of total</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-forest" role="progressbar" style="width: <?= $percent; ?>%;" aria-valuenow="<?= $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Verified Recent Expenses Ledger Table -->
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white">
            <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-journal-check text-gold me-2"></i> Recent Verified Expense Records</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                        <?php if (empty($recentExpenses)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No expense records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentExpenses as $exp): ?>
                            <tr>
                                <td class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i> <?= format_date($exp['expense_date']); ?></td>
                                <td>
                                    <span class="badge bg-cream text-forest-dark border">
                                        <i class="bi <?= e($exp['icon_class']); ?> me-1"></i> <?= e($exp['category_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-forest-dark"><?= e($exp['title']); ?></strong>
                                    <?php if (!empty($exp['description'])): ?>
                                        <small class="d-block text-muted"><?= e($exp['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($exp['vendor_name'] ?? 'Direct Sanctuary Procurement'); ?></td>
                                <td class="text-end font-serif text-forest-dark fw-bold"><?= format_inr($exp['amount'], true); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
