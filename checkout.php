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
    $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'razorpay');

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

            $orderNumber = Order::placeOrder($customerData, $shippingData, $paymentMethod);

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
            Provide your courier delivery address to finalize your organic A2 order.
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
                
                <!-- Left Column: Customer & Shipping Details -->
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
                                <label class="form-label small fw-bold text-forest-dark">Mobile Phone *</label>
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
                        <h2 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-credit-card-2-front-fill text-gold me-2"></i> 3. Payment Method</h2>

                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3 active">
                                    <input type="radio" name="payment_method" value="razorpay" checked class="d-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-shield-check fs-4 text-gold"></i>
                                        <div>
                                            <strong class="d-block text-forest-dark">Online Checkout</strong>
                                            <small class="text-muted">UPI / Cards / NetBanking</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="btn btn-outline-forest w-100 p-3 text-start rounded-3">
                                    <input type="radio" name="payment_method" value="cash" class="d-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-truck fs-4 text-forest"></i>
                                        <div>
                                            <strong class="d-block text-forest-dark">Cash on Delivery</strong>
                                            <small class="text-muted">Pay upon arrival</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Right Column: Order Review Box -->
                <div class="col-lg-5">
                    <div class="card p-4 rounded-4 border-0 shadow-md bg-white sticky-top" style="top: 100px;">
                        <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-bag-check text-gold me-2"></i> Your Order Summary</h3>
                        
                        <div class="d-flex flex-column gap-3 mb-3">
                            <?php foreach ($cart['items'] as $item): ?>
                            <div class="d-flex justify-content-between align-items-center small pb-2 border-bottom">
                                <div>
                                    <strong class="text-forest-dark d-block"><?= e($item['name']); ?></strong>
                                    <span class="text-muted"><?= $item['quantity']; ?> &times; <?= format_inr($item['effective_price']); ?> (<?= e($item['unit']); ?>)</span>
                                </div>
                                <span class="font-serif text-forest-dark fw-bold"><?= format_inr($item['line_total']); ?></span>
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
                            <span class="fs-5 font-serif text-forest-dark fw-bold">Total Amount Payable:</span>
                            <span class="fs-3 font-serif text-forest-dark fw-bold"><?= format_inr($cart['grand_total']); ?></span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold fs-5">
                                <i class="bi bi-check-circle-fill me-2"></i> Place Order & Pay
                            </button>
                        </div>

                        <div class="text-center mt-3 small text-muted">
                            <i class="bi bi-lock-fill text-forest me-1"></i> Safe & Tamper-Proof Packaging Guaranteed
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
