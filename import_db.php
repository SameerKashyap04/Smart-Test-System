<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/db.php';

echo "<h1>Database Import</h1>";

// Read the SQL file
$sql = file_get_contents('database_setup.sql');

// Split the SQL file into individual queries
$queries = explode(';', $sql);

// Execute each query
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!mysqli_query($conn, $query)) {
            echo "<p style='color: red;'>Error executing query: " . mysqli_error($conn) . "</p>";
            echo "<p>Query: " . htmlspecialchars($query) . "</p>";
            $success = false;
        }
    }
}

if ($success) {
    echo "<p style='color: green;'>Database schema imported successfully!</p>";
    echo "<p><a href='check_db.php'>Check Database Structure</a></p>";
} else {
    echo "<p style='color: red;'>There were errors importing the database schema.</p>";
}
?> 