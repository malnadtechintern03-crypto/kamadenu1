/**
 * KAMADENU GOUSHALA - GALLERY MASONRY & LIGHTBOX CONTROLLER
 */

document.addEventListener('DOMContentLoaded', () => {
  initGalleryTabs();
  initLightbox();
});

let currentGalleryList = [];
let currentLightboxIndex = 0;

function initGalleryTabs() {
  const tabs = document.querySelectorAll('[data-gallery-filter]');
  const container = document.getElementById('galleryGridContainer');
  if (!tabs.length || !container) return;

  tabs.forEach(tab => {
    tab.addEventListener('click', async function(e) {
      e.preventDefault();
      tabs.forEach(t => {
        t.classList.remove('btn-forest', 'active');
        t.classList.add('btn-outline-forest');
      });
      this.classList.add('btn-forest', 'active');
      this.classList.remove('btn-outline-forest');

      const catSlug = this.getAttribute('data-gallery-filter');

      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <div class="spinner-border text-forest" role="status"></div>
          <p class="text-muted mt-2 small">Loading sanctuary moments...</p>
        </div>
      `;

      try {
        const url = `ajax/gallery.php?category=${encodeURIComponent(catSlug)}`;
        const res = await fetch(url);
        const data = await res.json();

        if (data.success && data.gallery.length > 0) {
          currentGalleryList = data.gallery;
          container.innerHTML = data.gallery.map((item, index) => `
            <div class="col-sm-6 col-lg-4 gallery-card-col">
              <div class="gallery-item cursor-pointer" onclick="openLightbox(${index})">
                <img src="${item.image_path}" alt="${item.title}" class="w-100 h-100 object-fit-cover d-block" onerror="this.onerror=null;this.src='assets/images/placeholder-gallery.jpg';">
                <div class="gallery-overlay">
                  <span class="badge bg-gold text-forest-dark mb-1 align-self-start small">${item.category_name}</span>
                  <h4 class="h6 text-white mb-0">${item.title}</h4>
                  <small class="text-cream opacity-75">${item.caption || ''}</small>
                </div>
              </div>
            </div>
          `).join('');
        } else {
          container.innerHTML = `
            <div class="col-12 text-center py-5">
              <i class="bi bi-images fs-1 text-muted"></i>
              <h5 class="font-serif text-forest-dark mt-2">No photos in this category yet</h5>
              <p class="text-muted small">Select another category or view all gallery moments.</p>
            </div>
          `;
        }
      } catch (err) {
        console.error('Gallery loading error:', err);
      }
    });
  });
}

function initLightbox() {
  const modalEl = document.getElementById('galleryLightboxModal');
  if (!modalEl) return;

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (!modalEl.classList.contains('show')) return;
    if (e.key === 'ArrowRight') nextLightbox();
    if (e.key === 'ArrowLeft') prevLightbox();
  });
}

function openLightbox(index) {
  if (!currentGalleryList || !currentGalleryList[index]) return;
  currentLightboxIndex = index;
  renderLightboxContent();

  const modalEl = document.getElementById('galleryLightboxModal');
  if (modalEl && typeof bootstrap !== 'undefined') {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}

function renderLightboxContent() {
  const item = currentGalleryList[currentLightboxIndex];
  if (!item) return;

  const titleEl = document.getElementById('lightboxTitle');
  const catEl = document.getElementById('lightboxCategory');
  const captionEl = document.getElementById('lightboxCaption');
  const imgEl = document.getElementById('lightboxImg');

  if (titleEl) titleEl.textContent = item.title;
  if (catEl) catEl.textContent = item.category_name;
  if (captionEl) captionEl.textContent = item.caption || '';
  if (imgEl && item.image_path) {
    imgEl.src = item.image_path;
    imgEl.alt = item.title;
  }
}

function nextLightbox() {
  if (currentLightboxIndex < currentGalleryList.length - 1) {
    currentLightboxIndex++;
  } else {
    currentLightboxIndex = 0;
  }
  renderLightboxContent();
}

function prevLightbox() {
  if (currentLightboxIndex > 0) {
    currentLightboxIndex--;
  } else {
    currentLightboxIndex = currentGalleryList.length - 1;
  }
  renderLightboxContent();
}
