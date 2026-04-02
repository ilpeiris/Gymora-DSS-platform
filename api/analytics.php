<?php
// /Gymora/api/analytics.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../config/session.php';

if (!isLoggedIn()) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$type = $_GET['type'] ?? '';

// ==========================================
// 1. USER PROGRESS ANALYTICS
// ==========================================
if ($type === 'progress') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

    if ($_SESSION['role'] === 'user' && $user_id !== $_SESSION['user_id']) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized data access']);
        exit();
    }

    try {
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
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'dates' => $dates, 'weights' => $weights, 'bmis' => $bmis]);
        exit();
    } catch (PDOException $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
        exit();
    }
} 
// ==========================================
// 2. ADMIN BI DASHBOARD ANALYTICS
// ==========================================
elseif ($type === 'admin') {
    if ($_SESSION['role'] !== 'admin') {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
        exit();
    }

    try {
        // 1. Metric Cards
        $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $total_assessments = $pdo->query("SELECT COUNT(*) FROM medical_assessments WHERE status = 'submitted'")->fetchColumn();
        $blocked_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'blocked'")->fetchColumn();
        $revenue = $pdo->query("SELECT SUM(p.price_gbp) FROM users u JOIN packages p ON u.package_id = p.id WHERE u.role = 'user'")->fetchColumn();
        
        // 2. Chart Data: Injury Distribution
        $injuries = $pdo->query("SELECT condition_name, COUNT(*) as count FROM medical_conditions GROUP BY condition_name")->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Chart Data: Class Popularity
        $classes = $pdo->query("SELECT name, enrolled_count FROM classes ORDER BY enrolled_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Chart Data: Booking Status (Shows DSS Impact)
        $bookings = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'metrics' => [
                'users' => $total_users,
                'assessments' => $total_assessments,
                'blocked' => $blocked_bookings,
                'revenue' => number_format((float)$revenue, 2)
            ],
            'charts' => [
                'injuries' => $injuries,
                'classes' => $classes,
                'bookings' => $bookings
            ]
        ]);
        exit();
    } catch (PDOException $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        exit();
    }
} 

else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid analytics type']);
    exit();
}
?>