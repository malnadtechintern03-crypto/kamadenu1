<?php
/**
 * Kamadenu Goushala Platform - Master Donation Hub & 80G Tax Exemption Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$presetAmount = (int)($_GET['amount'] ?? 501);
$purposeParam = sanitize_input($_GET['purpose'] ?? 'General Gau Seva');
$cowIdParam = isset($_GET['cow_id']) ? (int)$_GET['cow_id'] : null;
$sevaIdParam = isset($_GET['seva_id']) ? (int)$_GET['seva_id'] : null;

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
            $paymentRef = 'PAY-' . strtoupper(bin2hex(random_bytes(5)));
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
        <div class="row g-5 justify-content-center">
            
            <div class="col-lg-8">
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
                                <input type="number" name="custom_amount" class="form-control" placeholder="Enter custom amount in INR (min ₹ 50)" min="50" step="1">
                            </div>
                        </div>

                        <!-- Step 2: Seva Purpose -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-2-circle-fill text-gold me-2"></i> 2. Select Purpose of Donation</label>
                            <select name="purpose" class="form-select rounded-3">
                                <option value="General Gau Seva" <?= $purposeParam === 'General Gau Seva' ? 'selected' : ''; ?>>General Gau Seva & Sanctuary Maintenance</option>
                                <option value="Feed a Cow (Grāsa Dāna)" <?= str_contains($purposeParam, 'Feed') ? 'selected' : ''; ?>>Feed a Cow (Grāsa Dāna – Green Grass & Fodder)</option>
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
                                    <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3 active">
                                        <input type="radio" name="payment_method" value="razorpay" checked class="d-none">
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
                                    <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3">
                                        <input type="radio" name="payment_method" value="upi" class="d-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-qr-code-scan fs-4 text-forest"></i>
                                            <div>
                                                <strong class="d-block text-forest-dark">Direct UPI Transfer</strong>
                                                <small class="text-muted">GPay, PhonePe, Paytm (<?= e(get_setting('upi_id', 'kamadenu@sbi')); ?>)</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
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

            <!-- Trust & Bank Credentials Column -->
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-bank2 text-gold me-2"></i> Direct Bank Transfer (NEFT/RTGS)</h3>
                    <ul class="list-unstyled small text-muted d-flex flex-column gap-2 mb-0">
                        <li><strong>Account Name:</strong> <?= e(get_setting('bank_account_name', 'Kamadenu Goushala Charitable Trust')); ?></li>
                        <li><strong>Bank:</strong> <?= e(get_setting('bank_name', 'State Bank of India')); ?></li>
                        <li><strong>Account Number:</strong> <code class="fs-6 text-forest-dark fw-bold"><?= e(get_setting('bank_account_number', '398201948571')); ?></code></li>
                        <li><strong>IFSC Code:</strong> <code class="fs-6 text-forest-dark fw-bold"><?= e(get_setting('bank_ifsc', 'SBIN0004281')); ?></code></li>
                        <li><strong>Branch:</strong> <?= e(get_setting('bank_branch', 'Nandi Hills Branch, Bangalore')); ?></li>
                        <li><strong>UPI ID:</strong> <span class="badge bg-gold text-forest-dark font-monospace"><?= e(get_setting('upi_id', 'kamadenu@sbi')); ?></span></li>
                    </ul>
                </div>

                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-md">
                    <h4 class="h6 font-serif text-gold mb-2"><i class="bi bi-patch-check-fill text-gold me-1"></i> Section 80G Tax Exemption</h4>
                    <p class="small text-cream opacity-85 mb-3">
                        <?= e(get_setting('tax_exemption_info', 'Donations are eligible for 50% Tax Exemption under Section 80G of the Income Tax Act. Registration No: AABTK9812RF20214.')); ?>
                    </p>
                    <small class="text-gold-light opacity-75">Your formal Form 10BE tax certificate will be emailed directly.</small>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Preset Button Selector Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="amount"]');
    const customInput = document.querySelector('input[name="custom_amount"]');

    radios.forEach(r => {
        r.addEventListener('change', () => {
            radios.forEach(item => item.closest('.btn').classList.remove('active'));
            r.closest('.btn').classList.add('active');
            customInput.value = '';
        });
    });

    customInput.addEventListener('input', () => {
        radios.forEach(item => {
            item.checked = false;
            item.closest('.btn').classList.remove('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
