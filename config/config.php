<?php
/**
 * Application Configuration File
 * Library Management System
 * 
 * This file contains application-wide configuration settings,
 * constants, and initialization code.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone setting
date_default_timezone_set('Asia/Kuala_Lumpur');

// Application constants
define('APP_NAME', 'Library Management System');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Professional Library Management System for Final Year Project');
define('APP_AUTHOR', 'Final Year Student');

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');
define('IMAGES_PATH', ROOT_PATH . '/assets/images');

// URL constants
define('BASE_URL', 'http://localhost/library-management-system');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/assets/uploads');
define('IMAGES_URL', BASE_URL . '/assets/images');

// Security constants
define('SALT', 'library_mgmt_salt_2024');
define('HASH_ALGORITHM', 'sha256');
define('SESSION_TIMEOUT', 7200); // 2 hours in seconds
define('MAX_LOGIN_ATTEMPTS', 3);
define('ACCOUNT_LOCKOUT_TIME', 1800); // 30 minutes in seconds

// File upload constants
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Pagination constants
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGINATION_LINKS', 5);

// Library system constants
define('DEFAULT_BORROW_DAYS', 14);
define('MAX_BOOKS_PER_USER', 5);
define('FINE_PER_DAY', 2.00);
define('CURRENCY_SYMBOL', 'RM');
define('GRACE_PERIOD_DAYS', 1);

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_LIBRARIAN', 'librarian');
define('ROLE_STAFF', 'staff');
define('ROLE_STUDENT', 'student');

// User statuses
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_SUSPENDED', 'suspended');

// Book/Borrowing statuses
define('BOOK_STATUS_AVAILABLE', 'available');
define('BOOK_STATUS_BORROWED', 'borrowed');
define('BOOK_STATUS_RESERVED', 'reserved');

define('BORROW_STATUS_BORROWED', 'borrowed');
define('BORROW_STATUS_RETURNED', 'returned');
define('BORROW_STATUS_OVERDUE', 'overdue');

// Fine statuses
define('FINE_STATUS_UNPAID', 'unpaid');
define('FINE_STATUS_PARTIAL', 'partial');
define('FINE_STATUS_PAID', 'paid');

// System settings array
$system_settings = [
    'maintenance_mode' => false,
    'registration_enabled' => true,
    'email_notifications' => true,
    'auto_calculate_fines' => true,
    'backup_enabled' => true,
    'debug_mode' => true
];

// Navigation menu items
$nav_menu = [
    'dashboard' => [
        'title' => 'Dashboard',
        'url' => 'index.php',
        'icon' => 'home',
        'roles' => ['admin', 'librarian', 'staff', 'student']
    ],
    'books' => [
        'title' => 'Books',
        'url' => 'books/index.php',
        'icon' => 'book',
        'roles' => ['admin', 'librarian', 'staff', 'student']
    ],
    'categories' => [
        'title' => 'Categories',
        'url' => 'categories/index.php',
        'icon' => 'folder',
        'roles' => ['admin', 'librarian']
    ],
    'borrowing' => [
        'title' => 'Borrowing',
        'url' => 'borrowing/index.php',
        'icon' => 'refresh',
        'roles' => ['admin', 'librarian', 'staff', 'student']
    ],
    'users' => [
        'title' => 'Users',
        'url' => 'users/index.php',
        'icon' => 'users',
        'roles' => ['admin', 'librarian']
    ],
    'fines' => [
        'title' => 'Fines',
        'url' => 'fines/index.php',
        'icon' => 'dollar-sign',
        'roles' => ['admin', 'librarian', 'staff', 'student']
    ],
    'reports' => [
        'title' => 'Reports',
        'url' => 'reports/index.php',
        'icon' => 'bar-chart',
        'roles' => ['admin', 'librarian']
    ]
];

// User permissions
$user_permissions = [
    'admin' => [
        'books' => ['create', 'read', 'update', 'delete'],
        'categories' => ['create', 'read', 'update', 'delete'],
        'users' => ['create', 'read', 'update', 'delete'],
        'borrowing' => ['create', 'read', 'update', 'delete'],
        'fines' => ['create', 'read', 'update', 'delete'],
        'reports' => ['read'],
        'settings' => ['read', 'update']
    ],
    'librarian' => [
        'books' => ['create', 'read', 'update', 'delete'],
        'categories' => ['create', 'read', 'update', 'delete'],
        'users' => ['create', 'read', 'update'],
        'borrowing' => ['create', 'read', 'update'],
        'fines' => ['create', 'read', 'update'],
        'reports' => ['read']
    ],
    'staff' => [
        'books' => ['read'],
        'borrowing' => ['read'],
        'fines' => ['read']
    ],
    'student' => [
        'books' => ['read'],
        'borrowing' => ['read'],
        'fines' => ['read']
    ]
];

/**
 * Get system setting value
 * 
 * @param string $setting_name Setting name
 * @param mixed $default_value Default value if setting not found
 * @return mixed Setting value
 */
function getSystemSetting($setting_name, $default_value = null) {
    global $system_settings;
    
    if (isset($system_settings[$setting_name])) {
        return $system_settings[$setting_name];
    }
    
    // Try to get from database
    $query = "SELECT setting_value FROM settings WHERE setting_name = ?";
    $result = getSingleRow($query, "s", $setting_name);
    
    if ($result) {
        return $result['setting_value'];
    }
    
    return $default_value;
}

/**
 * Update system setting
 * 
 * @param string $setting_name Setting name
 * @param mixed $setting_value Setting value
 * @return bool True on success, false on failure
 */
function updateSystemSetting($setting_name, $setting_value) {
    global $system_settings;
    
    // Update in memory
    $system_settings[$setting_name] = $setting_value;
    
    // Update in database
    $query = "INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    
    return executeNonQuery($query, "ss", $setting_name, $setting_value);
}

/**
 * Check if user has permission for specific action
 * 
 * @param string $role User role
 * @param string $module Module name
 * @param string $action Action name (create, read, update, delete)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($role, $module, $action) {
    global $user_permissions;
    
    if (!isset($user_permissions[$role])) {
        return false;
    }
    
    if (!isset($user_permissions[$role][$module])) {
        return false;
    }
    
    return in_array($action, $user_permissions[$role][$module]);
}

/**
 * Check if current user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if current user has specific role
 * 
 * @param string|array $allowed_roles Single role or array of roles
 * @return bool True if user has required role, false otherwise
 */
function hasRole($allowed_roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    
    if (is_array($allowed_roles)) {
        return in_array($user_role, $allowed_roles);
    }
    
    return $user_role === $allowed_roles;
}

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Require specific role
 * Redirects to appropriate page if user doesn't have required role
 * 
 * @param string|array $required_roles Required roles
 */
function requireRole($required_roles) {
    requireLogin();
    
    if (!hasRole($required_roles)) {
        header('Location: ' . BASE_URL . '/index.php?error=access_denied');
        exit();
    }
}

/**
 * Get current user information
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    
    return date($format, strtotime($date));
}

/**
 * Format currency amount
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random password
 * 
 * @param int $length Password length
 * @return string Generated password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Hash password
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log system activity
 * 
 * @param string $action Action performed
 * @param string $details Action details
 * @param int $user_id User ID (optional)
 */
function logActivity($action, $details = '', $user_id = null) {
    if (!$user_id && isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log to file
    $log_file = ROOT_PATH . '/logs/activity.log';
    $log_message = json_encode($log_entry) . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Show success message
 * 
 * @param string $message Message to display
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Show error message
 * 
 * @param string $message Message to display
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Success message or null
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    
    return null;
}

/**
 * Get and clear error message
 * 
 * @return string|null Error message or null
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    
    return null;
}

/**
 * Redirect to URL
 * 
 * @param string $url URL to redirect to
 * @param bool $permanent Whether redirect is permanent
 */
function redirect($url, $permanent = false) {
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    
    header('Location: ' . $url);
    exit();
}

/**
 * Calculate fine amount for overdue book
 * 
 * @param string $due_date Due date
 * @param string $return_date Return date (optional, defaults to today)
 * @return float Fine amount
 */
function calculateFine($due_date, $return_date = null) {
    if (!$return_date) {
        $return_date = date('Y-m-d');
    }
    
    $due = strtotime($due_date);
    $returned = strtotime($return_date);
    
    // No fine if returned on time or early
    if ($returned <= $due) {
        return 0.00;
    }
    
    // Calculate overdue days
    $overdue_days = ceil(($returned - $due) / (24 * 60 * 60));
    
    // Apply grace period
    $overdue_days = max(0, $overdue_days - GRACE_PERIOD_DAYS);
    
    return $overdue_days * FINE_PER_DAY;
}

/**
 * Get file extension
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

// Include database configuration
require_once INCLUDES_PATH . '/../config/database.php';

// Load system settings from database
try {
    $db_settings = getAllRows("SELECT setting_name, setting_value FROM settings");
    
    foreach ($db_settings as $setting) {
        $system_settings[$setting['setting_name']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    error_log("Failed to load system settings: " . $e->getMessage());
}

// Check for maintenance mode
if (getSystemSetting('maintenance_mode', false) && !hasRole('admin')) {
    if (basename($_SERVER['PHP_SELF']) !== 'maintenance.php') {
        header('Location: ' . BASE_URL . '/maintenance.php');
        exit();
    }
}

// Session timeout check
if (isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_destroy();
        redirect(BASE_URL . '/auth/login.php?timeout=1');
    }
}

// Update last activity time
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}
?>