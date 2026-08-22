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

<!-- Sanctuary Overview Welcome Card with Grand Particles & Live Ticker -->
<div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-4 bg-forest-dark text-white position-relative">
    <!-- Golden Particles Canvas Layer -->
    <canvas id="adminHeroParticles" class="hero-particles-layer" style="opacity: 0.55;"></canvas>

    <div class="row g-0 align-items-center position-relative" style="z-index: 4;">
        <div class="col-md-4 col-lg-3 position-relative" style="min-height: 240px; height: 100%;">
            <img 
                src="<?= e($dashboardCowImage); ?>" 
                alt="Kamadhenu - Sanctuary Matriarch" 
                class="w-100 h-100 object-fit-cover position-absolute top-0 start-0"
                style="border-top-left-radius: var(--radius-lg); border-bottom-left-radius: var(--radius-lg);"
                onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';"
            >
            <div class="position-absolute top-0 start-0 m-3">
                <span class="badge bg-gold text-forest-dark fw-bold shadow-sm animate-pulse-glow">
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
                    <span class="pulse-dot pulse-gold"></span>
                    <span>Live Sanctuary Operations Hub • Nandi Hills, Karnataka</span>
                </div>
                <div class="d-flex align-items-center gap-2 small text-white-50">
                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                        <span class="pulse-dot me-1"></span> Live Active
                    </span>
                    <span id="adminLiveClock" class="fw-bold font-monospace text-gold ms-1"></span>
                </div>
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

<!-- Quick Action Shortcuts Bar with Hover Animation -->
<div class="card p-3 rounded-4 border-0 shadow-sm bg-white mb-4 admin-action-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-gold text-forest-dark fw-bold px-3 py-1 rounded-pill grand-shimmer-badge">Quick Actions</span>
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

<!-- Primary KPI Metrics Grid with Grand 3D Tilt & SVG Circular Progress Gauges -->
<div class="row g-4 mb-4">
    
    <!-- KPI 1: Resident Cows -->
    <div class="col-sm-6 col-xl-3">
        <div class="luminous-kpi-card tilt-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="stat-icon-wrapper" style="background:rgba(31,82,87,0.12);color:var(--color-secondary);">
                    <i class="bi bi-flower1"></i>
                </div>
                <!-- Circular Capacity Meter (85%) -->
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="52" height="52">
                        <circle class="circle-progress-bg" cx="26" cy="26" r="22"></circle>
                        <circle class="circle-progress-bar" cx="26" cy="26" r="22" data-percent="85" style="stroke: var(--color-secondary);"></circle>
                    </svg>
                    <span class="circle-progress-text">85%</span>
                </div>
            </div>
            <div>
                <div class="stat-number fs-3 admin-counter-value" data-target="<?= $totalCows; ?>"><?= $totalCows; ?></div>
                <p class="stat-label mb-1">Protected Resident Cows</p>
                <small class="text-muted d-block"><?= $treatmentCows; ?> in Medical/Hospice &bull; <?= $adoptedCows; ?> Adopted</small>
            </div>
        </div>
    </div>

    <!-- KPI 2: Total 80G Donations -->
    <div class="col-sm-6 col-xl-3">
        <div class="luminous-kpi-card tilt-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="stat-icon-wrapper" style="background:rgba(233,120,58,0.14);color:var(--color-accent);">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <!-- Circular Target Meter (92%) -->
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="52" height="52">
                        <circle class="circle-progress-bg" cx="26" cy="26" r="22"></circle>
                        <circle class="circle-progress-bar" cx="26" cy="26" r="22" data-percent="92" style="stroke: var(--color-accent);"></circle>
                    </svg>
                    <span class="circle-progress-text">92%</span>
                </div>
            </div>
            <div>
                <div class="stat-number fs-3 admin-counter-value" data-target="<?= $totalDonationsAmount; ?>" data-is-currency="true"><?= format_inr($totalDonationsAmount, true); ?></div>
                <p class="stat-label mb-1">Total 80G Seva Donations</p>
                <small class="text-muted d-block"><?= $totalDonationsCount; ?> Verified Transactions</small>
            </div>
        </div>
    </div>

    <!-- KPI 3: Store Orders & Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="luminous-kpi-card tilt-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="stat-icon-wrapper" style="background:rgba(95,168,168,0.15);color:var(--color-highlight);">
                    <i class="bi bi-bag-check"></i>
                </div>
                <!-- Circular Inventory Turnover (76%) -->
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="52" height="52">
                        <circle class="circle-progress-bg" cx="26" cy="26" r="22"></circle>
                        <circle class="circle-progress-bar" cx="26" cy="26" r="22" data-percent="76" style="stroke: var(--color-highlight);"></circle>
                    </svg>
                    <span class="circle-progress-text">76%</span>
                </div>
            </div>
            <div>
                <div class="stat-number fs-3 admin-counter-value" data-target="<?= $totalStoreRevenue; ?>" data-is-currency="true"><?= format_inr($totalStoreRevenue, true); ?></div>
                <p class="stat-label mb-1">Organic Store Revenue</p>
                <small class="text-muted d-block"><?= $totalOrdersCount; ?> Total Customer Orders</small>
            </div>
        </div>
    </div>

    <!-- KPI 4: Active Adoptions & Messages -->
    <div class="col-sm-6 col-xl-3">
        <div class="luminous-kpi-card tilt-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="stat-icon-wrapper" style="background:rgba(244,177,131,0.18);color:var(--color-accent-light);">
                    <i class="bi bi-suit-heart"></i>
                </div>
                <!-- Circular Guardianship Rate (68%) -->
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="52" height="52">
                        <circle class="circle-progress-bg" cx="26" cy="26" r="22"></circle>
                        <circle class="circle-progress-bar" cx="26" cy="26" r="22" data-percent="68" style="stroke: var(--color-primary);"></circle>
                    </svg>
                    <span class="circle-progress-text">68%</span>
                </div>
            </div>
            <div>
                <div class="stat-number fs-3 admin-counter-value" data-target="<?= $totalAdoptionsCount; ?>"><?= $totalAdoptionsCount; ?></div>
                <p class="stat-label mb-1">Active Cow Guardians</p>
                <small class="text-muted d-block">
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge bg-danger rounded-pill animate-pulse-glow"><?= $unreadMessages; ?> Unread Inquiries</span>
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
