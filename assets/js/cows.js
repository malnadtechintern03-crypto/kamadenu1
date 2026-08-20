/**
 * KAMADENU GOUSHALA - COWS AJAX FILTER & INTERACTIVE DIRECTORY
 */

document.addEventListener('DOMContentLoaded', () => {
  initCowFilterTabs();
});

function initCowFilterTabs() {
  const filterBtns = document.querySelectorAll('[data-cow-filter]');
  const container = document.getElementById('cowsShowcaseContainer');
  if (!filterBtns.length || !container) return;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', async function(e) {
      e.preventDefault();
      
      filterBtns.forEach(b => {
        b.classList.remove('active', 'btn-forest');
        b.classList.add('btn-outline-forest');
      });
      this.classList.add('active', 'btn-forest');
      this.classList.remove('btn-outline-forest');

      const filterVal = this.getAttribute('data-cow-filter');
      const breedVal = this.getAttribute('data-cow-breed') || '';

      // Show loading skeleton
      container.innerHTML = `
        <div class="col-12 text-center py-5">
          <div class="spinner-border text-forest" role="status">
            <span class="visually-hidden">Loading cows...</span>
          </div>
          <p class="text-muted mt-2 small">Fetching blessed souls...</p>
        </div>
      `;

      try {
        let url = `${window.location.origin}/kamadenu1/ajax/cows.php?limit=8`;
        if (breedVal && breedVal !== 'all') {
          url += `&breed=${encodeURIComponent(breedVal)}`;
        }
        if (filterVal && filterVal !== 'all') {
          url += `&filter=${encodeURIComponent(filterVal)}`;
        }

        const res = await fetch(url);
        const data = await res.json();

        if (data.success && data.cows.length > 0) {
          container.innerHTML = data.cows.map(cow => `
            <div class="col-md-6 col-lg-3">
              <div class="heritage-card h-100 d-flex flex-column">
                <div class="cow-image-wrapper">
                  <img
                    src="${cow.image_url}"
                    alt="${cow.name}"
                    class="cow-card-image"
                    loading="lazy"
                    width="600"
                    height="450"
                    onerror="this.onerror=null;this.src='${window.location.origin}/kamadenu1/assets/images/placeholder-cow.jpg';"
                  >
                  <span class="position-absolute top-0 end-0 m-3 badge ${cow.health_class} badge-heritage">
                    ${cow.health_status}
                  </span>
                  <span class="position-absolute bottom-0 start-0 m-3 badge bg-black bg-opacity-75 text-white small">
                    ${cow.cow_code}
                  </span>
                </div>

                <div class="heritage-card-inner d-flex flex-column flex-grow-1">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <h3 class="h5 font-serif text-forest-dark mb-0">${cow.name}</h3>
                    <span class="badge bg-gold-subtle text-gold-dark small">${cow.breed_name}</span>
                  </div>
                  <p class="small text-muted mb-3 flex-grow-1">
                    ${cow.excerpt}
                  </p>
                  <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-auto">
                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> ${cow.rescue_date}</small>
                    <a href="${cow.url}" class="btn btn-sm btn-outline-forest rounded-pill px-3">
                      View Profile
                    </a>
                  </div>
                </div>
              </div>
            </div>
          `).join('');
        } else {
          container.innerHTML = `
            <div class="col-12 text-center py-5">
              <i class="bi bi-search fs-1 text-muted"></i>
              <h5 class="font-serif text-forest-dark mt-2">No cows found matching this filter</h5>
              <p class="text-muted small">Try selecting another breed or reset filter to "All".</p>
            </div>
          `;
        }
      } catch (err) {
        console.error('Cow filter error:', err);
        container.innerHTML = `
          <div class="col-12 text-center py-4">
            <div class="alert alert-danger d-inline-block">Failed to load cows. Please try again.</div>
          </div>
        `;
      }
    });
  });
}
