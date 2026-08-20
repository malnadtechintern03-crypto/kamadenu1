<?php
/**
 * Kamadenu Goushala Platform - Refund & Cancellation Policy
 */

declare(strict_types=1);

$pageTitle = 'Refund & Cancellation Policy';
$metaDescription = 'Refund, cancellation, and transaction dispute policies for Kamadenu Goushala donations, cow adoptions, and organic products.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container text-center">
        <h1 class="page-hero-title">Refund & Cancellation Policy</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Clear, Fair & Transparent Guidelines for Donations and Orders
        </p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 legal-content">
                <p class="lead text-muted">
                    At <strong>Kamadenu Goushala</strong>, we value the trust and goodwill of our donors, supporters, and buyers. Below is our policy regarding donation cancellations, erroneous transactions, and physical product returns.
                </p>

                <h3>1. Voluntary Gau Seva Donations</h3>
                <p>
                    Donations made towards Gau Seva (Grāsa Dāna, Medical Care, Rescue, Adoption, or General Sanctuary Maintenance) are voluntary charitable gifts and are generally non-refundable once processed and allocated for animal feed or medical supplies.
                </p>

                <h3>2. Erroneous or Duplicate Online Transactions</h3>
                <p>
                    In the event of an unintended technical error, such as a duplicate deduction, erroneous amount entry, or unauthorized transaction, please notify us within <strong>7 days</strong> of the transaction date:
                </p>
                <ul>
                    <li>Email us at <a href="mailto:<?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?>"><?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?></a> with your Transaction ID, Bank Reference Number, and proof of payment.</li>
                    <li>Upon verification by our accounts team, approved refunds will be credited back to the original source bank account/card within <strong>5 to 7 working days</strong>.</li>
                </ul>

                <h3>3. Recurring Cow Adoption Sponsorship Cancellations</h3>
                <p>
                    Monthly cow adopters may cancel or pause their recurring sponsorship at any time by contacting our support team or updating their preferences before the next billing cycle.
                </p>

                <h3>4. Physical Organic A2 Products (Returns & Exchanges)</h3>
                <p>
                    For orders of our traditional A2 Bilona Ghee, Panchagavya Ghrita, or Dhoop Cups:
                </p>
                <ul>
                    <li><strong>Damaged in Transit / Seal Broken:</strong> If you receive a jar with broken tamper seal or shipping damage, please send photo evidence within <strong>48 hours</strong> of delivery to receive an immediate free replacement or full refund.</li>
                    <li><strong>Incorrect Item Delivered:</strong> If an incorrect item was dispatched, we will arrange a complimentary pickup and deliver the correct product at zero additional cost.</li>
                </ul>

                <h3>5. Contact for Refund Inquiries</h3>
                <p>
                    Please contact our accounts desk directly at <a href="mailto:accounts@kamadenugoushala.org">accounts@kamadenugoushala.org</a> or call <a href="tel:<?= e(get_setting('site_phone', '+919845012345')); ?>"><?= e(get_setting('site_phone', '+91 98450 12345')); ?></a> for immediate assistance.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
