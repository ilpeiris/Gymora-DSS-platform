<?php
// /Gymora/api/analytics.php
error_reporting(0);
require_once '../config/db.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

// Prevent users from viewing other users' data (unless they are a doctor/trainer)
if ($_SESSION['role'] === 'user' && $user_id !== $_SESSION['user_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized data access']);
    exit();
}

if ($type === 'progress') {
    try {
        // Fetch up to the last 30 progress logs
        $stmt = $pdo->prepare("SELECT log_date, weight_kg, bmi, body_fat_pct FROM progress_logs WHERE user_id = ? ORDER BY log_date ASC LIMIT 30");
        $stmt->execute([$user_id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dates = [];
        $weights = [];
        $bmis = [];
        
        foreach ($logs as $log) {
            $dates[] = date('M j', strtotime($log['log_date']));
            $weights[] = floatval($log['weight_kg']);
            $bmis[] = floatval($log['bmi']);
        }
        
        echo json_encode([
            'status' => 'success', 
            'dates' => $dates, 
            'weights' => $weights, 
            'bmis' => $bmis
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid analytics type']);
}
?>