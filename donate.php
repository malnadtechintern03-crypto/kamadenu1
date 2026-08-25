<?php
/**
 * Kamadenu Goushala Platform - Master Donation Hub & 80G Tax Exemption Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$presetAmount = (int)($_GET['amount'] ?? 501);
if ($presetAmount <= 0) $presetAmount = 501;

$purposeParam = sanitize_input($_GET['purpose'] ?? 'General Gau Seva');
$cowIdParam = isset($_GET['cow_id']) ? (int)$_GET['cow_id'] : null;
$sevaIdParam = isset($_GET['seva_id']) ? (int)$_GET['seva_id'] : null;

// Extra parameters from Seva & Feeding Planners
$cowCountParam = isset($_GET['cow_count']) ? (int)$_GET['cow_count'] : null;
$durationParam = isset($_GET['duration_multiplier']) ? (int)$_GET['duration_multiplier'] : null;
$occasionParam = sanitize_input($_GET['occasion'] ?? '');
$sankalpamParam = sanitize_input($_GET['sankalpam_name'] ?? '');

$upiId = get_setting('upi_id', 'kamadenu@sbi');
$payeeName = get_setting('site_name', 'Kamadenu Goushala');

// Initial QR Code Generation URL
$initialUpiUrl = "upi://pay?pa=" . rawurlencode($upiId) . "&pn=" . rawurlencode($payeeName) . "&am=" . $presetAmount . "&cu=INR&tn=" . rawurlencode($purposeParam);
$initialQrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=8&data=" . rawurlencode($initialUpiUrl);

$errors = [];

// Handle Online Donation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $amount = (float)($_POST['amount'] ?? 0);
    $customAmount = (float)($_POST['custom_amount'] ?? 0);
    if ($customAmount > 0) {
        $amount = $customAmount;
    }

    $donorName = sanitize_input($_POST['donor_name'] ?? '');
    $donorEmail = sanitize_input($_POST['donor_email'] ?? '');
    $donorPhone = sanitize_input($_POST['donor_phone'] ?? '');
    $donorPan = sanitize_input($_POST['donor_pan'] ?? '');
    $donorAddress = sanitize_input($_POST['donor_address'] ?? '');
    $donorCity = sanitize_input($_POST['donor_city'] ?? '');
    $donorState = sanitize_input($_POST['donor_state'] ?? '');
    $donorPincode = sanitize_input($_POST['donor_pincode'] ?? '');
    $purpose = sanitize_input($_POST['purpose'] ?? 'General Gau Seva');
    $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'razorpay');
    $utrNumber = sanitize_input($_POST['utr_number'] ?? '');
    $cowId = !empty($_POST['cow_id']) ? (int)$_POST['cow_id'] : null;
    $sevaId = !empty($_POST['seva_id']) ? (int)$_POST['seva_id'] : null;

    if ($amount < 50) {
        $errors[] = 'Minimum donation amount is ₹ 50.';
    }
    if (empty($donorName)) {
        $errors[] = 'Please enter your full name.';
    }
    if (empty($donorEmail) || !filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($donorPhone)) {
        $errors[] = 'Please enter your mobile phone number.';
    }

    if (empty($errors)) {
        try {
            // 1. Create Donation Record
            $donationNumber = Donation::create([
                'seva_program_id' => $sevaId,
                'cow_id'          => $cowId,
                'donor_name'      => $donorName,
                'donor_email'     => $donorEmail,
                'donor_phone'     => $donorPhone,
                'donor_pan'       => $donorPan,
                'donor_address'   => $donorAddress,
                'donor_city'      => $donorCity,
                'donor_state'     => $donorState,
                'donor_pincode'   => $donorPincode,
                'amount'          => $amount,
                'purpose'         => $purpose,
                'is_80g_claimed'  => !empty($donorPan) ? 1 : 1
            ]);

            // 2. Mark Success & Record Payment Transaction Server-Side
            $paymentRef = !empty($utrNumber) ? $utrNumber : ('PAY-' . strtoupper(bin2hex(random_bytes(5))));
            Donation::markSuccess($donationNumber, $paymentRef, $paymentMethod);

            // 3. Redirect to official digital receipt page
            header('Location: ' . BASE_URL . '/receipt.php?num=' . urlencode($donationNumber));
            exit;

        } catch (Throwable $t) {
            error_log('Donation error: ' . $t->getMessage());
            $errors[] = 'An error occurred while processing your donation. Please try again.';
        }
    }
}

$pageTitle = 'Donate Online – Support Sacred Gau Seva with 80G Tax Benefits';
$metaDescription = 'Make a tax-exempt donation to Kamadenu Goushala. Support green fodder, veterinary care, and rescue operations. Instant Section 80G receipt.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-shield-check me-1"></i> Section 80G Tax Deductible (50% Exemption)
        </span>
        <h1 class="page-hero-title">Support Sacred Gau Seva</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Your generous contribution directly sustains nutritional green fodder, 24x7 emergency medical surgeries, and lifelong sanctuary for rescued cows.
        </p>
    </div>
</section>

<!-- Main Donation Checkout Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        
        <?php if ($cowCountParam || $occasionParam || $sankalpamParam): ?>
            <!-- Devotional Seva Customization Banner -->
            <div class="card p-3 rounded-4 bg-white border border-warning border-opacity-50 shadow-sm mb-4" data-animate="fade-down">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon stat-icon-gold" style="width:48px;height:48px;font-size:1.3rem;">
                            <i class="bi bi-flower1"></i>
                        </div>
                        <div>
                            <span class="badge bg-gold text-forest-dark fw-bold small">Dedicated Seva Intent</span>
                            <h5 class="font-serif text-forest-dark mb-0"><?= e($purposeParam); ?></h5>
                            <small class="text-muted">
                                <?php if ($cowCountParam): ?>
                                    Feeding <strong><?= $cowCountParam; ?> Cow(s)</strong>
                                <?php endif; ?>
                                <?php if ($durationParam): ?>
                                    &bull; Duration: <strong><?= $durationParam; ?> Day(s)</strong>
                                <?php endif; ?>
                                <?php if ($occasionParam): ?>
                                    &bull; Occasion: <strong><?= e($occasionParam); ?></strong>
                                <?php endif; ?>
                                <?php if ($sankalpamParam): ?>
                                    &bull; Sankalpam In Name: <strong><?= e($sankalpamParam); ?></strong>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    <span class="h4 font-serif text-forest-dark mb-0 fw-bold">₹ <?= number_format($presetAmount, 0, '.', ','); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-5 justify-content-center">
            
            <!-- Left Column: Donation Form -->
            <div class="col-lg-7">
                <div class="card p-4 p-md-5 rounded-4 border-0 shadow-md bg-white">
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Please correct the following:</strong>
                            <ul class="mb-0 mt-1 small">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL; ?>/donate.php" id="donationForm">
                        <?= csrf_field(); ?>
                        <?php if ($cowIdParam): ?>
                            <input type="hidden" name="cow_id" value="<?= $cowIdParam; ?>">
                        <?php endif; ?>
                        <?php if ($sevaIdParam): ?>
                            <input type="hidden" name="seva_id" value="<?= $sevaIdParam; ?>">
                        <?php endif; ?>

                        <!-- Step 1: Select Amount -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-1-circle-fill text-gold me-2"></i> 1. Select Donation Amount</label>
                            
                            <div class="row g-2 mb-3" id="donationAmountPresets">
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 101) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="101" <?= ($presetAmount === 101) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 101</span>
                                    </label>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 501) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="501" <?= ($presetAmount === 501) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 501</span>
                                    </label>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 1001) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="1001" <?= ($presetAmount === 1001) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 1,001</span>
                                    </label>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 2501) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="2501" <?= ($presetAmount === 2501) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 2,501</span>
                                    </label>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 5001) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="5001" <?= ($presetAmount === 5001) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 5,001</span>
                                    </label>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="btn btn-outline-forest w-100 py-2 text-center rounded-3 <?= ($presetAmount === 10001) ? 'active' : ''; ?>">
                                        <input type="radio" name="amount" value="10001" <?= ($presetAmount === 10001) ? 'checked' : ''; ?> class="d-none">
                                        <span class="fw-bold">₹ 10,001</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Custom Amount Input -->
                            <div class="input-group">
                                <span class="input-group-text bg-cream text-forest-dark fw-bold">₹ Other Amount:</span>
                                <input type="number" name="custom_amount" id="customAmountInput" class="form-control" placeholder="Enter custom amount in INR (min ₹ 50)" min="50" step="1" value="<?= !in_array($presetAmount, [101, 501, 1001, 2501, 5001, 10001]) ? $presetAmount : ''; ?>">
                            </div>
                        </div>

                        <!-- Step 2: Seva Purpose -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-2-circle-fill text-gold me-2"></i> 2. Select Purpose of Donation</label>
                            <select name="purpose" id="donationPurposeSelect" class="form-select rounded-3">
                                <option value="General Gau Seva" <?= $purposeParam === 'General Gau Seva' ? 'selected' : ''; ?>>General Gau Seva & Sanctuary Maintenance</option>
                                <option value="Feed a Cow (Grāsa Dāna)" <?= str_contains($purposeParam, 'Feed') || str_contains($purposeParam, 'Grāsa') ? 'selected' : ''; ?>>Feed a Cow (Grāsa Dāna – Green Grass & Fodder)</option>
                                <option value="Medical Care & Surgery Fund" <?= str_contains($purposeParam, 'Medical') ? 'selected' : ''; ?>>Medical Care, Medicines & Veterinary Surgeries</option>
                                <option value="Emergency Cow Rescue Ambulance" <?= str_contains($purposeParam, 'Rescue') ? 'selected' : ''; ?>>Emergency Cow Rescue Ambulance & Fuel</option>
                                <option value="Senior Cow Hospice & Care" <?= str_contains($purposeParam, 'Senior') ? 'selected' : ''; ?>>Senior & Differently-Abled Cow Hospice Fund</option>
                                <option value="Shelter & Solar Infrastructure" <?= str_contains($purposeParam, 'Shelter') ? 'selected' : ''; ?>>Sanctuary Shed Expansion & Solar Infrastructure</option>
                            </select>
                        </div>

                        <!-- Step 3: Donor Details -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-3-circle-fill text-gold me-2"></i> 3. Donor Details (For Official 80G Receipt)</label>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Full Name *</label>
                                    <input type="text" name="donor_name" class="form-control" placeholder="e.g. Ramesh Kumar" required value="<?= e($_POST['donor_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Email Address *</label>
                                    <input type="email" name="donor_email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['donor_email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Mobile Phone Number *</label>
                                    <input type="tel" name="donor_phone" class="form-control" placeholder="+91 98765 43210" required value="<?= e($_POST['donor_phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">PAN Number (For 80G Tax Exemption)</label>
                                    <input type="text" name="donor_pan" class="form-control text-uppercase" placeholder="ABCDE1234F" maxlength="10" value="<?= e($_POST['donor_pan'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-forest-dark">Address (For 80G Certificate Generation)</label>
                                    <input type="text" name="donor_address" class="form-control" placeholder="Street, Building, Flat No." value="<?= e($_POST['donor_address'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-forest-dark">City</label>
                                    <input type="text" name="donor_city" class="form-control" placeholder="Bangalore" value="<?= e($_POST['donor_city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-forest-dark">State</label>
                                    <input type="text" name="donor_state" class="form-control" placeholder="Karnataka" value="<?= e($_POST['donor_state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-forest-dark">Pincode</label>
                                    <input type="text" name="donor_pincode" class="form-control" placeholder="560001" value="<?= e($_POST['donor_pincode'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Payment Gateway Mode -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-4-circle-fill text-gold me-2"></i> 4. Payment Method</label>
                            
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3 active" id="labelPaymentRazorpay">
                                        <input type="radio" name="payment_method" value="razorpay" checked class="d-none" id="radioPaymentRazorpay">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-credit-card fs-4 text-gold"></i>
                                            <div>
                                                <strong class="d-block text-forest-dark">UPI / Cards / NetBanking</strong>
                                                <small class="text-muted">Instant online donation checkout</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3" id="labelPaymentUpi">
                                        <input type="radio" name="payment_method" value="upi" class="d-none" id="radioPaymentUpi">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-qr-code-scan fs-4 text-forest"></i>
                                            <div>
                                                <strong class="d-block text-forest-dark">Direct UPI / QR Scanner</strong>
                                                <small class="text-muted">GPay, PhonePe, Paytm, BHIM</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Dynamic UTR / Transaction Reference field when paying via QR Scanner -->
                            <div id="utrInputFieldContainer" class="mt-3 p-3 bg-cream rounded-3 border border-warning border-opacity-50" style="display: none;">
                                <label class="form-label small fw-bold text-forest-dark">
                                    <i class="bi bi-receipt-cutoff text-gold me-1"></i> UPI 12-Digit Reference / UTR Number (Optional)
                                </label>
                                <input type="text" name="utr_number" class="form-control font-monospace" placeholder="e.g. 423819284712 (from your UPI app receipt)">
                                <small class="text-muted d-block mt-1">If you scanned the QR code on your phone and completed payment, enter your 12-digit UTR for automatic receipt reconciliation.</small>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold fs-5">
                                <i class="bi bi-heart-fill me-2"></i> Complete Sacred Donation
                            </button>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-lock-fill text-forest me-1"></i> 256-Bit SSL Encrypted &bull; 80G Tax Deductible &bull; Instant PDF Receipt
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Live QR Code Scanner Widget & Bank Details -->
            <div class="col-lg-5">
                
                <!-- Live UPI QR Code Scanner Widget -->
                <div class="upi-scanner-card mb-4" data-animate="zoom-in">
                    <div class="upi-scanner-header">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-gold text-forest-dark small fw-bold"><i class="bi bi-patch-check-fill me-1"></i> Verified Trust UPI</span>
                            <span class="badge bg-white bg-opacity-25 text-white small"><i class="bi bi-shield-check me-1"></i> Instant 80G</span>
                        </div>
                        <h4 class="h6 font-serif text-cream mb-0"><?= e(get_setting('site_name', 'Kamadenu Goushala Charitable Trust')); ?></h4>
                        <small class="text-gold-light opacity-90">Scan with any UPI Application</small>
                    </div>

                    <div class="p-4 text-center bg-white">
                        <!-- Live Scanner Frame -->
                        <div class="upi-scanner-frame mb-3 position-relative">
                            <div class="scanner-corner scanner-corner-tl"></div>
                            <div class="scanner-corner scanner-corner-tr"></div>
                            <div class="scanner-corner scanner-corner-bl"></div>
                            <div class="scanner-corner scanner-corner-br"></div>
                            
                            <div class="upi-scanner-laser"></div>
                            
                            <img src="<?= e($initialQrCodeUrl); ?>" 
                                 alt="Scan to Pay via UPI QR Code" 
                                 id="upiQrCodeImage" 
                                 class="img-fluid"
                                 onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= rawurlencode($initialUpiUrl); ?>';">
                        </div>

                        <!-- Dynamic Amount Indicator -->
                        <div class="mb-3">
                            <span class="text-muted small d-block mb-1">Payable Seva Amount</span>
                            <div class="h3 font-serif text-forest-dark fw-bold mb-0" id="qrDisplayAmount">₹ <?= number_format($presetAmount, 0, '.', ','); ?></div>
                        </div>

                        <!-- UPI ID with Copy Button -->
                        <div class="input-group input-group-sm mb-3">
                            <span class="input-group-text bg-cream text-forest-dark fw-bold"><i class="bi bi-qr-code me-1"></i> UPI ID</span>
                            <input type="text" class="form-control text-center font-monospace fw-bold bg-white" id="upiIdInput" value="<?= e($upiId); ?>" readonly>
                            <button class="btn btn-outline-forest" type="button" id="copyUpiBtn" onclick="copyUpiId()">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>

                        <!-- Direct Mobile Deep Link -->
                        <div class="d-grid mb-3">
                            <a href="<?= e($initialUpiUrl); ?>" id="upiMobilePayBtn" class="btn btn-forest btn-sm rounded-pill py-2 fw-bold d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-phone-fill"></i> Pay via UPI App (GPay / PhonePe)
                            </a>
                        </div>

                        <!-- Supported UPI Apps Badges -->
                        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap pt-2 border-top">
                            <span class="badge bg-cream text-forest-dark border"><i class="bi bi-google me-1 text-primary"></i> GPay</span>
                            <span class="badge bg-cream text-forest-dark border"><i class="bi bi-phone me-1 text-success"></i> PhonePe</span>
                            <span class="badge bg-cream text-forest-dark border"><i class="bi bi-wallet2 me-1 text-info"></i> Paytm</span>
                            <span class="badge bg-cream text-forest-dark border"><i class="bi bi-bank me-1 text-gold"></i> BHIM</span>
                            <span class="badge bg-cream text-forest-dark border"><i class="bi bi-shield-check me-1 text-forest"></i> Any UPI</span>
                        </div>
                    </div>
                </div>

                <!-- Direct Bank Transfer Details -->
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4" data-animate="fade-up">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-bank2 text-gold me-2"></i> Direct Bank Transfer (NEFT/RTGS)</h3>
                    <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-0">
                        <li><strong>Account Name:</strong> <?= e(get_setting('bank_account_name', 'Kamadenu Goushala Charitable Trust')); ?></li>
                        <li><strong>Bank:</strong> <?= e(get_setting('bank_name', 'State Bank of India')); ?></li>
                        <li><strong>Account Number:</strong> <code class="fs-6 text-forest-dark fw-bold"><?= e(get_setting('bank_account_number', '398201948571')); ?></code></li>
                        <li><strong>IFSC Code:</strong> <code class="fs-6 text-forest-dark fw-bold"><?= e(get_setting('bank_ifsc', 'SBIN0004281')); ?></code></li>
                        <li><strong>Branch:</strong> <?= e(get_setting('bank_branch', 'Nandi Hills Branch, Bangalore')); ?></li>
                        <li><strong>UPI ID:</strong> <span class="badge bg-gold text-forest-dark font-monospace"><?= e($upiId); ?></span></li>
                    </ul>
                </div>

                <!-- 80G Tax Exemption Card -->
                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-md" data-animate="fade-up">
                    <h4 class="h6 font-serif text-gold mb-2"><i class="bi bi-patch-check-fill text-gold me-1"></i> Section 80G Tax Exemption</h4>
                    <p class="small text-cream opacity-85 mb-3">
                        <?= e(get_setting('tax_exemption_info', 'Donations are eligible for 50% Tax Exemption under Section 80G of the Income Tax Act. Registration No: AABTK9812RF20214.')); ?>
                    </p>
                    <small class="text-gold-light opacity-75">Your formal Form 10BE tax certificate will be emailed directly.</small>
                </div>

                <!-- WhatsApp Direct Seva Support -->
                <?php
                    $donateWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                    $cleanDonateWaPhone = preg_replace('/\D/', '', $donateWaPhone);
                    $donateWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                   "I would like to contribute towards Gau Seva (80G Tax Exemption).\n" .
                                   "Please share bank / UPI transfer assistance and official 80G receipt confirmation details.";
                    $donateWaUrl = "https://wa.me/" . $cleanDonateWaPhone . "?text=" . rawurlencode($donateWaMsg);
                ?>
                <div class="card p-4 rounded-4 bg-white border border-success border-opacity-50 text-center shadow-xs mt-3">
                    <i class="bi bi-whatsapp text-success fs-3 mb-2"></i>
                    <h5 class="h6 font-serif text-forest-dark mb-1">Direct Seva Assistance</h5>
                    <p class="small text-muted mb-3">Prefer offline NEFT/RTGS, Cheque, or need help with your 80G receipt?</p>
                    <a href="<?= e($donateWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm rounded-pill w-100 fw-semibold">
                        <i class="bi bi-whatsapp me-1"></i> Connect on WhatsApp
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Preset Button Selector, UPI QR Generator & Copy Handler -->
<script>
function copyUpiId() {
    const upiInput = document.getElementById('upiIdInput');
    if (!upiInput) return;
    
    navigator.clipboard.writeText(upiInput.value).then(() => {
        const copyBtn = document.getElementById('copyUpiBtn');
        if (copyBtn) {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="bi bi-check2-circle me-1 text-success"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2500);
        }
        if (typeof showToast === 'function') {
            showToast('UPI ID copied to clipboard: ' + upiInput.value, 'success');
        }
    }).catch(err => {
        console.error('Failed to copy UPI ID:', err);
    });
}

function updateUpiQrCode() {
    let amount = 501;
    const checkedRadio = document.querySelector('input[name="amount"]:checked');
    const customInput = document.getElementById('customAmountInput');
    
    if (customInput && parseFloat(customInput.value) >= 50) {
        amount = parseFloat(customInput.value);
    } else if (checkedRadio) {
        amount = parseFloat(checkedRadio.value);
    }

    const purposeSelect = document.getElementById('donationPurposeSelect');
    const purpose = purposeSelect ? purposeSelect.value : 'General Gau Seva';
    
    const upiId = "<?= e($upiId); ?>";
    const payee = "<?= e($payeeName); ?>";
    const upiLink = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(payee)}&am=${amount}&cu=INR&tn=${encodeURIComponent(purpose)}`;
    
    const qrImg = document.getElementById('upiQrCodeImage');
    const upiPayBtn = document.getElementById('upiMobilePayBtn');
    const displayAmount = document.getElementById('qrDisplayAmount');
    
    if (qrImg) {
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=8&data=${encodeURIComponent(upiLink)}`;
    }
    if (upiPayBtn) {
        upiPayBtn.href = upiLink;
    }
    if (displayAmount) {
        displayAmount.textContent = '₹ ' + Number(amount).toLocaleString('en-IN');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="amount"]');
    const customInput = document.getElementById('customAmountInput');
    const purposeSelect = document.getElementById('donationPurposeSelect');
    
    const radioRazorpay = document.getElementById('radioPaymentRazorpay');
    const radioUpi = document.getElementById('radioPaymentUpi');
    const labelRazorpay = document.getElementById('labelPaymentRazorpay');
    const labelUpi = document.getElementById('labelPaymentUpi');
    const utrContainer = document.getElementById('utrInputFieldContainer');

    radios.forEach(r => {
        r.addEventListener('change', () => {
            radios.forEach(item => item.closest('.btn').classList.remove('active'));
            r.closest('.btn').classList.add('active');
            if (customInput) customInput.value = '';
            updateUpiQrCode();
        });
    });

    if (customInput) {
        customInput.addEventListener('input', () => {
            radios.forEach(item => {
                item.checked = false;
                item.closest('.btn').classList.remove('active');
            });
            updateUpiQrCode();
        });
    }

    if (purposeSelect) {
        purposeSelect.addEventListener('change', updateUpiQrCode);
    }

    // Toggle Payment Method Selection
    if (radioRazorpay && radioUpi) {
        radioRazorpay.addEventListener('change', () => {
            labelRazorpay.classList.add('active');
            labelUpi.classList.remove('active');
            if (utrContainer) utrContainer.style.display = 'none';
        });

        radioUpi.addEventListener('change', () => {
            labelUpi.classList.add('active');
            labelRazorpay.classList.remove('active');
            if (utrContainer) utrContainer.style.display = 'block';
            
            // Scroll smoothly to scanner on mobile if needed
            const scannerCard = document.querySelector('.upi-scanner-card');
            if (scannerCard && window.innerWidth < 992) {
                scannerCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

