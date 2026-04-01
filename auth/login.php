<?php
// /Gymora/auth/login.php
require_once '../config/db.php';
require_once '../includes/header.php';

// If already logged in, redirect them to their respective dashboard
if (isLoggedIn()) {
    header("Location: " . BASE_URL . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Fetch the user from the database
        $stmt = $pdo->prepare("SELECT id, name, password_hash, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Verify user exists AND password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Check if the admin deactivated this account
            if ($user['is_active'] == 0) {
                $error = "Your account has been deactivated. Please contact support.";
            } else {
                // Password is correct! Set up the session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect them to their specific role dashboard
                header("Location: " . BASE_URL . $user['role'] . "/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Login to Gymora</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                    <div class="alert alert-warning">You must log in to view that page.</div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Login</button>
                </form>
                <div class="mt-3 text-center">
                    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>