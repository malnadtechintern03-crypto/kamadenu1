<?php
/**
 * Kamadenu Goushala Platform - Admin Cows Management
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/services/UploadService.php';

require_role(['super_admin', 'admin', 'manager', 'editor', 'staff']);

$currentUser = get_logged_in_user();

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['delete', 'delete_cow'], true)) {
    verify_csrf_or_die();
    require_role(['super_admin', 'admin', 'manager']);
    
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        $cow = Database::fetchOne("SELECT * FROM cows WHERE id = ?", [$deleteId]);
        if ($cow) {
            // Delete associated cow main image from uploads if stored
            if (!empty($cow['main_image'])) {
                UploadService::delete($cow['main_image'], 'cows');
            }
            
            // Delete gallery images if any
            try {
                $galleryImages = Database::fetchAll("SELECT image FROM cow_images WHERE cow_id = ?", [$deleteId]);
                foreach ($galleryImages as $gImg) {
                    if (!empty($gImg['image'])) {
                        UploadService::delete($gImg['image'], 'cows');
                    }
                }
                Database::execute("DELETE FROM cow_images WHERE cow_id = ?", [$deleteId]);
            } catch (Throwable $e) {
                // Table might not exist or empty
            }

            // Clean related tables before deletion
            try { Database::execute("DELETE FROM cow_medical_records WHERE cow_id = ?", [$deleteId]); } catch (Throwable $e) {}
            try { Database::execute("DELETE FROM cow_vaccinations WHERE cow_id = ?", [$deleteId]); } catch (Throwable $e) {}
            try { Database::execute("DELETE FROM cow_diet_plans WHERE cow_id = ?", [$deleteId]); } catch (Throwable $e) {}
            try { Database::execute("DELETE FROM sponsors WHERE cow_id = ?", [$deleteId]); } catch (Throwable $e) {}
            try { Database::execute("DELETE FROM adoptions WHERE cow_id = ?", [$deleteId]); } catch (Throwable $e) {}

            // Delete cow record
            Database::execute("DELETE FROM cows WHERE id = ?", [$deleteId]);
            log_activity((int)($currentUser['id'] ?? 0), 'delete_cow', 'cows', $deleteId, "Deleted cow '{$cow['name']}' ({$cow['cow_code']})");
            set_flash('success', "Cow '{$cow['name']}' ({$cow['cow_code']}) was deleted successfully.");
        } else {
            set_flash('danger', 'Cow record not found.');
        }
        header('Location: ' . BASE_URL . '/admin/cows.php');
        exit;
    }
}

$search = sanitize_input($_GET['q'] ?? '');
$breedId = (int)($_GET['breed_id'] ?? 0);
$status = sanitize_input($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(c.name LIKE ? OR c.cow_code LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($breedId > 0) {
    $where[] = "c.breed_id = ?";
    $params[] = $breedId;
}
if (!empty($status)) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

$cows = Database::fetchAll("
    SELECT c.*, b.name AS breed_name 
    FROM cows c 
    JOIN cow_breeds b ON c.breed_id = b.id 
    WHERE {$whereClause} 
    ORDER BY c.id DESC
", $params);

$breeds = Breed::getAllWithCount();

$pageTitle = 'Manage Sanctuary Cows Directory';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Sanctuary Cows Catalog (<?= count($cows); ?>)</h2>
            <small class="text-muted">Register, update health vitals, and manage adoption statuses.</small>
        </div>
        <a href="<?= BASE_URL; ?>/admin/cow-edit.php" class="btn btn-forest rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Add New Cow
        </a>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?= BASE_URL; ?>/admin/cows.php" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name or code..." value="<?= e($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="breed_id" class="form-select form-select-sm">
                <option value="">All Breeds</option>
                <?php foreach ($breeds as $b): ?>
                    <option value="<?= $b['id']; ?>" <?= ($breedId === $b['id']) ? 'selected' : ''; ?>><?= e($b['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="available" <?= ($status === 'available') ? 'selected' : ''; ?>>Available for Adoption</option>
                <option value="adopted" <?= ($status === 'adopted') ? 'selected' : ''; ?>>Adopted</option>
                <option value="in_treatment" <?= ($status === 'in_treatment') ? 'selected' : ''; ?>>In Treatment</option>
                <option value="permanent_resident" <?= ($status === 'permanent_resident') ? 'selected' : ''; ?>>Permanent Resident</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-gold btn-sm w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
    </form>

    <!-- Cows Data Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-forest-dark text-white small">
                <tr>
                    <th>Code & Cow</th>
                    <th>Breed</th>
                    <th>Age / Gender</th>
                    <th>Health Status</th>
                    <th>Adoption Status</th>
                    <th>Rescue Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cows)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No cows match your search criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cows as $c): 
                        $healthClass = match($c['health_status']) {
                            'under_treatment' => 'badge-health-treatment',
                            'elderly_care'   => 'badge-health-elderly',
                            'recovering'     => 'badge-health-recovering',
                            default          => 'badge-health-healthy'
                        };
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($c['main_image'])): ?>
                                    <img src="<?= image_url($c['main_image'], 'cows', 'placeholder-cow.jpg'); ?>" alt="<?= e($c['name']); ?>" class="rounded-3 object-fit-cover flex-shrink-0" style="width: 38px; height: 38px;">
                                <?php else: ?>
                                    <div class="rounded-3 bg-forest-dark text-gold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                        <i class="bi bi-flower1"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <a href="<?= BASE_URL; ?>/admin/cow-edit.php?id=<?= $c['id']; ?>" class="text-decoration-none text-forest-dark fw-bold hover-forest d-block">
                                        <?= e($c['name']); ?>
                                    </a>
                                    <span class="font-monospace small text-muted d-block"><?= e($c['cow_code']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($c['breed_name']); ?></td>
                        <td>
                            <span class="small"><?= calculate_cow_age($c['date_of_birth']); ?></span>
                            <small class="text-muted d-block"><?= ucfirst($c['gender']); ?></small>
                        </td>
                        <td>
                            <span class="badge <?= $healthClass; ?> badge-heritage">
                                <?= ucfirst(str_replace('_', ' ', $c['health_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($c['status'] === 'adopted'): ?>
                                <span class="badge bg-gold text-forest-dark fw-bold">Adopted</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border">Available</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= format_date($c['rescue_date']); ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <!-- WhatsApp Share & Message Editor Button -->
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#whatsappCowModal<?= $c['id']; ?>" title="Share on WhatsApp with Custom Message">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($c['slug']); ?>" target="_blank" class="btn btn-outline-secondary" title="View Public Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL; ?>/admin/cow-edit.php?id=<?= $c['id']; ?>" class="btn btn-outline-forest" title="Edit Cow">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (has_role(['super_admin', 'admin', 'manager'])): ?>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/cows.php" onsubmit="return confirm('Are you sure you want to permanently delete cow record \'<?= e(addslashes($c['name'])); ?>\' (<?= e($c['cow_code']); ?>)? This action cannot be undone.');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_cow">
                                    <input type="hidden" name="id" value="<?= $c['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger" title="Delete Cow">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- WhatsApp Message Editing & Share Modals for Each Cow -->
<?php if (!empty($cows)): ?>
    <?php foreach ($cows as $c): 
        $cowPublicUrl = BASE_URL . '/cow-details.php?slug=' . urlencode($c['slug']);
        $cowAge = calculate_cow_age($c['date_of_birth']);
        $cowHealth = ucfirst(str_replace('_', ' ', $c['health_status']));
        $cowStatusText = ($c['status'] === 'adopted') ? 'Currently Adopted by Devotee' : 'Available for Monthly Adoption (₹ 3,000/mo)';
        $sitePhone = get_setting('site_phone', '+91 98450 12345');

        $defaultMsg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" .
                      "Meet our sacred resident cow:\n" .
                      "🐮 *Name:* " . $c['name'] . " (" . $c['cow_code'] . ")\n" .
                      "🌾 *Breed:* " . $c['breed_name'] . " (" . ucfirst($c['gender']) . ")\n" .
                      "⏳ *Age:* " . $cowAge . "\n" .
                      "🏥 *Health Status:* " . $cowHealth . "\n" .
                      "❤️ *Adoption Status:* " . $cowStatusText . "\n\n" .
                      "✨ *About Her:* " . mb_strimwidth($c['description'] ?: ($c['rescue_story'] ?: 'Nurtured with loving care at our Nandi Hills sanctuary.'), 0, 140, '...') . "\n\n" .
                      "🔗 *View Sacred Profile & Adopt:* " . $cowPublicUrl . "\n\n" .
                      "📞 *Helpline / Seva Desk:* " . $sitePhone . "\n" .
                      "🙏 *Jai Gau Mata!*";
    ?>
    <div class="modal fade" id="whatsappCowModal<?= $c['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white p-4">
                    <h5 class="modal-title font-serif">
                        <i class="bi bi-whatsapp text-success me-2 fs-5"></i> WhatsApp Message Editor: <?= e($c['name']); ?> (<?= e($c['cow_code']); ?>)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <!-- Cow Info Preview Strip -->
                    <div class="d-flex align-items-center gap-3 p-3 bg-cream-soft rounded-3 border mb-3">
                        <div class="rounded-3 border overflow-hidden flex-shrink-0" style="width: 55px; height: 55px; background: var(--color-forest-dark);">
                            <img src="<?= e(image_url($c['main_image'], 'cows', 'placeholder-cow.jpg')); ?>" alt="<?= e($c['name']); ?>" class="w-100 h-100 object-fit-cover" onerror="this.onerror=null;this.src='<?= BASE_URL; ?>/assets/images/placeholder-cow.jpg';">
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h6 class="font-serif text-forest-dark mb-0 fw-bold"><?= e($c['name']); ?> (<?= e($c['cow_code']); ?>)</h6>
                                    <small class="text-muted"><?= e($c['breed_name']); ?> &bull; <?= $cowAge; ?> &bull; <?= ucfirst($c['gender']); ?></small>
                                </div>
                                <div>
                                    <span class="badge bg-gold text-forest-dark fw-bold"><?= ucfirst($c['status']); ?></span>
                                    <span class="badge bg-success-subtle text-success border ms-1"><?= $cowHealth; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipient & Preset Controls -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Recipient WhatsApp Number (Optional)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-success"><i class="bi bi-whatsapp"></i></span>
                                <input type="text" id="waRecipientPhone<?= $c['id']; ?>" class="form-control font-monospace" placeholder="e.g. 919845012345 (Optional)" oninput="updateWaLink(<?= $c['id']; ?>)">
                            </div>
                            <small class="text-muted extra-small">Leave empty to choose recipient/group inside WhatsApp.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-forest-dark">Message Templates / Presets</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset(<?= $c['id']; ?>, 'profile', <?= json_encode($c['name']); ?>, <?= json_encode($c['cow_code']); ?>, <?= json_encode($c['breed_name']); ?>, <?= json_encode($cowAge); ?>, <?= json_encode($cowHealth); ?>, <?= json_encode($cowStatusText); ?>, <?= json_encode($cowPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">General Profile</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset(<?= $c['id']; ?>, 'adopt', <?= json_encode($c['name']); ?>, <?= json_encode($c['cow_code']); ?>, <?= json_encode($c['breed_name']); ?>, <?= json_encode($cowAge); ?>, <?= json_encode($cowHealth); ?>, <?= json_encode($cowStatusText); ?>, <?= json_encode($cowPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Adoption Appeal</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset(<?= $c['id']; ?>, 'medical', <?= json_encode($c['name']); ?>, <?= json_encode($c['cow_code']); ?>, <?= json_encode($c['breed_name']); ?>, <?= json_encode($cowAge); ?>, <?= json_encode($cowHealth); ?>, <?= json_encode($cowStatusText); ?>, <?= json_encode($cowPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Medical Seva</button>
                                <button type="button" class="btn btn-outline-forest btn-xs rounded-pill" onclick="applyWaPreset(<?= $c['id']; ?>, 'blessing', <?= json_encode($c['name']); ?>, <?= json_encode($c['cow_code']); ?>, <?= json_encode($c['breed_name']); ?>, <?= json_encode($cowAge); ?>, <?= json_encode($cowHealth); ?>, <?= json_encode($cowStatusText); ?>, <?= json_encode($cowPublicUrl); ?>, <?= json_encode($sitePhone); ?>)">Birthday Seva</button>
                            </div>
                        </div>
                    </div>

                    <!-- Editable WhatsApp Message Body -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold text-forest-dark mb-0">Customizable WhatsApp Message Text</label>
                            <button type="button" class="btn btn-link btn-xs text-forest text-decoration-none p-0 fw-semibold" onclick="copyWaText(<?= $c['id']; ?>)">
                                <i class="bi bi-clipboard me-1"></i> Copy to Clipboard
                            </button>
                        </div>
                        <textarea id="waMessageText<?= $c['id']; ?>" class="form-control font-monospace small" rows="9" oninput="updateWaLink(<?= $c['id']; ?>)"><?= e($defaultMsg); ?></textarea>
                        <small class="text-muted extra-small">You can freely edit, add personal donor greetings, or customize the text above before sending.</small>
                    </div>

                </div>
                <div class="modal-footer bg-cream-soft border-0 p-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success rounded-pill px-3" onclick="copyWaText(<?= $c['id']; ?>)">
                            <i class="bi bi-clipboard me-1"></i> Copy Text
                        </button>
                        <a id="waSendBtn<?= $c['id']; ?>" href="https://api.whatsapp.com/send?text=<?= rawurlencode($defaultMsg); ?>" target="_blank" rel="noopener" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- JavaScript for Dynamic WhatsApp Link & Message Editor -->
<script>
function updateWaLink(cowId) {
    const textEl = document.getElementById('waMessageText' + cowId);
    const phoneEl = document.getElementById('waRecipientPhone' + cowId);
    const sendBtn = document.getElementById('waSendBtn' + cowId);
    
    if (!textEl || !sendBtn) return;
    
    const message = encodeURIComponent(textEl.value);
    const rawPhone = phoneEl ? phoneEl.value.replace(/\D/g, '') : '';
    
    if (rawPhone.length > 0) {
        sendBtn.href = 'https://wa.me/' + rawPhone + '?text=' + message;
    } else {
        sendBtn.href = 'https://api.whatsapp.com/send?text=' + message;
    }
}

function copyWaText(cowId) {
    const textEl = document.getElementById('waMessageText' + cowId);
    if (!textEl) return;
    navigator.clipboard.writeText(textEl.value).then(() => {
        showToast('WhatsApp message copied to clipboard!', 'success');
    }).catch(() => {
        textEl.select();
        document.execCommand('copy');
        showToast('WhatsApp message copied to clipboard!', 'success');
    });
}

function applyWaPreset(cowId, preset, name, code, breed, age, health, statusText, url, phone) {
    const textEl = document.getElementById('waMessageText' + cowId);
    if (!textEl) return;
    
    let msg = '';
    if (preset === 'adopt') {
        msg = "🙏 *Gau Seva Adoption Appeal - " + name + "*\n\n" +
              "Kamadenu Goushala invites compassionate devotees to support:\n" +
              "🐮 *Cow:* " + name + " (" + code + ")\n" +
              "🌾 *Breed:* " + breed + " | *Age:* " + age + "\n" +
              "🏥 *Health:* " + health + "\n\n" +
              "By adopting " + name + " for ₹3,000/month, you ensure her lifelong green fodder, nutritious Ayurvedic mash, and 24x7 veterinary care.\n\n" +
              "📜 *Tax Benefit:* 50% Tax Exemption under Section 80G.\n" +
              "🔗 *Adopt Online:* " + url + "\n" +
              "📞 *WhatsApp / Call Helpline:* " + phone + "\n\n" +
              "🙏 *Jai Gau Mata!*";
    } else if (preset === 'medical') {
        msg = "🏥 *Emergency Veterinary Seva Appeal for " + name + " (" + code + ")*\n\n" +
              "Namaste! Our rescued cow " + name + " is under specialized care.\n" +
              "Condition: " + health + "\n\n" +
              "Help fund her clinical medications, dressing bandages, and probiotic recovery feeds:\n" +
              "🔗 *Support Medical Care:* " + url + "\n" +
              "📞 *Helpline Desk:* " + phone + "\n\n" +
              "Every small contribution heals a sacred life. 🙏";
    } else if (preset === 'blessing') {
        msg = "🌸 *Celebrate Your Birthday or Anniversary with Gau Seva!*\n\n" +
              "Dedicate a day of sacred Grāsa Dāna (fresh green grass, jaggery & fruits) for " + name + " (" + code + ") at Nandi Hills Sanctuary.\n\n" +
              "🌾 *Sponsor Fodder on Your Special Day:*\n" +
              "🔗 " + url + "\n" +
              "📞 *Contact Seva Desk:* " + phone + "\n\n" +
              "Receive Vedic prayers & cow darshan video. 🙏✨";
    } else {
        msg = "🙏 *Namaste from Kamadenu Goushala!*\n\n" +
              "Meet our sacred resident cow:\n" +
              "🐮 *Name:* " + name + " (" + code + ")\n" +
              "🌾 *Breed:* " + breed + "\n" +
              "⏳ *Age:* " + age + "\n" +
              "🏥 *Health Status:* " + health + "\n" +
              "❤️ *Adoption Status:* " + statusText + "\n\n" +
              "🔗 *View Sacred Profile & Adopt:* " + url + "\n\n" +
              "📞 *Helpline / Seva Desk:* " + phone + "\n" +
              "🙏 *Jai Gau Mata!*";
    }
    
    textEl.value = msg;
    updateWaLink(cowId);
    showToast('Template preset applied. You can edit the text before sending.', 'info');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
