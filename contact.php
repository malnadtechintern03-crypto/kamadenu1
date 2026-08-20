<?php
/**
 * Kamadenu Goushala Platform - Contact Us & Sanctuary Darshan
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$errors = [];
$successMessage = '';

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? 'General Inquiry');
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($name)) $errors[] = 'Please enter your full name.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';
    if (empty($message)) $errors[] = 'Please enter your message or question.';

    if (empty($errors)) {
        try {
            Database::insert("
                INSERT INTO contact_messages (name, email, phone, subject, message, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ", [$name, $email, $phone, $subject, $message]);

            $successMessage = 'Namaste! Your message has been received. Our sanctuary seva desk will respond within 24 hours.';
            $_POST = []; // Clear fields
        } catch (Throwable $t) {
            error_log('Contact form error: ' . $t->getMessage());
            $errors[] = 'Could not send message. Please try again or reach us on WhatsApp.';
        }
    }
}

$pageTitle = 'Contact Us & Sanctuary Darshan – Kamadenu Goushala';
$metaDescription = 'Contact Kamadenu Goushala. Visit our Nandi Hills sanctuary for morning cow darshan, seva volunteering, and educational workshops.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-geo-alt-fill me-1"></i> Foothills of Nandi Hills
        </span>
        <h1 class="page-hero-title">Contact & Sanctuary Darshan</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Experience the divine serenity of free-grazing indigenous cows in nature. We welcome devotees, volunteers, and animal lovers daily.
        </p>
    </div>
</section>

<!-- Main Contact Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <div class="row g-5">
            
            <!-- Left Column: Contact Form -->
            <div class="col-lg-7">
                <div class="card p-4 p-md-5 rounded-4 border-0 shadow-md bg-white">
                    <span class="section-tag"><i class="bi bi-envelope-fill"></i> Reach Out to Us</span>
                    <h2 class="h3 font-serif text-forest-dark mb-4">Send a Message / Book Darshan</h2>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success rounded-3 mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div><?= e($successMessage); ?></div>
                        </div>
                    <?php endif; ?>

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

                    <form method="POST" action="<?= BASE_URL; ?>/contact.php">
                        <?= csrf_field(); ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Your Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Radhakrishna" required value="<?= e($_POST['name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Email Address *</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" required value="<?= e($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Mobile Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?= e($_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-forest-dark">Subject / Purpose</label>
                                <select name="subject" class="form-select">
                                    <option value="Sanctuary Visit / Darshan">Sanctuary Visit / Darshan</option>
                                    <option value="Gau Seva & Adoption Inquiry">Gau Seva & Adoption Inquiry</option>
                                    <option value="Emergency Cow Rescue Assistance">Emergency Cow Rescue Assistance</option>
                                    <option value="Organic Products & Bulk Orders">Organic Products & Bulk Orders</option>
                                    <option value="Volunteer Seva / School Visit">Volunteer Seva / School Visit</option>
                                    <option value="Other Query">Other Query</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-forest-dark">Your Message *</label>
                                <textarea name="message" class="form-control" rows="4" placeholder="How can we assist you with Gau Seva or sanctuary darshan?" required><?= e($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-gold btn-lg rounded-pill px-5 fw-bold shadow-gold">
                                    <i class="bi bi-send-fill me-2"></i> Submit Inquiry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Sanctuary Info, Darshan Timings & Map -->
            <div class="col-lg-5">
                
                <!-- Contact Details Card -->
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-info-circle text-gold me-2"></i> Sanctuary Coordinates</h3>
                    
                    <ul class="list-unstyled d-flex flex-column gap-3 small text-muted mb-0">
                        <li class="d-flex gap-3">
                            <i class="bi bi-geo-alt-fill text-forest fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-forest-dark d-block">Physical Sanctuary Address:</strong>
                                <span><?= e(get_setting('site_address', 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore Rural - 562103')); ?></span>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <i class="bi bi-telephone-fill text-forest fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-forest-dark d-block">Helpline & Seva Desk:</strong>
                                <a href="tel:<?= e(get_setting('site_phone', '+91 98450 12345')); ?>" class="text-forest text-decoration-none"><?= e(get_setting('site_phone', '+91 98450 12345')); ?></a>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <i class="bi bi-whatsapp text-success fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-forest-dark d-block">WhatsApp Seva Support:</strong>
                                <a href="https://wa.me/919845012345" target="_blank" class="text-success text-decoration-none">+91 98450 12345 (Click to Chat)</a>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <i class="bi bi-envelope-fill text-forest fs-5 flex-shrink-0"></i>
                            <div>
                                <strong class="text-forest-dark d-block">Email Desk:</strong>
                                <a href="mailto:<?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?>" class="text-forest text-decoration-none"><?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?></a>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Darshan Hours Card -->
                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-md mb-4">
                    <h3 class="h5 font-serif text-gold mb-3"><i class="bi bi-clock-history me-2"></i> Daily Darshan & Seva Hours</h3>
                    <div class="d-flex justify-content-between pb-2 border-bottom border-secondary border-opacity-50 small">
                        <span><i class="bi bi-sun-fill text-gold me-1"></i> Morning Gau Aarti & Darshan</span>
                        <strong class="text-cream">6:30 AM – 11:30 AM</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-2 small">
                        <span><i class="bi bi-sunset-fill text-gold me-1"></i> Evening Pasture & Puja</span>
                        <strong class="text-cream">4:00 PM – 7:00 PM</strong>
                    </div>
                    <div class="small text-cream opacity-75 mt-3">
                        <i class="bi bi-check2-circle text-gold me-1"></i> Open on all 365 days including Sundays and National Holidays.
                    </div>
                </div>

                <!-- Interactive Google Map Frame -->
                <div class="card p-2 rounded-4 border-0 shadow-sm overflow-hidden bg-white">
                    <div class="ratio ratio-16x9 rounded-3 overflow-hidden">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d124312.3582457857!2d77.61895995820313!3d13.370216500000004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bb1e523f2b6e159%3A0xc3ce704cf4c803df!2sNandi%20Hills!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
