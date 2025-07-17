<?php
/**
 * Database Configuration File
 * Library Management System
 * 
 * This file contains database connection settings and connection functions
 * for the Library Management System.
 * 
 * @author Final Year Student
 * @version 1.0
 */

// Prevent direct access
if (!defined('LIBRARY_SYSTEM')) {
    die('Direct access not permitted');
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'library_db');
define('DB_CHARSET', 'utf8mb4');

// Global database connection variable
$conn = null;

/**
 * Establish database connection using MySQLi
 * 
 * @return mysqli|false Database connection object or false on failure
 */
function getDatabaseConnection() {
    global $conn;
    
    // Return existing connection if available
    if ($conn && $conn->ping()) {
        return $conn;
    }
    
    try {
        // Create new MySQLi connection
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset
        if (!$conn->set_charset(DB_CHARSET)) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        // Set timezone
        $conn->query("SET time_zone = '+08:00'");
        
        return $conn;
        
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

/**
 * Close database connection
 * 
 * @return bool True on success, false on failure
 */
function closeDatabaseConnection() {
    global $conn;
    
    if ($conn) {
        $result = $conn->close();
        $conn = null;
        return $result;
    }
    
    return true;
}

/**
 * Execute a prepared statement with parameters
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
 * @param mixed ...$params Parameters to bind
 * @return mysqli_result|bool Result object or false on failure
 */
function executeQuery($query, $types = "", ...$params) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Prepare statement
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters if provided
        if ($types && !empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind parameters failed: " . $stmt->error);
            }
        }
        
        // Execute statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Get result
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a non-select query (INSERT, UPDATE, DELETE)
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param mixed ...$params Parameters to bind
 * @return bool|int True on success, false on failure, or insert_id for INSERT
 */
function executeNonQuery($query, $types = "", ...$params) {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Prepare statement
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters if provided
        if ($types && !empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                throw new Exception("Bind parameters failed: " . $stmt->error);
            }
        }
        
        // Execute statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Get affected rows or insert id
        $affected_rows = $stmt->affected_rows;
        $insert_id = $conn->insert_id;
        
        $stmt->close();
        
        // Return insert_id for INSERT queries, otherwise return success status
        if ($insert_id > 0) {
            return $insert_id;
        }
        
        return $affected_rows > 0;
        
    } catch (Exception $e) {
        error_log("Non-query execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get single row from database
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param mixed ...$params Parameters to bind
 * @return array|null Single row as associative array or null
 */
function getSingleRow($query, $types = "", ...$params) {
    $result = executeQuery($query, $types, ...$params);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Get all rows from database
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param mixed ...$params Parameters to bind
 * @return array Array of associative arrays
 */
function getAllRows($query, $types = "", ...$params) {
    $result = executeQuery($query, $types, ...$params);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Get total count of rows
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param mixed ...$params Parameters to bind
 * @return int Total count
 */
function getRowCount($query, $types = "", ...$params) {
    $result = executeQuery($query, $types, ...$params);
    
    if ($result) {
        return $result->num_rows;
    }
    
    return 0;
}

/**
 * Escape string for database queries
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string) {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        return $conn->real_escape_string($string);
    }
    
    return addslashes($string);
}

/**
 * Begin database transaction
 * 
 * @return bool True on success, false on failure
 */
function beginTransaction() {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        return $conn->begin_transaction();
    }
    
    return false;
}

/**
 * Commit database transaction
 * 
 * @return bool True on success, false on failure
 */
function commitTransaction() {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        return $conn->commit();
    }
    
    return false;
}

/**
 * Rollback database transaction
 * 
 * @return bool True on success, false on failure
 */
function rollbackTransaction() {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        return $conn->rollback();
    }
    
    return false;
}

/**
 * Check if table exists
 * 
 * @param string $table_name Table name to check
 * @return bool True if table exists, false otherwise
 */
function tableExists($table_name) {
    $query = "SHOW TABLES LIKE ?";
    $result = executeQuery($query, "s", $table_name);
    
    return $result && $result->num_rows > 0;
}

/**
 * Test database connection
 * 
 * @return bool True if connection successful, false otherwise
 */
function testConnection() {
    $conn = getDatabaseConnection();
    
    if ($conn) {
        return $conn->ping();
    }
    
    return false;
}

// Initialize database connection on file include
try {
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    
    // Display user-friendly error message
    if (!defined('SUPPRESS_DB_ERROR')) {
        die("Database connection failed. Please check your configuration.");
    }
}
?>