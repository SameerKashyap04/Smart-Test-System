<?php
// ====================================================
// HOSTINGER CONFIGURATION
// ====================================================
// 1. Create a database in Hostinger (Databases -> Management)
// 2. Copy the Database Name, Username, and Password
// 3. Update the variables below:

// Auto-detect environment
$is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'devify.live') !== false);

if ($is_production) {
    // HOSTINGER Credentials
    $username = 'u414525231_testsytem_db'; // Hostinger Database Username
    $password = 'SmartTest@2025';          // NEW Password (Update this in Hostinger)
    $database = 'u414525231_testsytem_db'; // Hostinger Database Name
    $host = 'localhost';
    $port = 3306;
    
    try {
        $conn = mysqli_connect($host, $username, $password, $database, $port);
    } catch (mysqli_sql_exception $e) {
        die("<h1>Database Connection Failed</h1>
             <p><strong>Error:</strong> Access Denied.</p>
             <p><strong>Details:</strong> The password in <code>config/db.php</code> does not match the Database Password in Hostinger.</p>
             <p>Please go to Hostinger -> Databases -> Change Password and set it to: <code>SmartTest@2025</code></p>");
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

// Set Global Timezone (PHP & MySQL)
date_default_timezone_set('Asia/Kolkata');
mysqli_query($conn, "SET time_zone = '+05:30'");
?>
