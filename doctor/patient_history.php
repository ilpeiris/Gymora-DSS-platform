<?php
// /Gymora/doctor/patient_history.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_DOCTOR);
$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) die("Invalid request. Missing patient ID.");

// 1. Fetch Patient Info
$patStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$patStmt->execute([$patient_id]);
$patient = $patStmt->fetch();

// 2. Fetch past medical assessments
$histStmt = $pdo->prepare("SELECT id, weight_kg, height_cm, bmi, created_at, status FROM medical_assessments WHERE user_id = ? ORDER BY created_at DESC");
$histStmt->execute([$patient_id]);
$history = $histStmt->fetchAll();

// 3. Fetch Workout Plans assigned by trainers
$planStmt = $pdo->prepare("SELECT w.week_number, w.status, w.created_at, t.name as trainer_name FROM workout_plans w JOIN users t ON w.trainer_id = t.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
$planStmt->execute([$patient_id]);
$workout_plans = $planStmt->fetchAll();

require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Patient 360 Profile</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Patient 360: <?= htmlspecialchars($patient['name'] ?? 'Unknown') ?></h2>
        <p class="text-muted">Review clinical assessments, trainer programming, and live progress data.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-danger mb-4">
            <div class="card-header bg-danger text-white fw-bold"><i class="bi bi-file-medical"></i> Clinical Assessments</div>
            <div class="card-body p-0">
                <?php if (count($history) > 0): ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>BMI</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td class="align-middle"><?= date('M j, Y', strtotime($record['created_at'])) ?></td>
                                    <td class="align-middle fw-bold"><?= htmlspecialchars($record['bmi']) ?></td>
                                    <td class="align-middle">
                                        <a href="report_view.php?assessment_id=<?= $record['id'] ?>" class="btn btn-sm btn-outline-danger">View Full Report</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-3 text-muted text-center">No past assessments found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-secondary">
            <div class="card-header bg-secondary text-white fw-bold"><i class="bi bi-activity"></i> Trainer Programming</div>
            <div class="card-body p-0">
                <?php if (count($workout_plans) > 0): ?>
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date Assigned</th>
                                <th>Week</th>
                                <th>Trainer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workout_plans as $plan): ?>
                                <tr>
                                    <td class="align-middle"><?= date('M j, Y', strtotime($plan['created_at'])) ?></td>
                                    <td class="align-middle">Week <?= htmlspecialchars($plan['week_number']) ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($plan['trainer_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-3 text-muted text-center small">No trainer workouts assigned yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-dark fw-bold">
                <i class="bi bi-graph-up-arrow"></i> Patient Weight Trend (kg)
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="doctorWeightChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const patientId = <?= json_encode($patient_id) ?>;
    
    fetch(`../api/analytics.php?type=progress&user_id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.dates.length > 0) {
                const ctx = document.getElementById('doctorWeightChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.dates,
                        datasets: [{
                            label: 'Patient Weight (kg)',
                            data: data.weights,
                            borderColor: '#0dcaf0',
                            backgroundColor: 'rgba(13, 202, 240, 0.2)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: { responsive: true }
                });
            } else {
                document.getElementById('doctorWeightChart').parentElement.innerHTML = '<p class="text-muted text-center">Patient has not logged any weight data yet.</p>';
            }
        })
        .catch(err => console.error("Error loading chart:", err));
});
</script>

<?php require_once '../includes/footer.php'; ?>