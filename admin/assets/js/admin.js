/**
 * KAMADENU GOUSHALA - ADMIN ANIMATIONS & INTERACTIVE SUITE
 */

document.addEventListener('DOMContentLoaded', () => {
    initAdminCounters();
    initAdminFormSpinners();
    initAdminRowStagger();
    initAdminCardsStagger();
    initAdminBellPulse();
    initAdminLiveClock();
    initGoldenParticles('adminHeroParticles');
    initCircleProgress();
    initAdminInteractive3DTilt();
    initAdminMagneticButtons();
});

/**
 * Animate Numbers on Admin Dashboard Metrics
 */
function initAdminCounters() {
    const counters = document.querySelectorAll('.admin-counter-value, .counter-value');
    if (counters.length === 0) return;

    counters.forEach(counter => {
        const rawTarget = counter.getAttribute('data-target');
        if (!rawTarget) return;

        const target = parseFloat(rawTarget);
        if (isNaN(target)) return;

        const isCurrency = counter.getAttribute('data-is-currency') === 'true' || counter.textContent.includes('₹');
        const duration = 1600;
        const startTime = performance.now();

        const easeOutExpo = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

        const update = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = easeOutExpo(progress) * target;

            if (isCurrency) {
                counter.textContent = '₹ ' + Math.floor(current).toLocaleString('en-IN');
            } else {
                counter.textContent = Math.floor(current).toLocaleString('en-IN');
            }

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                if (isCurrency) {
                    counter.textContent = '₹ ' + Math.floor(target).toLocaleString('en-IN');
                } else {
                    counter.textContent = Math.floor(target).toLocaleString('en-IN');
                }
                counter.classList.add('counted');
            }
        };

        requestAnimationFrame(update);
    });
}

/**
 * Form Submit Button Loading Spinner Animation
 */
function initAdminFormSpinners() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Allow form submit but add visual spinner
                const origHtml = submitBtn.innerHTML;
                setTimeout(() => {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...`;
                }, 50);
            }
        });
    });
}

/**
 * Table Rows and Cards Stagger Animation
 */
function initAdminRowStagger() {
    document.querySelectorAll('.table tbody tr').forEach((row, idx) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(12px)';
        row.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, Math.min(idx * 40, 400));
    });
}

/**
 * Notification Bell Shake on Unread Count
 */
function initAdminBellPulse() {
    const unreadBadge = document.querySelector('.admin-nav-bell .badge');
    const bellIcon = document.querySelector('.admin-nav-bell i');
    if (unreadBadge && bellIcon && parseInt(unreadBadge.textContent || '0', 10) > 0) {
        setInterval(() => {
            bellIcon.classList.add('animate-bell');
            setTimeout(() => bellIcon.classList.remove('animate-bell'), 700);
        }, 5000);
    }
}

/**
 * Card Stagger Animation across all Admin Views
 */
function initAdminCardsStagger() {
    document.querySelectorAll('.admin-main .card, .luminous-kpi-card').forEach((card, idx) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(16px)';
        card.style.transition = 'opacity 0.45s cubic-bezier(0.16, 1, 0.3, 1), transform 0.45s cubic-bezier(0.16, 1, 0.3, 1)';

        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, Math.min(idx * 70 + 40, 500));
    });
}

/**
 * Real-Time Digital Clock with Seconds Ticker (Bangalore IST)
 */
function initAdminLiveClock() {
    const clockEl = document.getElementById('adminLiveClock');
    if (!clockEl) return;

    function updateTime() {
        const now = new Date();
        const options = { timeZone: 'Asia/Kolkata', hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' };
        clockEl.textContent = now.toLocaleTimeString('en-IN', options);
    }

    updateTime();
    setInterval(updateTime, 1000);
}

/**
 * Golden Particle Sparks & Sacred Motes Canvas
 */
function initGoldenParticles(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = (canvas.width = canvas.parentElement.offsetWidth || 800);
    let height = (canvas.height = canvas.parentElement.offsetHeight || 260);

    const particles = [];
    const particleCount = Math.min(Math.floor(width / 30), 30);

    class Spark {
        constructor() {
            this.reset(true);
        }
        reset(initial = false) {
            this.x = Math.random() * width;
            this.y = initial ? Math.random() * height : height + 5;
            this.size = Math.random() * 2.2 + 1;
            this.speedY = Math.random() * 0.4 + 0.2;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.opacity = Math.random() * 0.6 + 0.2;
            this.maxOpacity = this.opacity;
            this.pulse = Math.random() * Math.PI * 2;
        }
        update() {
            this.y -= this.speedY;
            this.x += this.speedX + Math.sin(this.pulse) * 0.2;
            this.pulse += 0.035;
            this.opacity = (Math.sin(this.pulse) * 0.5 + 0.5) * this.maxOpacity;
            if (this.y < -5 || this.x < -5 || this.x > width + 5) {
                this.reset(false);
            }
        }
        draw() {
            ctx.save();
            ctx.beginPath();
            const grad = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, this.size * 2);
            grad.addColorStop(0, `rgba(255, 230, 167, ${this.opacity})`);
            grad.addColorStop(0.5, `rgba(232, 120, 42, ${this.opacity * 0.7})`);
            grad.addColorStop(1, 'rgba(232, 120, 42, 0)');
            ctx.fillStyle = grad;
            ctx.arc(this.x, this.y, this.size * 2, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    for (let i = 0; i < particleCount; i++) {
        particles.push(new Spark());
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
}

/**
 * SVG Circular Progress Meters
 */
function initCircleProgress() {
    const meters = document.querySelectorAll('.circle-progress-bar');
    if (meters.length === 0) return;

    meters.forEach(bar => {
        const percent = parseFloat(bar.getAttribute('data-percent') || '0');
        const circumference = 138;
        const offset = circumference - (percent / 100) * circumference;
        setTimeout(() => {
            bar.style.strokeDashoffset = offset.toString();
        }, 300);
    });
}

/**
 * Interactive 3D Perspective Tilt on Admin Metric & Action Cards
 */
function initAdminInteractive3DTilt() {
    const cards = document.querySelectorAll('.luminous-kpi-card, .admin-action-card, .admin-card-animate');
    if (cards.length === 0 || window.innerWidth < 992) return;

    cards.forEach(card => {
        let isHovered = false;
        let currentX = 0;
        let currentY = 0;
        let targetX = 0;
        let targetY = 0;
        let animId = null;

        const updateTilt = () => {
            if (!isHovered) return;
            currentX += (targetX - currentX) * 0.12;
            currentY += (targetY - currentY) * 0.12;
            card.style.transform = `perspective(1000px) rotateX(${currentY.toFixed(2)}deg) rotateY(${currentX.toFixed(2)}deg) translateY(-6px)`;
            animId = requestAnimationFrame(updateTilt);
        };

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            targetX = ((x - centerX) / centerX) * 5;
            targetY = ((y - centerY) / centerY) * -5;

            if (!isHovered) {
                isHovered = true;
                animId = requestAnimationFrame(updateTilt);
            }
        });

        card.addEventListener('mouseleave', () => {
            isHovered = false;
            if (animId) cancelAnimationFrame(animId);
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)';
            card.style.transition = 'transform 0.5s cubic-bezier(0.19, 1, 0.22, 1)';
        });

        card.addEventListener('mouseenter', () => {
            card.style.transition = 'none';
            isHovered = true;
        });
    });
}

/**
 * Magnetic Micro-Interactions on Admin Primary Buttons
 */
function initAdminMagneticButtons() {
    const btns = document.querySelectorAll('.btn-gold, .btn-forest');
    if (btns.length === 0 || window.innerWidth < 992) return;

    btns.forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            btn.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px) scale(1.02)`;
        });

        btn.addEventListener('mouseleave', () => {
            btn.style.transform = 'translate(0px, 0px) scale(1)';
            btn.style.transition = 'transform 0.4s cubic-bezier(0.19, 1, 0.22, 1)';
        });

        btn.addEventListener('mouseenter', () => {
            btn.style.transition = 'none';
        });
    });
}


