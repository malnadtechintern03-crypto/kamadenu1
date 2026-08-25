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
                            <div class="w-100">
                                <strong class="text-forest-dark d-block mb-1">WhatsApp Seva Lines & Desks:</strong>
                                <?php 
                                    $contactWaLines = get_whatsapp_numbers();
                                    $contactWaMsg = get_setting('whatsapp_default_message', 'Namaste Kamadenu Goushala! I would like to inquire about Gau Seva.');
                                    foreach ($contactWaLines as $cLine):
                                        $cClean = preg_replace('/\D/', '', $cLine['phone']);
                                        $cUrl = 'https://wa.me/' . $cClean . '?text=' . urlencode($contactWaMsg);
                                ?>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <div>
                                            <span class="small fw-semibold text-forest-dark"><?= e($cLine['label']); ?></span>
                                            <small class="text-muted d-block extra-small"><?= e($cLine['phone']); ?></small>
                                        </div>
                                        <a href="<?= e($cUrl); ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" title="Chat on WhatsApp">
                                            <i class="bi bi-whatsapp me-1"></i> Chat
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty(get_setting('whatsapp_hours'))): ?>
                                    <small class="text-muted d-block extra-small mt-1"><i class="bi bi-clock me-1"></i>Hours: <?= e(get_setting('whatsapp_hours')); ?></small>
                                <?php endif; ?>
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

                <!-- Dynamic Darshan & Visiting Hours Card -->
                <?php $contactTimings = get_goushala_timings(); ?>
                <div class="card p-4 rounded-4 text-white shadow-md mb-4" style="background: linear-gradient(145deg, #18281e 0%, #0d1a13 100%); border: 1.5px solid rgba(255, 179, 0, 0.45); box-shadow: 0 8px 24px rgba(230, 81, 0, 0.18);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 font-serif text-gold mb-0"><i class="bi bi-clock-history text-gold me-2"></i> Daily Darshan & Seva Hours</h3>
                        <span class="badge rounded-pill px-3 py-1 small fw-bold shadow-xs" style="background: linear-gradient(135deg, #ff7a00 0%, #e65100 100%); color: #ffffff;">
                            <?= e($contactTimings['status_text']); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom" style="border-color: rgba(255, 179, 0, 0.2) !important;">
                        <span class="d-flex align-items-center text-white small">
                            <span class="rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="background: rgba(255, 107, 0, 0.2); width: 26px; height: 26px;">
                                <i class="bi bi-sun-fill" style="color: #ff9100;"></i>
                            </span>
                            <strong class="text-white">Morning Darshan:</strong>
                        </span>
                        <span class="badge font-monospace fw-bold fs-6 px-3 py-1 shadow-xs" style="background: linear-gradient(135deg, #ff7a00 0%, #e65100 100%); color: #ffffff; border: 1px solid #ffa726;">
                            <?= e($contactTimings['morning']); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom" style="border-color: rgba(255, 179, 0, 0.2) !important;">
                        <span class="d-flex align-items-center text-white small">
                            <span class="rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="background: rgba(255, 179, 0, 0.2); width: 26px; height: 26px;">
                                <i class="bi bi-sunset-fill" style="color: #ffb300;"></i>
                            </span>
                            <strong class="text-white">Evening Darshan:</strong>
                        </span>
                        <span class="badge font-monospace fw-bold fs-6 px-3 py-1 shadow-xs" style="background: linear-gradient(135deg, #ffb300 0%, #f57f17 100%); color: #122016; border: 1px solid #ffd54f;">
                            <?= e($contactTimings['evening']); ?>
                        </span>
                    </div>
                    <div class="p-2 px-3 rounded-3 small my-2 d-flex align-items-center gap-2" style="background: rgba(230, 81, 0, 0.15); border: 1px solid rgba(255, 179, 0, 0.4); color: #ffe082;">
                        <i class="bi bi-bell-fill fs-6 flex-shrink-0" style="color: #ffb300;"></i>
                        <span class="fw-semibold"><?= e($contactTimings['aarti']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="badge px-3 py-1 shadow-xs" style="background: #0f3d2a; color: #a3e635; border: 1px solid rgba(163, 230, 53, 0.4);">
                            <i class="bi bi-calendar-check-fill me-1"></i> <?= e($contactTimings['days']); ?>
                        </span>
                        <span class="extra-small fw-semibold" style="color: #ffd54f;">
                            <i class="bi bi-flower1 me-1"></i> Free Darshan & Parikrama
                        </span>
                    </div>
                    <?php if (!empty($contactTimings['note'])): ?>
                        <div class="extra-small text-cream opacity-85 mt-3 pt-2 border-top border-white border-opacity-10 fst-italic">
                            <?= e($contactTimings['note']); ?>
                        </div>
                    <?php endif; ?>
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
