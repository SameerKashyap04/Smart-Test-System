<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ultimate Database Connection Tester</h1>";
echo "<p>This script attempts to connect using multiple password variations to find the correct one.</p>";

$host = 'localhost';
$username = 'u414525231_smart_test_usr'; // NEW User
$database = 'u414525231_smart_test_db';  // NEW Database

$passwords_to_try = [
    'SmartTest@2025',
    'SmartTest2025',
    'Devify@123',
    'devify@123',
    'smarttest@2025'
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Attempt</th><th>Password</th><th>Result</th><th>Error Message</th></tr>";

foreach ($passwords_to_try as $index => $password) {
    echo "<tr>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td><code>" . htmlspecialchars($password) . "</code></td>";
    
    try {
        $conn = mysqli_connect($host, $username, $password, $database);
        echo "<td style='background-color: #d4edda; color: #155724; font-weight: bold;'>✅ SUCCESS</td>";
        echo "<td>Connected successfully!</td>";
        mysqli_close($conn);
    } catch (Exception $e) {
        echo "<td style='background-color: #f8d7da; color: #721c24; font-weight: bold;'>❌ FAILED</td>";
        echo "<td>" . htmlspecialchars($e->getMessage()) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h3>Diagnostic Info:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>User:</strong> $username</li>";
echo "<li><strong>Database:</strong> $database</li>";
echo "</ul>";
?>
