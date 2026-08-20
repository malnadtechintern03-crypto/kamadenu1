<?php
/**
 * Kamadenu Goushala Platform - Admin Devotee Messages
 */

declare(strict_types=1);

$pageTitle = 'Devotee Contact Messages & Inquiries';

require_once __DIR__ . '/includes/header.php';

// Handle Mark Read/Unread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_read') {
    verify_csrf_or_die();
    $msgId = (int)($_POST['message_id'] ?? 0);
    $status = (int)($_POST['is_read'] ?? 1);
    Database::execute("UPDATE contact_messages SET is_read = ? WHERE id = ?", [$status, $msgId]);
    set_flash('success', 'Message status updated.');
    header('Location: ' . BASE_URL . '/admin/messages.php');
    exit;
}

$messages = Database::fetchAll("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h5 font-serif text-forest-dark mb-0">Devotee Inquiries & Darshan Requests (<?= count($messages); ?>)</h2>
            <small class="text-muted">Inquiries submitted via the public contact portal.</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="bg-forest-dark text-white">
                <tr>
                    <th>Date</th>
                    <th>Devotee Name</th>
                    <th>Email / Phone</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No messages received yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                    <tr class="<?= $m['is_read'] ? '' : 'table-warning'; ?>">
                        <td class="text-nowrap text-muted"><?= format_date($m['created_at']); ?></td>
                        <td><strong><?= e($m['name']); ?></strong></td>
                        <td>
                            <div><a href="mailto:<?= e($m['email']); ?>" class="text-forest text-decoration-none"><?= e($m['email']); ?></a></div>
                            <small class="text-muted"><?= e($m['phone'] ?? 'N/A'); ?></small>
                        </td>
                        <td><?= e($m['subject']); ?></td>
                        <td>
                            <?php if ($m['is_read']): ?>
                                <span class="badge bg-light text-muted border">Read</span>
                            <?php else: ?>
                                <span class="badge bg-danger">New Inquiry</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-outline-forest btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#msgModal-<?= $m['id']; ?>" title="View Message">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <form method="POST" action="<?= BASE_URL; ?>/admin/messages.php" class="d-inline">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle_read">
                                    <input type="hidden" name="message_id" value="<?= $m['id']; ?>">
                                    <input type="hidden" name="is_read" value="<?= $m['is_read'] ? 0 : 1; ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Toggle Read Status">
                                        <i class="bi <?= $m['is_read'] ? 'bi-envelope' : 'bi-envelope-check'; ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Message Detail Modal -->
                    <div class="modal fade" id="msgModal-<?= $m['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow-lg">
                                <div class="modal-header bg-forest text-white">
                                    <h5 class="modal-title font-serif fs-6">Inquiry: <?= e($m['subject']); ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="mb-3 pb-3 border-bottom small text-muted">
                                        <div>From: <strong class="text-forest-dark"><?= e($m['name']); ?></strong></div>
                                        <div>Email: <a href="mailto:<?= e($m['email']); ?>"><?= e($m['email']); ?></a></div>
                                        <div>Phone: <?= e($m['phone'] ?? 'N/A'); ?></div>
                                        <div>Received: <?= format_date($m['created_at']); ?></div>
                                    </div>
                                    <h6 class="font-serif text-forest-dark mb-2">Message Content:</h6>
                                    <p class="text-muted small lh-base mb-0"><?= nl2br(e($m['message'])); ?></p>
                                </div>
                                <div class="modal-footer bg-cream-soft border-0">
                                    <a href="mailto:<?= e($m['email']); ?>" class="btn btn-forest rounded-pill btn-sm px-4">
                                        <i class="bi bi-reply me-1"></i> Reply via Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
