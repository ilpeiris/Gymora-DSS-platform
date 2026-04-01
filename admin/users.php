<?php
// /Gymora/admin/users.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);

$success = '';
$error = '';

// Handle Account Deactivation/Activation
if (isset($_GET['toggle_active']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $new_status = $_GET['toggle_active'] == '1' ? 1 : 0;
    
    // Prevent admin from deactivating themselves
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot deactivate your own admin account.";
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        if ($updateStmt->execute([$new_status, $user_id])) {
            $success = "User account status updated successfully.";
        } else {
            $error = "Failed to update account status.";
        }
    }
}

// Fetch all users with their package names
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at, p.name as package_name 
    FROM users u 
    LEFT JOIN packages p ON u.package_id = p.id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Manage Users</li>
            </ol>
        </nav>
        <h2 class="fw-bold">User Management</h2>
        <hr>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Registered Accounts</h5>
                <span class="badge bg-light text-dark"><?= count($users) ?> Total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="align-middle fw-bold"><?= htmlspecialchars($u['name']) ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="align-middle">
                                        <span class="badge bg-primary"><?= ucfirst(htmlspecialchars($u['role'])) ?></span>
                                    </td>
                                    <td class="align-middle"><?= htmlspecialchars($u['package_name'] ?? 'None') ?></td>
                                    <td class="align-middle">
                                        <?php if ($u['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Deactivated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="btn-group" role="group">
                                            <a href="assign_roles.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-dark">Change Role</a>
                                            <?php if ($u['is_active']): ?>
                                                <a href="users.php?toggle_active=0&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this user?');">Deactivate</a>
                                            <?php else: ?>
                                                <a href="users.php?toggle_active=1&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-success">Activate</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>