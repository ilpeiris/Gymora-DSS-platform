<?php
// /Gymora/trainer/classes.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_TRAINER);
$trainer_id = $_SESSION['user_id'];
$success = '';

// Handle creating a new class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    $name = trim($_POST['name']);
    $datetime = $_POST['datetime'];
    $duration = intval($_POST['duration_minutes']);
    $capacity = intval($_POST['capacity']);
    $impact = $_POST['impact_level'];
    $location = trim($_POST['location']);
    $desc = trim($_POST['description']);
    
    // Convert the selected contraindications into a JSON array for the DSS Engine
    $tags = isset($_POST['tags']) ? json_encode($_POST['tags']) : '[]';
    
    $stmt = $pdo->prepare("INSERT INTO classes (name, trainer_id, datetime, duration_minutes, capacity, impact_level, contraindication_tags, location, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $trainer_id, $datetime, $duration, $capacity, $impact, $tags, $location, $desc]);
    $success = "New class successfully added to the schedule!";
}

// Fetch all classes created by this trainer
$stmt = $pdo->prepare("SELECT * FROM classes WHERE trainer_id = ? ORDER BY datetime DESC");
$stmt->execute([$trainer_id]);
$my_classes = $stmt->fetchAll();

// Fetch unique medical conditions so the trainer can tag the class
$conds = $pdo->query("SELECT DISTINCT condition_name FROM dss_rules ORDER BY condition_name")->fetchAll(PDO::FETCH_COLUMN);

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-collection-play"></i> Manage My Classes</h2>
        <p class="text-muted">Create group sessions and set DSS safety tags.</p>
        <hr>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-bold">Add New Class</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="create_class" value="1">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Class Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Morning Yoga">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Date & Time</label>
                        <input type="datetime-local" name="datetime" class="form-control" required>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold">Duration (Min)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="60" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Capacity</label>
                            <input type="number" name="capacity" class="form-control" value="15" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Room / Location</label>
                        <input type="text" name="location" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Impact Level</label>
                        <select name="impact_level" class="form-select">
                            <option value="low">Low Impact</option>
                            <option value="medium">Medium Impact</option>
                            <option value="high">High Impact</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger"><i class="bi bi-shield-lock"></i> DSS Safety Tags</label>
                        <p class="small text-muted mb-1">Select conditions that should be BLOCKED from booking this class.</p>
                        <div class="border p-2 rounded bg-light" style="max-height: 120px; overflow-y: auto;">
                            <?php foreach ($conds as $c): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="<?= $c ?>">
                                    <label class="form-check-label"><?= ucwords(str_replace('_', ' ', $c)) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Publish Class</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white fw-bold">My Schedule</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Date & Time</th>
                            <th>Enrolled</th>
                            <th>DSS Danger Tags</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_classes as $c): 
                            $tags = json_decode($c['contraindication_tags'], true) ?? [];
                        ?>
                            <tr>
                                <td class="align-middle fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="align-middle"><?= date('M j, g:i A', strtotime($c['datetime'])) ?></td>
                                <td class="align-middle">
                                    <span class="badge <?= $c['enrolled_count'] >= $c['capacity'] ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $c['enrolled_count'] ?> / <?= $c['capacity'] ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <?php if(empty($tags)): ?>
                                        <span class="badge bg-secondary">None</span>
                                    <?php else: ?>
                                        <small class="text-danger fw-bold"><?= implode(', ', array_map(function($t){ return ucwords(str_replace('_',' ',$t)); }, $tags)) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>