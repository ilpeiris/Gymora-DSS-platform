<?php
// /Gymora/user/packages.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

// Fetch all active packages
$stmt = $pdo->query("SELECT * FROM packages WHERE is_active = 1");
$packages = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12 text-center">
        <h2 class="fw-bold">Membership Packages</h2>
        <p class="text-muted">Select a plan to access the gym and book medical consultations.</p>
        <hr>
    </div>
</div>

<div class="row justify-content-center mt-4">
    <?php foreach ($packages as $pkg): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow text-center h-100 border-primary">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0"><?= htmlspecialchars($pkg['name']) ?></h4>
                </div>
                <div class="card-body d-flex flex-column">
                    <h2 class="card-title my-3">£<?= htmlspecialchars($pkg['price_gbp']) ?><small class="text-muted fs-6">/<?= $pkg['duration_months'] ?> mo</small></h2>
                    <p class="text-dark fw-bold border-bottom pb-2">Includes <?= $pkg['consultation_count'] ?> Doctor Consultation(s)</p>
                    
                    <ul class="list-unstyled mb-4">
                        <?php 
                        $features = json_decode($pkg['features'], true);
                        if ($features) {
                            foreach ($features as $feature) {
                                echo "<li class='mb-2 text-start'><i class='bi bi-check-circle-fill text-success me-2'></i> " . htmlspecialchars($feature) . "</li>";
                            }
                        }
                        ?>
                    </ul>
                    
                    <div class="mt-auto">
                        <a href="checkout.php?package_id=<?= $pkg['id'] ?>" class="btn btn-primary w-100 fw-bold py-2 rounded-pill">Select This Plan</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once '../includes/footer.php'; ?>