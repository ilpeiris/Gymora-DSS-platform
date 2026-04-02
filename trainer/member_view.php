<?php
// /Gymora/trainer/member_view.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_TRAINER);

$client_id = $_GET['user_id'] ?? null;
if (!$client_id) die("Invalid request. Missing client ID.");

// 1. Fetch Client Details
$clientStmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$clientStmt->execute([$client_id]);
$client = $clientStmt->fetch();

if (!$client) die("Client not found.");

// 2. Fetch Latest Medical Assessment & Conditions
$medStmt = $pdo->prepare("
    SELECT weight_kg, height_cm, bmi, notes_encrypted, created_at 
    FROM medical_assessments 
    WHERE user_id = ? AND status = 'submitted' 
    ORDER BY created_at DESC LIMIT 1
");
$medStmt->execute([$client_id]);
$medical = $medStmt->fetch();

$conditions = [];
if ($medical) {
    $condStmt = $pdo->prepare("
        SELECT c.condition_name, c.severity 
        FROM medical_conditions c
        JOIN medical_assessments a ON c.assessment_id = a.id
        WHERE a.user_id = ? AND a.status = 'submitted' AND c.is_active = 1
    ");
    $condStmt->execute([$client_id]);
    $conditions = $condStmt->fetchAll();
}

// 3. Fetch Workout Plan History (All Plans)
$planStmt = $pdo->prepare("SELECT id, week_number, status, notes, created_at FROM workout_plans WHERE user_id = ? ORDER BY week_number DESC, created_at DESC");
$planStmt->execute([$client_id]);
$all_plans = $planStmt->fetchAll();

$plan_exercises = [];
if (count($all_plans) > 0) {
    $planIds = array_column($all_plans, 'id');
    $inQuery = implode(',', array_fill(0, count($planIds), '?'));
    
    $exStmt = $pdo->prepare("
        SELECT we.plan_id, we.day_of_week, we.sets, we.reps, e.name as exercise_name
        FROM workout_exercises we
        JOIN exercises e ON we.exercise_id = e.id
        WHERE we.plan_id IN ($inQuery)
        ORDER BY FIELD(we.day_of_week, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun')
    ");
    $exStmt->execute($planIds);
    $fetched_exercises = $exStmt->fetchAll();
    
    foreach ($fetched_exercises as $ex) {
        $plan_exercises[$ex['plan_id']][] = $ex;
    }
}

// 4. Fetch Progress Logs (History)
$progStmt = $pdo->prepare("SELECT log_date, weight_kg, bmi, body_fat_pct, notes FROM progress_logs WHERE user_id = ? ORDER BY log_date DESC");
$progStmt->execute([$client_id]);
$progress_logs = $progStmt->fetchAll();

require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Client Profile</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold">Client: <?= htmlspecialchars($client['name']) ?></h2>
            <a href="create_plan.php?user_id=<?= $client_id ?>" class="btn btn-primary fw-bold">
                <i class="bi bi-plus-circle"></i> Create New Workout Plan
            </a>
        </div>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-dark fw-bold">
                <i class="bi bi-clipboard2-pulse"></i> Medical Clearance Profile
            </div>
            <div class="card-body">
                <?php if ($medical): ?>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <strong>Latest Weight:</strong> <span><?= htmlspecialchars($medical['weight_kg']) ?> kg</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <strong>Latest BMI:</strong> <span><?= htmlspecialchars($medical['bmi']) ?></span>
                        </li>
                        <li class="list-group-item px-0">
                            <strong>Doctor's Notes:</strong><br>
                            <small class="text-muted"><?= nl2br(htmlspecialchars($medical['notes_encrypted'] ?? 'None')) ?></small>
                        </li>
                    </ul>
                    
                    <h6 class="fw-bold mt-3 border-bottom pb-1">Active Diagnoses (DSS Triggers)</h6>
                    <?php if (count($conditions) > 0): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($conditions as $cond): ?>
                                <li class="mb-2">
                                    <span class="badge bg-danger">Sev: <?= $cond['severity'] ?></span> 
                                    <?= ucwords(str_replace('_', ' ', htmlspecialchars($cond['condition_name']))) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small">No active conditions reported.</p>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No medical assessment on file. Do not assign exercises.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-card-checklist"></i> Workout Plan History
            </div>
            <div class="card-body p-2">
                <?php if (count($all_plans) > 0): ?>
                    <div class="accordion" id="plansAccordion">
                        <?php foreach ($all_plans as $index => $plan): 
                            $pid = $plan['id'];
                            $is_latest = ($index === 0); 
                            $badge_color = $plan['status'] == 'active' ? 'bg-success' : ($plan['status'] == 'completed' ? 'bg-secondary' : 'bg-warning text-dark');
                        ?>
                            <div class="accordion-item border-secondary mb-2" style="border-radius: 6px; overflow: hidden;">
                                <h2 class="accordion-header" id="heading<?= $pid ?>">
                                    <button class="accordion-button <?= $is_latest ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $pid ?>">
                                        <strong>Week <?= htmlspecialchars($plan['week_number']) ?></strong> 
                                        <span class="badge <?= $badge_color ?> ms-3"><?= ucfirst(htmlspecialchars($plan['status'])) ?></span>
                                        <small class="ms-auto text-muted pe-3"><?= date('M j, Y', strtotime($plan['created_at'])) ?></small>
                                    </button>
                                </h2>
                                <div id="collapse<?= $pid ?>" class="accordion-collapse collapse <?= $is_latest ? 'show' : '' ?>" data-bs-parent="#plansAccordion">
                                    <div class="accordion-body bg-light">
                                        <div class="bg-white p-3 rounded border mb-3">
                                            <strong>Trainer Notes:</strong><br>
                                            <span style="white-space: pre-wrap;"><?= htmlspecialchars($plan['notes']) ?></span>
                                        </div>

                                        <?php if (isset($plan_exercises[$pid])): ?>
                                            <h6 class="fw-bold border-bottom pb-1">Assigned Routine</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered bg-white">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Day</th>
                                                            <th>Exercise</th>
                                                            <th>Sets</th>
                                                            <th>Reps</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($plan_exercises[$pid] as $ex): ?>
                                                            <tr>
                                                                <td class="align-middle"><strong><?= htmlspecialchars($ex['day_of_week']) ?></strong></td>
                                                                <td class="align-middle"><?= htmlspecialchars($ex['exercise_name']) ?></td>
                                                                <td class="align-middle"><?= htmlspecialchars($ex['sets']) ?></td>
                                                                <td class="align-middle"><?= htmlspecialchars($ex['reps']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted small mb-0">No specific exercises were added to this plan.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-3">
                        <p class="text-muted mb-0">No workout plans created for this client yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-success mb-4">
            <div class="card-header bg-success text-white fw-bold">
                <i class="bi bi-graph-up-arrow"></i> Client Weight Trend
            </div>
            <div class="card-body">
                <canvas id="trainerWeightChart" height="100"></canvas>
            </div>
        </div>

        <div class="card shadow-sm border-secondary">
            <div class="card-header bg-secondary text-white fw-bold">
                <i class="bi bi-journal-text"></i> Client Progress History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Body Fat %</th>
                                <th>User Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($progress_logs) > 0): ?>
                                <?php foreach ($progress_logs as $log): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($log['log_date'])) ?></td>
                                        <td><?= htmlspecialchars($log['weight_kg'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['bmi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['body_fat_pct'] ?? '-') ?>%</td>
                                        <td><small><?= htmlspecialchars($log['notes'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No progress logs recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const clientId = <?= json_encode($client_id) ?>;
    
    // Fetch data using the specific client_id
    fetch(`../api/analytics.php?type=progress&user_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.dates.length > 0) {
                const ctx = document.getElementById('trainerWeightChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.dates,
                        datasets: [{
                            label: 'Client Weight (kg)',
                            data: data.weights,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.2)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: { responsive: true }
                });
            } else {
                // If there's no data, replace the canvas with a friendly message
                document.getElementById('trainerWeightChart').parentElement.innerHTML = '<p class="text-muted text-center py-4 mb-0">Client has not logged any weight data yet.</p>';
            }
        })
        .catch(err => console.error("Error loading chart:", err));
});
</script>

<?php require_once '../includes/footer.php'; ?>