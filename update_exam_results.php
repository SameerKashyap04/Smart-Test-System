<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config/db.php';

echo "<h1>Updating Exam Results Table</h1>";

// Check if connection is successful
if ($conn) {
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Add total_marks column to exam_results table
    $alter_sql = "ALTER TABLE exam_results ADD COLUMN IF NOT EXISTS total_marks INT NOT NULL DEFAULT 0";
    if (mysqli_query($conn, $alter_sql)) {
        echo "<p style='color: green;'>Added total_marks column to exam_results table</p>";
    } else {
        echo "<p style='color: red;'>Error adding total_marks column: " . mysqli_error($conn) . "</p>";
    }
    
    // Update existing records with total marks from exams table
    $update_sql = "UPDATE exam_results er
                   JOIN exams e ON er.exam_id = e.id
                   SET er.total_marks = e.total_marks";
    if (mysqli_query($conn, $update_sql)) {
        echo "<p style='color: green;'>Updated existing records with total marks from exams table</p>";
        echo "<p>Rows affected: " . mysqli_affected_rows($conn) . "</p>";
    } else {
        echo "<p style='color: red;'>Error updating records: " . mysqli_error($conn) . "</p>";
    }
    
    // Verify the update
    $verify_sql = "SELECT COUNT(*) as count FROM exam_results WHERE total_marks > 0";
    $result = mysqli_query($conn, $verify_sql);
    $row = mysqli_fetch_assoc($result);
    echo "<p>Number of records with total_marks > 0: " . $row['count'] . "</p>";
    
    echo "<p><a href='index.php'>Return to Home</a></p>";
} else {
    echo "<p style='color: red;'>Database connection failed!</p>";
}
?> 