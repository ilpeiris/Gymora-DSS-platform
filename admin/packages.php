<?php
// /Gymora/admin/packages.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);

$success = '';
$error = '';

// Handle creating a new package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_package'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price_gbp']);
    $duration = intval($_POST['duration_months']);
    $consultations = intval($_POST['consultation_count']);
    // Storing features as JSON as per schema
    $features = json_encode(explode("\n", trim($_POST['features'])));
    
    if (empty($name) || $price < 0 || $duration <= 0) {
        $error = "Please provide valid package details.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO packages (name, price_gbp, duration_months, consultation_count, features) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $duration, $consultations, $features]);
            $success = "New package created successfully!";
        } catch (PDOException $e) {
            $error = "Failed to create package. Error: " . $e->getMessage();
        }
    }
}

// Handle toggling package status (Active/Inactive)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['toggle'] == '1' ? 1 : 0;
    
    $updateStmt = $pdo->prepare("UPDATE packages SET is_active = ? WHERE id = ?");
    $updateStmt->execute([$status, $id]);
    $success = "Package status updated.";
}

// Fetch all packages
$packagesStmt = $pdo->query("SELECT * FROM packages ORDER BY price_gbp ASC");
$packages = $packagesStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Manage Packages</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Membership Packages</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Create New Package</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="add_package" value="1">
                    <div class="mb-3">
                        <label class="form-label">Package Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Premium Plus">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Price (£)</label>
                            <input type="number" step="0.01" name="price_gbp" class="form-control" required placeholder="49.99">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duration (Months)</label>
                            <input type="number" name="duration_months" class="form-control" required value="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Included Consultations</label>
                        <input type="number" name="consultation_count" class="form-control" required value="1">
                        <small class="text-muted">Doctor visits included</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Features (One per line)</label>
                        <textarea name="features" class="form-control" rows="3" placeholder="24/7 Gym Access&#10;Free Classes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Save Package</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Current Packages</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Consults</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $pkg): ?>
                            <tr>
                                <td class="align-middle fw-bold"><?= htmlspecialchars($pkg['name']) ?></td>
                                <td class="align-middle">£<?= htmlspecialchars($pkg['price_gbp']) ?></td>
                                <td class="align-middle"><?= htmlspecialchars($pkg['consultation_count']) ?></td>
                                <td class="align-middle">
                                    <?= $pkg['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($pkg['is_active']): ?>
                                        <a href="packages.php?toggle=0&id=<?= $pkg['id'] ?>" class="btn btn-sm btn-outline-danger">Deactivate</a>
                                    <?php else: ?>
                                        <a href="packages.php?toggle=1&id=<?= $pkg['id'] ?>" class="btn btn-sm btn-outline-success">Activate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($packages) == 0): ?>
                            <tr><td colspan="5" class="text-center py-3">No packages created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>