<?php
/**
 * Debug Login Test
 * Temporary file to test database connection and user authentication
 */

// Define library system constant
define('LIBRARY_SYSTEM', true);

// Include configuration
require_once 'config/config.php';

echo "<h2>Database Connection Test</h2>";

// Test database connection
if (testConnection()) {
    echo "✅ Database connection: SUCCESS<br>";
} else {
    echo "❌ Database connection: FAILED<br>";
    die("Cannot proceed without database connection");
}

echo "<h2>Users in Database:</h2>";

// List all users
$users = getAllRows("SELECT id, username, email, password, role, status FROM users");

if (empty($users)) {
    echo "❌ No users found in database<br>";
    echo "<br><strong>Please run the SQL file first to create sample users.</strong><br>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Password</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . substr($user['password'], 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Login Test</h2>";

// Test login for each demo user
$demo_users = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'librarian', 'password' => 'admin123'],
    ['username' => 'student001', 'password' => 'admin123'],
    ['username' => 'staff001', 'password' => 'admin123']
];

foreach ($demo_users as $demo) {
    echo "<h3>Testing: " . $demo['username'] . " / " . $demo['password'] . "</h3>";
    
    // Query user
    $user_query = "SELECT id, username, email, password, role, full_name, status 
                   FROM users 
                   WHERE (username = ? OR email = ?) AND status = 'active'";
    
    $user = getSingleRow($user_query, "ss", $demo['username'], $demo['username']);
    
    if ($user) {
        echo "✅ User found in database<br>";
        echo "- ID: " . $user['id'] . "<br>";
        echo "- Username: " . $user['username'] . "<br>";
        echo "- Role: " . $user['role'] . "<br>";
        echo "- Status: " . $user['status'] . "<br>";
        echo "- Stored Password: " . $user['password'] . "<br>";
        
        // Test password verification
        if ($user['password'] === $demo['password']) {
            echo "✅ Password matches (plain text)<br>";
        } elseif (password_verify($demo['password'], $user['password'])) {
            echo "✅ Password matches (hashed)<br>";
        } else {
            echo "❌ Password does not match<br>";
            echo "- Trying to verify: '" . $demo['password'] . "'<br>";
            echo "- Against stored: '" . $user['password'] . "'<br>";
        }
    } else {
        echo "❌ User not found in database<br>";
    }
    
    echo "<hr>";
}

echo "<h2>Quick Fix Instructions:</h2>";
echo "<ol>";
echo "<li>If no users are found, please import the SQL file: <code>database/library_db.sql</code></li>";
echo "<li>If users exist but passwords don't match, update them manually:</li>";
echo "</ol>";

echo "<h3>SQL Commands to Fix Passwords:</h3>";
echo "<pre>";
echo "UPDATE users SET password = 'admin123' WHERE username = 'admin';\n";
echo "UPDATE users SET password = 'admin123' WHERE username = 'librarian';\n";
echo "UPDATE users SET password = 'admin123' WHERE username = 'student001';\n";
echo "UPDATE users SET password = 'admin123' WHERE username = 'staff001';\n";
echo "</pre>";

echo "<p><a href='auth/login.php'>← Back to Login</a></p>";
?>