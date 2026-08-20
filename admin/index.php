<?php
/**
 * Kamadenu Goushala Platform - Master Admin Dashboard
 */

declare(strict_types=1);

$pageTitle = 'Sanctuary Executive Dashboard';

require_once __DIR__ . '/includes/header.php';

// Fetch Live Statistics
$totalCows = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE status != 'deceased'");
$treatmentCows = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE health_status IN ('under_treatment', 'recovering', 'elderly_care') AND status != 'deceased'");
$adoptedCows = (int)Database::fetchColumn("SELECT COUNT(*) FROM cows WHERE status = 'adopted'");

$totalDonationsAmount = (float)Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'success'");
$totalDonationsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM donations WHERE status = 'success'");

$totalAdoptionsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM adoptions WHERE status = 'active'");

$totalStoreRevenue = (float)Database::fetchColumn("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'");
$totalOrdersCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM orders");

$unreadMessages = (int)Database::fetchColumn("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$lowStockProducts = Database::fetchAll("SELECT * FROM products WHERE stock_quantity <= 10 AND is_active = 1 LIMIT 5");

// Recent Transactions
$recentDonations = Database::fetchAll("
    SELECT d.*, c.name AS cow_name 
    FROM donations d 
    LEFT JOIN cows c ON d.cow_id = c.id 
    ORDER BY d.created_at DESC 
    LIMIT 5
");

$recentOrders = Database::fetchAll("
    SELECT o.*, c.name AS customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

$recentMessages = Database::fetchAll("
    SELECT * FROM contact_messages 
    ORDER BY created_at DESC 
    LIMIT 5
");

$dashboardCowImage = image_url('kamadhenu.jpg', 'cows', 'placeholder-cow.jpg');
?>

<!-- Sanctuary Overview Welcome Card with Kamadhenu Image -->
<div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-4 bg-forest-dark text-white position-relative">
    <div class="row g-0 align-items-center">
        <div class="col-md-4 col-lg-3 position-relative" style="min-height: 240px; height: 100%;">
            <img 
                src="<?= e($dashboardCowImage); ?>" 
                alt="Kamadhenu - Sanctuary Matriarch" 
                class="w-100 h-100 object-fit-cover position-absolute top-0 start-0"
                style="border-top-left-radius: var(--radius-lg); border-bottom-left-radius: var(--radius-lg);"
                onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';"
            >
            <div class="position-absolute top-0 start-0 m-3">
                <span class="badge bg-gold text-forest-dark fw-bold shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i> Sanctuary Matriarch
                </span>
            </div>
            <div class="position-absolute bottom-0 start-0 m-3">
                <span class="badge bg-black bg-opacity-75 text-white small">
                    Kamadhenu (ID: KG-2023-01)
                </span>
            </div>
        </div>
        <div class="col-md-8 col-lg-9 p-4 p-lg-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-gold-subtle text-gold small border border-warning border-opacity-25">
                    <i class="bi bi-shield-fill-check"></i>
                    <span>Live Sanctuary Operations Hub • Nandi Hills, Karnataka</span>
                </div>
                <span class="small text-white-50">
                    <i class="bi bi-calendar3 me-1"></i> <?= date('l, d F Y'); ?>
                </span>
            </div>
            <h2 class="h3 font-serif text-cream fw-bold mb-2">
                Welcome back, <?= e($currentUser['name'] ?? 'Administrator'); ?>
            </h2>
            <p class="text-white-50 mb-4 max-w-700">
                You are managing <strong><?= $totalCows; ?> resident indigenous cows</strong> across 6 sacred breeds, with <strong><?= $treatmentCows; ?> active clinical treatments</strong> and <strong><?= format_inr($totalDonationsAmount, true); ?></strong> in verified 80G philanthropic seva contributions.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL; ?>/admin/cow-edit.php" class="btn btn-gold btn-sm rounded-pill px-3 shadow-gold">
                    <i class="bi bi-plus-circle-fill me-1"></i> Register Rescued Cow
                </a>
                <a href="<?= BASE_URL; ?>/admin/medical.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-heart-pulse-fill me-1"></i> Record Medical Entry
                </a>
                <a href="<?= BASE_URL; ?>/admin/donations.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-receipt-cutoff me-1"></i> 80G Donations Ledger
                </a>
                <a href="<?= BASE_URL; ?>/index.php" target="_blank" class="btn btn-outline-gold btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Live Portal
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Shortcuts Bar -->
<div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-gold text-forest-dark fw-bold px-3 py-1 rounded-pill">Quick Actions</span>
            <span class="small text-muted">Direct management shortcuts:</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL; ?>/admin/cow-edit.php" class="btn btn-forest btn-sm rounded-pill">
                <i class="bi bi-plus-circle me-1"></i> Register New Cow
            </a>
            <a href="<?= BASE_URL; ?>/admin/medical.php" class="btn btn-outline-forest btn-sm rounded-pill">
                <i class="bi bi-heart-pulse me-1"></i> Add Medical Log
            </a>
            <a href="<?= BASE_URL; ?>/admin/products.php" class="btn btn-outline-forest btn-sm rounded-pill">
                <i class="bi bi-box-seam me-1"></i> Add Store Product
            </a>
            <a href="<?= BASE_URL; ?>/admin/expenses.php" class="btn btn-outline-forest btn-sm rounded-pill">
                <i class="bi bi-receipt me-1"></i> Log Expense
            </a>
        </div>
    </div>
</div>

<!-- Primary KPI Metrics Grid -->
<div class="row g-4 mb-4">
    
    <!-- KPI 1: Resident Cows -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrapper" style="background:rgba(31,96,69,0.15);color:var(--color-forest);">
                <i class="bi bi-flower1"></i>
            </div>
            <div>
                <div class="stat-number fs-3"><?= $totalCows; ?></div>
                <p class="stat-label mb-1">Protected Resident Cows</p>
                <small class="text-muted d-block"><?= $treatmentCows; ?> in Medical/Hospice &bull; <?= $adoptedCows; ?> Adopted</small>
            </div>
        </div>
    </div>

    <!-- KPI 2: Total 80G Donations -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrapper" style="background:rgba(214,154,58,0.15);color:var(--color-gold-dark);">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="stat-number fs-3"><?= format_inr($totalDonationsAmount, true); ?></div>
                <p class="stat-label mb-1">Total 80G Seva Donations</p>
                <small class="text-muted d-block"><?= $totalDonationsCount; ?> Verified Transactions</small>
            </div>
        </div>
    </div>

    <!-- KPI 3: Store Orders & Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrapper" style="background:rgba(139,94,60,0.15);color:var(--color-earth);">
                <i class="bi bi-bag-check"></i>
            </div>
            <div>
                <div class="stat-number fs-3"><?= format_inr($totalStoreRevenue, true); ?></div>
                <p class="stat-label mb-1">Organic Store Revenue</p>
                <small class="text-muted d-block"><?= $totalOrdersCount; ?> Total Customer Orders</small>
            </div>
        </div>
    </div>

    <!-- KPI 4: Active Adoptions & Messages -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrapper" style="background:rgba(18,55,42,0.15);color:var(--color-forest-dark);">
                <i class="bi bi-suit-heart"></i>
            </div>
            <div>
                <div class="stat-number fs-3"><?= $totalAdoptionsCount; ?></div>
                <p class="stat-label mb-1">Active Cow Guardians</p>
                <small class="text-muted d-block">
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $unreadMessages; ?> Unread Inquiries</span>
                    <?php else: ?>
                        0 Unread Inquiries
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>

</div>

<!-- Low Stock Alert Banner (If Applicable) -->
<?php if (!empty($lowStockProducts)): ?>
<div class="alert alert-warning rounded-4 border-0 shadow-xs mb-4 p-3">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
        <strong class="text-forest-dark">Low Stock Inventory Alert (Needs Production / Packaging):</strong>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($lowStockProducts as $lp): ?>
            <span class="badge bg-white text-forest-dark border shadow-xs p-2">
                <?= e($lp['name']); ?>: <strong><?= $lp['stock_quantity']; ?> left</strong>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Transactions Two-Column Grid -->
<div class="row g-4 mb-4">
    
    <!-- Left Column: Recent Donations -->
    <div class="col-lg-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 font-serif text-forest-dark mb-0"><i class="bi bi-cash text-gold me-2"></i> Recent 80G Donations</h2>
                <a href="<?= BASE_URL; ?>/admin/donations.php" class="small text-forest fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="bg-cream-soft">
                        <tr>
                            <th>Donor</th>
                            <th>Purpose</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDonations as $d): ?>
                        <tr>
                            <td>
                                <strong><?= e($d['donor_name']); ?></strong>
                                <small class="text-muted d-block"><?= format_date($d['created_at']); ?></small>
                            </td>
                            <td><?= e(mb_strimwidth($d['purpose'], 0, 25, '...')); ?></td>
                            <td class="text-end font-serif fw-bold text-forest-dark"><?= format_inr($d['amount']); ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL; ?>/receipt.php?num=<?= e($d['donation_number']); ?>" target="_blank" class="btn btn-outline-forest btn-xs rounded-pill px-2 py-0" title="View 80G Receipt">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Recent E-Store Orders -->
    <div class="col-lg-6">
        <div class="card p-4 rounded-4 border-0 shadow-sm bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 font-serif text-forest-dark mb-0"><i class="bi bi-bag-check text-gold me-2"></i> Recent Organic Store Orders</h2>
                <a href="<?= BASE_URL; ?>/admin/orders.php" class="small text-forest fw-semibold">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="bg-cream-soft">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): 
                            $statusClass = match($o['order_status']) {
                                'delivered'  => 'bg-success',
                                'dispatched' => 'bg-info text-dark',
                                'processing' => 'bg-warning text-dark',
                                default      => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL; ?>/admin/order-details.php?id=<?= $o['id']; ?>" class="fw-bold text-forest text-decoration-none">
                                    <?= e($o['order_number']); ?>
                                </a>
                            </td>
                            <td><?= e($o['customer_name']); ?></td>
                            <td><span class="badge <?= $statusClass; ?> rounded-pill small"><?= ucfirst($o['order_status']); ?></span></td>
                            <td class="text-end font-serif fw-bold text-forest-dark"><?= format_inr($o['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Recent Inquiries Section -->
<div class="card p-4 rounded-4 border-0 shadow-sm bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 font-serif text-forest-dark mb-0"><i class="bi bi-envelope text-gold me-2"></i> Recent Devotee Inquiries</h2>
        <a href="<?= BASE_URL; ?>/admin/messages.php" class="small text-forest fw-semibold">View All Messages <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-cream-soft">
                <tr>
                    <th>Date</th>
                    <th>Devotee Name</th>
                    <th>Email / Phone</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentMessages as $m): ?>
                <tr>
                    <td class="text-nowrap text-muted"><?= format_date($m['created_at']); ?></td>
                    <td><strong><?= e($m['name']); ?></strong></td>
                    <td><?= e($m['email']); ?> &bull; <?= e($m['phone'] ?? 'N/A'); ?></td>
                    <td><?= e($m['subject']); ?></td>
                    <td>
                        <?php if ($m['is_read']): ?>
                            <span class="badge bg-light text-muted border">Read</span>
                        <?php else: ?>
                            <span class="badge bg-danger">New Inquiry</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
