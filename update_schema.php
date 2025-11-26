<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';

echo "<h1>Updating Database Schema...</h1>";

$queries = [
    // Add missing columns to users table
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS id_document_path VARCHAR(255) NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0",
    
    // Add missing columns to exam_results table (just in case)
    "ALTER TABLE exam_results ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE exam_results ADD COLUMN IF NOT EXISTS total_marks INT NOT NULL DEFAULT 0"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color: green;'>Executed: " . htmlspecialchars($query) . "</p>";
    } else {
        // Ignore "Duplicate column name" errors
        if (mysqli_errno($conn) == 1060) {
             echo "<p style='color: orange;'>Skipped (Already exists): " . htmlspecialchars($query) . "</p>";
        } else {
             echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
        }
    }
}

echo "<p><strong>Schema update complete!</strong> You can now use the settings page.</p>";
echo "<a href='student/settings.php'>Go back to Settings</a>";
?>
