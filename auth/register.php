<?php
// /Gymora/auth/register.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

// If already logged in, send them away
if (isLoggedIn()) {
    header("Location: " . BASE_URL . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // Grabbing the role from the new dropdown
    
    // Validate the role against our allowed list to prevent tampering
    $allowed_roles = [ROLE_USER, ROLE_DOCTOR, ROLE_TRAINER, ROLE_ADMIN];
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "That email is already registered. Please login.";
        } else {
            // Hash the password securely
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                // Insert the new user with their chosen role
                $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $insertStmt->execute([$name, $email, $password_hash, $role]);
                
                $success = "Registration successful! You can now login as a " . ucfirst($role) . ".";
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-sm border-dark">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Register for Gymora</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br><br>
                        <a href="login.php" class="btn btn-success w-100">Go to Login</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label text-danger fw-bold">Account Role (Testing Purposes Only)</label>
                            <select name="role" class="form-select border-danger" required>
                                <option value="user" selected>Gym Member (User)</option>
                                <option value="doctor">Medical Professional</option>
                                <option value="trainer">Fitness Trainer</option>
                                <option value="admin">System Administrator</option>
                            </select>
                            <small class="text-muted">In a live environment, staff accounts would be assigned internally.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-dark w-100 fw-bold">Create Account</button>
                    </form>
                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="login.php">Login here</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>