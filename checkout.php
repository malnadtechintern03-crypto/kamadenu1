<?php
/**
 * Kamadenu Goushala Platform - Secure Store Checkout
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$cart = Order::getCart();
if (empty($cart['items'])) {
    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$upiId = get_setting('upi_id', 'kamadenu@sbi');
$payeeName = get_setting('site_name', 'Kamadenu Goushala');
$upiUrl = "upi://pay?pa=" . rawurlencode($upiId) . "&pn=" . rawurlencode($payeeName) . "&am=" . $cart['grand_total'] . "&cu=INR&tn=" . rawurlencode('Kamadenu Organic Order');
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=6&data=" . rawurlencode($upiUrl);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $name = sanitize_input($_POST['customer_name'] ?? '');
    $email = sanitize_input($_POST['customer_email'] ?? '');
    $phone = sanitize_input($_POST['customer_phone'] ?? '');
    $address1 = sanitize_input($_POST['address_line1'] ?? '');
    $address2 = sanitize_input($_POST['address_line2'] ?? '');
    $city = sanitize_input($_POST['city'] ?? '');
    $state = sanitize_input($_POST['state'] ?? '');
    $pincode = sanitize_input($_POST['pincode'] ?? '');
    $landmark = sanitize_input($_POST['landmark'] ?? '');
    $notes = sanitize_input($_POST['customer_notes'] ?? '');
    $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'upi');
    $utrNumber = sanitize_input($_POST['utr_number'] ?? '');

    if (empty($name)) $errors[] = 'Please enter your full name.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (empty($phone)) $errors[] = 'Please enter your mobile phone number.';
    if (empty($address1)) $errors[] = 'Please enter your street / building address.';
    if (empty($city)) $errors[] = 'Please enter your city.';
    if (empty($state)) $errors[] = 'Please enter your state.';
    if (empty($pincode)) $errors[] = 'Please enter your 6-digit postal pincode.';

    if (empty($errors)) {
        try {
            $customerData = [
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes
            ];

            $shippingData = [
                'address_line1' => $address1,
                'address_line2' => $address2,
                'city'          => $city,
                'state'         => $state,
                'pincode'       => $pincode,
                'landmark'      => $landmark
            ];

            $orderNumber = Order::placeOrder($customerData, $shippingData, $paymentMethod, $utrNumber);

            header('Location: ' . BASE_URL . '/order-confirmation.php?order=' . urlencode($orderNumber));
            exit;

        } catch (Throwable $t) {
            error_log('Checkout error: ' . $t->getMessage());
            $errors[] = 'Failed to process order: ' . $t->getMessage();
        }
    }
}

$pageTitle = 'Secure Checkout – Kamadenu Goushala Store';
$metaDescription = 'Complete your purchase of pure A2 Gir Cow Ghee and Ayurvedic products with secure courier delivery across India.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-shield-lock-fill me-1"></i> 256-Bit SSL Encrypted Checkout
        </span>
        <h1 class="page-hero-title">Secure Store Checkout</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Provide your courier delivery address and choose your preferred payment option to finalize your order.
        </p>
    </div>
</section>

<!-- Checkout Form Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <form method="POST" action="<?= BASE_URL; ?>/checkout.php" id="checkoutForm">
            <?= csrf_field(); ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger rounded-3 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please resolve the following:</strong>
                    <ul class="mb-0 mt-1 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-5">
                
                <!-- Left Column: Customer, Shipping & Payment Selection -->
                <div class="col-lg-7">
                    <div class="card p-4 p-md-5 rounded-4 border-0 shadow-md bg-white mb-4">
                        
                        <!-- 1. Contact Information -->
                        <h2 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-person-circle text-gold me-2"></i> 1. Contact Details</h2>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Full Name *</label>
                                <input type="text" name="customer_name" class="form-control" placeholder="e.g. Anand Deshmukh" required value="<?= e($_POST['customer_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Email Address *</label>
                                <input type="email" name="customer_email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['customer_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Mobile Phone Number *</label>
                                <input type="tel" name="customer_phone" class="form-control" placeholder="+91 98765 43210" required value="<?= e($_POST['customer_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- 2. Shipping Address -->
                        <h2 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-geo-alt-fill text-gold me-2"></i> 2. Delivery Address</h2>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Flat, Building, Street Address *</label>
                                <input type="text" name="address_line1" class="form-control" placeholder="e.g. Flat 402, Shanti Nilayam, 8th Main Road" required value="<?= e($_POST['address_line1'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Area, Colony, Sector (Optional)</label>
                                <input type="text" name="address_line2" class="form-control" placeholder="e.g. Malleswaram West" value="<?= e($_POST['address_line2'] ?? ''); ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-forest-dark">City / Town *</label>
                                <input type="text" name="city" class="form-control" placeholder="Bangalore" required value="<?= e($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-forest-dark">State *</label>
                                <input type="text" name="state" class="form-control" placeholder="Karnataka" required value="<?= e($_POST['state'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-forest-dark">Pincode *</label>
                                <input type="text" name="pincode" class="form-control font-monospace" placeholder="560003" maxlength="6" required value="<?= e($_POST['pincode'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Landmark (Optional)</label>
                                <input type="text" name="landmark" class="form-control" placeholder="Near Temple / Metro Station" value="<?= e($_POST['landmark'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Special Delivery Notes (Optional)</label>
                                <textarea name="customer_notes" class="form-control" rows="2" placeholder="Leave with security / call before delivery..."><?= e($_POST['customer_notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- 3. Payment Method Selection -->
                        <h2 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-credit-card-2-front-fill text-gold me-2"></i> 3. Choose Payment Method</h2>

                        <div class="row g-3" id="paymentMethodCards">
                            <!-- Option 1: Direct UPI / QR Code -->
                            <div class="col-12">
                                <label class="btn btn-outline-forest w-100 p-3 text-start rounded-4 payment-opt-card active" id="labelPaymentUpi" style="cursor: pointer;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="radio" name="payment_method" value="upi" checked class="form-check-input mt-0" id="radioPaymentUpi" style="cursor: pointer; width: 20px; height: 20px;">
                                            <div class="stat-icon stat-icon-gold flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                                <i class="bi bi-qr-code-scan"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block text-forest-dark fs-6">Direct UPI / QR Code Scanner</strong>
                                                <small class="text-muted">Google Pay, PhonePe, Paytm, BHIM & all UPI apps</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-gold text-forest-dark fw-bold small d-none d-sm-inline">Instant & Zero Fee</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Option 2: Cash on Delivery (COD) -->
                            <div class="col-12">
                                <label class="btn btn-outline-forest w-100 p-3 text-start rounded-4 payment-opt-card" id="labelPaymentCod" style="cursor: pointer;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="radio" name="payment_method" value="cash" class="form-check-input mt-0" id="radioPaymentCod" style="cursor: pointer; width: 20px; height: 20px;">
                                            <div class="stat-icon stat-icon-forest flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                                <i class="bi bi-truck"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block text-forest-dark fs-6">Cash on Delivery (COD)</strong>
                                                <small class="text-muted">Pay in cash or UPI to courier agent upon arrival</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-forest text-white small d-none d-sm-inline">Doorstep Delivery</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Option 3: Online Checkout (Cards / NetBanking) -->
                            <div class="col-12">
                                <label class="btn btn-outline-forest w-100 p-3 text-start rounded-4 payment-opt-card" id="labelPaymentRazorpay" style="cursor: pointer;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <input type="radio" name="payment_method" value="razorpay" class="form-check-input mt-0" id="radioPaymentRazorpay" style="cursor: pointer; width: 20px; height: 20px;">
                                            <div class="stat-icon stat-icon-gold flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                                <i class="bi bi-credit-card-2-front"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block text-forest-dark fs-6">Credit / Debit Card & NetBanking</strong>
                                                <small class="text-muted">Visa, MasterCard, RuPay & NetBanking Portal</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border small d-none d-sm-inline">Secured Gateway</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Dynamic UPI QR Scanner Panel (Shown when UPI is selected) -->
                        <div id="upiScannerDetailsPanel" class="mt-4 p-4 rounded-4 bg-cream border border-warning border-opacity-50" style="display: block;">
                            <div class="row align-items-center g-3">
                                <div class="col-sm-5 text-center">
                                    <div class="bg-white p-2 rounded-3 border shadow-xs d-inline-block">
                                        <img src="<?= e($qrCodeUrl); ?>" alt="Sanctuary Store UPI QR Code" class="img-fluid rounded" style="max-width: 180px; height: auto;">
                                    </div>
                                    <small class="d-block text-muted mt-1 extra-small"><i class="bi bi-shield-check text-success me-1"></i> Scan with any UPI app</small>
                                </div>
                                <div class="col-sm-7">
                                    <span class="badge bg-gold text-forest-dark fw-bold small mb-2"><i class="bi bi-patch-check-fill me-1"></i> Verified Merchant UPI</span>
                                    <div class="small text-muted mb-1">Official Sanctuary UPI VPA:</div>
                                    <div class="input-group mb-2">
                                        <input type="text" id="upiIdInput" class="form-control font-monospace bg-white text-forest-dark fw-bold" value="<?= e($upiId); ?>" readonly>
                                        <button class="btn btn-forest" type="button" id="btnCopyUpiId" title="Copy UPI ID">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        Amount to Pay: <strong class="text-forest-dark fs-6"><?= format_inr($cart['grand_total']); ?></strong>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label extra-small fw-bold text-muted mb-1">
                                            UPI 12-Digit UTR / Transaction Reference (Optional):
                                        </label>
                                        <input type="text" name="utr_number" class="form-control form-control-sm font-monospace" placeholder="e.g. 423819284712 (from payment receipt)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic COD Details Panel (Shown when Cash on Delivery is selected) -->
                        <div id="codDetailsPanel" class="mt-4 p-4 rounded-4 bg-cream border border-warning border-opacity-50" style="display: none;">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon stat-icon-forest flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif text-forest-dark mb-1">Doorstep Cash on Delivery Selected</h6>
                                    <p class="small text-muted mb-0 lh-base">
                                        You can pay the full order amount of <strong class="text-forest-dark"><?= format_inr($cart['grand_total']); ?></strong> directly in cash or via mobile UPI to the courier delivery executive upon arrival at your doorstep. No advance payment is needed now.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Online Cards Details Panel (Shown when Cards/NetBanking is selected) -->
                        <div id="razorpayDetailsPanel" class="mt-4 p-4 rounded-4 bg-cream border border-warning border-opacity-50" style="display: none;">
                            <div class="d-flex align-items-start gap-3">
                                <div class="stat-icon stat-icon-gold flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.2rem;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif text-forest-dark mb-1">Encrypted Payment Gateway</h6>
                                    <p class="small text-muted mb-0 lh-base">
                                        You will be securely connected to our RBI-authorized banking gateway to complete your payment via Credit/Debit card, RuPay, or NetBanking with instant automated confirmation.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right Column: Order Review Box -->
                <div class="col-lg-5">
                    <div class="card p-4 rounded-4 border-0 shadow-md bg-white sticky-top" style="top: 100px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h5 font-serif text-forest-dark mb-0"><i class="bi bi-bag-check text-gold me-2"></i> Order Summary</h3>
                            <span class="badge bg-gold-subtle text-gold-dark fw-bold"><?= $cart['count']; ?> <?= $cart['count'] === 1 ? 'Item' : 'Items'; ?></span>
                        </div>
                        
                        <div class="d-flex flex-column gap-3 mb-3">
                            <?php foreach ($cart['items'] as $item): ?>
                            <div class="d-flex justify-content-between align-items-center small pb-2 border-bottom">
                                <div class="pe-2">
                                    <strong class="text-forest-dark d-block"><?= e($item['name']); ?></strong>
                                    <span class="text-muted"><?= $item['quantity']; ?> &times; <?= format_inr($item['effective_price']); ?> (<?= e($item['unit']); ?>)</span>
                                </div>
                                <span class="font-serif text-forest-dark fw-bold text-nowrap"><?= format_inr($item['line_total']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Items Subtotal:</span>
                            <span class="fw-bold"><?= format_inr($cart['subtotal']); ?></span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">Courier Shipping:</span>
                            <span class="fw-bold <?= $cart['shipping'] == 0 ? 'text-success' : ''; ?>">
                                <?= $cart['shipping'] == 0 ? 'FREE' : format_inr($cart['shipping']); ?>
                            </span>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-5 font-serif text-forest-dark fw-bold">Total Payable:</span>
                            <span class="fs-3 font-serif text-forest-dark fw-bold text-gold-dark"><?= format_inr($cart['grand_total']); ?></span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold fs-5" id="btnPlaceOrderSubmit">
                                <i class="bi bi-check-circle-fill me-2"></i> Place Order & Pay (<?= format_inr($cart['grand_total']); ?>)
                            </button>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-shield-check text-forest me-1"></i> Safe & Tamper-Proof Packaging Guaranteed &bull; Instant Confirmation
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>

<!-- Client Side Interactive Payment Radio & Copy Helper Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioUpi = document.getElementById('radioPaymentUpi');
    const radioCod = document.getElementById('radioPaymentCod');
    const radioRazorpay = document.getElementById('radioPaymentRazorpay');

    const labelUpi = document.getElementById('labelPaymentUpi');
    const labelCod = document.getElementById('labelPaymentCod');
    const labelRazorpay = document.getElementById('labelPaymentRazorpay');

    const panelUpi = document.getElementById('upiScannerDetailsPanel');
    const panelCod = document.getElementById('codDetailsPanel');
    const panelRazorpay = document.getElementById('razorpayDetailsPanel');

    const btnSubmit = document.getElementById('btnPlaceOrderSubmit');
    const grandTotalText = '<?= format_inr($cart['grand_total']); ?>';

    function updatePaymentView() {
        [labelUpi, labelCod, labelRazorpay].forEach(el => el.classList.remove('active'));
        panelUpi.style.display = 'none';
        panelCod.style.display = 'none';
        panelRazorpay.style.display = 'none';

        if (radioUpi && radioUpi.checked) {
            labelUpi.classList.add('active');
            panelUpi.style.display = 'block';
            if (btnSubmit) {
                btnSubmit.innerHTML = '<i class="bi bi-qr-code-scan me-2"></i> Confirm UPI Payment & Place Order (' + grandTotalText + ')';
            }
        } else if (radioCod && radioCod.checked) {
            labelCod.classList.add('active');
            panelCod.style.display = 'block';
            if (btnSubmit) {
                btnSubmit.innerHTML = '<i class="bi bi-truck me-2"></i> Confirm Cash on Delivery Order (' + grandTotalText + ')';
            }
        } else if (radioRazorpay && radioRazorpay.checked) {
            labelRazorpay.classList.add('active');
            panelRazorpay.style.display = 'block';
            if (btnSubmit) {
                btnSubmit.innerHTML = '<i class="bi bi-shield-check me-2"></i> Proceed to Online Payment (' + grandTotalText + ')';
            }
        }
    }

    [radioUpi, radioCod, radioRazorpay].forEach(radio => {
        if (radio) {
            radio.addEventListener('change', updatePaymentView);
        }
    });

    [labelUpi, labelCod, labelRazorpay].forEach(label => {
        if (label) {
            label.addEventListener('click', function() {
                const r = this.querySelector('input[type="radio"]');
                if (r) {
                    r.checked = true;
                    updatePaymentView();
                }
            });
        }
    });

    updatePaymentView();

    // Copy UPI ID helper
    const btnCopy = document.getElementById('btnCopyUpiId');
    const upiInput = document.getElementById('upiIdInput');
    if (btnCopy && upiInput) {
        btnCopy.addEventListener('click', function() {
            navigator.clipboard.writeText(upiInput.value).then(() => {
                const originalHtml = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                btnCopy.classList.remove('btn-forest');
                btnCopy.classList.add('btn-success');
                setTimeout(() => {
                    btnCopy.innerHTML = originalHtml;
                    btnCopy.classList.remove('btn-success');
                    btnCopy.classList.add('btn-forest');
                }, 2500);
            }).catch(err => {
                console.error('Copy failed', err);
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
