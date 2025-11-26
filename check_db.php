<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/db.php';

echo "<h1>Database Connection Test</h1>";

// Check if connection is successful
if ($conn) {
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Check if database exists
    $result = mysqli_query($conn, "SELECT DATABASE()");
    $row = mysqli_fetch_row($result);
    echo "<p>Current database: " . $row[0] . "</p>";
    
    // Check if tables exist
    $tables = ['users', 'exams', 'questions', 'exam_results'];
    echo "<h2>Table Check</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "<li style='color: green;'>Table '$table' exists</li>";
            
            // Show table structure
            echo "<ul>";
            $result = mysqli_query($conn, "DESCRIBE $table");
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
            }
            echo "</ul>";
            
            // Count records
            $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
            $row = mysqli_fetch_assoc($result);
            echo "<li>Number of records: " . $row['count'] . "</li>";
        } else {
            echo "<li style='color: red;'>Table '$table' does not exist</li>";
        }
    }
    echo "</ul>";
    
    // Check if there are any users
    $result = mysqli_query($conn, "SELECT * FROM users LIMIT 5");
    echo "<h2>Sample Users</h2>";
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role'] . "</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>Database connection failed: " . mysqli_connect_error() . "</p>";
}
?> 