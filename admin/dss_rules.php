<?php
// /Gymora/admin/dss_rules.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);

// Fetch DSS Rules joining with exercises to get readable names
try {
    $stmt = $pdo->query("
        SELECT r.id, r.condition_name, r.rule_type, r.reason, r.severity_threshold, 
               e.name as restricted_exercise, alt_e.name as alternative_exercise
        FROM dss_rules r
        LEFT JOIN exercises e ON r.exercise_id = e.id
        LEFT JOIN exercises alt_e ON r.alternative_exercise_id = alt_e.id
        ORDER BY r.condition_name ASC
    ");
    $rules = $stmt->fetchAll();
} catch (PDOException $e) {
    // If the tables aren't perfectly seeded yet, catch the error gracefully
    $rules = [];
    $db_error = "Database tables for DSS rules might not be fully set up yet. Don't worry, we will seed these in Iteration 2!";
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">DSS Logic Rules</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-info">DSS Rules Engine (Read-Only)</h2>
        <p class="text-muted">These are the core logic rules that automatically filter exercises based on doctor assessments.</p>
        <hr>
    </div>
</div>

<?php if (isset($db_error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($db_error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-dark">
                <h5 class="mb-0">Active Contraindication Rules</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Medical Condition</th>
                                <th>Severity Trigger</th>
                                <th>Restricted Exercise</th>
                                <th>Action Type</th>
                                <th>Reasoning / Alternative</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rules) > 0): ?>
                                <?php foreach ($rules as $rule): ?>
                                    <tr>
                                        <td class="align-middle fw-bold"><?= ucwords(str_replace('_', ' ', htmlspecialchars($rule['condition_name']))) ?></td>
                                        <td class="align-middle">&ge; <?= htmlspecialchars($rule['severity_threshold']) ?>/5</td>
                                        <td class="align-middle"><?= htmlspecialchars($rule['restricted_exercise'] ?? 'Unknown Exercise') ?></td>
                                        <td class="align-middle">
                                            <?php if ($rule['rule_type'] === 'BLOCK'): ?>
                                                <span class="badge bg-danger">BLOCK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">WARN</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <small><?= htmlspecialchars($rule['reason']) ?></small>
                                            <?php if ($rule['alternative_exercise']): ?>
                                                <br><span class="badge bg-success mt-1">Suggest: <?= htmlspecialchars($rule['alternative_exercise']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <h5 class="text-muted">No DSS rules found.</h5>
                                        <p class="text-muted mb-0">The rule engine will be populated with data in Iteration 2.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>