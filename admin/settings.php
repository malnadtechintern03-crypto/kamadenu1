<?php
/**
 * Kamadenu Goushala Platform - Admin Sanctuary Settings & Logo/Branding Manager
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin']);

$currentUser = get_logged_in_user();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    // Handle Logo Upload if present
    if (!empty($_FILES['site_logo']['name'])) {
        try {
            $logoFilename = UploadService::upload($_FILES['site_logo'], 'logo');
            Database::execute("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES ('site_logo', ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ", [$logoFilename]);
            log_activity((int)$currentUser['id'], 'update_logo', 'settings', null, "Uploaded new sanctuary logo: {$logoFilename}");
        } catch (Exception $e) {
            set_flash('danger', 'Logo upload failed: ' . $e->getMessage());
        }
    }

    $settingsPost = $_POST['settings'] ?? [];
    if (is_array($settingsPost)) {
        foreach ($settingsPost as $key => $val) {
            $key = sanitize_input($key);
            $val = sanitize_input($val);
            Database::execute("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ", [$key, $val]);
        }
        log_activity((int)$currentUser['id'], 'update_settings', 'settings', null, "Updated sanctuary configuration settings");
        set_flash('success', 'Sanctuary configuration settings updated successfully.');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    }
}

$allSettings = Database::fetchAll("SELECT * FROM settings ORDER BY id ASC");
$settingsMap = [];
foreach ($allSettings as $st) {
    $settingsMap[$st['setting_key']] = $st['setting_value'];
}

$pageTitle = 'Sanctuary Configuration & Platform Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card p-4 p-md-5 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 font-serif text-forest-dark mb-0">Sanctuary Control Center & Platform Settings</h1>
            <small class="text-muted">Direct administrative control over sanctuary branding, contact lines, statutory tax numbers, and bank UPI accounts.</small>
        </div>
    </div>

    <form method="POST" action="<?= BASE_URL; ?>/admin/settings.php" enctype="multipart/form-data">
        <?= csrf_field(); ?>

        <!-- Section 1: Branding & Logo -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <h2 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-palette-fill text-gold me-2"></i> Branding & Visual Identity</h2>
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-forest-dark">Current Official Logo</label>
                    <div class="p-3 bg-white rounded-3 border text-center">
                        <img 
                            src="<?= BASE_URL; ?>/<?= !empty($settingsMap['site_logo']) ? (str_starts_with($settingsMap['site_logo'], 'uploads/') ? e($settingsMap['site_logo']) : 'uploads/logo/' . e($settingsMap['site_logo'])) : 'assets/images/logo.png'; ?>" 
                            alt="Sanctuary Logo" 
                            class="img-fluid" 
                            style="max-height: 70px;"
                            onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/logo.png';"
                        >
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label small fw-bold text-forest-dark">Upload New Sanctuary Logo (PNG, WEBP, JPG max 5MB)</label>
                    <input type="file" name="site_logo" class="form-control" accept="image/png,image/webp,image/jpeg">
                    <small class="text-muted">Recommended dimensions: 512x512px transparent PNG.</small>
                </div>
            </div>
        </div>

        <!-- Section 2: General Information -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <h2 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-info-circle text-gold me-2"></i> General Sanctuary Identity</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Goushala Organization Name</label>
                    <input type="text" name="settings[site_name]" class="form-control" value="<?= e($settingsMap['site_name'] ?? 'Kamadenu Goushala'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Platform Tagline</label>
                    <input type="text" name="settings[site_tagline]" class="form-control" value="<?= e($settingsMap['site_tagline'] ?? 'Sacred Indigenous Cow Sanctuary & Research Centre'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Primary Phone Helpline</label>
                    <input type="text" name="settings[site_phone]" class="form-control" value="<?= e($settingsMap['site_phone'] ?? '+91 98450 12345'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">WhatsApp Direct Number</label>
                    <input type="text" name="settings[whatsapp_number]" class="form-control" value="<?= e($settingsMap['whatsapp_number'] ?? '+91 98450 12345'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Seva Email Desk</label>
                    <input type="email" name="settings[site_email]" class="form-control" value="<?= e($settingsMap['site_email'] ?? 'seva@kamadenugoushala.org'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Physical Sanctuary Address</label>
                    <input type="text" name="settings[site_address]" class="form-control" value="<?= e($settingsMap['site_address'] ?? 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore Rural - 562103'); ?>">
                </div>
            </div>
        </div>

        <!-- Section 3: Bank & UPI Accounts -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <h2 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-bank2 text-gold me-2"></i> Bank & Direct UPI Configuration</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Beneficiary Account Name</label>
                    <input type="text" name="settings[bank_account_name]" class="form-control" value="<?= e($settingsMap['bank_account_name'] ?? 'Kamadenu Goushala Charitable Trust'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Bank Name</label>
                    <input type="text" name="settings[bank_name]" class="form-control" value="<?= e($settingsMap['bank_name'] ?? 'State Bank of India'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Bank Account Number</label>
                    <input type="text" name="settings[bank_account_number]" class="form-control font-monospace" value="<?= e($settingsMap['bank_account_number'] ?? '398201948571'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">IFSC Code</label>
                    <input type="text" name="settings[bank_ifsc]" class="form-control font-monospace text-uppercase" value="<?= e($settingsMap['bank_ifsc'] ?? 'SBIN0004281'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">UPI ID (VPA)</label>
                    <input type="text" name="settings[upi_id]" class="form-control font-monospace" value="<?= e($settingsMap['upi_id'] ?? 'kamadenu@sbi'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Branch Name</label>
                    <input type="text" name="settings[bank_branch]" class="form-control" value="<?= e($settingsMap['bank_branch'] ?? 'Nandi Hills Branch, Bangalore'); ?>">
                </div>
            </div>
        </div>

        <!-- Section 4: Statutory & 80G Information -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <h2 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-shield-check text-gold me-2"></i> Section 80G Statutory & Tax Registration</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Section 80G Exemption Declaration Notice</label>
                    <textarea name="settings[tax_exemption_info]" class="form-control" rows="2"><?= e($settingsMap['tax_exemption_info'] ?? 'Donations are eligible for 50% Tax Exemption under Section 80G of the Income Tax Act. Registration No: AABTK9812RF20214.'); ?></textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-gold btn-lg rounded-pill px-5 fw-bold shadow-gold">
                <i class="bi bi-save me-1"></i> Save Platform Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
