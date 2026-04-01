<?php
// /Gymora/dss/audit_logger.php
require_once dirname(__DIR__) . '/config/db.php';

function logAudit($user_id, $action, $data_type, $record_id = null) {
    global $pdo;
    
    // Grab the user's IP and Browser info for GDPR evidence
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, data_type, record_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $data_type, $record_id, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        // In a real app, we might log this to a server file. 
        // For now, we fail silently so it doesn't break the user experience.
        error_log("Audit Log Error: " . $e->getMessage());
    }
}
?>