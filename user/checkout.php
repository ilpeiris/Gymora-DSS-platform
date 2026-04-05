<?php
// /Gymora/user/checkout.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

// Get the package ID from the URL
$package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
$success = '';
$error = '';

// Fetch package details to show on the checkout page
$pkgStmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
$pkgStmt->execute([$package_id]);
$package = $pkgStmt->fetch();

// If they manipulated the URL or package doesn't exist, send them back
if (!$package) {
    header("Location: packages.php");
    exit();
}

// Handle the Mock Payment POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // In a real system, you would call the Stripe/PayPal API here using the POSTed card details.
    // For this prototype, we assume the "payment" was successful and update the DB.
    
    $duration = $package['duration_months'];
    $expiry_date = date('Y-m-d', strtotime("+$duration months"));
    
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET package_id = ?, package_expiry = ?, consultations_remaining = ? 
        WHERE id = ?
    ");
    
    try {
        $updateStmt->execute([$package_id, $expiry_date, $package['consultation_count'], $_SESSION['user_id']]);
        $success = "Payment Successful! Your " . htmlspecialchars($package['name']) . " is now active.";
    } catch (PDOException $e) {
        $error = "Transaction failed. Please try again.";
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        
        <?php if ($success): ?>
            <div class="card shadow border-success text-center py-5">
                <div class="card-body">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3 fw-bold">Payment Successful!</h2>
                    <p class="text-muted fs-5"><?= htmlspecialchars($success) ?></p>
                    <a href="dashboard.php" class="btn btn-success btn-lg mt-3 px-5 rounded-pill">Go to My Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <h2 class="fw-bold mb-4"><i class="bi bi-lock-fill text-success"></i> Secure Checkout</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-5 order-md-2 mb-4">
                    <div class="card shadow-sm border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <h4 class="fw-bold text-primary"><?= htmlspecialchars($package['name']) ?></h4>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Duration:</span>
                                <strong><?= htmlspecialchars($package['duration_months']) ?> Month(s)</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Medical Consultations:</span>
                                <strong><?= htmlspecialchars($package['consultation_count']) ?> Included</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5 fw-bold">
                                <span>Total Due:</span>
                                <span>£<?= htmlspecialchars($package['price_gbp']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 order-md-1">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Payment Details</h5>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Name on Card</label>
                                    <input type="text" class="form-control" placeholder="John Doe" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Card Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-credit-card"></i></span>
                                        <input type="text" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6 mb-4">
                                        <label class="form-label text-muted small fw-bold">Expiry Date</label>
                                        <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                    <div class="col-6 mb-4">
                                        <label class="form-label text-muted small fw-bold">CVV</label>
                                        <input type="password" class="form-control" placeholder="123" maxlength="3" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5 rounded-pill shadow-sm">
                                    Pay £<?= htmlspecialchars($package['price_gbp']) ?>
                                </button>
                                <p class="text-center text-muted small mt-3 mb-0"><i class="bi bi-shield-lock"></i> Payments are mock-processed securely.</p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>