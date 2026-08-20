<?php
/**
 * Kamadenu Goushala Platform - About Us Page
 */

declare(strict_types=1);

$pageTitle = 'About Us – Our Sacred Vedic Mission & Sanctuary';
$metaDescription = 'Learn about Kamadenu Goushala, our 15-acre sanctuary in the Nandi Hills foothills, our dedicated veterinary team, and our commitment to indigenous cow preservation.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-flower1 me-1"></i> Gāvo Viśvasya Mātaraḥ (Cows are the Mothers of the Universe)
        </span>
        <h1 class="page-hero-title">Our Sacred Mission & Heritage</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Dedicated to the rescue, rehabilitation, and lifetime veneration of indigenous Indian cattle (Bos Indicus) through Vedic care and modern veterinary science.
        </p>
    </div>
</section>

<!-- Sanctuary Genesis & Founding Story -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-tag"><i class="bi bi-compass"></i> Our Origins</span>
                <h2 class="section-title">A Sanctuary Born Out of Devotion</h2>
                <p class="text-muted">
                    Founded in 2012 by revered spiritual seekers and veterinary doctors at the serene foothills of Nandi Hills in Karnataka, <strong>Kamadenu Goushala</strong> started with just three rescued cows found injured by the roadside.
                </p>
                <p class="text-muted">
                    Over the last 14 years, our humble effort has blossomed into a comprehensive 15-acre holistic sanctuary housing hundreds of rescued cattle. We provide round-the-clock intensive medical treatment, lush organic pastures, and an environment of pure love and non-violence.
                </p>
                <div class="row g-3 mt-2">
                    <div class="col-6">
                        <div class="p-3 bg-cream rounded-3 border">
                            <h3 class="h4 font-serif text-forest-dark mb-1">15+ Acres</h3>
                            <p class="small text-muted mb-0">Organic Grazing Pasture</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-cream rounded-3 border">
                            <h3 class="h4 font-serif text-forest-dark mb-1">24x7 Care</h3>
                            <p class="small text-muted mb-0">Veterinary ICU & Ambulance</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-image-wrapper mb-4">
                    <img
                        src="<?= BASE_URL; ?>/assets/images/about-goushala.jpg"
                        alt="Kamadenu Goushala sanctuary"
                        class="img-fluid rounded-5 shadow-lg w-100"
                        loading="lazy"
                    >
                </div>
                <div class="heritage-card p-4 bg-forest-dark text-white rounded-4 border-warning">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="navbar-brand-logo" style="width:56px;height:56px;font-size:1.8rem;">
                            <i class="bi bi-shield-fill-check text-gold"></i>
                        </div>
                        <div>
                            <h3 class="font-serif text-cream mb-0">The Kamadenu Vow</h3>
                            <small class="text-gold">Our Guiding Sacred Principles</small>
                        </div>
                    </div>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                        <li class="d-flex gap-3 align-items-start">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-cream">No Abandonment or Commercial Exploitation:</strong>
                                <p class="small opacity-75 mb-0">Every cow lives peacefully until its natural last breath regardless of age or milk yield.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-3 align-items-start">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-cream">Indigenous Breed Preservation:</strong>
                                <p class="small opacity-75 mb-0">Specialized conservation for Gir, Sahiwal, Hallikar, Tharparkar, and Malnad Gidda genetics.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-3 align-items-start">
                            <i class="bi bi-check-circle-fill text-gold fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-cream">Holistic Vedic & Modern Veterinary Synthesis:</strong>
                                <p class="small opacity-75 mb-0">Ayurvedic herbs and organic nutrition paired with sterile surgical care and regular vaccinations.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Pillars -->
<section class="py-5 bg-cream-soft">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-columns-gap"></i> Foundational Pillars</span>
            <h2 class="section-title">How We Serve Mother Cow</h2>
            <p class="section-subtitle mx-auto">Our four-fold framework ensures holistic animal welfare and ecological sustainability.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="stat-icon-wrapper mb-3">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-2">Emergency Rescue</h3>
                    <p class="small text-muted mb-0">
                        Our dedicated ambulance network operates 24/7 across highways and urban centers to rescue abandoned, accident-hit, or trafficking victims.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="stat-icon-wrapper mb-3" style="background:rgba(214,154,58,0.15);color:var(--color-gold-dark);">
                        <i class="bi bi-bandaid"></i>
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-2">Veterinary Hospital</h3>
                    <p class="small text-muted mb-0">
                        Equipped with a surgical theatre, digital ultrasound, foreign body rumenotomy equipment, sterile recovery pens, and round-the-clock vets.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="stat-icon-wrapper mb-3" style="background:rgba(139,94,60,0.12);color:var(--color-earth);">
                        <i class="bi bi-shield-heart"></i>
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-2">Senior Cow Hospice</h3>
                    <p class="small text-muted mb-0">
                        Blind, handicapped, and elderly cows receive soft cushioned bedding, warm digestible herbal porridge, and pain-management therapies.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <div class="stat-icon-wrapper mb-3">
                        <i class="bi bi-tree"></i>
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-2">Eco-Pasture & Biogas</h3>
                    <p class="small text-muted mb-0">
                        100% organic closed-loop ecosystem generating solar energy, organic fertilizer (Pramana manure), and medicinal Panchagavya.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Leadership & Veterinary Team -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="section-tag justify-content-center"><i class="bi bi-people"></i> Dedicated Guardians</span>
            <h2 class="section-title">The Heart Behind Kamadenu</h2>
            <p class="section-subtitle mx-auto">Guided by Vedic scholars, dedicated veterinarians, and tireless caretakers.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="heritage-card text-center p-4 h-100">
                    <div class="testimonial-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;">
                        R
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-1">Mahant Shri Radheyshyam</h3>
                    <p class="small text-gold-dark fw-semibold mb-3">Spiritual Founder & Trustee</p>
                    <p class="small text-muted mb-0">
                        Devoted over 30 years to Vedic animal welfare, scripture education, and leading our daily Gau aarti and spiritual programs.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="heritage-card text-center p-4 h-100">
                    <div class="testimonial-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;background:rgba(214,154,58,0.15);color:var(--color-gold-dark);">
                        N
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-1">Dr. H. V. Narayana</h3>
                    <p class="small text-gold-dark fw-semibold mb-3">Chief Veterinary Officer (MVSc)</p>
                    <p class="small text-muted mb-0">
                        Renowned livestock surgeon with 22 years of surgical expertise in bovine polythene extraction, fracture stabilization, and herbal immunology.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="heritage-card text-center p-4 h-100">
                    <div class="testimonial-avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;background:rgba(139,94,60,0.12);color:var(--color-earth);">
                        P
                    </div>
                    <h3 class="h5 font-serif text-forest-dark mb-1">Ramesh Patel</h3>
                    <p class="small text-gold-dark fw-semibold mb-3">Sanctuary Operations Manager</p>
                    <p class="small text-muted mb-0">
                        Coordinates daily green fodder harvesting, shelter sanitization, donor visits, and organic A2 Bilona ghee preparation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Visitor Guidelines & Darshan Timings -->
<section class="py-5 bg-cream">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="section-tag"><i class="bi bi-clock-history"></i> Sanctuary Darshan</span>
                <h2 class="section-title">Visitor Guidelines & Seva Hours</h2>
                <p class="text-muted">
                    We warmly welcome families, schools, and spiritual seekers to visit, perform Gau Puja, and experience the immense peace of touching and feeding our mother cows.
                </p>
                <div class="d-flex flex-column gap-3 mt-3">
                    <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-xs">
                        <i class="bi bi-sun-fill text-warning fs-3"></i>
                        <div>
                            <strong class="text-forest-dark">Morning Darshan & Aarti:</strong>
                            <div class="text-muted small">7:00 AM – 12:00 PM (Gau Puja & Feeding)</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 shadow-xs">
                        <i class="bi bi-moon-stars-fill text-forest fs-3"></i>
                        <div>
                            <strong class="text-forest-dark">Evening Pasture Grazing & Aarti:</strong>
                            <div class="text-muted small">3:30 PM – 6:30 PM (Sunset Sandhya Aarti)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-4 bg-white border shadow-sm">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-shield-exclamation text-gold me-2"></i> Sanctuary Etiquette</h3>
                    <ul class="small text-muted d-flex flex-column gap-2 mb-4">
                        <li><i class="bi bi-check2 text-forest me-2"></i>Please treat all cows with calmness and affection. Avoid loud noises or sudden gestures.</li>
                        <li><i class="bi bi-check2 text-forest me-2"></i>Only feed items provided or approved by our sanctuary staff (fresh jaggery, clean carrots, cut green grass).</li>
                        <li><i class="bi bi-check2 text-forest me-2"></i>Plastic bags, processed junk food, and outside commercial fodder are strictly prohibited on premises.</li>
                        <li><i class="bi bi-check2 text-forest me-2"></i>Photography for personal and devotional memories is warmly welcomed.</li>
                    </ul>
                    <a href="<?= BASE_URL; ?>/contact.php" class="btn btn-gold w-100 rounded-pill">
                        <i class="bi bi-calendar-check me-1"></i> Schedule a Group or Family Visit
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statutory Trust Credentials -->
<section class="py-5 bg-white border-top text-center">
    <div class="container py-2">
        <h3 class="h5 font-serif text-forest-dark mb-3">Registered Charitable Trust Credentials</h3>
        <div class="d-flex flex-wrap justify-content-center gap-4 text-muted small">
            <div><i class="bi bi-patch-check-fill text-gold me-1"></i> Trust Reg. No: <strong>BK-IV-4921/2012</strong></div>
            <div><i class="bi bi-patch-check-fill text-gold me-1"></i> Section 80G Approval: <strong>AABTK9812RF20214</strong></div>
            <div><i class="bi bi-patch-check-fill text-gold me-1"></i> 12A Certification: <strong>AAATK1928ME2014</strong></div>
            <div><i class="bi bi-patch-check-fill text-gold me-1"></i> AWBI Recognized: <strong>KA-881/2016</strong></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
