<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';

echo "<h1>Updating Violations Schema...</h1>";

$queries = [
    // Add proof_image_path column to exam_violations table if it doesn't exist
    "ALTER TABLE exam_violations ADD COLUMN IF NOT EXISTS proof_image_path VARCHAR(255) NULL AFTER violation_description"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>Executed: " . htmlspecialchars($query) . "</p>";
    } else {
        // Ignore "Duplicate column name" errors (error code 1060)
        if (mysqli_errno($conn) == 1060) {
             echo "<p style='color: orange;'>Skipped (Already exists): " . htmlspecialchars($query) . "</p>";
        } else {
             echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
        }
    }
}

// Create violations upload directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/violations';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "<p style='color: green;'>Created violations upload directory: uploads/violations</p>";
    } else {
        echo "<p style='color: red;'>Failed to create violations upload directory.</p>";
    }
} else {
    echo "<p style='color: orange;'>Violations upload directory already exists.</p>";
}

echo "<p><strong>Schema update complete!</strong> Violations table now supports proof images.</p>";
echo "<a href='index.php'>Go to Home</a>";
?>
