<?php
// /Gymora/doctor/patient_history.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_DOCTOR);

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    die("Invalid request. Missing patient ID.");
}

// Fetch Patient Name
$patStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$patStmt->execute([$patient_id]);
$patient = $patStmt->fetch();

// Fetch all assessments for this patient
$histStmt = $pdo->prepare("
    SELECT id, weight_kg, height_cm, bmi, created_at, status 
    FROM medical_assessments 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$histStmt->execute([$patient_id]);
$history = $histStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Patient History</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Medical History: <?= htmlspecialchars($patient['name'] ?? 'Unknown') ?></h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Past Assessments</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($history) > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td class="align-middle"><?= date('M j, Y', strtotime($record['created_at'])) ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($record['weight_kg']) ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($record['bmi']) ?></td>
                                    <td class="align-middle"><span class="badge bg-secondary"><?= ucfirst($record['status']) ?></span></td>
                                    <td class="align-middle">
                                        <a href="report_view.php?assessment_id=<?= $record['id'] ?>" class="btn btn-sm btn-outline-primary">View Full Report</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No past assessments found for this patient.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>