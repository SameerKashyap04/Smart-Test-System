<?php
// ====================================================
// HOSTINGER CONFIGURATION
// ====================================================
// 1. Create a database in Hostinger (Databases -> Management)
// 2. Copy the Database Name, Username, and Password
// 3. Update the variables below:

$is_production = false; // SET THIS TO TRUE ON HOSTINGER

if ($is_production) {
    // HOSTINGER Credentials
    $username = 'u123456789_username'; // Replace with Hostinger Username
    $password = 'YourPassword';        // Replace with Hostinger Password
    $database = 'u123456789_database'; // Replace with Hostinger Database Name
    $host = 'localhost';               // Usually localhost for Hostinger
    $port = 3306;
    
    $conn = mysqli_connect($host, $username, $password, $database, $port);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
} else {
    // LOCALHOST / XAMPP SETUP
    $username = 'root';
    $password = '';
    $database = 'smart_test_system';
    
    // Auto-detect host and port for XAMPP/MAMP
    $hosts = ['127.0.0.1', 'localhost'];
    $ports = [3306, 3307, 8889]; 
    
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }
    
    $conn = null;
    $errors = [];
    
    foreach ($hosts as $test_host) {
        foreach ($ports as $test_port) {
            $conn = @mysqli_connect($test_host, $username, $password, $database, $test_port);
            if ($conn) {
                break 2;
            }
            // Try connection without DB to create it if needed
            $tmpConn = @mysqli_connect($test_host, $username, $password, '', $test_port);
            if ($tmpConn) {
                @mysqli_query($tmpConn, "CREATE DATABASE IF NOT EXISTS `" . $database . "`");
                @mysqli_select_db($tmpConn, $database);
                $conn = $tmpConn;
                break 2;
            }
        }
    }
    
    if (!$conn) {
        die("Local connection failed. Please ensure XAMPP/MAMP is running.");
    }
}
?>
