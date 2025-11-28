<?php
// ====================================================
// DATABASE CONNECTION & SYSTEM SECURITY
// ====================================================

// 1. SECURITY CHECK: Load Credentials
// If this file is missing (e.g., downloaded from GitHub without permission),
// the system will NOT run.
$creds_file = __DIR__ . '/credentials.php';
if (!file_exists($creds_file)) {
    die("<h1>System Error: Configuration Missing</h1>
         <p>The system cannot start because the secure configuration file is missing.</p>
         <p>If you are the administrator, please ensure <code>config/credentials.php</code> exists.</p>");
}

require_once $creds_file;

// 2. LICENSE CHECK
if (!defined('SYSTEM_LICENSE_HASH') || empty(SYSTEM_LICENSE_HASH)) {
    die("<h1>Access Denied</h1><p>Invalid System License.</p>");
}

// 3. DOMAIN LOCK (Security Feature)
// Prevent the system from running on unauthorized domains
$allowed_domains = ['devify.live', 'www.devify.live', 'localhost', '127.0.0.1'];
$current_domain = $_SERVER['HTTP_HOST'] ?? '';
// Remove port number if present
$current_domain = explode(':', $current_domain)[0];

if (!in_array($current_domain, $allowed_domains)) {
    die("<h1>Unauthorized Domain</h1>
         <p>This software is licensed only for use on authorized domains.</p>
         <p>Detected Domain: " . htmlspecialchars($current_domain) . "</p>");
}

// Auto-detect environment
$is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'devify.live') !== false) || (strpos(__DIR__, '/home/u414525231') !== false) || (php_sapi_name() == 'cli' && getenv('APP_ENV') == 'production');

if ($is_production) {
    // HOSTINGER Credentials (Loaded from credentials.php)
    $username = $PROD_CREDENTIALS['username'];
    $password = $PROD_CREDENTIALS['password'];
    $database = $PROD_CREDENTIALS['database'];
    $host = 'localhost';
    $port = 3306;
    
    $conn = mysqli_connect($host, $username, $password, $database, $port);
    
    if (!$conn) {
        die("<h1>Database Connection Failed</h1>
             <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>");
    }
} else {
    // LOCALHOST / XAMPP SETUP
    $username = $LOCAL_CREDENTIALS['username'];
    $password = $LOCAL_CREDENTIALS['password'];
    $database = $LOCAL_CREDENTIALS['database'];
    
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
