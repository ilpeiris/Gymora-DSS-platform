<?php
// /Gymora/user/appointments.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Appointment Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $staff_id = intval($_POST['staff_id']);
    $type = $_POST['type']; // 'medical_consultation' or 'training_session'
    $raw_datetime = $_POST['datetime'];
    
    // Check if datetime is in the past
    if (strtotime($raw_datetime) < time()) {
        $error = "You cannot book an appointment in the past.";
    } else {
        // Parse the requested date and time
        $requested_time = date('H:i:s', strtotime($raw_datetime));
        $requested_day = date('l', strtotime($raw_datetime)); // e.g. 'Monday'
        
        // 1. Check Consultation Credits (Only deduct for medical consultations)
        $userStmt = $pdo->prepare("SELECT consultations_remaining FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $user_data = $userStmt->fetch();
        
        if ($type === 'medical_consultation' && $user_data['consultations_remaining'] <= 0) {
            $error = "You have 0 medical consultations remaining. Please upgrade your package.";
        } else {
            // 2. Check Staff Availability (Are they working that day/time?)
            $availStmt = $pdo->prepare("
                SELECT id FROM staff_availability 
                WHERE staff_id = ? 
                AND day_of_week = ? 
                AND start_time <= ? 
                AND end_time >= DATE_ADD(?, INTERVAL 60 MINUTE)
            ");
            $availStmt->execute([$staff_id, $requested_day, $requested_time, $requested_time]);
            
            if ($availStmt->rowCount() === 0) {
                $error = "The selected staff member is not available on {$requested_day}s at that time. Please check their schedule.";
            } else {
                // 3. Check for Conflicts (Double-Booking Prevention)
                $conflictStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE staff_id = ? AND datetime = ? AND status = 'scheduled'
                ");
                $conflictStmt->execute([$staff_id, date('Y-m-d H:i:s', strtotime($raw_datetime))]);
                
                if ($conflictStmt->fetchColumn() > 0) {
                    $error = "That time slot is already booked. Please choose another time.";
                } else {
                    // All checks passed! Execute booking.
                    try {
                        $pdo->beginTransaction();
                        
                        $slot_used = ($type === 'medical_consultation') ? 1 : 0;
                        
                        $bookStmt = $pdo->prepare("INSERT INTO appointments (user_id, staff_id, type, datetime, status, consultation_slot_used) VALUES (?, ?, ?, ?, 'scheduled', ?)");
                        $bookStmt->execute([$user_id, $staff_id, $type, date('Y-m-d H:i:s', strtotime($raw_datetime)), $slot_used]);
                        
                        if ($slot_used) {
                            $updateUser = $pdo->prepare("UPDATE users SET consultations_remaining = consultations_remaining - 1 WHERE id = ?");
                            $updateUser->execute([$user_id]);
                        }
                        
                        $pdo->commit();
                        $success = "Appointment successfully booked!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Booking failed due to a system error.";
                    }
                }
            }
        }
    }
}

// Fetch User Data for Dashboard
$userStmt = $pdo->prepare("SELECT consultations_remaining FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$current_user = $userStmt->fetch();

// Fetch Available Staff for Dropdown
$staffStmt = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('doctor', 'trainer') AND is_active = 1 ORDER BY role, name");
$all_staff = $staffStmt->fetchAll();

// Fetch Upcoming & Past Appointments
$apptStmt = $pdo->prepare("
    SELECT a.*, u.name as staff_name, u.role as staff_role 
    FROM appointments a 
    JOIN users u ON a.staff_id = u.id 
    WHERE a.user_id = ? 
    ORDER BY a.datetime ASC
");
$apptStmt->execute([$user_id]);
$appointments = $apptStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-calendar-check"></i> Manage Appointments</h2>
        <p class="text-muted">Book and manage your 1-on-1 sessions.</p>
        <hr>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                Book New Session
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2">
                    <strong>Medical Consultations Left:</strong> <span class="badge bg-dark fs-6"><?= $current_user['consultations_remaining'] ?></span>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="book_appointment" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Staff Member</label>
                       <select name="staff_id" id="staffSelect" class="form-select" onchange="fetchSchedule()" required>
                            <option value="">-- Choose Doctor or Trainer --</option>
                            <?php foreach ($all_staff as $staff): ?>
                                <option value="<?= $staff['id'] ?>"><?= ucfirst($staff['role']) ?>: <?= htmlspecialchars($staff['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="scheduleDisplay"></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Session Type</label>
                        <select name="type" class="form-select" required>
                            <option value="medical_consultation">Medical Consultation (Uses 1 Credit)</option>
                            <option value="training_session">Personal Training Session</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Date & Time</label>
                        <input type="datetime-local" name="datetime" class="form-control" required>
                        <small class="text-muted d-block mt-1">Our test Doctor works Mon-Wed (9am-5pm). Our test Trainer works Wed-Fri (12pm-8pm).</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Check Availability & Book</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white fw-bold">
                My Schedule
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Staff</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): 
                                $is_past = (strtotime($appt['datetime']) < time());
                                $badge_color = 'bg-primary';
                                if ($appt['status'] == 'completed') $badge_color = 'bg-success';
                                if ($appt['status'] == 'cancelled') $badge_color = 'bg-danger';
                                if ($is_past && $appt['status'] == 'scheduled') $badge_color = 'bg-secondary';
                            ?>
                                <tr class="<?= $is_past ? 'text-muted' : '' ?>">
                                    <td class="align-middle">
                                        <strong><?= date('D, M j, Y', strtotime($appt['datetime'])) ?></strong><br>
                                        <?= date('g:i A', strtotime($appt['datetime'])) ?>
                                    </td>
                                    <td class="align-middle">
                                        Dr/Tr. <?= htmlspecialchars($appt['staff_name']) ?>
                                    </td>
                                    <td class="align-middle">
                                        <?= ucwords(str_replace('_', ' ', $appt['type'])) ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge <?= $badge_color ?>"><?= ucfirst($appt['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($appointments) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">You have no appointments booked.</td>
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

<script>
function fetchSchedule() {
    const staffId = document.getElementById('staffSelect').value;
    const display = document.getElementById('scheduleDisplay');

    if (staffId === "") {
        display.innerHTML = "";
        return;
    }

    display.innerHTML = "<small class='text-muted'>Loading schedule...</small>";

    fetch(`../api/get_schedule.php?staff_id=${staffId}`)
        .then(response => response.text())
        .then(html => {
            display.innerHTML = html;
        });
}
</script>