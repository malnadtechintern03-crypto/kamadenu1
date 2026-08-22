<?php
/**
 * Kamadenu Goushala Platform - Admin Footer
 */

declare(strict_types=1);
?>
        </main>

        <!-- Admin Footer Info -->
        <footer class="bg-white border-top p-3 px-4 text-center text-md-between d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <div>
                &copy; <?= date('Y'); ?> <strong>Kamadenu Goushala Charitable Trust</strong> &bull; Administrative Operations Portal
            </div>
            <div>
                PHP <?= PHP_VERSION; ?> &bull; Section 80G Certified
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin Animations & Interactive Suite -->
<script src="<?= BASE_URL; ?>/admin/assets/js/admin.js"></script>

<!-- Sidebar Toggle Script (Desktop Collapse + Mobile Drawer) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('adminSidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth < 992) {
                sidebar.classList.toggle('show');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('collapsed');
            }
        });
    }

    // Close mobile drawer when clicking outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && toggleBtn && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});
</script>

</body>
</html>
