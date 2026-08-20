<?php
/**
 * Kamadenu Goushala Platform - Video Stories Hub & Embed Player
 */

declare(strict_types=1);

$pageTitle = 'Videos – Life at Kamadenu Goushala';
$metaDescription = 'Watch video documentaries of cow rescue operations, traditional A2 Bilona ghee preparation, and daily life at Kamadenu Goushala.';

require_once __DIR__ . '/includes/header.php';

$videos = Database::fetchAll("SELECT * FROM videos ORDER BY display_order ASC, id DESC");
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-play-circle-fill me-1"></i> Sacred Documentaries & Rescues
        </span>
        <h1 class="page-hero-title">Video Stories & Documentaries</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Watch our emergency rescue missions, Vedic Bilona ghee making, and daily life in our herbal grazing pastures.
        </p>
    </div>
</section>

<!-- Videos Grid Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <div class="row g-4">
            <?php foreach ($videos as $v): ?>
            <div class="col-md-6 col-lg-4">
                <div class="heritage-card h-100 d-flex flex-column">
                    
                    <!-- Video Card Thumbnail with Play Button -->
                    <div class="position-relative cursor-pointer video-thumb-box" style="height: 220px; background-color: var(--color-forest-dark);" onclick="openVideoPlayer('<?= e($v['youtube_video_id']); ?>', '<?= e(addslashes($v['title'])); ?>')">
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-gold fs-1 bg-forest-subtle">
                            <i class="bi bi-youtube"></i>
                        </div>
                        
                        <!-- Play Icon Circle Overlay -->
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <div class="rounded-circle bg-gold text-forest-dark d-flex align-items-center justify-content-center shadow-lg" style="width: 58px; height: 58px; font-size: 1.6rem;">
                                <i class="bi bi-play-fill ms-1"></i>
                            </div>
                        </div>

                        <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                            <i class="bi bi-play-btn me-1"></i> YouTube Video
                        </span>
                    </div>

                    <!-- Video Details -->
                    <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                        <h3 class="h5 font-serif text-forest-dark mb-2"><?= e($v['title']); ?></h3>
                        <p class="small text-muted mb-4 flex-grow-1">
                            <?= e($v['description']); ?>
                        </p>
                        <div class="pt-3 border-top mt-auto">
                            <button type="button" class="btn btn-outline-forest btn-sm rounded-pill w-100" onclick="openVideoPlayer('<?= e($v['youtube_video_id']); ?>', '<?= e(addslashes($v['title'])); ?>')">
                                <i class="bi bi-play-circle me-1"></i> Watch Video
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- YouTube Embed Player Modal -->
<div class="modal fade" id="videoPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-forest-dark text-white rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title font-serif text-cream fs-5" id="videoPlayerModalTitle">Video Player</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeVideoPlayer()"></button>
            </div>
            <div class="modal-body p-3 text-center">
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden bg-black">
                    <iframe id="videoIframe" src="" title="Kamadenu Video Player" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openVideoPlayer(videoId, title) {
    const iframe = document.getElementById('videoIframe');
    const titleEl = document.getElementById('videoPlayerModalTitle');
    
    if (iframe) iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
    if (titleEl) titleEl.textContent = title;

    const modalEl = document.getElementById('videoPlayerModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function closeVideoPlayer() {
    const iframe = document.getElementById('videoIframe');
    if (iframe) iframe.src = '';
}

document.getElementById('videoPlayerModal')?.addEventListener('hidden.bs.modal', closeVideoPlayer);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
