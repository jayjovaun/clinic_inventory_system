<?php
// Start session with strict mode
ini_set('session.use_strict_mode', 1);
session_start();

// Include necessary files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/helpers.php';
require_once 'models/User.php';

// Check if user is already logged in
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Get database connection
$db = Database::getInstance();
$pdo = $db->getConnection();

// Initialize variables
$error = null;
$attempted_login = false;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempted_login = true;
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        require_once 'auth.php';
        
        if (loginUser($username, $password)) {
            $_SESSION['isLoggedIn'] = true;
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Clinic Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bcrypt.js/2.4.0/bcrypt.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .system-title {
            font-size: 1.5rem;
            color: #343a40;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
        }
        
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        
        .input-group-text {
            background-color: transparent;
            border-color: #e2e8f0;
            color: #6c757d;
        }
        
        .alert {
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="images/logo_new.png" alt="Health Logo" class="logo">
            <h1 class="system-title">Clinic Inventory</h1>
            <p class="text-muted">Please login to continue</p>
        </div>
        
        <form id="loginForm" method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                </div>
            </div>
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <button type="button" class="input-group-text" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <script>
                document.getElementById('togglePassword').addEventListener('click', function() {
                    const password = document.getElementById('password');
                    const icon = this.querySelector('i');
                    
                    if (password.type === 'password') {
                        password.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        password.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            </script>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
        <a href="signup.php" class="btn btn-outline-secondary w-100">
            <i class="fas fa-user-plus me-2"></i>Sign Up
        </a>
        
        <?php if ($attempted_login && $error): ?>
        <div class="alert alert-danger mt-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 