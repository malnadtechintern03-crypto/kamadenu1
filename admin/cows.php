<?php
/**
 * Kamadenu Goushala Platform - Admin Cows Management
 */

declare(strict_types=1);

$pageTitle = 'Manage Sanctuary Cows Directory';

require_once __DIR__ . '/includes/header.php';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $deleteId = (int)($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        Database::execute("UPDATE cows SET status = 'deceased' WHERE id = ?", [$deleteId]);
        set_flash('success', 'Cow record status updated successfully.');
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
                                <div class="rounded-3 bg-forest-dark text-gold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi bi-flower1"></i>
                                </div>
                                <div>
                                    <strong class="text-forest-dark"><?= e($c['name']); ?></strong>
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
                                <a href="<?= BASE_URL; ?>/cow-details.php?slug=<?= e($c['slug']); ?>" target="_blank" class="btn btn-outline-secondary" title="View Public Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL; ?>/admin/cow-edit.php?id=<?= $c['id']; ?>" class="btn btn-outline-forest" title="Edit Cow">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
