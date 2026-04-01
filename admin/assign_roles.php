<?php
// /Gymora/admin/assign_roles.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);

$error = '';
$success = '';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    die("Invalid request. Missing user ID.");
}

// Fetch the user
$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    die("User not found.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_role = $_POST['role'];
    $allowed_roles = [ROLE_USER, ROLE_DOCTOR, ROLE_TRAINER, ROLE_ADMIN];
    
    if (!in_array($new_role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } elseif ($user_id == $_SESSION['user_id'] && $new_role != ROLE_ADMIN) {
        $error = "You cannot demote yourself from Admin status.";
    } else {
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $updateStmt->execute([$new_role, $user_id]);
            $success = "Role successfully updated to " . ucfirst($new_role) . "!";
            $target_user['role'] = $new_role; // Update UI
        } catch (PDOException $e) {
            $error = "Failed to update role.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                <li class="breadcrumb-item"><a href="users.php">Manage Users</a></li>
                <li class="breadcrumb-item active">Change Role</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Assign System Role</h2>
        <hr>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Modify Account: <?= htmlspecialchars($target_user['name']) ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <p><strong>Email:</strong> <?= htmlspecialchars($target_user['email']) ?></p>
                <p><strong>Current Role:</strong> <span class="badge bg-primary fs-6"><?= ucfirst(htmlspecialchars($target_user['role'])) ?></span></p>
                
                <form method="POST" action="" class="mt-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select New Role</label>
                        <select name="role" class="form-select">
                            <option value="<?= ROLE_USER ?>" <?= $target_user['role'] == ROLE_USER ? 'selected' : '' ?>>Gym Member (User)</option>
                            <option value="<?= ROLE_DOCTOR ?>" <?= $target_user['role'] == ROLE_DOCTOR ? 'selected' : '' ?>>Medical Doctor</option>
                            <option value="<?= ROLE_TRAINER ?>" <?= $target_user['role'] == ROLE_TRAINER ? 'selected' : '' ?>>Fitness Trainer</option>
                            <option value="<?= ROLE_ADMIN ?>" <?= $target_user['role'] == ROLE_ADMIN ? 'selected' : '' ?>>System Admin</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="users.php" class="btn btn-outline-secondary">Back to List</a>
                        <button type="submit" class="btn btn-dark fw-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>