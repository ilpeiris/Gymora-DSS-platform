<?php
// /Gymora/user/dashboard.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

// SECURITY GATEWAY: Only allow 'user' roles to view this page
requireRole(ROLE_USER); 
$user_id = $_SESSION['user_id'];

// 1. Fetch the user's details and their active package name
$stmt = $pdo->prepare("
    SELECT u.*, p.name as package_name 
    FROM users u 
    LEFT JOIN packages p ON u.package_id = p.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Fetch Upcoming 1-on-1 Appointments
$apptStmt = $pdo->prepare("
    SELECT a.datetime, a.type, a.status, u.name as staff_name 
    FROM appointments a 
    JOIN users u ON a.staff_id = u.id 
    WHERE a.user_id = ? AND a.datetime >= CURDATE() AND a.status = 'scheduled' 
    ORDER BY a.datetime ASC LIMIT 5
");
$apptStmt->execute([$user_id]);
$appointments = $apptStmt->fetchAll();

// 3. Fetch Upcoming Booked Classes
$classStmt = $pdo->prepare("
    SELECT c.name, c.datetime, c.location, u.name as trainer_name 
    FROM bookings b 
    JOIN classes c ON b.class_id = c.id 
    JOIN users u ON c.trainer_id = u.id 
    WHERE b.user_id = ? AND b.status = 'confirmed' AND c.datetime >= NOW() 
    ORDER BY c.datetime ASC LIMIT 5
");
$classStmt->execute([$user_id]);
$booked_classes = $classStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">Welcome back, <?= htmlspecialchars($user['name']) ?>!</h2>
        <p class="text-muted">Here is your medical fitness overview.</p>
        <hr>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'class_booked'): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <strong><i class="bi bi-check-circle"></i> Success!</strong> Your class has been successfully booked. Our DSS engine verified it is medically safe for you to attend.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-5 mb-4 mb-md-0">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-person-vcard"></i> My Membership
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php if ($user['package_id']): ?>
                    <h5 class="fw-bold text-primary mb-3"><?= htmlspecialchars($user['package_name']) ?></h5>
                    <p class="mb-2"><strong>Expires On:</strong> <?= date('F j, Y', strtotime($user['package_expiry'])) ?></p>
                    <p class="mb-0"><strong>Doctor Consultations Left:</strong> 
                        <span class="badge bg-dark fs-6 ms-1"><?= $user['consultations_remaining'] ?></span>
                    </p>
                <?php else: ?>
                    <div class="text-center py-2">
                        <p class="text-muted mb-3">You do not have an active membership package.</p>
                        <a href="packages.php" class="btn btn-primary w-100 fw-bold">Browse Packages</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm h-100 border-dark">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-lightning-charge"></i> Quick Actions
            </div>
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <a href="classes.php" class="btn btn-outline-success w-100 py-3 fw-bold"><i class="bi bi-calendar-plus d-block fs-3 mb-1"></i> Book a Class</a>
                    </div>
                    <div class="col-sm-6">
                        <a href="appointments.php" class="btn btn-outline-info w-100 py-3 fw-bold text-dark"><i class="bi bi-person-video d-block fs-3 mb-1"></i> Book Appointment</a>
                    </div>
                    <div class="col-sm-6">
                        <a href="workout_plan.php" class="btn btn-outline-primary w-100 py-3 fw-bold"><i class="bi bi-activity d-block fs-3 mb-1"></i> My Workout Plan</a>
                    </div>
                    <div class="col-sm-6">
                        <a href="progress.php" class="btn btn-outline-secondary w-100 py-3 fw-bold"><i class="bi bi-graph-up d-block fs-3 mb-1"></i> Log Progress</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-success h-100">
            <div class="card-header bg-success text-white fw-bold"><i class="bi bi-calendar-event"></i> My Upcoming Classes</div>
            <div class="card-body p-0">
                <?php if (count($booked_classes) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($booked_classes as $c): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= date('D, M j @ g:i A', strtotime($c['datetime'])) ?> | <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($c['location']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4 text-muted text-center">You haven't booked any upcoming classes.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-dark fw-bold"><i class="bi bi-person-video"></i> 1-on-1 Appointments</div>
            <div class="card-body p-0">
                <?php if (count($appointments) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($appointments as $a): ?>
                            <li class="list-group-item">
                                <strong><?= ucwords(str_replace('_', ' ', $a['type'])) ?></strong><br>
                                <small class="text-muted"><i class="bi bi-person-badge"></i> With <?= htmlspecialchars($a['staff_name']) ?> | <i class="bi bi-clock"></i> <?= date('D, M j @ g:i A', strtotime($a['datetime'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4 text-muted text-center">No 1-on-1 appointments scheduled.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>