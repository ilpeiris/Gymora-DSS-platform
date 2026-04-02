<?php
// /Gymora/user/workout_plan.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);
$user_id = $_SESSION['user_id'];

// 1. Fetch all workout plans assigned to this specific user
$stmt = $pdo->prepare("
    SELECT w.*, t.name as trainer_name 
    FROM workout_plans w 
    JOIN users t ON w.trainer_id = t.id 
    WHERE w.user_id = ? AND w.status != 'draft'
    ORDER BY w.week_number ASC
");
$stmt->execute([$user_id]);
$plans = $stmt->fetchAll();

// 2. Fetch all exercises for these plans
$plan_exercises = [];
if (count($plans) > 0) {
    $planIds = array_column($plans, 'id');
    $inQuery = implode(',', array_fill(0, count($planIds), '?'));
    
    $exStmt = $pdo->prepare("
        SELECT we.plan_id, we.day_of_week, we.sets, we.reps, we.notes, e.name as exercise_name
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

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-activity"></i> My Training Program</h2>
        <p class="text-muted">Follow your medically-approved workout routine below.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if (count($plans) > 0): ?>
            <div class="accordion shadow-sm" id="workoutAccordion">
                <?php foreach ($plans as $index => $plan): 
                    $pid = $plan['id'];
                ?>
                    <div class="accordion-item border-primary mb-3" style="border-radius: 8px; overflow: hidden;">
                        <h2 class="accordion-header" id="heading<?= $pid ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $pid ?>">
                                <strong>Week <?= htmlspecialchars($plan['week_number']) ?></strong> 
                                <span class="badge bg-primary ms-3">Assigned by Trainer <?= htmlspecialchars($plan['trainer_name']) ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?= $pid ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#workoutAccordion">
                            <div class="accordion-body bg-light">
                                
                                <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                    <span class="text-muted"><strong>Status:</strong> <?= ucfirst(htmlspecialchars($plan['status'])) ?></span>
                                    <span class="text-muted"><strong>Assigned on:</strong> <?= date('F j, Y', strtotime($plan['created_at'])) ?></span>
                                </div>
                                
                                <div class="p-3 bg-white border rounded mb-3">
                                    <strong>Trainer Notes:</strong>
                                    <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($plan['notes']) ?></p>
                                </div>

                                <?php if (isset($plan_exercises[$pid])): ?>
                                    <h6 class="fw-bold text-secondary mb-2">This Week's Routine</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover bg-white">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Exercise</th>
                                                    <th>Sets</th>
                                                    <th>Reps</th>
                                                    <th>Form Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($plan_exercises[$pid] as $ex): ?>
                                                    <tr>
                                                        <td class="align-middle fw-bold text-primary"><?= htmlspecialchars($ex['day_of_week']) ?></td>
                                                        <td class="align-middle fw-bold"><?= htmlspecialchars($ex['exercise_name']) ?></td>
                                                        <td class="align-middle"><?= htmlspecialchars($ex['sets']) ?></td>
                                                        <td class="align-middle"><?= htmlspecialchars($ex['reps']) ?></td>
                                                        <td class="align-middle text-muted small"><?= htmlspecialchars($ex['notes'] ?? '-') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning py-2 mb-0">No specific exercises were assigned to this week yet.</div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center py-5">
                    <h4 class="text-warning mb-3">No Workout Plan Assigned Yet</h4>
                    <p class="text-muted">You need to be cleared by a doctor before a trainer can design your custom workout plan.</p>
                    <a href="appointments.php" class="btn btn-outline-dark mt-2">Book an Appointment</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>