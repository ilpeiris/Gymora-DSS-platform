<?php
// /Gymora/doctor/schedule.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_DOCTOR);
$staff_id = $_SESSION['user_id'];

// Handle Adding Hours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hours'])) {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    
    // Simple check to prevent overlapping exact same day
    $check = $pdo->prepare("SELECT id FROM staff_availability WHERE staff_id = ? AND day_of_week = ?");
    $check->execute([$staff_id, $day]);
    
    if ($check->rowCount() > 0) {
        $error = "You already have hours set for $day. Please delete them first to update.";
    } else {
        $insert = $pdo->prepare("INSERT INTO staff_availability (staff_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        $insert->execute([$staff_id, $day, $start, $end]);
        $success = "Working hours added successfully!";
    }
}

// Handle Deleting Hours
if (isset($_GET['delete'])) {
    $del = $pdo->prepare("DELETE FROM staff_availability WHERE id = ? AND staff_id = ?");
    $del->execute([$_GET['delete'], $staff_id]);
    header("Location: schedule.php");
    exit();
}

// Fetch current hours
$stmt = $pdo->prepare("
    SELECT * FROM staff_availability 
    WHERE staff_id = ? 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
");
$stmt->execute([$staff_id]);
$schedule = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-clock-history"></i> My Schedule</h2>
        <p class="text-muted">Set the days and times you are available for patient consultations.</p>
        <hr>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white fw-bold">Add Working Hours</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_hours" value="1">
                    <div class="mb-3">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold">Save Hours</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Current Availability</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <td class="align-middle fw-bold"><?= $s['day_of_week'] ?></td>
                                <td class="align-middle"><?= date('g:i A', strtotime($s['start_time'])) ?></td>
                                <td class="align-middle"><?= date('g:i A', strtotime($s['end_time'])) ?></td>
                                <td class="align-middle">
                                    <a href="schedule.php?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove these hours?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($schedule) == 0): ?>
                            <tr><td colspan="4" class="text-center py-4">No hours set. Users cannot book you.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>