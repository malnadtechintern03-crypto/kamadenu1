<?php
/**
 * Kamadenu Goushala Platform - Privacy Policy
 */

declare(strict_types=1);

$pageTitle = 'Privacy Policy';
$metaDescription = 'Privacy Policy of Kamadenu Goushala. We are committed to protecting donor, adopter, and visitor privacy with strict data security.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container text-center">
        <h1 class="page-hero-title">Privacy Policy</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Last Updated: August 2026 | Kamadenu Goushala Charitable Trust
        </p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 legal-content">
                <p class="lead text-muted">
                    At <strong>Kamadenu Goushala</strong> ("we", "our", or "us"), we deeply respect and protect the personal information of our donors, cow guardians, visitors, and customers. This Privacy Policy details how we collect, safeguard, and utilize your information across our website and digital platforms.
                </p>

                <h3>1. Information We Collect</h3>
                <p>We may collect personal identification information from you in several ways, including when you visit our website, make a Gau Seva donation, adopt a cow, purchase organic A2 products, or fill out a contact form:</p>
                <ul>
                    <li><strong>Donor & Adopter Details:</strong> Full Name, Email Address, Mobile Phone Number, Residential / Postal Address.</li>
                    <li><strong>Statutory Tax Data:</strong> Permanent Account Number (PAN) strictly required for Section 80G tax exemption certificates under the Income Tax Act of India.</li>
                    <li><strong>Transaction Details:</strong> Payment method, date, transaction reference IDs (we do NOT store credit/debit card details or bank passwords).</li>
                    <li><strong>Technical Data:</strong> IP address, browser type, device information, and interaction logs collected for security and fraud prevention.</li>
                </ul>

                <h3>2. How We Use Collected Information</h3>
                <p>We use your information exclusively for legitimate organizational purposes:</p>
                <ul>
                    <li>Generating and delivering official 80G Tax Exemption donation receipts and Adoption Certificates.</li>
                    <li>Processing orders for organic A2 products and coordinating courier delivery.</li>
                    <li>Sending monthly medical and wellness photo updates of your adopted or sponsored cows.</li>
                    <li>Responding to your inquiries, festival seva requests, or sanctuary darshan bookings.</li>
                    <li>Complying with statutory audits, financial accounting, and regulatory filings in India.</li>
                </ul>

                <h3>3. Protection and Security of Your Information</h3>
                <p>
                    We adopt industry-standard data collection, storage, and processing practices, including TLS 1.3 encryption, prepared database statements, and strict role-based administrative access to protect against unauthorized access, alteration, or disclosure.
                </p>

                <h3>4. Payment Security & Third-Party Processors</h3>
                <p>
                    All online monetary transactions are securely processed through RBI-authorized payment gateways (e.g., Razorpay, UPI networks). We never capture, store, or view sensitive payment instruments such as CVV numbers or card PINs on our servers.
                </p>

                <h3>5. Non-Disclosure & Third-Party Sharing</h3>
                <p>
                    We do NOT sell, trade, or rent donor or customer personal information to third parties or marketing agencies under any circumstances. Information is only shared with statutory auditors and regulatory bodies as mandated by Indian law.
                </p>

                <h3>6. Contact Us Regarding Your Privacy</h3>
                <p>
                    If you have questions about this Privacy Policy or wish to update your donor profile, please reach out to us at <a href="mailto:<?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?>"><?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?></a> or call <a href="tel:<?= e(get_setting('site_phone', '+919845012345')); ?>"><?= e(get_setting('site_phone', '+91 98450 12345')); ?></a>.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
