<?php
// Database connection parameters (defaults for XAMPP)
$username = 'root';       // Database username
$password = '';          // Database password (empty for default XAMPP setup)
$database = 'smart_test_system';  // Database name

// Common host/port combos: try IPv4 first to avoid named pipes issues on Windows
$hosts = ['127.0.0.1', 'localhost'];
$ports = [3306, 3307, 8889]; // Added 8889 for MAMP

// Prevent warnings from spamming output during fallback attempts
if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = null;
$errors = [];

// Attempt connections with DB specified first; then without DB to create it
foreach ($hosts as $host) {
	foreach ($ports as $port) {
		$conn = @mysqli_connect($host, $username, $password, $database, $port);
		if ($conn) {
			break 2;
		}
		$errors[] = "Failed to connect to $host:$port with DB: " . mysqli_connect_error();
		
		// Try without DB, create if possible
		$tmpConn = @mysqli_connect($host, $username, $password, '', $port);
		if ($tmpConn) {
			@mysqli_query($tmpConn, "CREATE DATABASE IF NOT EXISTS `" . $database . "`");
			@mysqli_select_db($tmpConn, $database);
			$conn = $tmpConn;
			break 2;
		} else {
			$errors[] = "Failed to connect to $host:$port without DB: " . mysqli_connect_error();
		}
	}
}

if (!$conn) {
	$errorMsg = "Connection failed.<br>Details:<br>" . implode("<br>", $errors);
	die(
		$errorMsg .
		"<br><br>Please check:<br>" .
		"1. Ensure MySQL is running in XAMPP/MAMP Control Panel<br>" .
		"2. Verify the port (default 3306, or 8889 for MAMP).<br>" .
		"3. Confirm username/password (default root with empty password)<br>" .
		"4. Confirm database '$database' exists or allow this script to create it."
	);
}
?>