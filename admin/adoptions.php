<?php
/**
 * Kamadenu Goushala Platform - Admin Cow Adoptions Ledger
 */

declare(strict_types=1);

$pageTitle = 'Sacred Cow Guardians & Adoptions Ledger';

require_once __DIR__ . '/includes/header.php';

$adoptions = Database::fetchAll("
    SELECT a.*, c.name AS cow_name, c.cow_code, b.name AS breed_name 
    FROM adoptions a 
    JOIN cows c ON a.cow_id = c.id 
    JOIN cow_breeds b ON c.breed_id = b.id 
    ORDER BY a.created_at DESC
");
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Active Cow Guardianship Adoptions (<?= count($adoptions); ?>)</h2>
            <small class="text-muted">Review monthly sponsorships, validity periods, and digital certificates.</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Adoption #</th>
                    <th>Guardian / Adopter</th>
                    <th>Adopted Cow</th>
                    <th>Duration & Validity</th>
                    <th>Contribution</th>
                    <th>Status</th>
                    <th class="text-center">Certificate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adoptions)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No adoption records registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($adoptions as $a): ?>
                    <tr>
                        <td class="font-monospace fw-bold text-forest-dark"><?= e($a['adoption_number']); ?></td>
                        <td>
                            <strong><?= e($a['adopter_name']); ?></strong>
                            <div class="text-muted"><?= e($a['adopter_email']); ?> &bull; <?= e($a['adopter_phone']); ?></div>
                        </td>
                        <td>
                            <strong class="text-forest-dark"><?= e($a['cow_name']); ?></strong>
                            <span class="font-monospace text-muted d-block"><?= e($a['cow_code']); ?> (<?= e($a['breed_name']); ?>)</span>
                        </td>
                        <td>
                            <span class="badge bg-cream text-forest-dark border mb-1"><?= $a['duration_months']; ?> Months</span>
                            <div class="text-muted extra-small">
                                <?= format_date($a['start_date']); ?> to <?= format_date($a['end_date']); ?>
                            </div>
                        </td>
                        <td>
                            <strong class="font-serif text-forest-dark"><?= format_inr($a['total_amount'], true); ?></strong>
                            <small class="text-muted d-block"><?= format_inr($a['monthly_amount']); ?> / mo</small>
                        </td>
                        <td>
                            <span class="badge bg-success rounded-pill"><?= ucfirst($a['status']); ?></span>
                        </td>
                        <td class="text-center">
                            <a href="<?= BASE_URL; ?>/adoption-certificate.php?cert=<?= e($a['certificate_number']); ?>" target="_blank" class="btn btn-outline-gold btn-sm rounded-pill px-3 py-1" title="View Digital Adoption Certificate">
                                <i class="bi bi-award me-1"></i> Certificate
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
