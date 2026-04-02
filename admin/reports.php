<?php
// /Gymora/admin/reports.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);

// --- CSV EXPORT LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'])) {
    $type = $_POST['export_type'];
    $filename = "Gymora_" . ucfirst($type) . "_Report_" . date('Y-m-d') . ".csv";
    
    // Tell the browser we are sending a CSV file to download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open the output stream
    $output = fopen('php://output', 'w');
    
    if ($type === 'users') {
        fputcsv($output, ['User ID', 'Name', 'Email', 'Role', 'Package Name', 'Registered Date', 'Status']);
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, p.name as package_name, u.created_at, u.is_active 
            FROM users u 
            LEFT JOIN packages p ON u.package_id = p.id 
            ORDER BY u.created_at DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_active'] = $row['is_active'] ? 'Active' : 'Deactivated';
            $row['package_name'] = $row['package_name'] ?? 'N/A';
            fputcsv($output, $row);
        }
    } 
    elseif ($type === 'audit_logs') {
        fputcsv($output, ['Log ID', 'User ID', 'Action', 'Data Type', 'Record ID', 'IP Address', 'Timestamp']);
        $stmt = $pdo->query("SELECT id, user_id, action, data_type, record_id, ip_address, timestamp FROM audit_logs ORDER BY timestamp DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    } 
    elseif ($type === 'revenue') {
        fputcsv($output, ['Month', 'New Subscriptions', 'Total Revenue (GBP)']);
        $stmt = $pdo->query("
            SELECT DATE_FORMAT(u.created_at, '%Y-%m') as month, COUNT(u.id) as subscriptions, SUM(p.price_gbp) as revenue 
            FROM users u 
            JOIN packages p ON u.package_id = p.id 
            WHERE u.role = 'user' 
            GROUP BY month 
            ORDER BY month DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['revenue'] = number_format((float)$row['revenue'], 2);
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit(); // Stop HTML from rendering below the CSV
}

// --- HTML UI START ---
require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Export Reports</li>
            </ol>
        </nav>
        <h2 class="fw-bold"><i class="bi bi-cloud-download"></i> Data Export Center</h2>
        <p class="text-muted">Generate and download CSV reports for external auditing and accounting.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-primary h-100">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="bi bi-people"></i> Complete User Roster
            </div>
            <div class="card-body">
                <p class="text-muted small">Exports all registered users across all roles, including their current active membership packages and registration dates.</p>
                <form method="POST">
                    <input type="hidden" name="export_type" value="users">
                    <button type="submit" class="btn btn-outline-primary w-100 fw-bold">Download Users CSV</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-danger h-100">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="bi bi-shield-lock"></i> GDPR Audit Logs
            </div>
            <div class="card-body">
                <p class="text-muted small">Exports the uneditable security ledger tracking every instance where a staff member accessed Special Category medical data.</p>
                <form method="POST">
                    <input type="hidden" name="export_type" value="audit_logs">
                    <button type="submit" class="btn btn-outline-danger w-100 fw-bold">Download Audit CSV</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-success h-100">
            <div class="card-header bg-success text-white fw-bold">
                <i class="bi bi-cash-coin"></i> Monthly Revenue Summary
            </div>
            <div class="card-body">
                <p class="text-muted small">Exports an aggregated financial report grouping total membership sales and revenue by calendar month.</p>
                <form method="POST">
                    <input type="hidden" name="export_type" value="revenue">
                    <button type="submit" class="btn btn-outline-success w-100 fw-bold">Download Revenue CSV</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>