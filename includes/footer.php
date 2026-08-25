<?php
/**
 * Kamadenu Goushala Platform - Global Page Footer
 */

declare(strict_types=1);
?>
<!-- Master Heritage Footer -->
<footer class="heritage-footer">
    <div class="container">
        <div class="row g-4 pb-4">
            <!-- Col 1: About Kamadenu & Mission -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="navbar-brand-logo" style="width:42px;height:42px;font-size:1.3rem;">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <div>
                        <h4 class="font-serif text-cream mb-0 fs-4"><?= e(get_setting('site_name', 'Kamadenu Goushala')); ?></h4>
                        <small class="text-gold-light text-uppercase tracking-wider">Sanctuary of Compassion</small>
                    </div>
                </div>
                <p class="text-cream opacity-75 small mb-3">
                    Kamadenu Goushala is a non-profit Vedic sanctuary dedicated to rescuing abandoned, injured, and slaughter-bound indigenous cows. We provide holistic medical care, organic pasture grazing, and lifelong love under Vedic principles.
                </p>
                <div class="p-3 rounded-3 bg-forest-subtle border border-warning border-opacity-25 mb-3">
                    <p class="small text-gold-light mb-0">
                        <i class="bi bi-shield-fill-check me-1 text-gold"></i>
                        <strong>80G Tax Exemption:</strong> All donations are 50% tax exempt under Section 80G of the Income Tax Act.
                    </p>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <a href="<?= e(get_setting('facebook_url', '#')); ?>" class="footer-social-icon" title="Facebook" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                    <a href="<?= e(get_setting('instagram_url', '#')); ?>" class="footer-social-icon" title="Instagram" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                    <a href="<?= e(get_setting('youtube_url', '#')); ?>" class="footer-social-icon" title="YouTube" target="_blank" rel="noopener"><i class="bi bi-youtube"></i></a>
                    <a href="<?= e(get_setting('twitter_url', '#')); ?>" class="footer-social-icon" title="Twitter" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i></a>
                    <a href="https://wa.me/<?= preg_replace('/\D/', '', get_setting('site_whatsapp', '919845012345')); ?>" class="footer-social-icon" title="WhatsApp" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <!-- Col 2: Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Explore</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL; ?>/index.php"><i class="bi bi-chevron-right text-gold"></i> Home</a></li>
                    <li><a href="<?= BASE_URL; ?>/about.php"><i class="bi bi-chevron-right text-gold"></i> About Sanctuary</a></li>
                    <li><a href="<?= BASE_URL; ?>/cows.php"><i class="bi bi-chevron-right text-gold"></i> Meet Our Cows</a></li>
                    <li><a href="<?= BASE_URL; ?>/breeds.php"><i class="bi bi-chevron-right text-gold"></i> Indigenous Breeds</a></li>
                    <li><a href="<?= BASE_URL; ?>/products.php"><i class="bi bi-chevron-right text-gold"></i> Vedic A2 Store</a></li>
                    <li><a href="<?= BASE_URL; ?>/gallery.php"><i class="bi bi-chevron-right text-gold"></i> Photo Gallery</a></li>
                    <li><a href="<?= BASE_URL; ?>/blog.php"><i class="bi bi-chevron-right text-gold"></i> Rescue Stories</a></li>
                </ul>
            </div>

            <!-- Col 3: Gau Seva Programs -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Gau Seva</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL; ?>/feed.php"><i class="bi bi-heart text-gold"></i> Feed a Cow (Grāsa Dāna)</a></li>
                    <li><a href="<?= BASE_URL; ?>/adopt.php"><i class="bi bi-heart text-gold"></i> Monthly Cow Adoption</a></li>
                    <li><a href="<?= BASE_URL; ?>/sponsor.php"><i class="bi bi-heart text-gold"></i> Sponsor Senior Cow</a></li>
                    <li><a href="<?= BASE_URL; ?>/seva.php"><i class="bi bi-heart text-gold"></i> Emergency Medical Care</a></li>
                    <li><a href="<?= BASE_URL; ?>/donate.php"><i class="bi bi-heart text-gold"></i> General Goushala Donation</a></li>
                    <li><a href="<?= BASE_URL; ?>/transparency.php"><i class="bi bi-graph-up-arrow text-gold"></i> Financial Transparency</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact & Bank Info -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Get in Touch</h5>
                <p class="small text-cream opacity-75 mb-2">
                    <i class="bi bi-geo-alt-fill text-gold me-2"></i>
                    <?= e(get_setting('site_address', 'Nandi Hills Foothills, Bangalore Rural, Karnataka - 562103')); ?>
                </p>
                <p class="small text-cream opacity-75 mb-2">
                    <i class="bi bi-telephone-fill text-gold me-2"></i>
                    <a href="tel:<?= e(get_setting('site_phone', '+919845012345')); ?>" class="text-cream"><?= e(get_setting('site_phone', '+91 98450 12345')); ?></a>
                </p>
                <p class="small text-cream opacity-75 mb-2">
                    <i class="bi bi-envelope-fill text-gold me-2"></i>
                    <a href="mailto:<?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?>" class="text-cream"><?= e(get_setting('site_email', 'seva@kamadenugoushala.org')); ?></a>
                </p>
                <?php $footTimings = get_goushala_timings(); ?>
                <p class="small text-cream opacity-75 mb-3">
                    <i class="bi bi-clock-history text-gold me-2"></i>
                    <span><strong>Darshan:</strong> <?= e($footTimings['morning']); ?> &bull; <?= e($footTimings['evening']); ?></span>
                </p>

                <div class="p-2 rounded bg-black bg-opacity-25 border border-secondary border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-gold text-forest-dark fw-bold">Direct UPI</span>
                        <span class="font-monospace text-gold-light small fw-bold"><?= e(get_setting('upi_id', 'kamadenu@sbi')); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Copyright & Policies -->
    <div class="footer-bottom">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
                &copy; <?= date('Y'); ?> <?= e(get_setting('site_name', 'Kamadenu Goushala')); ?>. All rights reserved. A Non-Profit Vedic Trust.
            </div>
            <div class="d-flex flex-wrap gap-3 small">
                <a href="<?= BASE_URL; ?>/privacy.php" class="text-cream opacity-75">Privacy Policy</a>
                <a href="<?= BASE_URL; ?>/terms.php" class="text-cream opacity-75">Terms of Seva</a>
                <a href="<?= BASE_URL; ?>/refund.php" class="text-cream opacity-75">Refund Policy</a>
                <a href="<?= BASE_URL; ?>/donation-policy.php" class="text-cream opacity-75">Donation Policy</a>
                <a href="<?= ADMIN_URL; ?>/login.php" class="text-gold"><i class="bi bi-lock-fill"></i> Staff Portal</a>
            </div>
        </div>
    </div>
<?php if (get_setting('enable_whatsapp_floating', '1') === '1'): 
    $allWaLines = get_whatsapp_numbers();
    $primaryWaNumber = preg_replace('/\D/', '', get_primary_whatsapp_number());
    $waDefaultMsg = get_setting('whatsapp_default_message', 'Namaste Kamadenu Goushala! I would like to inquire about Gau Seva.');
    $primaryWaUrl = 'https://wa.me/' . $primaryWaNumber . (!empty($waDefaultMsg) ? ('?text=' . urlencode($waDefaultMsg)) : '');
?>
<!-- Floating WhatsApp Action Widget (Available on All Pages) -->
<?php if (count($allWaLines) > 1): ?>
    <button type="button" class="floating-whatsapp-btn border-0 cursor-pointer" data-bs-toggle="modal" data-bs-target="#whatsappQuickHelpModal" title="Connect with Sanctuary on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </button>
<?php else: ?>
    <a href="<?= e($primaryWaUrl); ?>" class="floating-whatsapp-btn" target="_blank" rel="noopener" title="Chat with Seva Desk on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>
<?php endif; ?>

<!-- WhatsApp Multi-Department Routing Modal -->
<div class="modal fade" id="whatsappQuickHelpModal" tabindex="-1" aria-labelledby="waHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header bg-forest-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp text-success fs-4"></i>
                    <div>
                        <h5 class="modal-title font-serif fs-6 mb-0 text-gold" id="waHelpModalLabel">Connect on WhatsApp</h5>
                        <small class="text-cream opacity-75 extra-small">Select a department to chat</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-cream-soft">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($allWaLines as $line): 
                        $cleanLineNum = preg_replace('/\D/', '', $line['phone']);
                        $deptUrl = 'https://wa.me/' . $cleanLineNum . '?text=' . urlencode("Namaste Kamadenu Goushala! I am connecting regarding {$line['label']}.");
                    ?>
                    <a href="<?= e($deptUrl); ?>" target="_blank" rel="noopener" class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between text-decoration-none shadow-xs transition-all hover-translate">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon-wrapper rounded-circle p-2 bg-success-subtle text-success" style="width:34px;height:34px;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <span class="small fw-bold text-forest-dark d-block"><?= e($line['label']); ?></span>
                                <span class="extra-small text-muted font-monospace"><?= e($line['phone']); ?></span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty(get_setting('whatsapp_hours'))): ?>
                    <div class="text-center mt-3 pt-2 border-top">
                        <small class="text-muted extra-small"><i class="bi bi-clock me-1"></i>Hours: <?= e(get_setting('whatsapp_hours')); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Master Main JS -->
<script src="<?= ASSETS_URL; ?>/js/main.js"></script>

</body>
</html>
