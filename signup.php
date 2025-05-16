<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';
require_once 'models/User.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = null;
$success = null;

// Handle signup form submission
if (isPost()) {
    $username = getPostData('username');
    $password = getPostData('password');
    $email = getPostData('email');
    
    if ($username && $password && $email) {
        $userModel = new User();
        
        // Check if username already exists
        if ($userModel->getByUsername($username)) {
            $error = 'Username already exists';
        }
        // Check if email already exists
        elseif ($userModel->getByEmail($email)) {
            $error = 'Email already exists';
        }
        // Validate password strength
        elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/', $password)) {
            $error = 'Password must be at least 6 characters with 1 uppercase letter, 1 number, and 1 special character';
        }
        // Create new user
        else {
            $data = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'role' => 'Staff' // Default role
            ];
            
            if ($userModel->create($data)) {
                $success = 'Account created successfully! You can now login.';
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    } else {
        $error = 'Please fill in all fields';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .signup-container {
            width: 100%;
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 2.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .brand-logo {
            width: 120px;
            height: auto;
            margin: 0 auto 1.5rem;
            display: block;
        }
        
        .form-control {
            padding: 0.75rem;
            border-radius: 0.375rem;
        }
        
        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            padding: 0.75rem;
            font-weight: 500;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        
        .invalid-feedback {
            text-align: left;
            font-size: 0.875rem;
        }
        
        .alert {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <img src="images/bsu_logo.png" alt="BSU Logo" class="brand-logo">
        <h3 class="text-center mb-4">Create an Account</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="signup.php" method="POST" id="signupForm" novalidate>
            <div class="mb-3">
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username"
                       placeholder="Username"
                       required>
                <div class="invalid-feedback">Please enter a username</div>
            </div>
            
            <div class="mb-3">
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email"
                       placeholder="Email"
                       required>
                <div class="invalid-feedback">Please enter a valid email</div>
            </div>
            
            <div class="mb-3 position-relative">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password"
                       placeholder="Password"
                       required>
                <i class="password-toggle fas fa-eye" onclick="togglePassword('password')"></i>
                <div class="invalid-feedback">Please enter a password</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">Sign Up</button>
            </div>
            
            <p class="text-center mt-3 mb-0">
                Already have an account? 
                <a href="login.php" class="text-decoration-none">Login</a>
            </p>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.parentNode.querySelector('.password-toggle');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }

        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const form = e.target;
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // Clear validation errors when typing
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', () => {
                input.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html> 