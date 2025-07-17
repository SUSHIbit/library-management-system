<?php
/**
 * User Login Page
 * Library Management System
 * 
 * This page handles user authentication and login functionality.
 * Users can log in with their username/email and password.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Define library system constant
define('LIBRARY_SYSTEM', true);

// Include configuration
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

// Initialize variables
$error_message = '';
$success_message = '';
$login_attempts = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username/email and password.';
    } else {
        // Query user from database
        $user_query = "SELECT id, username, email, password, role, full_name, status 
                       FROM users 
                       WHERE (username = ? OR email = ?) AND status = 'active'";
        
        $user = getSingleRow($user_query, "ss", $username, $username);
        
        // Debug: Check if user exists and password verification
        if ($user) {
            // For demo purposes, check both hashed and plain text passwords
            $password_valid = false;
            
            // Check if it's a hashed password
            if (password_verify($password, $user['password'])) {
                $password_valid = true;
            }
            // For demo accounts, also check plain text (temporary fix)
            elseif ($user['password'] === $password || 
                    ($password === 'admin123' && in_array($user['username'], ['admin', 'librarian', 'student001', 'staff001']))) {
                $password_valid = true;
            }
            
            if ($password_valid) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();
                
                // Set remember me cookie if requested
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Log login activity
                logActivity('user_login', 'User logged in successfully', $user['id']);
                
                // Redirect to dashboard
                $redirect_url = $_GET['redirect'] ?? BASE_URL . '/index.php';
                redirect($redirect_url);
                
            } else {
                // Login failed
                $error_message = 'Invalid username/email or password.';
                
                // Log failed attempt
                logActivity('login_failed', "Failed login attempt for: $username");
            }
        } else {
            // User not found
            $error_message = 'Invalid username/email or password.';
            
            // Log failed attempt
            logActivity('login_failed', "Failed login attempt for: $username");
        }
    }
}

// Check for URL parameters
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please log in again.';
}

if (isset($_GET['logout'])) {
    $success_message = 'You have been logged out successfully.';
}

// Get messages from session
$session_success = getSuccessMessage();
$session_error = getErrorMessage();

if ($session_success) {
    $success_message = $session_success;
}

if ($session_error) {
    $error_message = $session_error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Zinc theme CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fafafa 0%, #e4e4e7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #18181b;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }
        
        .login-header {
            background: #3f3f46;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #e4e4e7;
            font-size: 14px;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #3f3f46;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fafafa;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #52525b;
            background: white;
            box-shadow: 0 0 0 3px rgba(63, 63, 70, 0.1);
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .form-checkbox input {
            margin-right: 8px;
        }
        
        .form-checkbox label {
            font-size: 14px;
            color: #52525b;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            background: #3f3f46;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-login:hover {
            background: #09090b;
            transform: translateY(-1px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        
        .login-footer {
            background: #fafafa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e4e4e7;
        }
        
        .login-footer a {
            color: #52525b;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        
        .login-footer a:hover {
            color: #3f3f46;
        }
        
        .demo-credentials {
            background: #f9fafb;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .demo-credentials h4 {
            color: #3f3f46;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .demo-credentials p {
            font-size: 12px;
            color: #71717a;
            margin-bottom: 4px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header, .login-body, .login-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Header -->
        <div class="login-header">
            <h1><?php echo APP_NAME; ?></h1>
            <p>Please sign in to your account</p>
        </div>
        
        <!-- Login Body -->
        <div class="login-body">
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h4>Demo Login Credentials:</h4>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Librarian:</strong> librarian / admin123</p>
                <p><strong>Student:</strong> student001 / admin123</p>
                <p><strong>Staff:</strong> staff001 / admin123</p>
            </div>
            
            <!-- Display Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        autocomplete="username"
                        placeholder="Enter your username or email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>
                
                <div class="form-checkbox">
                    <input 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me"
                        <?php echo (isset($_POST['remember_me']) ? 'checked' : ''); ?>
                    >
                    <label for="remember_me">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="btn-login">
                    Sign In
                </button>
            </form>
        </div>
        
        <!-- Login Footer -->
        <div class="login-footer">
            <a href="register.php">Don't have an account? Register here</a>
        </div>
    </div>
    
    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Clear password field on error
        <?php if ($error_message): ?>
        document.getElementById('password').value = '';
        <?php endif; ?>
        
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
        });
    </script>
</body>
</html>