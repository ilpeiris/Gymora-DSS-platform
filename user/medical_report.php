<?php
// /Gymora/user/medical_report.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);
$user_id = $_SESSION['user_id'];

// Fetch the user's latest submitted assessment
$stmt = $pdo->prepare("
    SELECT m.*, d.name as doctor_name 
    FROM medical_assessments m
    LEFT JOIN users d ON m.doctor_id = d.id
    WHERE m.user_id = ? AND m.status = 'submitted'
    ORDER BY m.created_at DESC LIMIT 1
");
$stmt->execute([$user_id]);
$report = $stmt->fetch();

$conditions = [];
if ($report) {
    $condStmt = $pdo->prepare("SELECT condition_name, severity FROM medical_conditions WHERE assessment_id = ?");
    $condStmt->execute([$report['id']]);
    $conditions = $condStmt->fetchAll();
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold text-danger"><i class="bi bi-file-medical"></i> My Clinical Profile</h2>
        <p class="text-muted">Review your official doctor's assessment and clinical advice.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if ($report): ?>
            <div class="card shadow border-danger">
                <div class="card-header bg-danger text-white d-flex justify-content-between">
                    <h5 class="mb-0">Assessment Date: <?= date('F j, Y', strtotime($report['created_at'])) ?></h5>
                    <h5 class="mb-0">Doctor: Dr. <?= htmlspecialchars($report['doctor_name'] ?? 'Unknown') ?></h5>
                </div>
                <div class="card-body">
                    
                    <h6 class="border-bottom pb-2 fw-bold text-secondary">1. Clinical Vitals</h6>
                    <div class="row mb-4">
                        <div class="col-sm-3"><p class="mb-1"><strong>Weight:</strong> <?= htmlspecialchars($report['weight_kg']) ?> kg</p></div>
                        <div class="col-sm-3"><p class="mb-1"><strong>Height:</strong> <?= htmlspecialchars($report['height_cm']) ?> cm</p></div>
                        <div class="col-sm-3"><p class="mb-1"><strong>BMI:</strong> <?= htmlspecialchars($report['bmi']) ?></p></div>
                        <div class="col-sm-3"><p class="mb-1"><strong>BP:</strong> <?= htmlspecialchars($report['blood_pressure_sys'] . '/' . $report['blood_pressure_dia']) ?></p></div>
                    </div>

                    <h6 class="border-bottom pb-2 fw-bold text-secondary mt-4">2. Diagnosed Conditions (DSS Triggers)</h6>
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
                        <div class="alert alert-success mb-4">No restricting medical conditions were diagnosed.</div>
                    <?php endif; ?>

                    <h6 class="border-bottom pb-2 fw-bold text-secondary">3. Clinical Notes & Advice</h6>
                    <div class="bg-light p-3 rounded border mb-3">
                        <strong>Doctor's General Notes:</strong><br>
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
        <?php else: ?>
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center py-5">
                    <h4 class="text-warning mb-3">No Medical Profile Found</h4>
                    <p class="text-muted">You have not completed a clinical assessment with our medical staff yet.</p>
                    <a href="appointments.php" class="btn btn-outline-dark mt-2">Book an Assessment</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>