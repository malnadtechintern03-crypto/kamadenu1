<?php
/**
 * Kamadenu Goushala Platform - Donation Policy & 80G Tax Information
 */

declare(strict_types=1);

$pageTitle = 'Donation Policy & 80G Tax Exemption';
$metaDescription = 'Understand our donation policy, Section 80G 50% tax exemption guidelines, fund utilization transparency, and receipt issuance at Kamadenu Goushala.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container text-center">
        <h1 class="page-hero-title">Donation Policy & 80G Benefits</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Transparency, Tax Exemption & Sacred Accountability for Every Rupee
        </p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 legal-content">
                <div class="p-4 rounded-4 bg-cream border border-warning border-opacity-50 mb-4 d-flex align-items-center gap-3">
                    <i class="bi bi-shield-fill-check text-gold fs-1 flex-shrink-0"></i>
                    <div>
                        <h3 class="h5 font-serif text-forest-dark mb-1">50% Income Tax Exemption Under Section 80G</h3>
                        <p class="small text-muted mb-0">
                            Kamadenu Goushala Charitable Trust is officially registered with the Income Tax Department of India under Section 80G (Unique Regn No: <strong>AABTK9812RF20214</strong>). All eligible donations qualify for a 50% deduction on taxable income.
                        </p>
                    </div>
                </div>

                <h3>1. Allocation of Donation Funds</h3>
                <p>All contributions received from devotees and donors are strictly allocated according to the donor's selected seva purpose:</p>
                <ul>
                    <li><strong>Feed a Cow (Grāsa Dāna):</strong> 100% utilized for procuring fresh green grass, jowar dry fodder, protein mash, and mineral lick blocks.</li>
                    <li><strong>Medical Care & Surgeries:</strong> Procuring veterinary medicines, sterile surgical sutures, deworming syrups, and specialized hospital equipment.</li>
                    <li><strong>Emergency Rescue Ambulance:</strong> Fuel, vehicle maintenance, rescue straps, and paramedic support.</li>
                    <li><strong>Shelter & Solar Infrastructure:</strong> Repairing waterproof shed roofs, automated water troughs, and solar water heaters for calves and senior cows.</li>
                </ul>

                <h3>2. How to Claim Your 80G Tax Exemption</h3>
                <p>
                    As mandated by the Central Board of Direct Taxes (CBDT), providing your <strong>Permanent Account Number (PAN)</strong> and full address is required to generate the Form 10BE certificate.
                </p>
                <ul>
                    <li>An official digital donation receipt containing our 80G registration number and donor details is generated immediately upon successful payment.</li>
                    <li>At the end of each financial year, we file Form 10BD with the Income Tax Department, allowing your deduction to be automatically pre-filled in your Annual Information Statement (AIS).</li>
                </ul>

                <h3>3. Accepted Modes of Donation</h3>
                <p>We accept contributions through secure and fully traceable digital and offline channels:</p>
                <ul>
                    <li><strong>UPI & QR Code:</strong> Direct transfer to <span class="font-monospace text-forest-dark fw-bold"><?= e(get_setting('upi_id', 'kamadenu@sbi')); ?></span>.</li>
                    <li><strong>Net Banking (NEFT / RTGS / IMPS):</strong> Direct bank transfer to our official State Bank of India trust account.</li>
                    <li><strong>Debit / Credit Cards & Netbanking Gateways:</strong> Instant online checkout with server-side HMAC verified security.</li>
                    <li><strong>Cheque / Demand Draft:</strong> In favor of <em>"Kamadenu Goushala Charitable Trust"</em>.</li>
                </ul>

                <h3>4. Foreign Contributions (FCRA Notice)</h3>
                <p class="text-muted">
                    Please note that under current FCRA regulations, donations can currently be accepted only from Indian bank accounts and Indian debit/credit cards or NRIs holding valid Indian passports/PAN cards.
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
