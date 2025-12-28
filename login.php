<?php
session_start();

// Database connection with error handling
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    $dbError = "Database configuration error. Please contact administrator.";
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'cashier':
            header('Location: cashier/dashboard.php');
            break;
        case 'tailor':
            header('Location: tailor/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        // Check if database connection is available
        if (!isset($pdo)) {
            $error = "System temporarily unavailable. Please try again later.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header('Location: admin/dashboard.php');
                            break;
                        case 'cashier':
                            header('Location: cashier/dashboard.php');
                            break;
                        case 'tailor':
                            header('Location: tailor/dashboard.php');
                            break;
                        default:
                            header('Location: index.php');
                    }
                    exit();
                } else {
                    $error = "Invalid username or password!";
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "System error. Please try again later.";
            }
        }
    } else {
        $error = "Please enter both username and password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TailorTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="Asset/logo_icon.png" alt="TailorTrack" style="width: 50px; height: 50px; object-fit: contain;" class="me-2">
                <span>TailorTrack</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="login-section">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your TailorTrack account</p>
                </div>
                
                <?php if (isset($dbError)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $dbError; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" class="login-form">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 login-btn">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>
                
                <div class="login-footer">
                    <div class="role-info">
                        <h6>Login Credentials (Demo):</h6>
                        <div class="role-badges">
                            <span class="badge bg-admin">Admin: admin / password</span>
                            <span class="badge bg-cashier">Cashier: cashier1 / password</span>
                            <span class="badge bg-tailor">Tailor: tailor1 / password</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="login-side">
                <div class="side-content">
                    <div class="side-icon">
                        <img src="Asset/logo_receipt.png" alt="TailorTrack" style="width: 200px; height: 200px; object-fit: contain;">
                    </div>
                    <h3>TailorTrack System</h3>
                    <p>Professional order tracking and management for custom clothing services</p>
                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Order Management</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Customer Tracking</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Measurement Records</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Progress Updates</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>
</body>
</html>