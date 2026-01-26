<?php
// Database Configuration
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($host == 'localhost' || $host == '127.0.0.1') {
    // LOCAL
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'driyum_db');
} else {
    // PRODUCTION
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u167160735_driyum');
    define('DB_PASS', 'DriyuM@1234');
    define('DB_NAME', 'u167160735_driyum');
}       

// Dynamic Base URL Configuration
// This handles local server subdirectories automatically
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$php_self = $_SERVER['PHP_SELF'] ?? '';
$path = !empty($script_name) ? $script_name : $php_self;
$base_dir = str_replace(basename($path), '', $path);

// If we are in api/ or admin/ subdirectory, strip it from the base_dir
$base_dir = preg_replace('/(api|admin)\/$/', '', $base_dir);

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_dir);
}

// Full Absolute URL (for SEO, sharing, and canonical tags)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = 'http';
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocol = 'https';
}
define('FULL_BASE_URL', $protocol . "://" . $host . BASE_URL);

// Create connection
function get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        // Suppress warnings for connection to allowing catching
        $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            // Throw exception instead of die() so API can return JSON error
            throw new Exception("Database Connection Error: " . mysqli_connect_error());
        }
        
        // Set charset to UTF-8
        mysqli_set_charset($conn, "utf8mb4");
    }
    
    return $conn;
}

// Close database connection
function close_db_connection() {
    $conn = get_db_connection();
    if ($conn) {
        mysqli_close($conn);
    }
}

// Execute query with error handling
function execute_query($sql, $params = []) {
    $conn = get_db_connection();
    
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$values);
            $success = mysqli_stmt_execute($stmt);
            
            if (!$success) {
                mysqli_stmt_close($stmt);
                return false;
            }

            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            
            if ($result === false) {
                // This was a non-SELECT query, so return success status
                return $success;
            }
            
            return $result;
        }
        
        return false;
    }
    
    return mysqli_query($conn, $sql);
}

// Fetch all rows as associative array
function fetch_all($sql, $params = []) {
    $result = execute_query($sql, $params);
    
    if ($result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    return [];
}

// Fetch single row as associative array
function fetch_one($sql, $params = []) {
    $result = execute_query($sql, $params);
    
    if ($result) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Get last insert ID
function get_last_insert_id() {
    $conn = get_db_connection();
    return mysqli_insert_id($conn);
}

// Escape string
function escape_string($string) {
    $conn = get_db_connection();
    return mysqli_real_escape_string($conn, $string);
}
