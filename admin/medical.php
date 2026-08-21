<?php
/**
 * Kamadenu Goushala Platform - Admin Medical Records Module
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_role(['super_admin', 'admin', 'manager', 'editor', 'staff']);

$currentUser = get_logged_in_user();

// Handle Add Medical Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'add_medical')) {
    verify_csrf_or_die();

    $cowId = (int)($_POST['cow_id'] ?? 0);
    $doctor = sanitize_input($_POST['doctor'] ?? 'Dr. H. V. Narayana (Chief Vet)');
    $diagnosis = sanitize_input($_POST['diagnosis'] ?? '');
    $treatment = sanitize_input($_POST['treatment'] ?? '');
    $medicine = sanitize_input($_POST['medicine'] ?? '');
    $visitDate = sanitize_input($_POST['visit_date'] ?? date('Y-m-d'));
    $nextVisit = sanitize_input($_POST['next_visit'] ?? '') ?: null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    if ($cowId > 0 && !empty($diagnosis) && !empty($treatment)) {
        Database::insert("
            INSERT INTO cow_medical_records (cow_id, doctor, diagnosis, treatment, medicine, visit_date, next_visit, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [$cowId, $doctor, $diagnosis, $treatment, $medicine, $visitDate, $nextVisit, $notes]);

        log_activity((int)($currentUser['id'] ?? 0), 'create_medical_record', 'cow_medical_records', null, "Logged medical treatment for cow ID {$cowId}: {$diagnosis}");
        set_flash('success', 'Veterinary medical treatment record logged successfully.');
        header('Location: ' . BASE_URL . '/admin/medical.php');
        exit;
    } else {
        set_flash('error', 'Please select a cow and enter diagnosis and treatment administered.');
    }
}

// Handle Edit Medical Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_medical') {
    verify_csrf_or_die();

    $recordId = (int)($_POST['record_id'] ?? 0);
    $cowId = (int)($_POST['cow_id'] ?? 0);
    $doctor = sanitize_input($_POST['doctor'] ?? 'Dr. H. V. Narayana (Chief Vet)');
    $diagnosis = sanitize_input($_POST['diagnosis'] ?? '');
    $treatment = sanitize_input($_POST['treatment'] ?? '');
    $medicine = sanitize_input($_POST['medicine'] ?? '');
    $visitDate = sanitize_input($_POST['visit_date'] ?? date('Y-m-d'));
    $nextVisit = sanitize_input($_POST['next_visit'] ?? '') ?: null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    if ($recordId > 0 && $cowId > 0 && !empty($diagnosis) && !empty($treatment)) {
        Database::execute("
            UPDATE cow_medical_records SET 
                cow_id = ?, doctor = ?, diagnosis = ?, treatment = ?, 
                medicine = ?, visit_date = ?, next_visit = ?, notes = ?
            WHERE id = ?
        ", [$cowId, $doctor, $diagnosis, $treatment, $medicine, $visitDate, $nextVisit, $notes, $recordId]);

        log_activity((int)($currentUser['id'] ?? 0), 'update_medical_record', 'cow_medical_records', $recordId, "Updated medical treatment record #{$recordId}");
        set_flash('success', 'Medical record updated successfully.');
        header('Location: ' . BASE_URL . '/admin/medical.php');
        exit;
    } else {
        set_flash('error', 'Please verify required fields.');
    }
}

// Handle Delete Medical Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_medical') {
    verify_csrf_or_die();
    $recordId = (int)($_POST['record_id'] ?? 0);
    if ($recordId > 0) {
        Database::execute("DELETE FROM cow_medical_records WHERE id = ?", [$recordId]);
        log_activity((int)($currentUser['id'] ?? 0), 'delete_medical_record', 'cow_medical_records', $recordId, "Deleted medical treatment record #{$recordId}");
        set_flash('success', 'Medical record deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/medical.php');
    exit;
}

$records = Database::fetchAll("
    SELECT mr.*, c.name AS cow_name, c.cow_code, b.name AS breed_name 
    FROM cow_medical_records mr 
    JOIN cows c ON mr.cow_id = c.id 
    JOIN cow_breeds b ON c.breed_id = b.id 
    ORDER BY mr.visit_date DESC, mr.id DESC
");

$cows = Database::fetchAll("SELECT id, cow_code, name FROM cows WHERE status != 'deceased' ORDER BY name ASC");

$pageTitle = 'Veterinary Medical Treatments Log';

require_once __DIR__ . '/includes/header.php';
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Sanctuary Clinical Records (<?= count($records); ?>)</h2>
            <small class="text-muted">Track diagnoses, medications, and veterinary surgery logs.</small>
        </div>
        <button type="button" class="btn btn-forest rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addMedicalModal">
            <i class="bi bi-plus-circle me-1"></i> Log Medical Treatment
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Visit Date</th>
                    <th>Cow Name & Code</th>
                    <th>Veterinary Doctor</th>
                    <th>Diagnosis</th>
                    <th>Treatment Administered</th>
                    <th>Prescribed Medicine</th>
                    <th>Next Visit</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No medical records logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td class="text-nowrap fw-bold"><i class="bi bi-calendar3 me-1 text-muted"></i> <?= format_date($r['visit_date']); ?></td>
                        <td>
                            <strong class="text-forest-dark"><?= e($r['cow_name']); ?></strong>
                            <span class="font-monospace text-muted d-block"><?= e($r['cow_code']); ?> (<?= e($r['breed_name']); ?>)</span>
                        </td>
                        <td><?= e($r['doctor']); ?></td>
                        <td><span class="badge bg-warning-subtle text-dark border"><?= e($r['diagnosis']); ?></span></td>
                        <td><?= e($r['treatment']); ?></td>
                        <td><?= !empty($r['medicine']) ? e($r['medicine']) : '<span class="text-muted">N/A</span>'; ?></td>
                        <td class="text-nowrap"><?= $r['next_visit'] ? format_date($r['next_visit']) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-forest btn-sm rounded-pill px-2 py-0" data-bs-toggle="modal" data-bs-target="#editMedicalModal<?= $r['id']; ?>" title="Edit Record">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/medical.php" onsubmit="return confirm('Are you sure you want to delete this clinical record?');" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_medical">
                                    <input type="hidden" name="record_id" value="<?= $r['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" title="Delete Record">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Medical Record Modal -->
<div class="modal fade" id="addMedicalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest text-white">
                <h5 class="modal-title font-serif"><i class="bi bi-heart-pulse me-2"></i> Log Veterinary Medical Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/medical.php">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add_medical">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Resident Cow *</label>
                            <select name="cow_id" class="form-select" required>
                                <option value="">-- Select Cow --</option>
                                <?php foreach ($cows as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= e($c['name']); ?> (<?= e($c['cow_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Attending Veterinary Doctor *</label>
                            <input type="text" name="doctor" class="form-control" value="Dr. H. V. Narayana (Chief Vet)" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Diagnosis / Clinical Condition *</label>
                            <input type="text" name="diagnosis" class="form-control" placeholder="e.g. Left Hind Leg Arthritic Stiffness" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Treatment Given *</label>
                            <input type="text" name="treatment" class="form-control" placeholder="e.g. Herbal Anti-inflammatory massage & Liver Tonic" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Medicines / Tonics Prescribed</label>
                            <input type="text" name="medicine" class="form-control" placeholder="e.g. Meloxicam 100ml, Liv-52 Liquid 50ml daily">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Visit Date *</label>
                            <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Next Follow-Up Date</label>
                            <input type="date" name="next_visit" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Veterinary Notes & Observations</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Appetite, rumination rate, progress..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-forest rounded-pill px-4">Save Medical Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Medical Record Modals -->
<?php if (!empty($records)): ?>
    <?php foreach ($records as $r): ?>
    <div class="modal fade" id="editMedicalModal<?= $r['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-forest-dark text-white">
                    <h5 class="modal-title font-serif"><i class="bi bi-pencil-square me-2"></i> Edit Medical Record #<?= $r['id']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= BASE_URL; ?>/admin/medical.php">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="edit_medical">
                    <input type="hidden" name="record_id" value="<?= $r['id']; ?>">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Resident Cow *</label>
                                <select name="cow_id" class="form-select" required>
                                    <option value="">-- Select Cow --</option>
                                    <?php foreach ($cows as $c): ?>
                                        <option value="<?= $c['id']; ?>" <?= ($r['cow_id'] == $c['id']) ? 'selected' : ''; ?>><?= e($c['name']); ?> (<?= e($c['cow_code']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Attending Veterinary Doctor *</label>
                                <input type="text" name="doctor" class="form-control" value="<?= e($r['doctor']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Diagnosis / Clinical Condition *</label>
                                <input type="text" name="diagnosis" class="form-control" value="<?= e($r['diagnosis']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Treatment Given *</label>
                                <input type="text" name="treatment" class="form-control" value="<?= e($r['treatment']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Medicines / Tonics Prescribed</label>
                                <input type="text" name="medicine" class="form-control" value="<?= e($r['medicine'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Visit Date *</label>
                                <input type="date" name="visit_date" class="form-control" value="<?= e($r['visit_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Next Follow-Up Date</label>
                                <input type="date" name="next_visit" class="form-control" value="<?= e($r['next_visit'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Veterinary Notes & Observations</label>
                                <textarea name="notes" class="form-control" rows="2"><?= e($r['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-cream-soft border-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-forest rounded-pill px-4">Update Medical Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
