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
        // Ensure floating WhatsApp boolean is stored properly
        if (!isset($settingsPost['enable_whatsapp_floating'])) {
            $settingsPost['enable_whatsapp_floating'] = '0';
        }

        // Process Multiple WhatsApp Numbers List
        $waNumbersInput = $_POST['wa_numbers'] ?? [];
        $activeWaIndex = isset($_POST['active_wa_index']) ? (int)$_POST['active_wa_index'] : 0;
        $cleanWaList = [];
        $selectedDefaultNumber = '';

        if (is_array($waNumbersInput)) {
            $currIdx = 0;
            foreach ($waNumbersInput as $key => $item) {
                $lbl = trim(sanitize_input($item['label'] ?? ''));
                $ph = trim(sanitize_input($item['phone'] ?? ''));
                if (!empty($ph)) {
                    $isDef = ($currIdx === $activeWaIndex) ? 1 : 0;
                    $cleanWaList[] = [
                        'id' => 'wa_' . ($currIdx + 1),
                        'label' => !empty($lbl) ? $lbl : 'Line ' . ($currIdx + 1),
                        'phone' => $ph,
                        'is_default' => $isDef
                    ];
                    if ($isDef === 1 || empty($selectedDefaultNumber)) {
                        $selectedDefaultNumber = $ph;
                    }
                    $currIdx++;
                }
            }
        }

        if (!empty($cleanWaList)) {
            $hasDef = false;
            foreach ($cleanWaList as $w) {
                if (!empty($w['is_default'])) { $hasDef = true; break; }
            }
            if (!$hasDef && isset($cleanWaList[0])) {
                $cleanWaList[0]['is_default'] = 1;
                $selectedDefaultNumber = $cleanWaList[0]['phone'];
            }
            $settingsPost['whatsapp_numbers_list'] = json_encode($cleanWaList, JSON_UNESCAPED_UNICODE);
            if (!empty($selectedDefaultNumber)) {
                $settingsPost['site_whatsapp'] = $selectedDefaultNumber;
                $settingsPost['whatsapp_number'] = $selectedDefaultNumber;
            }
        } elseif (isset($settingsPost['site_whatsapp'])) {
            $settingsPost['whatsapp_number'] = $settingsPost['site_whatsapp'];
            $settingsPost['whatsapp_numbers_list'] = json_encode([[
                'id' => 'wa_1',
                'label' => 'Main Seva Helpline',
                'phone' => $settingsPost['site_whatsapp'],
                'is_default' => 1
            ]], JSON_UNESCAPED_UNICODE);
        }

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
            <small class="text-muted">Direct administrative control over sanctuary branding, WhatsApp department lines, contact lines, statutory tax numbers, and bank UPI accounts.</small>
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
                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <span class="fw-bold text-forest-dark small"><i class="bi bi-layout-text-window-reverse text-gold me-1"></i> Homepage Hero Slides & Banners</span>
                            <p class="text-muted extra-small mb-0">Customize hero section headlines, Sanskrit taglines, call-to-action buttons, and photography.</p>
                        </div>
                        <a href="<?= BASE_URL; ?>/admin/hero.php" class="btn btn-sm btn-gold rounded-pill px-3 shadow-gold">
                            <i class="bi bi-sliders me-1"></i> Manage Hero Slides
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: General Information -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <h2 class="h6 font-serif text-forest-dark mb-3"><i class="bi bi-info-circle text-gold me-2"></i> General Sanctuary Identity</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Goushala Organization Name</label>
                    <input type="text" name="settings[site_name]" class="form-control" value="<?= e($settingsMap['site_name'] ?? 'Kamadenu Goushala'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Platform Tagline</label>
                    <input type="text" name="settings[site_tagline]" class="form-control" value="<?= e($settingsMap['site_tagline'] ?? 'Sacred Indigenous Cow Sanctuary & Research Centre'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Primary Phone Helpline</label>
                    <input type="text" name="settings[site_phone]" class="form-control" value="<?= e($settingsMap['site_phone'] ?? '+91 98450 12345'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Seva Email Desk</label>
                    <input type="email" name="settings[site_email]" class="form-control" value="<?= e($settingsMap['site_email'] ?? 'seva@kamadenugoushala.org'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Physical Sanctuary Address</label>
                    <input type="text" name="settings[site_address]" class="form-control" value="<?= e($settingsMap['site_address'] ?? 'Survey No. 42, Vedic Green Sanctuary Road, Near Nandi Hills Foothills, Bangalore Rural - 562103'); ?>">
                </div>
            </div>
        </div>

        <!-- Section 2.5: Sanctuary Visiting & Darshan Hours (Opening & Closing Times) -->
        <?php $currTimings = get_goushala_timings(); ?>
        <div class="p-4 rounded-4 bg-cream-soft border mb-4" id="visiting-hours-section">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                    <h2 class="h6 font-serif text-forest-dark mb-0">
                        <i class="bi bi-clock-history text-gold me-2 fs-5"></i> Goushala Opening & Closing Timings (Darshan Hours)
                    </h2>
                    <small class="text-muted">Configure public darshan slots, daily Aarti schedules, open days, and live status shown across the website & admin dashboard.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3 py-2 fw-bold shadow-xs" style="background: linear-gradient(135deg, #ff7a00 0%, #e65100 100%); color: #ffffff;">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i> <?= e($currTimings['status_text']); ?>
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-sun text-warning me-1"></i> Morning Darshan Slot (Opening - Closing) *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-warning"><i class="bi bi-sun-fill"></i></span>
                        <input type="text" name="settings[visiting_hours_morning]" class="form-control font-monospace" placeholder="e.g. 06:30 AM - 12:30 PM" value="<?= e($settingsMap['visiting_hours_morning'] ?? '06:30 AM - 12:30 PM'); ?>" required>
                    </div>
                    <small class="text-muted extra-small">Morning darshan, Go-puja, and fresh grass feeding slot.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-sunset text-gold me-1"></i> Evening Darshan Slot (Opening - Closing) *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-gold"><i class="bi bi-sunset-fill"></i></span>
                        <input type="text" name="settings[visiting_hours_evening]" class="form-control font-monospace" placeholder="e.g. 04:00 PM - 07:30 PM" value="<?= e($settingsMap['visiting_hours_evening'] ?? '04:00 PM - 07:30 PM'); ?>" required>
                    </div>
                    <small class="text-muted extra-small">Evening pasture darshan, deepa aarti, and parikrama slot.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-calendar3 text-forest me-1"></i> Operating Days of the Week</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-forest"><i class="bi bi-calendar-check-fill"></i></span>
                        <input type="text" name="settings[visiting_days]" class="form-control" placeholder="e.g. Open All 7 Days • Monday to Sunday" value="<?= e($settingsMap['visiting_days'] ?? 'Open All 7 Days • Monday to Sunday'); ?>">
                    </div>
                    <small class="text-muted extra-small">Displayed on visitor banners and header.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-bell-fill text-gold me-1"></i> Daily Aarti & Sankalpam Schedule</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-gold"><i class="bi bi-bell"></i></span>
                        <input type="text" name="settings[aarti_timings]" class="form-control" placeholder="e.g. Morning Gomata Aarti: 06:30 AM | Sandhya Deepa Aarti: 06:45 PM" value="<?= e($settingsMap['aarti_timings'] ?? 'Morning Gomata Aarti: 06:30 AM | Sandhya Deepa Aarti: 06:45 PM'); ?>">
                    </div>
                    <small class="text-muted extra-small">Public prayer and Vedic offering schedules.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-toggles text-forest me-1"></i> Live Status Override</label>
                    <select name="settings[visiting_status_override]" class="form-select">
                        <option value="auto" <?= (($settingsMap['visiting_status_override'] ?? 'auto') === 'auto') ? 'selected' : ''; ?>>Automatic (Live Clock Based on Morning & Evening Slots)</option>
                        <option value="open" <?= (($settingsMap['visiting_status_override'] ?? '') === 'open') ? 'selected' : ''; ?>>Always Show OPEN (Special Days / All Day Darshan)</option>
                        <option value="festival_special" <?= (($settingsMap['visiting_status_override'] ?? '') === 'festival_special') ? 'selected' : ''; ?>>Festival Special Darshan Open (Gopashtami / Pongal)</option>
                        <option value="closed" <?= (($settingsMap['visiting_status_override'] ?? '') === 'closed') ? 'selected' : ''; ?>>Temporarily Closed for Visitors (Sanctuary Maintenance)</option>
                    </select>
                    <small class="text-muted extra-small">Choose Automatic for real-time slot checking or override for festivals/maintenance.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark"><i class="bi bi-chat-heart text-gold me-1"></i> Visitor Darshan Note & Guidelines</label>
                    <textarea name="settings[goushala_timings_note]" class="form-control" rows="2" placeholder="e.g. Devotees and families are warmly welcome for sacred Gomata Darshan, fresh grass feeding, and sanctuary parikrama."><?= e($settingsMap['goushala_timings_note'] ?? 'Devotees and families are warmly welcome for sacred Gomata Darshan, fresh grass feeding, and sanctuary parikrama.'); ?></textarea>
                    <small class="text-muted extra-small">Brief warm note shown to devotees on homepage and contact page.</small>
                </div>
            </div>
        </div>

        <!-- Section 3: WhatsApp Seva Integration & Multi-Number Router Slab -->
        <div class="p-4 rounded-4 bg-cream-soft border mb-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                    <h2 class="h6 font-serif text-forest-dark mb-0">
                        <i class="bi bi-whatsapp text-success me-2 fs-5"></i> WhatsApp Management & Dedicated Line Router
                    </h2>
                    <small class="text-muted">Configure multiple WhatsApp numbers for various departments. Tick the radio button to select the active primary number.</small>
                </div>
                <?php 
                    $waCleanNum = preg_replace('/\D/', '', $settingsMap['site_whatsapp'] ?? $settingsMap['whatsapp_number'] ?? '919845012345');
                    $waPreviewMsg = urlencode($settingsMap['whatsapp_default_message'] ?? 'Namaste Kamadenu Goushala! I would like to inquire about Gau Seva.');
                ?>
                <a href="https://wa.me/<?= $waCleanNum; ?>?text=<?= $waPreviewMsg; ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs bg-white" title="Test Active Live WhatsApp Link">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Test Active Number
                </a>
            </div>

            <!-- Multi-Number List Repeater -->
            <div class="card p-3 rounded-3 bg-white border mb-4 shadow-xs">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small fw-bold text-forest-dark">
                        <i class="bi bi-telephone-plus-fill text-gold me-1"></i> Configured Department Lines & Numbers
                    </span>
                    <button type="button" class="btn btn-xs btn-forest rounded-pill px-3 shadow-xs" onclick="addWhatsAppRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add New Number
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-cream-soft small text-forest-dark">
                            <tr>
                                <th style="width: 130px;" class="text-center">Active Default</th>
                                <th>Department / Purpose Label</th>
                                <th>WhatsApp Phone Number (with country code)</th>
                                <th style="width: 70px;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="waNumbersContainer">
                            <?php 
                            $waNumbersList = get_whatsapp_numbers();
                            foreach ($waNumbersList as $idx => $wa): 
                            ?>
                            <tr class="wa-number-row" data-index="<?= $idx; ?>">
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center align-items-center mb-0">
                                        <input class="form-check-input wa-default-radio" type="radio" name="active_wa_index" value="<?= $idx; ?>" id="waDefRadio<?= $idx; ?>" <?= !empty($wa['is_default']) ? 'checked' : ''; ?> title="Set as Active Website Primary Number">
                                    </div>
                                    <small class="text-muted extra-small d-block">Site Default</small>
                                </td>
                                <td>
                                    <input type="text" name="wa_numbers[<?= $idx; ?>][label]" class="form-control form-control-sm" placeholder="e.g. Main Seva Helpline" value="<?= e($wa['label']); ?>" required>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                                        <input type="text" name="wa_numbers[<?= $idx; ?>][phone]" class="form-control form-control-sm font-monospace wa-phone-input" placeholder="+91 98450 12345" value="<?= e($wa['phone']); ?>" required>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-danger btn-xs rounded-pill px-2" onclick="removeWhatsAppRow(this)" title="Delete Number">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted extra-small"><i class="bi bi-info-circle me-1"></i> The active ticked radio button sets the global fallback and floating widget number for the site.</small>
                    <button type="button" class="btn btn-xs btn-outline-forest rounded-pill px-3" onclick="addWhatsAppRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Number
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark">Support Availability Hours</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-forest"><i class="bi bi-clock"></i></span>
                        <input type="text" name="settings[whatsapp_hours]" class="form-control" placeholder="e.g. 7:00 AM - 8:00 PM Daily" value="<?= e($settingsMap['whatsapp_hours'] ?? '7:00 AM - 8:00 PM Daily'); ?>">
                    </div>
                    <small class="text-muted">Shown on Contact and Seva support cards.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-forest-dark">Floating Live Chat Widget</label>
                    <div class="p-2 px-3 bg-white rounded-3 border d-flex align-items-center justify-content-between h-auto" style="min-height: 48px;">
                        <div>
                            <span class="small fw-bold text-forest-dark d-block">Floating WhatsApp Button</span>
                            <small class="text-muted extra-small">Display floating WhatsApp pulse button on all website pages.</small>
                        </div>
                        <div class="form-check form-switch mb-0 ms-2">
                            <input class="form-check-input" type="checkbox" name="settings[enable_whatsapp_floating]" value="1" id="switchWaFloating" <?= (($settingsMap['enable_whatsapp_floating'] ?? '1') === '1') ? 'checked' : ''; ?>>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-forest-dark">Default Website Chat Pre-filled Template</label>
                    <textarea name="settings[whatsapp_default_message]" class="form-control" rows="2" placeholder="e.g. Namaste Kamadenu Goushala! I would like to inquire about cow adoption, daily grass seva, and visiting timings."><?= e($settingsMap['whatsapp_default_message'] ?? 'Namaste Kamadenu Goushala! I would like to inquire about cow adoption, daily grass seva, and visiting timings.'); ?></textarea>
                    <small class="text-muted">When visitors tap the floating WhatsApp button, this message opens pre-typed in their chat.</small>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-forest-dark">WhatsApp Channel / Community Group URL (Optional)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-success"><i class="bi bi-broadcast"></i></span>
                        <input type="url" name="settings[whatsapp_channel_url]" class="form-control" placeholder="https://whatsapp.com/channel/..." value="<?= e($settingsMap['whatsapp_channel_url'] ?? ''); ?>">
                    </div>
                    <small class="text-muted">Link to your official WhatsApp broadcast channel or devotee community.</small>
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

<script>
function addWhatsAppRow() {
    const container = document.getElementById('waNumbersContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.wa-number-row');
    let maxIdx = -1;
    rows.forEach(r => {
        const idx = parseInt(r.getAttribute('data-index') || '0', 10);
        if (idx > maxIdx) maxIdx = idx;
    });
    const newIdx = maxIdx + 1;
    
    const tr = document.createElement('tr');
    tr.className = 'wa-number-row';
    tr.setAttribute('data-index', newIdx);
    tr.innerHTML = `
        <td class="text-center">
            <div class="form-check d-flex justify-content-center align-items-center mb-0">
                <input class="form-check-input wa-default-radio" type="radio" name="active_wa_index" value="${newIdx}" id="waDefRadio${newIdx}">
            </div>
            <small class="text-muted extra-small d-block">Site Default</small>
        </td>
        <td>
            <input type="text" name="wa_numbers[${newIdx}][label]" class="form-control form-control-sm" placeholder="e.g. Organic Store Line" value="Store Desk Line ${newIdx + 1}" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                <input type="text" name="wa_numbers[${newIdx}][phone]" class="form-control form-control-sm font-monospace wa-phone-input" placeholder="+91 98450 00000" required>
            </div>
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger btn-xs rounded-pill px-2" onclick="removeWhatsAppRow(this)" title="Delete Number">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(tr);
    showToast('New WhatsApp department row added. Fill details and save settings.', 'info');
}

function removeWhatsAppRow(btn) {
    const container = document.getElementById('waNumbersContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.wa-number-row');
    if (rows.length <= 1) {
        alert('At least one WhatsApp number is required for sanctuary operations.');
        return;
    }
    const row = btn.closest('.wa-number-row');
    const radio = row.querySelector('.wa-default-radio');
    const wasChecked = radio && radio.checked;
    row.remove();
    
    if (wasChecked) {
        const remainingRadios = container.querySelectorAll('.wa-default-radio');
        if (remainingRadios.length > 0) {
            remainingRadios[0].checked = true;
        }
    }
    showToast('WhatsApp number line removed.', 'warning');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
