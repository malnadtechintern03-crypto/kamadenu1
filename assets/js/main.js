/**
 * KAMADENU GOUSHALA - MASTER JAVASCRIPT
 * Global UI interactions, sticky navigation, live counters, and AJAX utilities
 */

document.addEventListener('DOMContentLoaded', () => {
  initStickyNavbar();
  initMobileNav();
  initAnimatedCounters();
  initScrollAnimations();
  initRippleEffects();
  initHeroParallax();
  initSmoothScroll();
  initTooltips();
  initGoldenParticles('heroParticlesCanvas');
  initCarouselProgress();
  initInteractive3DTilt();
  initCircleProgress();
});

/**
 * Mobile Navigation Drawer Utilities
 */
function initMobileNav() {
  const mobileDrawer = document.getElementById('mobileMenuDrawer');
  if (!mobileDrawer) return;

  // Auto-close offcanvas on internal navigation link clicks
  mobileDrawer.querySelectorAll('a[href]:not([data-bs-toggle="collapse"])').forEach(link => {
    link.addEventListener('click', () => {
      if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
        const bsOffcanvas = bootstrap.Offcanvas.getInstance(mobileDrawer);
        if (bsOffcanvas) {
          bsOffcanvas.hide();
        }
      }
    });
  });
}

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
 * Animate Numbers on Impact Dashboard with Easing
 */
function initAnimatedCounters() {
  const counters = document.querySelectorAll('.counter-value');
  if (counters.length === 0) return;

  const observerOptions = {
    threshold: 0.2,
    rootMargin: '0px 0px -40px 0px'
  };

  const counterObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const counter = entry.target;
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        const duration = 1800; // ms
        const startTime = performance.now();

        // EaseOutExpo curve
        const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

        const updateCounter = (currentTime) => {
          const elapsed = currentTime - startTime;
          const progress = Math.min(elapsed / duration, 1);
          const current = Math.floor(easeOutExpo(progress) * target);

          counter.textContent = current.toLocaleString('en-IN');

          if (progress < 1) {
            requestAnimationFrame(updateCounter);
          } else {
            counter.textContent = target.toLocaleString('en-IN');
            counter.classList.add('counted');
          }
        };

        requestAnimationFrame(updateCounter);
        observer.unobserve(counter);
      }
    });
  }, observerOptions);

  counters.forEach(counter => counterObserver.observe(counter));
}

/**
 * Scroll Reveal Animation Engine
 * Automatically animates elements with [data-animate] and auto-staggers card grids
 */
function initScrollAnimations() {
  // Auto-tag common cards & sections across all pages with data-animate if not already tagged
  const targetSelectors = [
    '.stat-card', '.cow-card', '.journey-step-card', '.seva-program-card',
    '.heritage-card', '.feature-box', '.product-card', '.gallery-item',
    '.preset-giving-card', '.testimonial-card', '.transparency-card',
    '.donor-tier-card', '.accordion-item', '.video-card', '.contact-info-card',
    '.donation-box', '.receipt-card', '.cart-summary-card', '.blog-card-img',
    '.legal-content h3', '.breed-card'
  ].join(', ');

  document.querySelectorAll(targetSelectors).forEach((el, index) => {
    if (!el.hasAttribute('data-animate')) {
      el.setAttribute('data-animate', 'fade-up');
      const delay = (index % 4) * 90 + 50;
      el.setAttribute('data-delay', delay.toString());
    }
  });

  const animElements = document.querySelectorAll('[data-animate], .reveal-on-scroll');
  if (animElements.length === 0) return;

  const animObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.12,
    rootMargin: '0px 0px -50px 0px'
  });

  animElements.forEach(el => animObserver.observe(el));
}

/**
 * Material / Liquid Button Ripple Click Feedback
 */
function initRippleEffects() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-gold, .btn-saffron, .btn-forest, .btn-outline-forest, .admin-nav-link');
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const ripple = document.createElement('span');
    ripple.className = 'btn-ripple-wave';
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;
    ripple.style.position = 'absolute';
    ripple.style.width = '20px';
    ripple.style.height = '20px';
    ripple.style.transform = 'translate(-50%, -50%) scale(0)';
    ripple.style.borderRadius = '50%';
    ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.4)';
    ripple.style.pointerEvents = 'none';
    ripple.style.transition = 'transform 0.5s ease-out, opacity 0.5s ease-out';
    ripple.style.zIndex = '10';

    btn.style.position = btn.style.position || 'relative';
    btn.appendChild(ripple);

    requestAnimationFrame(() => {
      ripple.style.transform = 'translate(-50%, -50%) scale(15)';
      ripple.style.opacity = '0';
    });

    setTimeout(() => ripple.remove(), 600);
  });
}

/**
 * Subtle Scroll Parallax for Hero Background
 */
function initHeroParallax() {
  const heroImage = document.querySelector('.hero-bg-image');
  if (!heroImage) return;

  let ticking = false;
  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        const scrolled = window.pageYOffset;
        if (scrolled < 800) {
          heroImage.style.transform = `translateY(${scrolled * 0.15}px)`;
        }
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });
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

/**
 * Grand Golden Particle Sparks & Sacred Motes Canvas Engine
 */
function initGoldenParticles(canvasId) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const parent = canvas.parentElement;
  if (!parent) return;

  let width = (canvas.width = parent.offsetWidth || window.innerWidth);
  let height = (canvas.height = parent.offsetHeight || window.innerHeight);

  const particles = [];
  const particleCount = Math.min(Math.floor(width / 20), 50);
  let mouse = { x: -1000, y: -1000, radius: 120 };

  parent.addEventListener('mousemove', (e) => {
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
  });

  parent.addEventListener('mouseleave', () => {
    mouse.x = -1000;
    mouse.y = -1000;
  });

  class GoldenMote {
    constructor() {
      this.reset(true);
    }
    reset(initial = false) {
      this.x = Math.random() * width;
      this.y = initial ? Math.random() * height : height + 15;
      this.size = Math.random() * 2.8 + 1.2;
      this.speedY = Math.random() * 0.45 + 0.25;
      this.speedX = (Math.random() - 0.5) * 0.4;
      this.opacity = Math.random() * 0.55 + 0.3;
      this.maxOpacity = this.opacity;
      this.pulse = Math.random() * Math.PI * 2;
    }
    update() {
      this.y -= this.speedY;
      this.x += this.speedX + Math.sin(this.pulse) * 0.3;
      this.pulse += 0.035;
      this.opacity = (Math.sin(this.pulse) * 0.5 + 0.5) * this.maxOpacity;

      // Interactive gentle mouse physics
      const dx = mouse.x - this.x;
      const dy = mouse.y - this.y;
      const dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < mouse.radius && dist > 0) {
        const force = (1 - dist / mouse.radius) * 1.6;
        this.x -= (dx / dist) * force;
        this.y -= (dy / dist) * force;
      }

      if (this.y < -15 || this.x < -15 || this.x > width + 15) {
        this.reset(false);
      }
    }
    draw() {
      ctx.save();
      ctx.beginPath();
      const gradient = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, this.size * 2.2);
      gradient.addColorStop(0, `rgba(255, 235, 175, ${this.opacity})`);
      gradient.addColorStop(0.35, `rgba(233, 120, 58, ${this.opacity * 0.8})`);
      gradient.addColorStop(1, 'rgba(233, 120, 58, 0)');
      ctx.fillStyle = gradient;
      ctx.arc(this.x, this.y, this.size * 2.2, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }
  }

  for (let i = 0; i < particleCount; i++) {
    particles.push(new GoldenMote());
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);
    particles.forEach(p => {
      p.update();
      p.draw();
    });
    requestAnimationFrame(animate);
  }
  animate();

  window.addEventListener('resize', () => {
    if (canvas && parent) {
      width = canvas.width = parent.offsetWidth;
      height = canvas.height = parent.offsetHeight;
    }
  }, { passive: true });
}

/**
 * Carousel Slide Progress Countdown Bar
 */
function initCarouselProgress() {
  const carousel = document.getElementById('homepageHeroCarousel');
  const progressBar = document.getElementById('heroCarouselProgress');
  if (!carousel || !progressBar) return;

  const duration = 6000; // ms
  let startTime = performance.now();
  let animId;

  function updateBar(now) {
    const elapsed = now - startTime;
    const progress = Math.min((elapsed / duration) * 100, 100);
    progressBar.style.width = progress + '%';

    if (progress < 100) {
      animId = requestAnimationFrame(updateBar);
    }
  }

  animId = requestAnimationFrame(updateBar);

  carousel.addEventListener('slide.bs.carousel', () => {
    cancelAnimationFrame(animId);
    progressBar.style.width = '0%';
    startTime = performance.now();
    animId = requestAnimationFrame(updateBar);
  });
}

/**
 * Interactive 3D Card Mouse Perspective Tilt & Dynamic Specular Sheen
 */
function initInteractive3DTilt() {
  const cards = document.querySelectorAll('.tilt-card, .cow-card, .stat-card, .preset-giving-card, .seva-program-card, .journey-step-card');
  if (cards.length === 0 || window.innerWidth < 992) return;

  cards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      
      card.style.setProperty('--mouse-x', `${x}px`);
      card.style.setProperty('--mouse-y', `${y}px`);

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = ((y - centerY) / centerY) * -5;
      const rotateY = ((x - centerX) / centerX) * 5;

      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)';
      card.style.transition = 'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
    });

    card.addEventListener('mouseenter', () => {
      card.style.transition = 'transform 0.1s ease-out';
    });
  });
}

/**
 * SVG Circular Progress Meters
 */
function initCircleProgress() {
  const meters = document.querySelectorAll('.circle-progress-bar');
  if (meters.length === 0) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const bar = entry.target;
        const percent = parseFloat(bar.getAttribute('data-percent') || '0');
        const circumference = 138;
        const offset = circumference - (percent / 100) * circumference;
        bar.style.strokeDashoffset = offset.toString();
        observer.unobserve(bar);
      }
    });
  }, { threshold: 0.2 });

  meters.forEach(m => observer.observe(m));
}

