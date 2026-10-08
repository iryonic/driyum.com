<?php
// Load environment variables from .env if it exists
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($env_k, $env_v) = explode('=', $line, 2);
            $env_k = trim($env_k);
            $env_v = trim($env_v, " \t\n\r\0\x0B\"'");
            if (getenv($env_k) === false) {
                putenv("$env_k=$env_v");
                $_ENV[$env_k] = $env_v;
            }
        }
    }
}

// Database Configuration
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($host == 'localhost' || $host == '127.0.0.1') {
    // LOCAL
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_NAME', getenv('DB_NAME') ?: 'driyum.com');

    // Mail Configuration (SMTP)
    define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.hostinger.com');
    define('MAIL_USER', getenv('MAIL_USER') ?: 'contact@driyum.com');
    define('MAIL_PASS', getenv('MAIL_PASS') ?: 'Driyum@123');
    define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 465));
} else {
    // PRODUCTION
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'u167160735_newdry');
    define('DB_PASS', getenv('DB_PASS') ?: 'NewDry@123');
    define('DB_NAME', getenv('DB_NAME') ?: 'u167160735_newdry');
    
    // Security: Hide errors in production
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Mail Configuration (SMTP)
    define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.hostinger.com');
    define('MAIL_USER', getenv('MAIL_USER') ?: 'contact@driyum.com');
    define('MAIL_PASS', getenv('MAIL_PASS') ?: 'Driyum@123');
    define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 465));
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

// Authentication Secret Key for HMAC signatures
if (!defined('AUTH_SECRET_KEY')) {
    define('AUTH_SECRET_KEY', 'driyum_sec_7f9c2e48a1d560b384ef92c1074e5b');
}

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

        // Set Timezone for current session (to match PHP)
        mysqli_query($conn, "SET time_zone = '+05:30'");
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


