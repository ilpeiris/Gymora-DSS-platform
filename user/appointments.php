<?php
// /Gymora/user/appointments.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

$success = '';
$error = '';

// Handle Appointment Booking (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $staff_id = $_POST['staff_id'];
    $datetime = $_POST['datetime'];
    
    // 1. Check if user has consultations remaining
    $userStmt = $pdo->prepare("SELECT consultations_remaining FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    
    if ($user['consultations_remaining'] <= 0) {
        $error = "You do not have any consultation credits left. Please purchase a package.";
    } else {
        // 2. Check for double-booking conflicts (is the doctor already busy?)
        $conflictStmt = $pdo->prepare("SELECT id FROM appointments WHERE staff_id = ? AND datetime = ? AND status = 'scheduled'");
        $conflictStmt->execute([$staff_id, $datetime]);
        
        if ($conflictStmt->rowCount() > 0) {
            $error = "That doctor is already booked at that time. Please choose another slot.";
        } else {
            // 3. Book the appointment and deduct the credit
            try {
                $pdo->beginTransaction();
                
                // Insert appointment
                $insertStmt = $pdo->prepare("
                    INSERT INTO appointments (user_id, staff_id, type, datetime, status, consultation_slot_used) 
                    VALUES (?, ?, 'medical_consultation', ?, 'scheduled', 1)
                ");
                $insertStmt->execute([$_SESSION['user_id'], $staff_id, $datetime]);
                
                // Deduct 1 consultation
                $updateUser = $pdo->prepare("UPDATE users SET consultations_remaining = consultations_remaining - 1 WHERE id = ?");
                $updateUser->execute([$_SESSION['user_id']]);
                
                $pdo->commit();
                $success = "Appointment successfully booked!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to book appointment. Please try again.";
            }
        }
    }
}

// Fetch all Doctors for the dropdown
$docStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'doctor' AND is_active = 1");
$doctors = $docStmt->fetchAll();

// Fetch User's Upcoming Appointments
$apptStmt = $pdo->prepare("
    SELECT a.*, u.name as doctor_name 
    FROM appointments a 
    JOIN users u ON a.staff_id = u.id 
    WHERE a.user_id = ? AND a.datetime >= CURDATE()
    ORDER BY a.datetime ASC
");
$apptStmt->execute([$_SESSION['user_id']]);
$upcoming_appointments = $apptStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">My Appointments</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Book a Consultation</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="book_appointment" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Select a Doctor</label>
                        <select name="staff_id" class="form-select" required>
                            <option value="" disabled selected>Choose a medical professional...</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?= $doc['id'] ?>">Dr. <?= htmlspecialchars($doc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Date and Time</label>
                        <input type="datetime-local" name="datetime" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Confirm Booking</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Upcoming Schedule</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($upcoming_appointments) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($upcoming_appointments as $appt): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="mb-1 fw-bold">Consultation with Dr. <?= htmlspecialchars($appt['doctor_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= date('l, F j, Y', strtotime($appt['datetime'])) ?> at <?= date('g:i A', strtotime($appt['datetime'])) ?>
                                    </small>
                                </div>
                                <div>
                                    <?php if ($appt['status'] == 'scheduled'): ?>
                                        <span class="badge bg-primary rounded-pill px-3 py-2">Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3 py-2"><?= ucfirst($appt['status']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0">You have no upcoming appointments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>