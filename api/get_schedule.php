<?php
// /Gymora/api/get_schedule.php
error_reporting(0);
require_once '../config/db.php';
require_once '../config/session.php';

if (!isset($_GET['staff_id'])) die();

$staff_id = intval($_GET['staff_id']);

$stmt = $pdo->prepare("
    SELECT day_of_week, start_time, end_time 
    FROM staff_availability 
    WHERE staff_id = ? 
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
");
$stmt->execute([$staff_id]);
$schedule = $stmt->fetchAll();

if (count($schedule) == 0) {
    echo "<div class='alert alert-warning py-2 mb-3'>This staff member has not set their working hours yet.</div>";
} else {
    echo "<div class='alert alert-secondary py-2 mb-3'><strong>Working Hours:</strong><ul class='mb-0 pl-3'>";
    foreach ($schedule as $s) {
        $start = date('g:i A', strtotime($s['start_time']));
        $end = date('g:i A', strtotime($s['end_time']));
        echo "<li>{$s['day_of_week']}: $start - $end</li>";
    }
    echo "</ul></div>";
}
?>