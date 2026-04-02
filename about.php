<?php
// /Gymora/about.php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/constants.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <span class="text-primary fw-bold text-uppercase tracking-wide">Our Mission</span>
            <h1 class="display-4 fw-bold text-dark mt-2 mb-4">Bridging the Exercise Prescription Gap.</h1>
            <p class="lead text-muted">
                For decades, doctors have prescribed exercise as medicine, but commercial gyms have lacked the digital infrastructure to enforce these medical directives safely. Gymora was built to change that.
            </p>
            <p class="text-muted">
                We are a Medical-Integrated Smart Gym. Our facility employs both certified fitness trainers and registered medical physicians. By centralizing your health data, we ensure that every squat, sprint, and stretch you perform is mathematically validated for your unique physiology.
            </p>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg bg-primary text-white p-5 rounded-4">
                <h3 class="fw-bold mb-4"><i class="bi bi-quote"></i> The Gymora Promise</h3>
                <ul class="list-unstyled mb-0 fs-5">
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> No generic workout plans.</li>
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Clinical oversight on every booking.</li>
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Zero guesswork for your health.</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Absolute data privacy.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>