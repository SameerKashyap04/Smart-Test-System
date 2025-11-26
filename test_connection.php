<?php
echo "<h1>Database Connection Test</h1>";

// Test credentials
$username = 'u414525231_testsyetem_usr';
$password = 'Devify@123';  // Current password in code
$database = 'u414525231_testsytem_db';
$host = 'localhost';
$port = 3306;

echo "<p><strong>Testing with:</strong></p>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>User: $username</li>";
echo "<li>Database: $database</li>";
echo "<li>Password: " . str_repeat('*', strlen($password)) . " (length: " . strlen($password) . ")</li>";
echo "</ul>";

// Attempt connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($host, $username, $password, $database, $port);
    echo "<p style='color:green; font-size:20px;'>✅ <strong>CONNECTION SUCCESSFUL!</strong></p>";
    echo "<p>The password <code>SmartTest2025</code> works!</p>";
    mysqli_close($conn);
} catch (mysqli_sql_exception $e) {
    echo "<p style='color:red; font-size:20px;'>❌ <strong>CONNECTION FAILED</strong></p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if ($e->getCode() == 1045) {
        echo "<hr>";
        echo "<h3>This is a PASSWORD ERROR (Code 1045)</h3>";
        echo "<p>The password in Hostinger is NOT <code>SmartTest2025</code></p>";
        echo "<p><strong>Solution:</strong> Go to Hostinger Dashboard and set the password to exactly: <code>SmartTest2025</code></p>";
    }
}
?>

