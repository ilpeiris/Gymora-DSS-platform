<?php
// /Gymora/admin/dashboard.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

// SECURITY GATEWAY: Only Admins allowed
requireRole(ROLE_ADMIN);

// Quick Stats Queries
$stats = [];

$userStmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $userStmt->fetchColumn();

$medStmt = $pdo->query("SELECT COUNT(*) FROM medical_assessments");
$stats['total_assessments'] = $medStmt->fetchColumn();

$apptStmt = $pdo->query("SELECT COUNT(*) FROM appointments");
$stats['total_appointments'] = $apptStmt->fetchColumn();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">System Administration</h2>
        <p class="text-muted">Welcome to the Gymora Management Console.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-primary text-center h-100">
            <div class="card-body py-4">
                <h1 class="display-4 fw-bold text-primary"><?= $stats['total_users'] ?></h1>
                <h5 class="text-muted mb-0">Total Registered Users</h5>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-success text-center h-100">
            <div class="card-body py-4">
                <h1 class="display-4 fw-bold text-success"><?= $stats['total_assessments'] ?></h1>
                <h5 class="text-muted mb-0">Medical Assessments</h5>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-info text-center h-100">
            <div class="card-body py-4">
                <h1 class="display-4 fw-bold text-info"><?= $stats['total_appointments'] ?></h1>
                <h5 class="text-muted mb-0">Total Appointments</h5>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body d-grid gap-3">
                <a href="users.php" class="btn btn-outline-dark text-start"> Manage Users & Roles</a>
                <a href="packages.php" class="btn btn-outline-dark text-start"> Manage Membership Packages</a>
                <a href="audit_logs.php" class="btn btn-outline-danger text-start"> View GDPR Audit Logs</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>