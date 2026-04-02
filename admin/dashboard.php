<?php
// /Gymora/admin/dashboard.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);
require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-speedometer2"></i> Business Intelligence Dashboard</h2>
        <p class="text-muted">Live analytics, revenue tracking, and system health.</p>
        <hr>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-gear-fill"></i> System Administration
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <a href="users.php" class="btn btn-outline-dark"><i class="bi bi-people"></i> Manage Users & Roles</a>
                    <a href="packages.php" class="btn btn-outline-dark"><i class="bi bi-box"></i> Manage Packages</a>
                    <a href="dss_rules.php" class="btn btn-outline-info text-dark"><i class="bi bi-diagram-3"></i> View DSS Logic</a>
                    <a href="audit_logs.php" class="btn btn-outline-danger"><i class="bi bi-shield-lock"></i> GDPR Audit Logs</a>
                    
                    <a href="reports.php" class="btn btn-primary ms-lg-auto fw-bold"><i class="bi bi-cloud-download"></i> Export CSV Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 text-center">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-primary h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Total Members</h6>
                <h2 class="fw-bold text-primary mb-0" id="metric-users">--</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-success h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Total Revenue</h6>
                <h2 class="fw-bold text-success mb-0">£<span id="metric-revenue">--</span></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-info h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">Medical Assessments</h6>
                <h2 class="fw-bold text-info mb-0" id="metric-assessments">--</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-danger h-100">
            <div class="card-body">
                <h6 class="text-muted text-uppercase mb-2">DSS Blocked Bookings</h6>
                <h2 class="fw-bold text-danger mb-0" id="metric-blocked">--</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-dark h-100">
            <div class="card-header bg-dark text-white fw-bold">
                Injury Demographics
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="injuryChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-dark h-100">
            <div class="card-header bg-dark text-white fw-bold">
                DSS Impact (Booking Status)
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="bookingChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-dark h-100">
            <div class="card-header bg-dark text-white fw-bold">
                Class Popularity
            </div>
            <div class="card-body">
                <canvas id="classChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('../api/analytics.php?type=admin')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Populate Metric Cards
                document.getElementById('metric-users').innerText = data.metrics.users;
                document.getElementById('metric-revenue').innerText = data.metrics.revenue;
                document.getElementById('metric-assessments').innerText = data.metrics.assessments;
                document.getElementById('metric-blocked').innerText = data.metrics.blocked;

                // Draw Injury Pie Chart
                const injLabels = data.charts.injuries.map(i => i.condition_name.replace('_', ' ').toUpperCase());
                const injData = data.charts.injuries.map(i => i.count);
                new Chart(document.getElementById('injuryChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: injLabels,
                        datasets: [{ data: injData, backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#6c757d'] }]
                    }
                });

                // Draw Booking Status Pie Chart
                const bookLabels = data.charts.bookings.map(b => b.status.toUpperCase());
                const bookData = data.charts.bookings.map(b => b.count);
                new Chart(document.getElementById('bookingChart').getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: bookLabels,
                        datasets: [{ data: bookData, backgroundColor: ['#198754', '#dc3545', '#ffc107'] }]
                    }
                });

                // Draw Class Popularity Bar Chart
                const classLabels = data.charts.classes.map(c => c.name);
                const classData = data.charts.classes.map(c => c.enrolled_count);
                new Chart(document.getElementById('classChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: classLabels,
                        datasets: [{ label: 'Enrollments', data: classData, backgroundColor: '#0d6efd' }]
                    },
                    options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            }
        })
        .catch(error => console.error("Error loading BI dashboard:", error));
});
</script>

<?php require_once '../includes/footer.php'; ?>