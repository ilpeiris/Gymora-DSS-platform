<?php
// /trainer/create_plan.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_TRAINER);

$error = '';
$success = '';

$client_id = $_GET['user_id'] ?? null;

if (!$client_id) {
    die("Invalid request. Missing client ID.");
}

// Fetch Client Details and Latest Medical Assessment
$clientStmt = $pdo->prepare("
    SELECT u.name, u.email, m.weight_kg, m.height_cm, m.bmi, m.blood_pressure, m.medical_notes 
    FROM users u 
    LEFT JOIN medical_assessments m ON u.id = m.user_id 
    WHERE u.id = ? 
    ORDER BY m.created_at DESC LIMIT 1
");
$clientStmt->execute([$client_id]);
$client = $clientStmt->fetch();

// Handle Workout Plan Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Matching your database columns exactly
    $week_number = intval($_POST['week_number']);
    $status = $_POST['status']; // 'draft', 'active', or 'completed'
    $notes = trim($_POST['notes']);
    
    if ($week_number <= 0 || empty($notes)) {
        $error = "Please provide a valid week number and plan notes.";
    } else {
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO workout_plans (user_id, trainer_id, week_number, status, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$client_id, $_SESSION['user_id'], $week_number, $status, $notes]);
            
            $success = "Week $week_number workout plan successfully assigned to " . htmlspecialchars($client['name']) . "!";
        } catch (PDOException $e) {
            $error = "Failed to assign workout plan: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Create Plan</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Design Workout Plan for <?= htmlspecialchars($client['name'] ?? 'Client') ?></h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-dark">
                <h5 class="mb-0">Medical Clearance Info</h5>
            </div>
            <div class="card-body">
                <?php if ($client && $client['bmi']): ?>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Weight:</strong> <span><?= htmlspecialchars($client['weight_kg']) ?> kg</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Height:</strong> <span><?= htmlspecialchars($client['height_cm']) ?> cm</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>BMI:</strong> <span><?= htmlspecialchars($client['bmi']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>BP:</strong> <span><?= htmlspecialchars($client['blood_pressure'] ?? 'Not recorded') ?></span>
                        </li>
                    </ul>
                    <div class="alert alert-warning">
                        <strong>Doctor's Notes:</strong><br>
                        <?= nl2br(htmlspecialchars($client['medical_notes'] ?? 'No specific restrictions noted.')) ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Warning: This user has not been cleared by a doctor yet. Proceed with caution.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-dark h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">New Workout Routine</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br><br>
                        <a href="dashboard.php" class="btn btn-success">Back to Dashboard</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Week Number</label>
                                <input type="number" min="1" name="week_number" class="form-control" required placeholder="e.g., 1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plan Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="draft">Draft (Working on it)</option>
                                    <option value="active" selected>Active (Ready for client)</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Workout Notes & Exercises</label>
                            <textarea name="notes" class="form-control" rows="8" required placeholder="Monday: Chest & Triceps...&#10;Tuesday: Back & Biceps..."></textarea>
                            <small class="text-muted">Be sure to accommodate any medical restrictions listed by the doctor.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Assign Plan to Client</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>