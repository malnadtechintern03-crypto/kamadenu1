<?php
/**
 * Kamadenu Goushala Platform - Formal Section 80G Tax Exemption Donation Receipt
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$identifier = sanitize_input($_GET['num'] ?? $_GET['receipt'] ?? '');
if (empty($identifier)) {
    header('Location: ' . BASE_URL . '/donate.php');
    exit;
}

$receipt = ReceiptService::getReceipt($identifier);
if (!$receipt) {
    http_response_code(404);
    $pageTitle = 'Receipt Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center my-5">
            <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
            <h1 class="font-serif text-forest-dark mt-3">Receipt Record Not Found</h1>
            <p class="text-muted">The donation reference you provided could not be located in our ledger.</p>
            <a href="' . BASE_URL . '/donate.php" class="btn btn-forest rounded-pill px-4 mt-2">Return to Donation Portal</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Official 80G Tax Receipt – ' . $receipt['receipt_number'];
$metaDescription = 'Official Section 80G Tax Exemption Donation Receipt issued by Kamadenu Goushala Charitable Trust.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-cream-soft py-5">
    <div class="container text-center mb-4 d-print-none">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success text-white mb-2 shadow-xs">
            <i class="bi bi-check-circle-fill"></i>
            <span class="small fw-bold">Payment & Donation Verified</span>
        </div>
        <h1 class="h3 font-serif text-forest-dark">Official 80G Tax Exemption Receipt</h1>
        <p class="text-muted small">Your contribution has been successfully credited to Kamadenu Goushala Trust.</p>
        <button class="btn btn-gold rounded-pill px-4 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print / Download Tax Receipt PDF
        </button>
    </div>

    <!-- Official Printable 80G Receipt Container -->
    <div class="container">
        <div class="card p-4 p-md-5 rounded-4 shadow-lg border bg-white mx-auto position-relative" style="max-width: 800px;">
            
            <!-- Receipt Header -->
            <div class="row align-items-center pb-3 border-bottom border-2">
                <div class="col-sm-8">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="navbar-brand-logo" style="width:40px;height:40px;font-size:1.2rem;">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <h3 class="font-serif text-forest-dark mb-0 fs-4">Kamadenu Goushala Charitable Trust</h3>
                    </div>
                    <p class="small text-muted mb-0">
                        <?= e(get_setting('site_address', 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore Rural - 562103')); ?>
                    </p>
                    <p class="small text-muted mb-0">
                        Email: <?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?> | Phone: <?= e(get_setting('site_phone', '+91 98450 12345')); ?>
                    </p>
                </div>
                <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                    <span class="badge bg-forest px-3 py-2 rounded-pill small fw-bold">80G TAX EXEMPT</span>
                    <div class="small text-muted mt-1">Trust Reg: <strong>BK-IV-4921/2012</strong></div>
                    <div class="small text-muted">80G Reg: <strong>AABTK9812RF20214</strong></div>
                </div>
            </div>

            <!-- Receipt Meta Numbers -->
            <div class="row py-3 bg-cream-soft rounded-3 my-3">
                <div class="col-sm-6">
                    <div class="small text-muted">Receipt Number:</div>
                    <strong class="text-forest-dark fs-6 font-monospace"><?= e($receipt['receipt_number']); ?></strong>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div class="small text-muted">Donation Date:</div>
                    <strong class="text-forest-dark fs-6"><?= format_date($receipt['donation_date'] ?? date('Y-m-d')); ?></strong>
                </div>
            </div>

            <!-- Donor & Transaction Information -->
            <div class="row g-3 py-2 small">
                <div class="col-sm-6">
                    <div class="text-muted">Received With Thanks From:</div>
                    <strong class="fs-6 text-forest-dark d-block"><?= e($receipt['donor_name']); ?></strong>
                    <div class="text-muted"><?= e($receipt['donor_address'] ?? 'Devotee Address on File'); ?></div>
                    <div class="text-muted"><?= e($receipt['donor_city'] ?? ''); ?>, <?= e($receipt['donor_state'] ?? ''); ?> <?= e($receipt['donor_pincode'] ?? ''); ?></div>
                    <div class="text-muted">Email: <?= e($receipt['donor_email'] ?? 'N/A'); ?> | Mobile: <?= e($receipt['donor_phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <div class="text-muted">Donor PAN:</div>
                    <strong class="fs-6 text-forest-dark font-monospace"><?= e($receipt['donor_pan'] ?? 'NOT PROVIDED'); ?></strong>
                    <div class="text-muted mt-2">Transaction ID:</div>
                    <span class="font-monospace small text-muted"><?= e($receipt['transaction_id'] ?? $receipt['gateway_payment_id'] ?? 'UPI-DIRECT'); ?></span>
                    <div class="text-muted mt-1">Payment Gateway: <?= strtoupper($receipt['gateway'] ?? 'ONLINE'); ?></div>
                </div>
            </div>

            <!-- Purpose & Amount Table -->
            <div class="table-responsive my-3">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="bg-forest text-white">
                        <tr>
                            <th>Seva Description / Purpose</th>
                            <th class="text-end" style="width: 180px;">Amount (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?= e($receipt['purpose'] ?? 'Sacred Gau Seva Contribution'); ?></strong>
                                <?php if (!empty($receipt['cow_name'])): ?>
                                    <div class="small text-muted">Dedicated to Cow: <strong><?= e($receipt['cow_name']); ?></strong> (<?= e($receipt['cow_code']); ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fs-5 font-serif text-forest-dark fw-bold">
                                <?= format_inr($receipt['amount'], true); ?>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-cream fw-bold">
                            <td class="text-end text-forest-dark">Total 80G Exempt Contribution:</td>
                            <td class="text-end fs-5 text-forest-dark"><?= format_inr($receipt['amount'], true); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Statutory Notes -->
            <div class="p-3 bg-cream-soft rounded-3 border small text-muted mb-4">
                <p class="mb-1">
                    <i class="bi bi-info-circle-fill text-gold me-1"></i>
                    <strong>Statutory Tax Deduction Notice:</strong> Donations are eligible for 50% tax exemption under Section 80G of the Income Tax Act, 1961 vide Order No. <strong>AABTK9812RF20214</strong> dated 24/09/2021.
                </p>
                <p class="mb-0">
                    This is a computer-generated official receipt verified on the Kamadenu Goushala core ledger and does not require a physical signature.
                </p>
            </div>

            <!-- Signatory & Seal -->
            <div class="d-flex justify-content-between align-items-end pt-2 text-center">
                <div class="text-start">
                    <div class="badge bg-gold-subtle text-gold-dark border border-warning px-3 py-2 rounded-pill small">
                        <i class="bi bi-shield-lock-fill me-1"></i> Digitally Signed & Sealed
                    </div>
                </div>
                <div>
                    <div class="font-serif text-forest-dark fw-bold">For Kamadenu Goushala Charitable Trust</div>
                    <small class="text-muted d-block mt-3 border-top pt-1">Authorized Trustee / Finance Desk</small>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
