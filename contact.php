<?php
// /Gymora/contact.php
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/constants.php';

$success = '';
// Simple form handler for demonstration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $success = "Thank you for reaching out! A member of our clinical team will contact you shortly.";
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <span class="text-info fw-bold text-uppercase tracking-wide">Get in Touch</span>
            <h1 class="display-4 fw-bold text-dark mt-2">Contact Gymora</h1>
            <p class="lead text-muted mx-auto" style="max-width: 600px;">Have questions about our medical assessments or membership plans? Drop us a message or visit our clinic.</p>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100">
                <div class="card-body">
                    <h4 class="fw-bold mb-4">Send us a Message</h4>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="send_message" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Message</label>
                                <textarea class="form-control" rows="5" required></textarea>
                            </div>
                            <div class="col-12 text-end mt-4">
                                <button type="submit" class="btn btn-primary fw-bold px-5">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm bg-dark text-white p-4 h-100">
                <div class="card-body">
                    <h4 class="fw-bold mb-4" style="color: #cbd5e1;">Facility Details</h4>
                    
                    <div class="d-flex align-items-start mb-4">
                        <i class="bi bi-geo-alt fs-3 text-primary me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1" style="color: #cbd5e1;">Location</h6>
                            <p class="text-muted mb-0 small">123 Innovation Way<br>Cardiff, CF10 1FG<br>United Kingdom</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <i class="bi bi-envelope fs-3 text-info me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1" style="color: #cbd5e1;">Email</h6>
                            <p class="text-muted mb-0 small">clinical@Gymora.com<br>support@Gymora.com</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <i class="bi bi-clock fs-3 text-success me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1" style="color: #cbd5e1;">Opening Hours</h6>
                            <p class="text-muted mb-0 small">Mon - Fri: 06:00 - 22:00<br>Sat - Sun: 08:00 - 20:00<br><span class="text-warning">Clinical Hours: 09:00 - 17:00</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>