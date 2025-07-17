<?php
// Simple test file to check PHP and database
echo "<h1>PHP & Database Test</h1>";

// Test PHP
echo "<h2>✅ PHP is working!</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
echo "<h2>Database Connection Test:</h2>";

// Database credentials
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_db';

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connection successful!<br>";
    
    // Test if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "✅ Users table exists!<br>";
        
        // Count users
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "✅ Found " . $row['count'] . " users in database<br>";
        
        // Show demo users
        $result = $conn->query("SELECT username, password FROM users WHERE username IN ('admin', 'librarian', 'student001', 'staff001')");
        
        if ($result->num_rows > 0) {
            echo "<h3>Demo Users:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Username</th><th>Password</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>" . $row['password'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No demo users found. Please run the SQL file.<br>";
        }
        
    } else {
        echo "❌ Users table does not exist. Please import the SQL file.<br>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Solutions:</strong><br>";
    echo "1. Make sure MySQL/XAMPP is running<br>";
    echo "2. Create database 'library_db'<br>";
    echo "3. Import the SQL file: database/library_db.sql<br>";
}

echo "<hr>";
echo "<h2>Quick Setup Instructions:</h2>";
echo "<ol>";
echo "<li>Start XAMPP/WAMP</li>";
echo "<li>Go to phpMyAdmin</li>";
echo "<li>Create database: <code>library_db</code></li>";
echo "<li>Import file: <code>database/library_db.sql</code></li>";
echo "<li>Try login again</li>";
echo "</ol>";

echo "<p><a href='auth/login.php'>Go to Login Page</a></p>";
?>