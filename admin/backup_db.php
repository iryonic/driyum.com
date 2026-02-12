<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Strict Admin Check - NO UI OUTPUT HERE
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Check if it's a backup request
if (isset($_GET['action']) && $_GET['action'] === 'generate') {
    $conn = get_db_connection();
    $db_name = DB_NAME;
    $date = date('Y-m-d_H-i-s');
    $filename = "driyum_backup_{$date}.sql";

    // Update Last Backup Time in settings
    $pretty_date = date('d M Y, h:i A');
    update_setting('last_backup_at', $pretty_date);

    // Clear any previous output buffers
    if (ob_get_level()) ob_end_clean();

    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    echo "-- Driyum Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Host: " . DB_HOST . "\n";
    echo "-- Database: " . DB_NAME . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET AUTOCOMMIT = 0;\n";
    echo "START TRANSACTION;\n\n";

    foreach ($tables as $table) {
        // Table Structure
        $res = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($res);
        echo "\n\n-- Table structure for table `$table` --\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $row[1] . ";\n\n";

        // Table Data
        $res = mysqli_query($conn, "SELECT * FROM `$table`");
        while ($row = mysqli_fetch_assoc($res)) {
            $keys = array_keys($row);
            $vals = array_values($row);
            
            $escaped_vals = array_map(function($v) use ($conn) {
                if ($v === null) return "NULL";
                return "'" . mysqli_real_escape_string($conn, $v) . "'";
            }, $vals);

            echo "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped_vals) . ");\n";
        }
        flush(); // Flush buffer periodically
    }

    echo "\n\nSET FOREIGN_KEY_CHECKS=1;\n";
    echo "COMMIT;\n";
    exit;
}

// Redirect if accessed improperly
header("Location: settings.php");
exit;
