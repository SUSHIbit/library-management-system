<?php
/**
 * Database Setup Helper
 * Library Management System
 * 
 * Run this file to set up the database automatically
 */

echo "<h1>Library Management System - Database Setup</h1>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'library_db';

try {
    // Connect to MySQL (without database first)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Connected to MySQL successfully!</h2>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$database' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE `$database`");
    
    // Read and execute SQL file
    $sqlFile = __DIR__ . '/database/library_db.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        echo "<h3>Executing SQL statements...</h3>";
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "<p>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
                } catch (PDOException $e) {
                    echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
        
        echo "<h2>✅ Database setup completed successfully!</h2>";
        
        // Test the tables
        echo "<h3>Testing database tables:</h3>";
        
        $tables = ['users', 'categories', 'books', 'borrowings', 'fines', 'settings'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $stmt->fetchColumn();
                echo "<p>✅ Table '$table': $count records</p>";
            } catch (PDOException $e) {
                echo "<p>❌ Table '$table': " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>Sample Users Created:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
        echo "<tr><td>admin</td><td>admin123</td><td>Admin</td></tr>";
        echo "<tr><td>librarian</td><td>admin123</td><td>Librarian</td></tr>";
        echo "<tr><td>student001</td><td>admin123</td><td>Student</td></tr>";
        echo "<tr><td>staff001</td><td>admin123</td><td>Staff</td></tr>";
        echo "</table>";
        
        echo "<h2>🎉 Setup Complete!</h2>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Delete this setup.php file for security</li>";
        echo "<li>Go to <a href='auth/login.php'>auth/login.php</a> to login</li>";
        echo "<li>Use any of the demo accounts above</li>";
        echo "</ol>";
        
    } else {
        echo "<h2>❌ Error: SQL file not found!</h2>";
        echo "<p>Please make sure 'database/library_db.sql' exists</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<h3>Solutions:</h3>";
    echo "<ul>";
    echo "<li>Make sure XAMPP/WAMP is running</li>";
    echo "<li>Check if MySQL service is started</li>";
    echo "<li>Verify database credentials in this file</li>";
    echo "</ul>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

table {
    width: 100%;
    margin: 10px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
}

th {
    background: #f4f4f5;
    font-weight: 600;
}

h1 {
    color: #18181b;
    border-bottom: 2px solid #3f3f46;
    padding-bottom: 10px;
}

h2 {
    color: #3f3f46;
}

.success {
    color: #16a34a;
}

.error {
    color: #dc2626;
}

.warning {
    color: #f59e0b;
}
</style>