<?php
// /Gymora/doctor/assessment.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../dss/audit_logger.php'; // Bring in the GDPR logger

requireRole(ROLE_DOCTOR);

$error = '';
$success = '';

$appointment_id = $_GET['appointment_id'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;

if (!$appointment_id || !$patient_id) {
    die("Invalid request. Missing appointment or patient ID.");
}

// Fetch Patient Details
$patStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$patStmt->execute([$patient_id]);
$patient = $patStmt->fetch();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = floatval($_POST['weight_kg']);
    $height = floatval($_POST['height_cm']);
    $bp_sys = intval($_POST['blood_pressure_sys']);
    $bp_dia = intval($_POST['blood_pressure_dia']);
    $heart_rate = intval($_POST['heart_rate_resting']);
    $notes = trim($_POST['medical_notes']);
    $diet_notes = trim($_POST['diet_notes']);
    $supp_notes = trim($_POST['supplement_notes']);
    
    if ($weight <= 0 || $height <= 0) {
        $error = "Please enter valid weight and height.";
    } else {
        $height_in_meters = $height / 100;
        $bmi = round($weight / ($height_in_meters * $height_in_meters), 1);
        
        try {
            $pdo->beginTransaction();
            
            // 1. Insert the medical assessment
            $insertStmt = $pdo->prepare("
                INSERT INTO medical_assessments (user_id, doctor_id, weight_kg, height_cm, bmi, blood_pressure_sys, blood_pressure_dia, heart_rate_resting, notes_encrypted, diet_notes, supplement_notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
            ");
            // Note: We are storing notes in 'notes_encrypted' column as per your schema
            $insertStmt->execute([$patient_id, $_SESSION['user_id'], $weight, $height, $bmi, $bp_sys, $bp_dia, $heart_rate, $notes, $diet_notes, $supp_notes]);
            
            // Get the ID of the assessment we just created
            $assessment_id = $pdo->lastInsertId();
            
            // 2. Insert Medical Conditions if any were checked
            if (isset($_POST['conditions']) && is_array($_POST['conditions'])) {
                $condStmt = $pdo->prepare("INSERT INTO medical_conditions (assessment_id, condition_name, severity, notes) VALUES (?, ?, ?, ?)");
                
                foreach ($_POST['conditions'] as $condition) {
                    $severity_key = "severity_" . $condition;
                    $severity = intval($_POST[$severity_key] ?? 1);
                    $condStmt->execute([$assessment_id, $condition, $severity, 'Added via assessment']);
                }
            }
            
            // 3. Mark the appointment as completed
            $updateAppt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $updateAppt->execute([$appointment_id]);
            
            // 4. LOG THE GDPR EVENT!
            logAudit($_SESSION['user_id'], 'SUBMIT_ASSESSMENT', 'medical', $assessment_id);
            
            $pdo->commit();
            $success = "Assessment submitted securely! BMI calculated as $bmi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to submit assessment. Please check database columns: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">Medical Assessment: <?= htmlspecialchars($patient['name']) ?></h2>
        <hr>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10 mb-4">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Record Patient Vitals & Conditions</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br><br>
                        <a href="dashboard.php" class="btn btn-success">Return to Dashboard</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <h5 class="border-bottom pb-2">1. Vitals</h5>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">BP (Sys/Dia)</label>
                                <div class="input-group">
                                    <input type="number" name="blood_pressure_sys" class="form-control" placeholder="120">
                                    <span class="input-group-text">/</span>
                                    <input type="number" name="blood_pressure_dia" class="form-control" placeholder="80">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Resting HR (bpm)</label>
                                <input type="number" name="heart_rate_resting" class="form-control" placeholder="70">
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mt-4">2. Medical Conditions (DSS Triggers)</h5>
                        <p class="text-muted small">Select conditions to automatically trigger DSS exercise restrictions for the trainer.</p>
                        <div class="row mb-3">
                            <?php 
                            $conditionsList = ['hypertension' => 'Hypertension', 'lumbar_disc' => 'Lumbar Disc Herniation', 'knee_injury' => 'Knee Injury', 'cardiovascular_risk' => 'Cardiovascular Risk'];
                            foreach ($conditionsList as $db_val => $label): 
                            ?>
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox" name="conditions[]" value="<?= $db_val ?>">
                                    </div>
                                    <span class="input-group-text w-50"><?= $label ?></span>
                                    <select name="severity_<?= $db_val ?>" class="form-select">
                                        <option value="1">Sev 1 (Mild)</option>
                                        <option value="3" selected>Sev 3 (Moderate)</option>
                                        <option value="5">Sev 5 (Severe)</option>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <h5 class="border-bottom pb-2 mt-4">3. Clinical Notes</h5>
                        <div class="mb-3">
                            <label class="form-label">General Medical Notes</label>
                            <textarea name="medical_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Dietary Recommendations</label>
                                <textarea name="diet_notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Supplement Recommendations</label>
                                <textarea name="supplement_notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary fw-bold">Submit Assessment Securely</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>