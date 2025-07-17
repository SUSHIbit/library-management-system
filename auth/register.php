<?php
/**
 * User Registration Page
 * Library Management System
 * 
 * This page handles new user registration for students and staff.
 * Admin and librarian accounts are created by administrators only.
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

// Check if registration is enabled
if (!getSystemSetting('registration_enabled', true)) {
    showError('Registration is currently disabled. Please contact the administrator.');
    redirect(BASE_URL . '/auth/login.php');
}

// Initialize variables
$error_message = '';
$success_message = '';
$form_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $form_data = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'role' => sanitizeInput($_POST['role'] ?? 'student')
    ];
    
    // Validation
    $errors = [];
    
    // Username validation
    if (empty($form_data['username'])) {
        $errors[] = 'Username is required.';
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    // Email validation
    if (empty($form_data['email'])) {
        $errors[] = 'Email is required.';
    } elseif (!isValidEmail($form_data['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Password validation
    if (empty($form_data['password'])) {
        $errors[] = 'Password is required.';
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    // Confirm password validation
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Full name validation
    if (empty($form_data['full_name'])) {
        $errors[] = 'Full name is required.';
    }
    
    // Role validation (only student and staff allowed for self-registration)
    if (!in_array($form_data['role'], ['student', 'staff'])) {
        $form_data['role'] = 'student';
    }
    
    // Check for existing username
    if (empty($errors)) {
        $existing_user = getSingleRow(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            "ss",
            $form_data['username'],
            $form_data['email']
        );
        
        if ($existing_user) {
            $errors[] = 'Username or email already exists.';
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $hashed_password = hashPassword($form_data['password']);
        
        $insert_query = "INSERT INTO users (username, email, password, role, full_name, phone, address, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
        
        $user_id = executeNonQuery(
            $insert_query,
            "sssssss",
            $form_data['username'],
            $form_data['email'],
            $hashed_password,
            $form_data['role'],
            $form_data['full_name'],
            $form_data['phone'],
            $form_data['address']
        );
        
        if ($user_id) {
            // Log registration activity
            logActivity('user_registered', "New user registered: {$form_data['username']}", $user_id);
            
            // Show success message
            showSuccess('Registration successful! You can now log in with your credentials.');
            redirect(BASE_URL . '/auth/login.php');
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
    
    // Set error message if any errors
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
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
    <title>Register - <?php echo APP_NAME; ?></title>
    
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
            padding: 20px 0;
        }
        
        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            margin: 20px;
        }
        
        .register-header {
            background: #3f3f46;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .register-header p {
            color: #e4e4e7;
            font-size: 14px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            flex: 1;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #3f3f46;
            font-size: 14px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fafafa;
            font-family: inherit;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #52525b;
            background: white;
            box-shadow: 0 0 0 3px rgba(63, 63, 70, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .required {
            color: #dc2626;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            background: #09090b;
            transform: translateY(-1px);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
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
        
        .register-footer {
            background: #fafafa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e4e4e7;
        }
        
        .register-footer a {
            color: #52525b;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }
        
        .register-footer a:hover {
            color: #3f3f46;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc2626; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: #16a34a; }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .register-container {
                margin: 10px;
            }
            
            .register-header, .register-body, .register-footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Register Header -->
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Join our library management system</p>
        </div>
        
        <!-- Register Body -->
        <div class="register-body">
            <!-- Display Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" action="" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">Username <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                            required
                            autocomplete="username"
                            placeholder="Choose a username"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="role" class="form-label">Account Type <span class="required">*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="student" <?php echo (($form_data['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="staff" <?php echo (($form_data['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                        placeholder="Enter your email address"
                    >
                </div>
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>"
                        required
                        autocomplete="name"
                        placeholder="Enter your full name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                    >
                </div>
                
                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea 
                        id="address" 
                        name="address" 
                        class="form-textarea" 
                        autocomplete="address"
                        placeholder="Enter your address"
                    ><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            required
                            autocomplete="new-password"
                            placeholder="Create a password"
                        >
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            required
                            autocomplete="new-password"
                            placeholder="Confirm your password"
                        >
                        <div id="password-match" class="password-strength"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-register">
                    Create Account
                </button>
            </form>
        </div>
        
        <!-- Register Footer -->
        <div class="register-footer">
            <a href="login.php">Already have an account? Sign in here</a>
        </div>
    </div>
    
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            if (password.length === 0) {
                feedback = '';
            } else if (strength <= 2) {
                feedback = '<span class="strength-weak">Weak password</span>';
            } else if (strength <= 4) {
                feedback = '<span class="strength-medium">Medium password</span>';
            } else {
                feedback = '<span class="strength-strong">Strong password</span>';
            }
            
            return feedback;
        }
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            strengthDiv.innerHTML = checkPasswordStrength(password);
        });
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchDiv.innerHTML = '';
            } else if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="strength-strong">Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak">Passwords do not match</span>';
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const fullName = document.getElementById('full_name').value.trim();
            
            // Basic validation
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            if (fullName === '') {
                e.preventDefault();
                alert('Full name is required.');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Username validation
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!usernameRegex.test(username)) {
                e.preventDefault();
                alert('Username can only contain letters, numbers, and underscores.');
                return false;
            }
        });
        
        // Auto-focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html>