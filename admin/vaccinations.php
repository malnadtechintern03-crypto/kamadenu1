<?php
/**
 * Kamadenu Goushala Platform - Admin Vaccinations Module
 */

declare(strict_types=1);

$pageTitle = 'Sanctuary Vaccination & Immunization Tracker';

require_once __DIR__ . '/includes/header.php';

// Handle Add Vaccination Record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $cowId = (int)($_POST['cow_id'] ?? 0);
    $vaccineName = sanitize_input($_POST['vaccine_name'] ?? '');
    $vaccinationDate = sanitize_input($_POST['vaccination_date'] ?? date('Y-m-d'));
    $nextDueDate = sanitize_input($_POST['next_due_date'] ?? '') ?: null;
    $batchNo = sanitize_input($_POST['batch_number'] ?? '');
    $administeredBy = sanitize_input($_POST['administered_by'] ?? 'Dr. H. V. Narayana (MVSc)');
    $notes = sanitize_input($_POST['notes'] ?? '');

    if ($cowId > 0 && !empty($vaccineName)) {
        Database::insert("
            INSERT INTO cow_vaccinations (cow_id, vaccine_name, vaccination_date, next_due_date, batch_number, administered_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [$cowId, $vaccineName, $vaccinationDate, $nextDueDate, $batchNo, $administeredBy, $notes]);

        set_flash('success', 'Immunization record saved successfully.');
        header('Location: ' . BASE_URL . '/admin/vaccinations.php');
        exit;
    } else {
        set_flash('error', 'Please select a cow and enter the vaccine name.');
    }
}

$vaccines = Database::fetchAll("
    SELECT cv.*, c.name AS cow_name, c.cow_code, b.name AS breed_name 
    FROM cow_vaccinations cv 
    JOIN cows c ON cv.cow_id = c.id 
    JOIN cow_breeds b ON c.breed_id = b.id 
    ORDER BY cv.vaccination_date DESC, cv.id DESC
");

$cows = Database::fetchAll("SELECT id, cow_code, name FROM cows WHERE status != 'deceased' ORDER BY name ASC");
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Herd Vaccination Records (<?= count($vaccines); ?>)</h2>
            <small class="text-muted">FMD, HS, BQ, Anthrax, and Brucellosis immunization schedule.</small>
        </div>
        <button type="button" class="btn btn-forest rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addVaccineModal">
            <i class="bi bi-shield-plus me-1"></i> Log Vaccination
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Administered Date</th>
                    <th>Cow Name & Code</th>
                    <th>Vaccine Name</th>
                    <th>Batch Number</th>
                    <th>Administered By</th>
                    <th>Next Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vaccines)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No vaccination records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($vaccines as $v): 
                        $isUpcoming = $v['next_due_date'] && strtotime($v['next_due_date']) < strtotime('+30 days');
                    ?>
                    <tr>
                        <td class="text-nowrap fw-bold"><i class="bi bi-calendar-check me-1 text-muted"></i> <?= format_date($v['vaccination_date']); ?></td>
                        <td>
                            <strong class="text-forest-dark"><?= e($v['cow_name']); ?></strong>
                            <span class="font-monospace text-muted d-block"><?= e($v['cow_code']); ?> (<?= e($v['breed_name']); ?>)</span>
                        </td>
                        <td><span class="badge bg-gold-subtle text-gold-dark fw-bold"><?= e($v['vaccine_name']); ?></span></td>
                        <td><span class="font-monospace text-muted"><?= e($v['batch_number'] ?? 'N/A'); ?></span></td>
                        <td><?= e($v['administered_by']); ?></td>
                        <td class="text-nowrap">
                            <?php if ($v['next_due_date']): ?>
                                <span class="badge <?= $isUpcoming ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                                    <?= format_date($v['next_due_date']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Vaccination Modal -->
<div class="modal fade" id="addVaccineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-forest text-white">
                <h5 class="modal-title font-serif"><i class="bi bi-shield-plus me-2"></i> Log Vaccination / Immunization</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL; ?>/admin/vaccinations.php">
                <?= csrf_field(); ?>
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
                            <label class="form-label small fw-bold">Vaccine Name *</label>
                            <select name="vaccine_name" class="form-select" required>
                                <option value="Foot & Mouth Disease (FMD) Quadrivalent">Foot & Mouth Disease (FMD) Quadrivalent</option>
                                <option value="Hemorrhagic Septicemia (HS)">Hemorrhagic Septicemia (HS)</option>
                                <option value="Black Quarter (BQ) Vaccine">Black Quarter (BQ) Vaccine</option>
                                <option value="Brucellosis S19 Strain">Brucellosis S19 Strain</option>
                                <option value="Theileriosis Cell-Culture Vaccine">Theileriosis Cell-Culture Vaccine</option>
                                <option value="Deworming Broad-Spectrum Ivermectin">Deworming Broad-Spectrum Ivermectin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Administered Date *</label>
                            <input type="date" name="vaccination_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Next Booster Due Date</label>
                            <input type="date" name="next_due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+6 months')); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Batch / Lot Number</label>
                            <input type="text" name="batch_number" class="form-control font-monospace" placeholder="FMD-2024-B891">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Administered By Doctor / Officer</label>
                            <input type="text" name="administered_by" class="form-control" value="Dr. H. V. Narayana (MVSc)">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Immunity / Tolerance Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Site of injection, reaction observations..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-cream-soft border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-forest rounded-pill px-4">Save Vaccination Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
