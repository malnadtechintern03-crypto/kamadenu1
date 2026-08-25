<?php
/**
 * Kamadenu Goushala Platform - Cow Adoption Portal & Digital Certificate Issuer
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$selectedCowId = (int)($_GET['cow_id'] ?? 0);
$allCows = Database::fetchAll("SELECT c.id, c.cow_code, c.name, b.name AS breed_name FROM cows c JOIN cow_breeds b ON c.breed_id = b.id WHERE c.status != 'deceased' ORDER BY c.name ASC");

$errors = [];
$successAdoption = null;

// Handle Adoption Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $cowId = (int)($_POST['cow_id'] ?? 0);
    $durationMonths = (int)($_POST['duration_months'] ?? 1);
    $adopterName = sanitize_input($_POST['adopter_name'] ?? '');
    $adopterEmail = sanitize_input($_POST['adopter_email'] ?? '');
    $adopterPhone = sanitize_input($_POST['adopter_phone'] ?? '');
    $adopterAddress = sanitize_input($_POST['adopter_address'] ?? '');
    $adopterPan = sanitize_input($_POST['adopter_pan'] ?? '');
    $monthlyAmount = 3000.00;

    if (empty($cowId)) {
        $errors[] = 'Please select a cow to adopt.';
    }
    if (empty($adopterName)) {
        $errors[] = 'Please enter your full name.';
    }
    if (empty($adopterEmail) || !filter_var($adopterEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($adopterPhone)) {
        $errors[] = 'Please provide your mobile phone number.';
    }

    if (empty($errors)) {
        try {
            $totalAmount = $monthlyAmount * $durationMonths;
            
            // 1. Create Adoption Record & Certificate
            $adoptionResult = Adoption::create([
                'cow_id'          => $cowId,
                'adopter_name'    => $adopterName,
                'adopter_email'   => $adopterEmail,
                'adopter_phone'   => $adopterPhone,
                'adopter_address' => $adopterAddress,
                'duration_months' => $durationMonths,
                'monthly_amount'  => $monthlyAmount,
                'notes'           => '80G PAN: ' . ($adopterPan ?: 'N/A')
            ]);

            // 2. Mark cow as adopted
            Database::execute("UPDATE cows SET status = 'adopted' WHERE id = ?", [$cowId]);

            // 3. Record Payment & Receipt
            $paySql = "
                INSERT INTO payments (
                    transaction_id, reference_type, reference_id, gateway,
                    amount, status, paid_at
                ) VALUES (?, 'adoption', (SELECT id FROM adoptions WHERE adoption_number = ?), 'razorpay', ?, 'captured', NOW())
            ";
            $txnId = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $paymentDbId = Database::insert($paySql, [$txnId, $adoptionResult['adoption_number'], $totalAmount]);

            // 4. Generate 80G Tax Exemption Receipt
            $receiptNo = 'REC-80G-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            Database::insert("
                INSERT INTO receipts (receipt_number, reference_type, reference_id, payment_id, donor_name, donor_pan, amount, tax_exemption_80g)
                VALUES (?, 'adoption', (SELECT id FROM adoptions WHERE adoption_number = ?), ?, ?, ?, ?, 1)
            ", [
                $receiptNo,
                $adoptionResult['adoption_number'],
                $paymentDbId,
                $adopterName,
                $adopterPan,
                $totalAmount
            ]);

            // Redirect to Digital Certificate View
            header('Location: ' . BASE_URL . '/adoption-certificate.php?cert=' . urlencode($adoptionResult['certificate_number']));
            exit;

        } catch (Throwable $t) {
            error_log('Adoption processing error: ' . $t->getMessage());
            $errors[] = 'An unexpected error occurred while processing your adoption. Please try again.';
        }
    }
}

$pageTitle = 'Adopt a Cow (Māsa Seva) – Become a Sacred Guardian';
$metaDescription = 'Adopt a rescued cow at Kamadenu Goushala. Choose your duration, receive an official digital adoption certificate, and support lifelong nutrition and medical care.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-suit-heart-fill me-1"></i> Māsa Seva Guardian Program
        </span>
        <h1 class="page-hero-title">Adopt a Sacred Rescued Cow</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Become a lifelong spiritual guardian for a rescued cow. Receive a personalized Digital Certificate, monthly health updates, and perform sacred pujas during visits.
        </p>
    </div>
</section>

<!-- Adoption Multi-Step Form Section -->
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

                    <form method="POST" action="<?= BASE_URL; ?>/adopt.php" id="adoptionForm">
                        <?= csrf_field(); ?>

                        <!-- Step 1: Select Cow -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-1-circle-fill text-gold me-2"></i> 1. Select the Cow You Wish to Adopt</label>
                            <select name="cow_id" class="form-select form-select-lg rounded-3" required>
                                <option value="">-- Choose a Blessed Cow --</option>
                                <?php foreach ($allCows as $c): ?>
                                    <option value="<?= $c['id']; ?>" <?= ($selectedCowId === $c['id']) ? 'selected' : ''; ?>>
                                        <?= e($c['name']); ?> (<?= e($c['cow_code']); ?>) – <?= e($c['breed_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Don't know which cow to choose? <a href="<?= BASE_URL; ?>/cows.php" target="_blank" class="text-forest fw-semibold">Browse our Cow Directory first <i class="bi bi-box-arrow-up-right"></i></a>
                            </div>
                        </div>

                        <!-- Step 2: Select Duration -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-2-circle-fill text-gold me-2"></i> 2. Select Adoption Duration</label>
                            <div class="row g-3" id="adoptionDurationCards">
                                <div class="col-6 col-sm-3">
                                    <label class="btn btn-outline-forest w-100 p-3 text-center rounded-4 h-100 active">
                                        <input type="radio" name="duration_months" value="1" data-amount="3000" checked class="d-none">
                                        <div class="fw-bold fs-5">1 Month</div>
                                        <div class="text-gold-dark fw-bold mt-1">₹ 3,000</div>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="btn btn-outline-forest w-100 p-3 text-center rounded-4 h-100">
                                        <input type="radio" name="duration_months" value="3" data-amount="9000" class="d-none">
                                        <div class="fw-bold fs-5">3 Months</div>
                                        <div class="text-gold-dark fw-bold mt-1">₹ 9,000</div>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="btn btn-outline-forest w-100 p-3 text-center rounded-4 h-100">
                                        <input type="radio" name="duration_months" value="6" data-amount="18000" class="d-none">
                                        <div class="fw-bold fs-5">6 Months</div>
                                        <div class="text-gold-dark fw-bold mt-1">₹ 18,000</div>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="btn btn-outline-forest w-100 p-3 text-center rounded-4 h-100">
                                        <input type="radio" name="duration_months" value="12" data-amount="36000" class="d-none">
                                        <div class="fw-bold fs-5">1 Year</div>
                                        <div class="text-gold-dark fw-bold mt-1">₹ 36,000</div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Adopter Information -->
                        <div class="mb-4">
                            <label class="form-label font-serif fs-5 text-forest-dark"><i class="bi bi-3-circle-fill text-gold me-2"></i> 3. Guardian (Adopter) Details</label>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Full Name *</label>
                                    <input type="text" name="adopter_name" class="form-control" placeholder="e.g. Ramesh Chandra Sharma" required value="<?= e($_POST['adopter_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Email Address *</label>
                                    <input type="email" name="adopter_email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['adopter_email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">Mobile Phone *</label>
                                    <input type="tel" name="adopter_phone" class="form-control" placeholder="+91 98765 43210" required value="<?= e($_POST['adopter_phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-forest-dark">PAN (For 80G 50% Tax Deduction)</label>
                                    <input type="text" name="adopter_pan" class="form-control text-uppercase" placeholder="ABCDE1234F" maxlength="10" value="<?= e($_POST['adopter_pan'] ?? ''); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-forest-dark">Address (For Official Certificate Delivery)</label>
                                    <textarea name="adopter_address" class="form-control" rows="2" placeholder="Full residential / postal address..."><?= e($_POST['adopter_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Summary & Instant Giving -->
                        <div class="p-4 rounded-4 bg-cream border border-warning border-opacity-50 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-forest-dark">Total Adoption Guardian Contribution:</span>
                                <span class="font-serif text-forest-dark fs-3 fw-bold" id="adoptionDisplayTotal">₹ 3,000</span>
                            </div>
                            <p class="small text-muted mb-0">
                                <i class="bi bi-shield-check text-forest me-1"></i> Includes instant digital certificate generation, 80G tax receipt, and monthly health bulletins.
                            </p>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold">
                                <i class="bi bi-patch-check-fill me-2"></i> Confirm Adoption & Generate Certificate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Perks of Adoption Sidebar -->
            <div class="col-lg-4">
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-award-fill text-gold me-2"></i> What You Receive as Guardian</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 small text-muted mb-0">
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Official Digital Certificate:</strong>
                                <p class="mb-0">A framed printable Certificate of Guardianship with your cow's photo and Vedic blessing seal.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Monthly Health & Photo Bulletin:</strong>
                                <p class="mb-0">Regular updates from our veterinary team on your cow's diet, vitals, and pasture life.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Unlimited Sanctuary Visits & Grooming:</strong>
                                <p class="mb-0">Visit and perform special family Gomata puja anytime during darshan hours.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Direct WhatsApp Adoption Desk -->
                <?php
                    $adoptWaPhone = get_setting('site_whatsapp', '+91 98450 12345');
                    $cleanAdoptWaPhone = preg_replace('/\D/', '', $adoptWaPhone);
                    $adoptWaMsg = "🙏 *Namaste Kamadenu Goushala!*\n\n" .
                                  "I would like to adopt a sacred cow (Māsa Seva) under the Guardian Program.\n" .
                                  "✨ *Monthly Seva:* ₹ 3,000 / month\n" .
                                  "📜 *Includes:* Digital Certificate, 80G Tax Exemption & regular photo bulletins.\n\n" .
                                  "Please guide me on how to proceed with the adoption.";
                    $adoptWaUrl = "https://wa.me/" . $cleanAdoptWaPhone . "?text=" . rawurlencode($adoptWaMsg);
                ?>
                <div class="card p-4 rounded-4 bg-cream-soft border border-success border-opacity-50 text-center shadow-xs mb-4">
                    <i class="bi bi-whatsapp text-success fs-2 mb-2"></i>
                    <h4 class="h6 font-serif text-forest-dark mb-1">Prefer Adopting via WhatsApp?</h4>
                    <p class="small text-muted mb-3">Connect directly with our Seva Desk to choose your cow and complete adoption formalities.</p>
                    <a href="<?= e($adoptWaUrl); ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm rounded-pill w-100 fw-bold shadow-xs">
                        <i class="bi bi-whatsapp me-1"></i> Chat with Adoption Desk
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Duration Selector Dynamic Calculation -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="duration_months"]');
    const displayTotal = document.getElementById('adoptionDisplayTotal');

    radios.forEach(r => {
        r.addEventListener('change', () => {
            radios.forEach(item => item.closest('.btn').classList.remove('active'));
            r.closest('.btn').classList.add('active');
            const amt = parseInt(r.getAttribute('data-amount'), 10);
            displayTotal.textContent = '₹ ' + amt.toLocaleString('en-IN');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
