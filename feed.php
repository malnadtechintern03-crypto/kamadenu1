<?php
/**
 * Kamadenu Goushala Platform - Feed a Cow (Grāsa Dāna) Dedicated Portal
 */

declare(strict_types=1);

$pageTitle = 'Feed a Cow (Grāsa Dāna) – Sacred Fodder Seva';
$metaDescription = 'Calculate and offer fresh green fodder and nutritious feed to rescued cows at Kamadenu Goushala for birthdays, anniversaries, and auspicious occasions. 80G Tax Exempted.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container text-center">
        <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill mb-2 border border-warning border-opacity-50">
            <i class="bi bi-flower1 me-1"></i> Grāsa Dāna Mahima
        </span>
        <h1 class="page-hero-title">Feed a Sacred Cow (Grāsa Dāna)</h1>
        <p class="fs-5 text-cream opacity-90 mx-auto max-w-700">
            Feeding a holy cow with fresh grass and jaggery removes planetary afflictions and bestows peace, health, and abundant blessings upon one's family.
        </p>
    </div>
</section>

<!-- Interactive Feed Calculator Section -->
<section class="py-5 bg-cream-soft">
    <div class="container py-3">
        <div class="row g-5">
            
            <!-- Calculator Form -->
            <div class="col-lg-7">
                <div class="card p-4 p-md-5 rounded-4 border-0 shadow-md bg-white">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-forest px-3 py-1 rounded-pill"><i class="bi bi-calculator me-1"></i> Interactive Seva Planner</span>
                        <h2 class="h4 font-serif text-forest-dark mb-0">Customize Your Feeding Seva</h2>
                    </div>

                    <form method="GET" action="<?= BASE_URL; ?>/donate.php" id="feedCalcForm">
                        <input type="hidden" name="seva_id" value="1">
                        <input type="hidden" name="purpose" value="Feed a Cow (Grāsa Dāna)">

                        <!-- Step 1: Number of Cows -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">1. Select Number of Cows to Feed</label>
                            <div class="row g-2" id="cowCountSelector">
                                <div class="col-6 col-sm-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3 active">
                                        <input type="radio" name="cow_count" value="1" data-rate="501" checked class="d-none">
                                        <div class="fw-bold">1 Cow</div>
                                        <small class="text-muted">₹ 501 / day</small>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="cow_count" value="5" data-rate="2501" class="d-none">
                                        <div class="fw-bold">5 Cows</div>
                                        <small class="text-muted">₹ 2,501 / day</small>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="cow_count" value="11" data-rate="5501" class="d-none">
                                        <div class="fw-bold">11 Cows</div>
                                        <small class="text-muted">₹ 5,501 / day</small>
                                    </label>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="cow_count" value="21" data-rate="10501" class="d-none">
                                        <div class="fw-bold">21 Cows</div>
                                        <small class="text-muted">₹ 10,501 / day</small>
                                    </label>
                                </div>
                                <div class="col-12 col-sm-8">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="cow_count" value="50" data-rate="25000" class="d-none">
                                        <div class="fw-bold">Full Goushala Herd (50+ Cows)</div>
                                        <small class="text-muted">₹ 25,000 / day</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Duration -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">2. Select Duration</label>
                            <div class="row g-2" id="durationSelector">
                                <div class="col-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3 active">
                                        <input type="radio" name="duration_multiplier" value="1" checked class="d-none">
                                        <div class="fw-bold">1 Day</div>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="duration_multiplier" value="7" class="d-none">
                                        <div class="fw-bold">1 Week (7 Days)</div>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <label class="btn btn-outline-forest btn-sm w-100 p-2 text-center rounded-3">
                                        <input type="radio" name="duration_multiplier" value="30" class="d-none">
                                        <div class="fw-bold">1 Month (30 Days)</div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Auspicious Occasion -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-forest-dark">3. Auspicious Occasion & Sankalpam</label>
                            <select name="occasion" class="form-select mb-2">
                                <option value="General Devotional Seva">General Devotional Seva</option>
                                <option value="Birthday Blessing">Birthday Celebration</option>
                                <option value="Marriage Anniversary">Marriage Anniversary</option>
                                <option value="Pitru Shraadh / Remembrance">Pitru Shraadh / Ancestor Remembrance</option>
                                <option value="Festival Puja">Festival / Ekadashi / Purnima</option>
                                <option value="Health & Healing Recovery">Health, Peace & Healing Prayers</option>
                            </select>
                            <input type="text" name="sankalpam_name" class="form-control form-control-sm" placeholder="Name of Person / Family (Optional for Morning Prayers)">
                        </div>

                        <!-- Calculated Amount Field -->
                        <input type="hidden" name="amount" id="calculatedTotalAmount" value="501">

                        <!-- Live Summary Box -->
                        <div class="p-3 rounded-3 bg-cream border border-warning border-opacity-50 d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <small class="text-muted d-block">Total Seva Offering:</small>
                                <span class="font-serif text-forest-dark fs-3 fw-bold" id="displayTotalAmount">₹ 501</span>
                            </div>
                            <span class="badge bg-gold text-forest-dark fw-bold">80G Tax Deductible</span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-lg rounded-pill py-3 fw-bold shadow-gold">
                                <i class="bi bi-heart-fill me-1"></i> Offer Grāsa Dāna Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Vedic Context & Fodder Nutrition Info -->
            <div class="col-lg-5">
                <div class="card p-4 rounded-4 border-0 shadow-sm bg-white mb-4">
                    <h3 class="h5 font-serif text-forest-dark mb-3"><i class="bi bi-flower1 text-gold me-2"></i> What Your Feed Includes</h3>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-0 small text-muted">
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Fresh Organic Green Fodder:</strong>
                                <p class="mb-0">Hybrid Napier, Lucerne, and fresh maize grass grown organically on our 15-acre farm.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Dry Jowar Husk & Bran:</strong>
                                <p class="mb-0">Digestive fiber and dry roughage ensuring balanced bovine digestion.</p>
                            </div>
                        </li>
                        <li class="d-flex gap-2 align-items-start">
                            <i class="bi bi-check-circle-fill text-forest fs-5 flex-shrink-0 mt-1"></i>
                            <div>
                                <strong class="text-forest-dark">Protein Mash & Pure Jaggery:</strong>
                                <p class="mb-0">Cottonseed cake, wheat bran, calcium mineral salts, and organic jaggery treats.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="card p-4 rounded-4 bg-forest-dark text-white border-0 shadow-sm">
                    <h4 class="h6 font-serif text-gold mb-2"><i class="bi bi-quote fs-4"></i> Sacred Vedic Reference</h4>
                    <p class="small text-cream opacity-85 fst-italic mb-3">
                        "He who feeds a cow with fresh green grass daily obtains the merit of feeding all three worlds."
                    </p>
                    <small class="text-gold-light opacity-75">— Bhavishya Purana</small>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Client Calculator Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cowRadios = document.querySelectorAll('input[name="cow_count"]');
    const durRadios = document.querySelectorAll('input[name="duration_multiplier"]');
    const displayTotal = document.getElementById('displayTotalAmount');
    const inputTotal = document.getElementById('calculatedTotalAmount');

    function calculate() {
        let baseRate = 501;
        cowRadios.forEach(r => {
            if (r.checked) {
                baseRate = parseInt(r.getAttribute('data-rate'), 10);
                r.closest('.btn').classList.add('active');
            } else {
                r.closest('.btn').classList.remove('active');
            }
        });

        let multiplier = 1;
        durRadios.forEach(r => {
            if (r.checked) {
                multiplier = parseInt(r.value, 10);
                r.closest('.btn').classList.add('active');
            } else {
                r.closest('.btn').classList.remove('active');
            }
        });

        const total = baseRate * multiplier;
        displayTotal.textContent = '₹ ' + total.toLocaleString('en-IN');
        inputTotal.value = total;
    }

    cowRadios.forEach(r => r.addEventListener('change', calculate));
    durRadios.forEach(r => r.addEventListener('change', calculate));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
