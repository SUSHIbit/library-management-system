<?php
/**
 * User Logout Handler
 * Library Management System
 * 
 * This script handles user logout functionality, clears session data,
 * and redirects to the login page.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Define library system constant
define('LIBRARY_SYSTEM', true);

// Include configuration
require_once '../config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Get user info before destroying session
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Log logout activity
    logActivity('user_logout', 'User logged out successfully', $user_id);
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
        
        // Remove token from database (if implemented)
        // $delete_token_query = "DELETE FROM remember_tokens WHERE user_id = ?";
        // executeNonQuery($delete_token_query, "i", $user_id);
    }
    
    // Destroy session
    session_destroy();
    
    // Start new session for logout message
    session_start();
    showSuccess('You have been logged out successfully.');
}

// Redirect to login page
redirect(BASE_URL . '/auth/login.php?logout=1');
?>