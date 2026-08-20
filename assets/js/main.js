/**
 * KAMADENU GOUSHALA - MASTER JAVASCRIPT
 * Global UI interactions, sticky navigation, live counters, and AJAX utilities
 */

document.addEventListener('DOMContentLoaded', () => {
  initStickyNavbar();
  initAnimatedCounters();
  initSmoothScroll();
  initTooltips();
});

/**
 * Sticky Navbar Header Observer
 */
function initStickyNavbar() {
  const navbar = document.querySelector('.heritage-navbar');
  if (!navbar) return;

  const handleScroll = () => {
    if (window.scrollY > 40) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

/**
 * Animate Numbers on Impact Dashboard
 */
function initAnimatedCounters() {
  const counters = document.querySelectorAll('.counter-value');
  if (counters.length === 0) return;

  const observerOptions = {
    threshold: 0.25,
    rootMargin: '0px'
  };

  const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const counter = entry.target;
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        const duration = 1600; // ms
        const stepTime = 20;
        const totalSteps = duration / stepTime;
        const stepValue = target / totalSteps;
        let current = 0;

        const timer = setInterval(() => {
          current += stepValue;
          if (current >= target) {
            counter.textContent = target.toLocaleString('en-IN');
            clearInterval(timer);
          } else {
            counter.textContent = Math.floor(current).toLocaleString('en-IN');
          }
        }, stepTime);

        observer.unobserve(counter);
      }
    });
  }, observerOptions);

  counters.forEach(counter => counterObserver.observe(counter));
}

/**
 * Smooth Anchor Scrolling
 */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        e.preventDefault();
        const headerOffset = 90;
        const elementPosition = targetElement.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
      }
    });
  });
}

/**
 * Initialize Bootstrap Tooltips & Popovers
 */
function initTooltips() {
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  }
}

/**
 * Global Toast Notification Helper
 */
function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1090';
    document.body.appendChild(container);
  }

  const toastId = 'toast-' + Date.now();
  const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : 'bg-warning text-dark');
  const icon = type === 'success' ? 'bi-check-circle' : (type === 'danger' ? 'bi-exclamation-triangle' : 'bi-info-circle');

  const toastEl = document.createElement('div');
  toastEl.id = toastId;
  toastEl.className = `toast align-items-center ${bgClass} border-0 shadow-lg`;
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.setAttribute('aria-atomic', 'true');

  toastEl.innerHTML = `
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2">
        <i class="bi ${icon} fs-5"></i>
        <span>${message}</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;

  container.appendChild(toastEl);

  if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }
}

/**
 * Universal AJAX Fetch with CSRF Protection
 */
async function apiFetch(url, options = {}) {
  const defaultHeaders = {
    'X-Requested-With': 'XMLHttpRequest'
  };

  const metaCsrf = document.querySelector('meta[name="csrf-token"]');
  if (metaCsrf) {
    defaultHeaders['X-CSRF-TOKEN'] = metaCsrf.getAttribute('content');
  }

  options.headers = {
    ...defaultHeaders,
    ...(options.headers || {})
  };

  try {
    const response = await fetch(url, options);
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('API Fetch Error:', error);
    throw error;
  }
}
