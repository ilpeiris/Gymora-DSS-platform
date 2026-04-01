<?php
// /Gymora/doctor/report_view.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../dss/audit_logger.php'; // GDPR Tracker

requireRole(ROLE_DOCTOR);

$assessment_id = $_GET['assessment_id'] ?? null;

if (!$assessment_id) {
    die("Invalid request. Missing assessment ID.");
}

// LOG THE GDPR EVENT - Doctor is reading medical data!
logAudit($_SESSION['user_id'], 'READ_MEDICAL_REPORT', 'medical', $assessment_id);

// Fetch Assessment Details
$stmt = $pdo->prepare("
    SELECT m.*, u.name as patient_name, d.name as doctor_name 
    FROM medical_assessments m
    JOIN users u ON m.user_id = u.id
    JOIN users d ON m.doctor_id = d.id
    WHERE m.id = ?
");
$stmt->execute([$assessment_id]);
$report = $stmt->fetch();

if (!$report) {
    die("Report not found.");
}

// Fetch associated conditions
$condStmt = $pdo->prepare("SELECT condition_name, severity FROM medical_conditions WHERE assessment_id = ?");
$condStmt->execute([$assessment_id]);
$conditions = $condStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12 text-center mb-4">
        <h2 class="fw-bold text-primary">Gymora Clinical Report</h2>
        <p class="text-muted">Generated on <?= date('F j, Y', strtotime($report['created_at'])) ?></p>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">🖨️ Print Report</button>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow border-dark">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <h5 class="mb-0">Patient: <?= htmlspecialchars($report['patient_name']) ?></h5>
                <h5 class="mb-0">Assessing Physician: Dr. <?= htmlspecialchars($report['doctor_name']) ?></h5>
            </div>
            <div class="card-body">
                
                <h6 class="border-bottom pb-2 fw-bold text-secondary">1. Vitals</h6>
                <div class="row mb-4">
                    <div class="col-sm-3"><p class="mb-1"><strong>Weight:</strong> <?= htmlspecialchars($report['weight_kg']) ?> kg</p></div>
                    <div class="col-sm-3"><p class="mb-1"><strong>Height:</strong> <?= htmlspecialchars($report['height_cm']) ?> cm</p></div>
                    <div class="col-sm-3"><p class="mb-1"><strong>BMI:</strong> <?= htmlspecialchars($report['bmi']) ?></p></div>
                    <div class="col-sm-3"><p class="mb-1"><strong>BP:</strong> <?= htmlspecialchars($report['blood_pressure_sys'] . '/' . $report['blood_pressure_dia']) ?></p></div>
                </div>

                <h6 class="border-bottom pb-2 fw-bold text-secondary mt-4">2. Diagnosed Conditions</h6>
                <?php if (count($conditions) > 0): ?>
                    <ul class="list-group mb-4">
                        <?php foreach ($conditions as $cond): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= ucwords(str_replace('_', ' ', htmlspecialchars($cond['condition_name']))) ?>
                                <span class="badge bg-danger rounded-pill">Severity: <?= $cond['severity'] ?>/5</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-4">No specific medical conditions recorded.</p>
                <?php endif; ?>

                <h6 class="border-bottom pb-2 fw-bold text-secondary">3. Clinical Notes & Recommendations</h6>
                <div class="bg-light p-3 rounded border mb-3">
                    <strong>General Notes:</strong><br>
                    <?= nl2br(htmlspecialchars($report['notes_encrypted'] ?? 'None')) ?>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded border">
                            <strong>Dietary Advice:</strong><br>
                            <?= nl2br(htmlspecialchars($report['diet_notes'] ?? 'None')) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded border">
                            <strong>Supplement Advice:</strong><br>
                            <?= nl2br(htmlspecialchars($report['supplement_notes'] ?? 'None')) ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>